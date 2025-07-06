<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Email;
use Illuminate\Http\JsonResponse;

trait EmailReceiving
{
    public function handleIncomingEmail(Request $request): JsonResponse
    {
        try {
            Log::info('📝 Contenu complet reçu', [
                'inputs' => $request->all(),
                'fichiers' => $request->allFiles()
            ]);

            Log::info('📨 === WEBHOOK DEBUG ===', [
                'subject' => $request->input('Subject'),
                'from' => $request->input('From'),
            ]);

            $from = $request->input('From') ?? $request->input('from');
            $to = $request->input('To') ?? $request->input('to');
            $subject = $request->input('Subject') ?? $request->input('subject') ?? 'Sans objet';
            $bodyPlain = $request->input('body-plain') ?? '';
            $bodyHtml = $request->input('body-html') ?? '';

            $isSignatureValid = $this->verifyWebhookSignature($request);

            $userId = $this->findUserByEmail($to);
            if (!$userId) {
                Log::warning('⚠️ Utilisateur non trouvé', ['email' => $to]);
                $userId = 1; // Par défaut
            }

            $emailContent = $bodyHtml ?: $bodyPlain;
            $fromEmail = $this->extractEmail($from);

            $spamClassification = $this->classifyEmail($emailContent);

            Log::info('🤖 Classification spam', $spamClassification);

            $domain = substr(strrchr($fromEmail, "@"), 1);
            $isApproved = \App\Models\ApprovedSender::where('user_id', $userId)
                ->where(function ($query) use ($fromEmail, $domain) {
                    $query->where('email', $fromEmail)
                        ->orWhere('domain', $domain);
                })
                ->exists();

            Log::info('🔍 Vérification expéditeur', [
                'from_email' => $fromEmail,
                'domain' => $domain,
                'user_id' => $userId,
                'is_approved' => $isApproved,
                'signature_valid' => $isSignatureValid
            ]);

            $folder = 'inbox';
            if ($spamClassification['is_spam'] && $spamClassification['spam_probability'] > 0.4) {
                $folder = 'spam';
                Log::info('📧 Email classé comme SPAM', ['spam_probability' => $spamClassification['spam_probability']]);
            } elseif (!$isSignatureValid && !$isApproved) {
                $folder = 'unverified';
                Log::info('📧 Email classé comme NON VÉRIFIÉ', [
                    'signature_valid' => $isSignatureValid,
                    'approved' => $isApproved,
                    'from_email' => $fromEmail
                ]);
            } else {
                Log::info('📧 Email classé dans BOÎTE DE RÉCEPTION', [
                    'signature_valid' => $isSignatureValid,
                    'approved' => $isApproved,
                    'from_email' => $fromEmail
                ]);
            }

            Log::info('📁 Dossier déterminé', [
                'folder' => $folder,
                'signature_valid' => $isSignatureValid,
                'approved' => $isApproved,
                'spam_probability' => $spamClassification['spam_probability'],
                'is_spam' => $spamClassification['is_spam'],
                'logic' => [
                    'is_spam_and_high_probability' => ($spamClassification['is_spam'] && $spamClassification['spam_probability'] > 0.4),
                    'signature_invalid_and_not_approved' => (!$isSignatureValid && !$isApproved)
                ]
            ]);

            // TRAITEMENT DES PIÈCES JOINTES CORRIGÉ
            $attachments = [];
            $attachmentCount = $request->input('attachment-count', 0);

            for ($i = 1; $i <= $attachmentCount; $i++) {
                if ($request->hasFile("attachment-{$i}")) {
                    $file = $request->file("attachment-{$i}");
                    $originalName = $file->getClientOriginalName();
                    
                    // Nettoyer le nom de fichier - UTILISER LE TRAIT FileUtilities
                    $cleanName = $this->cleanFilename($originalName);
                    $filename = $this->generateUniqueFilename($originalName);

                    // Sauvegarder directement dans storage/app/public/attachments/
                    $storagePath = 'attachments/' . $filename;
                    
                    try {
                        // Utiliser storeAs pour sauvegarder
                        $file->storeAs('public', $storagePath);
                        
                        $fullPath = storage_path('app/public/' . $storagePath);
                        
                        if (file_exists($fullPath)) {
                            Log::info('✅ Pièce jointe sauvegardée', [
                                'original' => $originalName,
                                'stored_as' => $filename,
                                'path' => $fullPath,
                                'size' => filesize($fullPath)
                            ]);

                            $attachments[] = [
                                'filename' => $originalName,
                                'stored_name' => $filename,
                                'path' => $storagePath, // attachments/filename
                                'size' => filesize($fullPath),
                                'mime_type' => $file->getMimeType()
                            ];
                        } else {
                            Log::error('❌ Fichier non sauvegardé', [
                                'expected_path' => $fullPath
                            ]);
                        }
                    } catch (\Exception $fileEx) {
                        Log::error('❌ Erreur sauvegarde pièce jointe', [
                            'error' => $fileEx->getMessage(),
                            'file' => $originalName
                        ]);
                    }
                }
            }

            $email = Email::create([
                'user_id' => $userId,
                'folder' => $folder,
                'from_email' => $fromEmail,
                'from_name' => $this->extractName($from) ?? $fromEmail,
                'to_email' => $this->extractEmail($to),
                'subject' => $subject,
                'content' => $emailContent,
                'preview' => substr($bodyPlain ?: strip_tags($bodyHtml), 0, 100),
                'is_html' => !empty($bodyHtml),
                'is_read' => false,
                'signature_verified' => $isSignatureValid,
                'attachments' => json_encode($attachments),
                'is_spam' => $spamClassification['is_spam'],
                'spam_probability' => $spamClassification['spam_probability'],
                'spam_confidence' => $spamClassification['confidence'],
                'spam_checked_at' => now(),
                'spam_details' => json_encode($spamClassification['details'] ?? []),
            ]);

            Log::info('✅ Email sauvegardé', [
                'email_id' => $email->id,
                'folder' => $folder,
                'attachments_count' => count($attachments)
            ]);

            return response()->json([
                'success' => true,
                'email_id' => $email->id,
                'folder' => $folder,
                'signature_verified' => $isSignatureValid,
                'attachments_count' => count($attachments)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erreur'], 500);
        }
    }

    // AJOUTER LA MÉTHODE MANQUANTE
    private function findUserByEmail(string $email): ?int
    {
        $user = \App\Models\User::where('email', $email)->first();
        return $user ? $user->id : null;
    }

    // Méthode simplifiée pour nettoyer les noms de fichiers - SUPPRIMÉE
    // Maintenant dans FileUtilities trait

    private function classifyEmail(string $emailContent): array
    {
        $spamApiUrl = config('services.spam_classifier.url', 'http://spam_classifier:8081');
        $cacheKey = 'spam_check_' . md5($emailContent);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            Log::info('🔍 Classification spam démarrée', [
                'content_length' => strlen($emailContent)
            ]);

            $response = Http::timeout(5)
                ->post("{$spamApiUrl}/classify", [
                    'text' => $emailContent
                ]);

            if ($response->successful()) {
                $result = $response->json();

                $classification = [
                    'is_spam' => $result['is_spam'] ?? false,
                    'spam_probability' => $result['spam_probability'] ?? 0.0,
                    'confidence' => $result['confidence'] ?? 'unknown',
                    'service_available' => true,
                    'processed_at' => now(),
                    'details' => $result
                ];

                // Si le service externe donne un score faible mais que le contenu semble suspect,
                // utiliser notre classification basique comme backup
                if ($classification['spam_probability'] < 0.5) {
                    $basicClassification = $this->basicSpamClassification($emailContent);
                    if ($basicClassification['spam_probability'] > $classification['spam_probability']) {
                        Log::info('🔄 Classification basique plus stricte, utilisation du score local', [
                            'external_score' => $classification['spam_probability'],
                            'basic_score' => $basicClassification['spam_probability']
                        ]);
                        
                        $classification['spam_probability'] = $basicClassification['spam_probability'];
                        $classification['is_spam'] = $basicClassification['is_spam'];
                        $classification['confidence'] = 'hybrid';
                        $classification['details']['fallback_used'] = true;
                        $classification['details']['basic_details'] = $basicClassification['details'];
                    }
                }

                Cache::put($cacheKey, $classification, 300);

                Log::info('✅ Classification réussie', $classification);
                return $classification;
            }

            Log::warning('⚠️ API spam indisponible', [
                'status' => $response->status()
            ]);

            return $this->getFallbackSpamResult();

        } catch (\Exception $e) {
            Log::error('❌ Erreur classification spam', [
                'error' => $e->getMessage()
            ]);

            return $this->getFallbackSpamResult();
        }
    }

    private function verifyWebhookSignature(Request $request): bool
    {
        try {
            $timestamp = $request->input('timestamp');
            $token = $request->input('token');
            $signature = $request->input('signature');

            if (!$timestamp || !$token || !$signature) {
                Log::info('🔐 Paramètres de signature manquants');
                return false;
            }

            if (abs(time() - $timestamp) > 900) {
                Log::info('⌛ Timestamp trop ancien');
                return false;
            }

            $expectedSignature = hash_hmac(
                'sha256',
                $timestamp . $token,
                $this->mailgunSecret
            );

            $isValid = hash_equals($signature, $expectedSignature);

            Log::info('🔐 Vérification signature', [
                'valid' => $isValid
            ]);

            return $isValid;

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification signature', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function extractEmail(string $emailString): string
    {
        if (preg_match('/<(.+?)>/', $emailString, $matches)) {
            return $matches[1];
        }
        return trim($emailString);
    }

    private function extractName(string $emailString): ?string
    {
        if (preg_match('/^(.+?)\s*<.+?>$/', $emailString, $matches)) {
            return trim($matches[1], '"');
        }
        return null;
    }

    private function getFallbackSpamResult(): array
    {
        return [
            'is_spam' => false,
            'spam_probability' => 0.0,
            'confidence' => 'fallback',
            'service_available' => false,
            'processed_at' => now(),
            'details' => ['method' => 'fallback']
        ];
    }

    // Classification spam basique si le service externe est indisponible
    private function basicSpamClassification(string $content): array
    {
        $spamWords = [
            // Mots français typiques
            'félicitations', 'gagné', 'gagne', 'prix', 'cadeau', 'gratuit',
            'confirmer maintenant', 'dépêche-toi', 'limité', 'urgent',
            'cliquez ici', 'récupérez', 'aspirateur', 'offre limitée',
            'vous avez gagné', 'toutes nos félicitations',
            
            // Mots anglais
            'free money', 'win now', 'urgent', 'limited time', 'act now',
            'congratulations', 'you have won', 'claim now', 'click here',
            'viagra', 'casino', 'lottery', 'inheritance'
        ];
        
        $suspiciousPatterns = [
            // Domaines suspects
            'storage.googleapis.com',
            'gp1fbbm',
            // Caractères Unicode suspects (comme dans le sujet)
            'ᴇ', 'ᴄ', 'ᴜ', 'ᴘ', 'ʀ', 'ᴀ', 'ᴅ', 'ɪ', 'ʟ', 'ᴏ', 'ᴛ', 'ᴍ', 'ɢ', 'ᴏ', 'ɴ', 'ʜ'
        ];
        
        $content = strtolower($content);
        $spamScore = 0;
        
        // Compter les mots spam
        foreach ($spamWords as $word) {
            if (strpos($content, strtolower($word)) !== false) {
                $spamScore += 0.2;
            }
        }
        
        // Compter les patterns suspects
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($content, strtolower($pattern)) !== false) {
                $spamScore += 0.3;
            }
        }
        
        // Bonus pour combinaisons typiques
        if (strpos($content, 'félicitations') !== false && strpos($content, 'gagné') !== false) {
            $spamScore += 0.4;
        }
        
        if (strpos($content, 'confirmer') !== false && strpos($content, 'maintenant') !== false) {
            $spamScore += 0.3;
        }
        
        // URLs suspectes multiples
        $urlCount = substr_count($content, 'http');
        if ($urlCount > 3) {
            $spamScore += 0.2;
        }
        
        $probability = min($spamScore, 1.0);
        $isSpam = $probability > 0.6;
        
        return [
            'is_spam' => $isSpam,
            'spam_probability' => $probability,
            'confidence' => 'enhanced_basic',
            'service_available' => false,
            'processed_at' => now(),
            'details' => [
                'method' => 'enhanced_keyword_detection',
                'spam_score' => $spamScore,
                'url_count' => $urlCount
            ]
        ];
    }
}