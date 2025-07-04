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
    /**
     * Envoi d'email avec gestion des pièces jointes
     */
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

        // Tracking (valeurs simples, pas de tableaux)
        $emailData['o:tracking'] = 'yes';
        $emailData['o:tracking-opens'] = 'yes';
        $emailData['o:tag'] = 'sent-email';

        // Traiter les pièces jointes IMMÉDIATEMENT
        $attachments = [];
        $tempFiles = [];
        
        if ($request->hasFile('attachments')) {
            // Créer le dossier de stockage temporaire
            $tempDir = storage_path('app/temp_attachments');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
        foreach ($request->file('attachments') as $index => $file) {
            if (!$file->isValid()) {
                Log::error('Fichier invalide', ['error' => $file->getErrorMessage()]);
                continue;
            }

            try {
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();
                $tempPath = $file->path();

                // Créer un nom de fichier sécurisé
                $safeName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $finalPath = $tempDir . '/' . $safeName;

              
                if (!copy($tempPath, $finalPath)) {
                    Log::error('Erreur copie fichier avant scan', [
                        'from' => $tempPath,
                        'to' => $finalPath
                    ]);
                    return response()->json(['error' => 'Erreur lors de la préparation du fichier.'], 500);
                }

                // Scanner la copie
                $clamavOutput = [];
                $clamavReturn = 0;

                exec('clamscan ' . escapeshellarg($finalPath), $clamavOutput, $clamavReturn);

                Log::info('Résultat ClamAV', [
                    'output' => $clamavOutput,
                    'return' => $clamavReturn
                ]);

                if ($clamavReturn === 1) {
                    unlink($finalPath); 
                    return response()->json([
                        'error' => "Le fichier « $originalName » est infecté et ne peut pas être envoyé."
                    ], 422);
                }

       
                $attachments[] = [
                    'filename' => $originalName,
                    'safe_name' => $safeName,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                    'full_path' => $finalPath
                ];

                $tempFiles[] = $finalPath;

                Log::info('Fichier copié et scanné avec succès', [
                    'original' => $originalName,
                    'path' => $finalPath,
                    'size' => filesize($finalPath)
                ]);


            } catch (\Exception $fileEx) {
                Log::error('Erreur traitement fichier', [
                    'error' => $fileEx->getMessage(),
                    'file' => $originalName ?? 'inconnu'
                ]);
            }
        }

        }

        Log::info('Préparation envoi', [
            'to' => $request->to,
            'attachments_count' => count($attachments)
        ]);

        // Envoyer l'email
        if (empty($attachments)) {
            $response = $this->sendSimpleEmail($emailData);
        } else {
            $response = $this->sendEmailWithAttachments($emailData, $attachments);
        }

        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
                Log::info('Fichier temporaire nettoyé', ['file' => basename($tempFile)]);
            }
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
                    unset($a['full_path']); // Supprimer le chemin complet
                    return $a;
                }, $attachments)),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès',
                'attachments_sent' => count($attachments)
            ]);
        }

        return response()->json(['error' => 'Erreur lors de l\'envoi: ' . ($response['error'] ?? 'Inconnue')], 500);

    } catch (\Exception $e) {
        Log::error('Erreur envoi email', [
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

        Log::error(' Erreur HTTP simple', ['status' => $response->status(), 'body' => $response->body()]);
        return ['success' => false, 'error' => 'HTTP ' . $response->status()];

    } catch (\Exception $e) {
        Log::error(' Exception email simple', ['error' => $e->getMessage()]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
    }

    private function sendEmailWithAttachments(array $emailData, array $attachments): array
    {
         try {
        $url = "https://{$this->mailgunEndpoint}/v3/{$this->mailgunDomain}/messages";
        
        Log::info(' Envoi avec pièces jointes', [
            'attachments_count' => count($attachments)
        ]);
        
        // Construire la commande cURL
        $cmd = "curl -s " . escapeshellarg($url);
        $cmd .= " -u " . escapeshellarg("api:{$this->mailgunSecret}");
        
        // Paramètres de base
        $params = array_merge([
            'from' => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
        ], $emailData);
        
        // Ajouter les paramètres (gérer les tableaux correctement)
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
                
                Log::info(' Pièce jointe ajoutée', [
                    'file' => $attachment['filename'],
                    'size' => filesize($attachment['full_path'])
                ]);
            } else {
                Log::error(' Fichier non trouvé', [
                    'path' => $attachment['full_path']
                ]);
            }
        }
        
        // Exécuter la commande
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        
        $response = implode("\n", $output);
        
        Log::info('Résultat envoi', [
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
        Log::error(' Exception cURL', ['error' => $e->getMessage()]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
    }

    


    private function processAttachments(array $files): array
    {
        return ['attachments' => [], 'tempFiles' => []];
    }

    private function scanFileWithClamAV(string $filePath, string $originalName)
    {
        return true;
    }
}