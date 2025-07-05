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

            $folder = 'inbox';
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

            // TRAITEMENT DES PIÈCES JOINTES CORRIGÉ
            $attachments = [];
            $attachmentCount = $request->input('attachment-count', 0);

            for ($i = 1; $i <= $attachmentCount; $i++) {
                if ($request->hasFile("attachment-{$i}")) {
                    $file = $request->file("attachment-{$i}");
                    $originalName = $file->getClientOriginalName();
                    
                    // Nettoyer le nom de fichier
                    $cleanName = $this->cleanFilename($originalName);
                    $filename = time() . '_' . uniqid() . '_' . $cleanName;

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
                'signature_verified' => $isSignatureValid || $isApproved,
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

   
    private function classifyEmail(string $emailContent): array
    {
        return $this->getFallbackSpamResult();
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
            'processed_at' => now()
        ];
    }
}