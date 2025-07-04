<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;  
use App\Models\Email;  

trait EmailReceiving
{
    public function handleIncomingEmail(Request $request): JsonResponse
    {
         try {
            Log::info('=== WEBHOOK DEBUG ===', [
                'subject' => $request->input('Subject'),
                'from' => $request->input('From'),
            ]);

            // Extraire les données d'en-tête AVANT d'utiliser $from/$to
            $from = $request->input('From') ?? $request->input('from');
            $to = $request->input('To') ?? $request->input('to');
            $subject = $request->input('Subject') ?? $request->input('subject') ?? 'Sans objet';
            $bodyPlain = $request->input('body-plain') ?? '';
            $bodyHtml = $request->input('body-html') ?? '';

            // Vérifier la signature
            $isSignatureValid = $this->verifyWebhookSignature($request);

            // Trouver l'utilisateur
            $userId = $this->findUserByEmail($to);
            if (!$userId) {
                Log::warning('Utilisateur non trouvé', ['email' => $to]);
                return response()->json(['error' => 'Destinataire non trouvé'], 404);
            }

            $emailContent = $bodyHtml ?: $bodyPlain;
            $fromEmail = $this->extractEmail($from);

            // Test simple sans détails
            $spamClassification = $this->classifyEmail($emailContent);

            Log::info('🤖 Classification spam', $spamClassification);

            // Vérifier si l'expéditeur est approuvé
            $domain = substr(strrchr($fromEmail, "@"), 1);
            $isApproved = \App\Models\ApprovedSender::where('user_id', $userId)
                ->where(function($query) use ($fromEmail, $domain) {
                    $query->where('email', $fromEmail)
                        ->orWhere('domain', $domain);
                })
                ->exists();

            // Déterminer le dossier basé sur signature, approbation ET spam
            $folder = 'inbox'; // Par défaut

            if ($spamClassification['is_spam'] && $spamClassification['spam_probability'] > 0.7) {
                $folder = 'spam';
            } elseif (!$isSignatureValid && !$isApproved) {
                $folder = 'unverified';
            }

            Log::info('📁 Dossier déterminé', [
                'folder' => $folder,
                'signature_valid' => $isSignatureValid,
                'approved' => $isApproved,
                'spam_probability' => $spamClassification['spam_probability']
            ]);

            // Traiter les pièces jointes (code existant)
            $attachments = [];
            $attachmentCount = $request->input('attachment-count', 0);

            for ($i = 1; $i <= $attachmentCount; $i++) {
                if ($request->hasFile("attachment-{$i}")) {
                    $file = $request->file("attachment-{$i}");
                    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    $path = 'attachments/' . $filename;

                    $file->storeAs('public', $path);

                    $attachments[] = [
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ];
                }
            }

            // Créer l'email avec valeurs par défaut pour le spam
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

                // Valeurs par défaut pour le spam
                // Vraies valeurs de classification spam - CORRECT !
'is_spam' => $spamClassification['is_spam'],
'spam_probability' => $spamClassification['spam_probability'],
'spam_confidence' => $spamClassification['confidence'],
'spam_checked_at' => now(),
'spam_details' => json_encode($spamClassification['details'] ?? []),
            ]);

            Log::info('✅ Email sauvegardé', [
                'email_id' => $email->id,
                'folder' => $folder,
                'is_spam' => $spamClassification['is_spam'],
                'spam_probability' => $spamClassification['spam_probability']
            ]);

            return response()->json([
                'success' => true,
                'email_id' => $email->id,
                'folder' => $folder,
                'signature_verified' => $isSignatureValid,
                'spam_classification' => [
                    'is_spam' => $spamClassification['is_spam'],
                    'probability' => $spamClassification['spam_probability'],
                    'confidence' => $spamClassification['confidence']
                ],
                'attachments_count' => count($attachments)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erreur'], 500);
        }
    }

    private function classifyEmail(string $emailContent): array
    {
        $spamApiUrl = config('services.spam_classifier.url', 'http://spam_classifier:8081');
        $cacheKey = 'spam_check_' . md5($emailContent);
        
        // Vérifier le cache (5 minutes)
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            Log::info('🤖 Classification spam démarrée', [
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
                    'processed_at' => now()
                ];
                
                // Mettre en cache pour 5 minutes
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
            
            // Vérifier que le timestamp n'est pas trop ancien (15 minutes max)
            if (abs(time() - $timestamp) > 900) {
                Log::info('🔐 Timestamp trop ancien');
                return false;
            }
            
            $expectedSignature = hash_hmac(
                'sha256',
                $timestamp . $token,
                $this->mailgunSecret
            );
            
            $isValid = hash_equals($signature, $expectedSignature);
            
            Log::info('🔐 Vérification signature', [
                'valid' => $isValid,
                'has_timestamp' => !empty($timestamp),
                'has_token' => !empty($token),
                'has_signature' => !empty($signature)
            ]);
            
            return $isValid;
            
        } catch (\Exception $e) {
            Log::error('🔐 Erreur vérification signature', ['error' => $e->getMessage()]);
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
            'processed_at' => now()
        ];
    }
}