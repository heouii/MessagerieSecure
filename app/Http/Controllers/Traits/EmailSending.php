<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Email;

trait EmailSending
{
    public function sendEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'cc' => 'nullable|email',
            'html_format' => 'nullable|in:0,1,true,false',
            'read_receipt' => 'nullable|in:0,1,true,false',
            'attachments.*' => 'nullable|file|max:25600',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        if ($this->isBlacklistedEmail($request->to)) {
            return response()->json(['error' => 'Email ou domaine bloqué'], 403);
        }

        try {
            // Construire l'email de base
            $emailData = [
                'to' => $request->to,
                'subject' => $request->subject,
            ];

            // CC si présent
            if ($request->cc) {
                $emailData['cc'] = $request->cc;
            }

            // Format HTML ou texte
            $isHtmlFormat = in_array($request->html_format, ['1', 'true', 1, true], true);
            if ($isHtmlFormat) {
                $emailData['html'] = nl2br(htmlspecialchars($request->message));
            } else {
                $emailData['text'] = $request->message;
            }

            // Tracking
            $emailData['o:tracking'] = 'yes';
            $emailData['o:tracking-opens'] = 'yes';
            $emailData['o:tag'] = 'sent-email';

            // Traiter les pièces jointes
            $attachments = [];
            $tempFiles = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    if (!$file->isValid()) {
                        Log::error('Fichier invalide', ['error' => $file->getErrorMessage()]);
                        continue;
                    }

                    try {
                        $originalName = $file->getClientOriginalName();
                        $cleanName = $this->cleanFilename($originalName);
                        $filename = time() . '_' . uniqid() . '_' . $cleanName;
                        
                        // Sauvegarder dans storage/app/public/attachments/
                        $storagePath = 'attachments/' . $filename;
                        $file->storeAs('public', $storagePath);
                        
                        $fullPath = storage_path('app/public/' . $storagePath);
                        
                        if (file_exists($fullPath)) {
                            $attachments[] = [
                                'filename' => $originalName,
                                'stored_name' => $filename,
                                'path' => $storagePath,
                                'size' => $file->getSize(),
                                'mime_type' => $file->getMimeType(),
                                'full_path' => $fullPath // Pour l'envoi via cURL
                            ];
                            
                            $tempFiles[] = $fullPath;
                            
                            Log::info('📎 Pièce jointe sauvegardée (outgoing)', [
                                'original' => $originalName,
                                'stored_as' => $filename,
                                'size' => filesize($fullPath)
                            ]);
                        }

                    } catch (\Exception $fileEx) {
                        Log::error('Erreur traitement fichier', [
                            'error' => $fileEx->getMessage(),
                            'file' => $originalName ?? 'inconnu'
                        ]);
                    }
                }
            }

            Log::info('✉️ Préparation envoi', [
                'to' => $request->to,
                'attachments_count' => count($attachments)
            ]);

            // Envoyer l'email
            if (empty($attachments)) {
                $response = $this->sendSimpleEmail($emailData);
            } else {
                $response = $this->sendEmailWithAttachments($emailData, $attachments);
            }

            if ($response['success']) {
                Email::create([
                    'user_id' => auth()->id(),
                    'mailgun_id' => $response['mailgun_id'],
                    'folder' => 'sent',
                    'from_email' => auth()->user()->email,
                    'from_name' => auth()->user()->prenom . ' ' . auth()->user()->nom,
                    'to_email' => $request->to,
                    'cc_email' => $request->cc,
                    'subject' => $request->subject,
                    'content' => $request->message,
                    'preview' => substr($request->message, 0, 100),
                    'is_html' => $isHtmlFormat,
                    'is_read' => true,
                    'attachments' => json_encode(array_map(function($a) {
                        unset($a['full_path']);
                        return $a;
                    }, $attachments)),
                ]);

                Log::info('✅ Email envoyé et sauvegardé');

                return response()->json([
                    'success' => true,
                    'message' => 'Email envoyé avec succès',
                    'attachments_sent' => count($attachments)
                ]);
            }

            return response()->json(['error' => 'Erreur lors de l\'envoi: ' . ($response['error'] ?? 'Inconnue')], 500);

        } catch (\Exception $e) {
            Log::error('❌ Exception envoi email', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
            return response()->json(['error' => 'Erreur système: ' . $e->getMessage()], 500);
        }
    }

  
    

    private function sendSimpleEmail(array $emailData): array
    {
        try {
            $url = "https://{$this->mailgunEndpoint}/v3/{$this->mailgunDomain}/messages";

            $response = Http::withBasicAuth('api', $this->mailgunSecret)
                ->timeout(60)
                ->asForm()
                ->post($url, array_merge([
                    'from' => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                ], $emailData));

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'mailgun_id' => $data['id'] ?? null,
                    'message' => $data['message'] ?? 'Envoyé'
                ];
            }

            Log::error('❌ Erreur HTTP simple', ['status' => $response->status()]);
            return ['success' => false, 'error' => 'HTTP ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('❌ Exception email simple', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendEmailWithAttachments(array $emailData, array $attachments): array
    {
        try {
            $url = "https://{$this->mailgunEndpoint}/v3/{$this->mailgunDomain}/messages";

            Log::info('📎 Envoi avec pièces jointes', [
                'attachments_count' => count($attachments)
            ]);

            // Construire la commande cURL
            $cmd = "curl -s " . escapeshellarg($url);
            $cmd .= " -u " . escapeshellarg("api:{$this->mailgunSecret}");

            // Paramètres de base
            $params = array_merge([
                'from' => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
            ], $emailData);

            // Ajouter les paramètres
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $arrayValue) {
                        $cmd .= " -F " . escapeshellarg("{$key}={$arrayValue}");
                    }
                } else {
                    $cmd .= " -F " . escapeshellarg("{$key}={$value}");
                }
            }

            // Ajouter les pièces jointes
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['full_path'])) {
                    $cmd .= " -F " . escapeshellarg("attachment=@{$attachment['full_path']}");

                    Log::info('📎 Pièce jointe ajoutée', [
                        'file' => $attachment['filename'],
                        'size' => filesize($attachment['full_path'])
                    ]);
                } else {
                    Log::error('❌ Fichier non trouvé', [
                        'path' => $attachment['full_path']
                    ]);
                }
            }

            // Exécuter la commande
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            $response = implode("\n", $output);

            Log::info('✅ Résultat envoi', [
                'return_code' => $returnCode,
                'response' => $response
            ]);

            if ($returnCode === 0 && !empty($response)) {
                $data = json_decode($response, true);
                if ($data && isset($data['id'])) {
                    return [
                        'success' => true,
                        'mailgun_id' => $data['id'],
                        'message' => $data['message'] ?? 'Envoyé avec pièces jointes'
                    ];
                }
            }

            return ['success' => false, 'error' => "Erreur cURL: {$response}"];

        } catch (\Exception $e) {
            Log::error('❌ Exception cURL', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Méthode pour vérifier les emails blacklistés (à implémenter)
    private function isBlacklistedEmail(string $email): bool
    {
        return false; // Simplifié pour l'instant
    }
}