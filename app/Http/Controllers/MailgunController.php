<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Email;

class MailgunController extends Controller
{
    private $mailgunDomain;
    private $mailgunSecret;
    private $mailgunEndpoint;

    public function __construct()
    {
        $this->mailgunDomain = config('services.mailgun.domain');
        $this->mailgunSecret = config('services.mailgun.secret');
        $this->mailgunEndpoint = config('services.mailgun.endpoint');
    }

    /**
     * Affichage de l'interface Gmail-like
     */
    public function index()
    {
        return view('interface');
    }

    /**
     * Vérifier si l'email ou le domaine est bloqué
     */
    private function isBlacklistedEmail(string $email): bool
    {
        $email = strtolower($email);
        $domain = substr(strrchr($email, "@"), 1);

        return \App\Models\Blacklist::where(function($query) use ($email, $domain) {
            $query->where(function($q) use ($email) {
                $q->where('type', 'email')->where('value', $email);
            })->orWhere(function($q) use ($domain) {
                $q->where('type', 'domain')->where('value', $domain);
            });
        })->exists();
    }

    /**
     * Envoi d'email avec gestion optimisée des pièces jointes
     */
 /**
 * Envoi d'email avec gestion définitivement corrigée des pièces jointes
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
                    // Obtenir les informations AVANT de déplacer le fichier
                    $originalName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $mimeType = $file->getMimeType();
                    $tempPath = $file->path(); // Chemin temporaire actuel
                    
                    Log::info('📄 Traitement fichier', [
                        'original_name' => $originalName,
                        'size' => $fileSize,
                        'mime_type' => $mimeType,
                        'temp_path' => $tempPath,
                        'exists' => file_exists($tempPath)
                    ]);
                    
                    // Créer un nom de fichier sécurisé
                    $safeName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $finalPath = $tempDir . '/' . $safeName;
                    
                    // Copier le fichier vers notre dossier temporaire
                    if (copy($tempPath, $finalPath)) {
                        $attachments[] = [
                            'filename' => $originalName,
                            'safe_name' => $safeName,
                            'size' => $fileSize,
                            'mime_type' => $mimeType,
                            'full_path' => $finalPath
                        ];
                        
                        $tempFiles[] = $finalPath;
                        
                        Log::info('✅ Fichier copié avec succès', [
                            'original' => $originalName,
                            'path' => $finalPath,
                            'size' => filesize($finalPath)
                        ]);
                    } else {
                        Log::error('❌ Erreur copie fichier', [
                            'from' => $tempPath,
                            'to' => $finalPath
                        ]);
                    }
                    
                } catch (\Exception $fileEx) {
                    Log::error('❌ Erreur traitement fichier', [
                        'error' => $fileEx->getMessage(),
                        'file' => $originalName ?? 'inconnu'
                    ]);
                }
            }
        }

        Log::info('📧 Préparation envoi', [
            'to' => $request->to,
            'attachments_count' => count($attachments)
        ]);

        // Envoyer l'email
        if (empty($attachments)) {
            $response = $this->sendSimpleEmail($emailData);
        } else {
            $response = $this->sendEmailWithAttachments($emailData, $attachments);
        }

        // Nettoyer les fichiers temporaires après envoi
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
                Log::info('🗑️ Fichier temporaire nettoyé', ['file' => basename($tempFile)]);
            }
        }

        if ($response['success']) {
            // Sauvegarder en base
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
        Log::error('💥 Erreur envoi email', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);
        return response()->json(['error' => 'Erreur système: ' . $e->getMessage()], 500);
    }
}

/**
 * Méthode simple pour les emails sans pièce jointe
 */
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

