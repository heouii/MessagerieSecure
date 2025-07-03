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
     * Envoi d'email direct (comme Gmail)
     */
    
    //Vérifier si l'email ou le domaine est bloqué
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

    public function sendEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'cc' => 'nullable|email',
            'html_format' => 'boolean',
            'read_receipt' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $toEmail = strtolower($request->to);
        if ($this->isBlacklistedEmail($toEmail)) {
            return response()->json([
                'error' => "L'adresse email ou le domaine du destinataire est bloqué. Veuillez vérifier l'adresse et réessayer."
            ], 403);
        }

        if ($request->cc && $this->isBlacklistedEmail(strtolower($request->cc))) {
            return response()->json([
                'error' => "L'adresse email ou le domaine du destinataire en copie est bloqué."
            ], 403);
        }

        try {
            // Construire l'email pour Mailgun
            $emailData = [
                'to' => $request->to,
                'subject' => $request->subject,
                'from' => auth()->user()->prenom . ' ' . auth()->user()->nom . ' <' . auth()->user()->email . '>',
            ];

            if ($request->cc) {
                $emailData['cc'] = $request->cc;
            }

            if ($request->html_format) {
                $emailData['html'] = nl2br(htmlspecialchars($request->message));
            } else {
                $emailData['text'] = $request->message;
            }

            // Tracking, envoi etc.
            $emailData['o:tracking'] = 'yes';
            $emailData['o:tracking-opens'] = 'yes';
            $emailData['o:tag'] = ['sent-email', 'user-' . auth()->id()];

            $response = $this->sendMailgunEmail($emailData);

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
                    'is_html' => $request->html_format ?? false,
                    'is_read' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email envoyé avec succès'
                ]);
            }

            return response()->json(['error' => 'Erreur lors de l\'envoi'], 500);

        } catch (\Exception $e) {
            Log::error('Erreur envoi email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur système'], 500);
        }
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
            
            // Vérification de la signature Mailgun réactivée
            if (!$this->verifyWebhookSignature($request)) {
                return response()->json(['error' => 'Signature invalide'], 401);
            }

            $from = $request->input('From') ?? $request->input('from');
            $to = $request->input('To') ?? $request->input('to');
            $subject = $request->input('Subject') ?? $request->input('subject') ?? 'Sans objet';
            $bodyPlain = $request->input('body-plain') ?? '';
            $bodyHtml = $request->input('body-html') ?? '';
            
            Log::info('📧 Données extraites', [
                'from' => $from,
                'to' => $to,
                'subject' => $subject
            ]);

            // Trouver l'utilisateur
            $userId = $this->findUserByEmail($to);
            
            if (!$userId) {
                Log::warning('❌ Utilisateur non trouvé', ['email' => $to]);
                return response()->json(['error' => 'Destinataire non trouvé'], 404);
            }

            // Créer l'email
            $email = Email::create([
                'user_id' => $userId,
                'folder' => 'inbox',
                'from_email' => $this->extractEmail($from),
                'from_name' => $this->extractName($from) ?? $this->extractEmail($from),
                'to_email' => $this->extractEmail($to),
                'subject' => $subject,
                'content' => $bodyHtml ?: $bodyPlain,
                'preview' => substr($bodyPlain ?: strip_tags($bodyHtml), 0, 100),
                'is_html' => !empty($bodyHtml),
                'is_read' => false,
            ]);

            Log::info('✅ Email sauvegardé', ['email_id' => $email->id]);

            return response()->json(['success' => true, 'email_id' => $email->id]);

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
        $timestamp = $request->input('timestamp');
        $token = $request->input('token');
        $signature = $request->input('signature');
        
        if (!$timestamp || !$token || !$signature) {
            Log::warning('Webhook Mailgun: paramètres de signature manquants');
            return false;
        }
        
        // Vérifier que le timestamp n'est pas trop ancien (15 minutes max)
        if (abs(time() - $timestamp) > 900) {
            Log::warning('Webhook Mailgun: timestamp trop ancien');
            return false;
        }
        
        $expectedSignature = hash_hmac(
            'sha256',
            $timestamp . $token,
            $this->mailgunSecret
        );
        
        $isValid = hash_equals($signature, $expectedSignature);
        
        if (!$isValid) {
            Log::warning('Webhook Mailgun: signature invalide', [
                'expected' => $expectedSignature,
                'received' => $signature
            ]);
        }
        
        return $isValid;
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
        
        // Si pas trouvé, créer un utilisateur temporaire ou retourner null
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
    

    // ==================== MÉTHODE PRIVÉE ====================

    /**
     * Envoyer email via Mailgun
     */
    private function sendMailgunEmail(array $params): array
    {
        try {
            $response = Http::withBasicAuth('api', $this->mailgunSecret)
                ->asForm()
                ->post("https://{$this->mailgunEndpoint}/v3/{$this->mailgunDomain}/messages", array_merge([
                    'from' => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                ], $params));

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'mailgun_id' => $data['id'] ?? null,
                    'message' => $data['message'] ?? 'Envoyé'
                ];
            }

            Log::error('Erreur Mailgun', ['response' => $response->body()]);
            return ['success' => false, 'error' => 'Erreur Mailgun API'];

        } catch (\Exception $e) {
            Log::error('Exception Mailgun', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
}