        Log::error('❌ Erreur HTTP simple', ['status' => $response->status(), 'body' => $response->body()]);
        return ['success' => false, 'error' => 'HTTP ' . $response->status()];

    } catch (\Exception $e) {
        Log::error('❌ Exception email simple', ['error' => $e->getMessage()]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Méthode pour les emails avec pièces jointes via cURL
 */
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
        
        Log::info('📊 Résultat envoi', [
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
    /**
     * Construire la commande cURL pour l'envoi avec pièces jointes
     */
   /**
 * Construire la commande cURL qui fonctionne - basée sur le test manuel réussi
 */
private function buildCurlCommand(string $url, array $emailData, array $attachments): string
{
    // Démarrer la commande comme dans le test qui fonctionne
    $parts = [];
    $parts[] = "curl -v";
    $parts[] = escapeshellarg($url);
    $parts[] = "-u " . escapeshellarg("api:{$this->mailgunSecret}");
    
    // Ajouter les paramètres de base
    $params = array_merge([
        'from' => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
    ], $emailData);
    
    foreach ($params as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $val) {
                $parts[] = "-F " . escapeshellarg("{$key}={$val}");
            }
        } else {
            $parts[] = "-F " . escapeshellarg("{$key}={$value}");
        }
    }
    
    // Ajouter les pièces jointes avec la syntaxe exacte qui fonctionne
    foreach ($attachments as $index => $attachment) {
        if (file_exists($attachment['full_path'])) {
            // Syntaxe exacte : attachment=@/path/to/file
            $parts[] = "-F " . escapeshellarg("attachment=@{$attachment['full_path']}");
            
            Log::info('📎 Pièce jointe ajoutée', [
                'filename' => $attachment['filename'],
                'path' => $attachment['full_path'],
                'exists' => file_exists($attachment['full_path']),
                'size' => filesize($attachment['full_path'])
            ]);
        } else {
            Log::error('❌ Fichier pièce jointe introuvable', [
                'path' => $attachment['full_path'],
                'filename' => $attachment['filename']
            ]);
        }
    }
    
    // Joindre toutes les parties
    $curlCommand = implode(' ', $parts);
    
    Log::info('🔧 Commande cURL construite', [
        'command_length' => strlen($curlCommand),
        'attachments_count' => count($attachments),
        'command_preview' => substr($curlCommand, 0, 200) . '...'
    ]);
    
    return $curlCommand;
}

    /**
     * Récupérer les emails par dossier
     */
    public function getEmails(Request $request, $folder = 'inbox'): JsonResponse
    {
        try {
            $query = Email::where('user_id', auth()->id())
                ->where('folder', $folder)
                ->orderBy('created_at', 'desc');

            // Recherche optionnelle
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('from_email', 'like', "%{$search}%");
                });
            }

            $emails = $query->limit(50)->get();

            // Formater pour l'interface
            $formattedEmails = $emails->map(function($email) {
                return [
                    'id' => $email->id,
                    'from' => $email->from_email,
                    'from_name' => $email->from_name,
                    'to' => $email->to_email,
                    'subject' => $email->subject,
                    'content' => $email->content,
                    'preview' => $email->preview ?: substr($email->content, 0, 100),
                    'date' => $email->created_at,
                    'read' => $email->is_read,
                    'attachments' => $email->attachments ? json_decode($email->attachments, true) : [],
                    'signature_verified' => $email->signature_verified ?? true,
                ];
            });

            return response()->json([
                'success' => true,
                'emails' => $formattedEmails,
                'folder' => $folder
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération emails', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur récupération emails'], 500);
        }
    }

    /**
     * Marquer un email comme lu
     */
    public function markEmailAsRead(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())->findOrFail($emailId);
            $email->update([
                'is_read' => true,
                'read_at' => Carbon::now()
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Erreur marquage lu', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur marquage'], 500);
        }
    }

    /**
     * Supprimer un email (déplacer vers corbeille)
     */
    public function deleteEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())->findOrFail($emailId);
            $email->update(['folder' => 'trash']);
            
            return response()->json(['success' => true, 'message' => 'Email déplacé vers la corbeille']);
            
        } catch (\Exception $e) {
            Log::error('Erreur suppression email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur suppression'], 500);
        }
    }

    /**
     * Créer des emails de démonstration
     */
    public function createDemoEmails(): JsonResponse
    {
        try {
            $demoEmails = [
                [
                    'folder' => 'inbox',
                    'from_email' => 'welcome@messageriesecure.fr',
                    'from_name' => 'Équipe MessagerieSecure',
                    'subject' => 'Bienvenue sur MessagerieSecure ! 🎉',
                    'content' => 'Félicitations ! Votre compte MessagerieSecure est maintenant actif. Vous pouvez envoyer et recevoir des emails sécurisés.',
                    'is_read' => false,
                ],
                [
                    'folder' => 'inbox',
                    'from_email' => 'noreply@mailgun.com',
                    'from_name' => 'Mailgun',
                    'subject' => 'Configuration Mailgun réussie ✅',
                    'content' => 'Excellente nouvelle ! Votre configuration Mailgun est maintenant active et prête à envoyer des emails.',
                    'is_read' => false,
                ]
            ];

            foreach ($demoEmails as $emailData) {
                Email::create(array_merge($emailData, [
                    'user_id' => auth()->id(),
                    'to_email' => auth()->user()->email,
                    'to_name' => auth()->user()->prenom . ' ' . auth()->user()->nom,
                    'preview' => substr($emailData['content'], 0, 100),
                ]));
            }

            return response()->json(['success' => true, 'message' => 'Emails de démonstration créés']);

        } catch (\Exception $e) {
            Log::error('Erreur création emails démo', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur création démo'], 500);
        }
    }

    /**
     * Validation d'email en temps réel via Mailgun
     */
    public function validateEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $response = Http::withBasicAuth('api', $this->mailgunSecret)
                ->get("https://{$this->mailgunEndpoint}/v4/address/validate", [
                    'address' => $request->email,
                    'provider_lookup' => true,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'valid' => $data['result'] === 'deliverable',
                    'risk' => $data['risk'] ?? 'unknown',
                    'reason' => $data['reason'] ?? null,
                ]);
            }

            return response()->json(['error' => 'Service indisponible'], 503);

        } catch (\Exception $e) {
            Log::error('Erreur validation email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur de validation'], 500);
        }
    }

    /**
     * Statistiques simples
     */
    public function getEmailStats(Request $request): JsonResponse
    {
        try {
            $stats = [
                'emails_sent' => Email::where('user_id', auth()->id())->where('folder', 'sent')->count(),
                'emails_received' => Email::where('user_id', auth()->id())->where('folder', 'inbox')->count(),
                'emails_unread' => Email::where('user_id', auth()->id())->where('folder', 'inbox')->where('is_read', false)->count(),
                'emails_total' => Email::where('user_id', auth()->id())->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur stats email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur récupération stats'], 500);
        }
    }

    /**
     * Recevoir des emails entrants via webhook Mailgun
     */
    public function handleIncomingEmail(Request $request): JsonResponse
    {
        try {
            Log::info('🔥 Webhook Mailgun reçu', $request->all());
            
            // Vérifier la signature
            $isSignatureValid = $this->verifyWebhookSignature($request);
            
            // Déterminer le dossier selon la signature
            $folder = $isSignatureValid ? 'inbox' : 'unverified';
            
            Log::info('🔐 Signature vérifiée', [
                'valid' => $isSignatureValid,
                'folder' => $folder
            ]);

            // Extraire les données
            $from = $request->input('From') ?? $request->input('from');
            $to = $request->input('To') ?? $request->input('to');
            $subject = $request->input('Subject') ?? $request->input('subject') ?? 'Sans objet';
            $bodyPlain = $request->input('body-plain') ?? '';
            $bodyHtml = $request->input('body-html') ?? '';

            // Traiter les pièces jointes entrantes
            $attachments = [];
            $attachmentCount = $request->input('attachment-count', 0);

            for ($i = 1; $i <= $attachmentCount; $i++) {
                if ($request->hasFile("attachment-{$i}")) {
                    $file = $request->file("attachment-{$i}");
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('incoming_attachments', $filename, 'private');
                    
                    $attachments[] = [
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ];
                    
                    Log::info('📎 Pièce jointe reçue', [
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize()
                    ]);
                }
            }
            
            // Trouver l'utilisateur
            $userId = $this->findUserByEmail($to);
            
            if (!$userId) {
                Log::warning('❌ Utilisateur non trouvé', ['email' => $to]);
                return response()->json(['error' => 'Destinataire non trouvé'], 404);
            }

            // Créer l'email avec le bon dossier et les pièces jointes
            $email = Email::create([
                'user_id' => $userId,
                'folder' => $folder, // 'inbox' ou 'unverified'
                'from_email' => $this->extractEmail($from),
                'from_name' => $this->extractName($from) ?? $this->extractEmail($from),
                'to_email' => $this->extractEmail($to),
                'subject' => $subject,
                'content' => $bodyHtml ?: $bodyPlain,
                'preview' => substr($bodyPlain ?: strip_tags($bodyHtml), 0, 100),
                'is_html' => !empty($bodyHtml),
                'is_read' => false,
                'signature_verified' => $isSignatureValid,
                'attachments' => json_encode($attachments),
            ]);

            Log::info('✅ Email sauvegardé', [
                'email_id' => $email->id,
                'folder' => $folder,
                'signature_valid' => $isSignatureValid,
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
            Log::error('💥 Erreur webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erreur'], 500);
        }
    }

    /**
     * Vérifier la signature du webhook Mailgun
     */
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

    /**
     * Trouver l'utilisateur par email
     */
    private function findUserByEmail($email): ?int
    {
        $cleanEmail = $this->extractEmail($email);
        
        // Chercher d'abord une correspondance exacte
        $user = User::where('email', $cleanEmail)->first();
        
        if ($user) {
            return $user->id;
        }
        
        Log::info('Utilisateur non trouvé pour l\'email', ['email' => $cleanEmail]);
        return null;
    }

    /**
     * Extraire l'email d'une chaîne "Nom <email@domain.com>"
     */
    private function extractEmail($emailString): string
    {
        if (preg_match('/<(.+?)>/', $emailString, $matches)) {
            return $matches[1];
        }
        return trim($emailString);
    }

    /**
     * Extraire le nom d'une chaîne "Nom <email@domain.com>"
     */
    private function extractName($emailString): ?string
    {
        if (preg_match('/^(.+?)\s*<.+?>$/', $emailString, $matches)) {
            return trim($matches[1], '"');
        }
        return null;
    }

    /**
     * Tester l'accessibilité du webhook Mailgun
     */
    public function testWebhook(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Webhook accessible',
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => $request->headers->all()
        ]);
    }

    /**
     * Télécharger une pièce jointe (pour les emails reçus)
     */
    public function downloadAttachment(Request $request, $emailId, $attachmentIndex): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())->findOrFail($emailId);
            $attachments = json_decode($email->attachments, true) ?? [];
            
            if (!isset($attachments[$attachmentIndex])) {
                abort(404, 'Pièce jointe non trouvée');
            }
            
            $attachment = $attachments[$attachmentIndex];
            $filePath = storage_path('app/private/' . $attachment['path']);
            
            if (!file_exists($filePath)) {
                abort(404, 'Fichier non trouvé');
            }
            
            Log::info('📥 Téléchargement pièce jointe', [
                'user_id' => auth()->id(),
                'email_id' => $emailId,
                'filename' => $attachment['filename']
            ]);
            
            return response()->download($filePath, $attachment['filename']);
            
        } catch (\Exception $e) {
            Log::error('Erreur téléchargement pièce jointe', [
                'error' => $e->getMessage(),
                'email_id' => $emailId,
                'attachment_index' => $attachmentIndex
            ]);
            abort(500, 'Erreur lors du téléchargement');
        }
    }
}