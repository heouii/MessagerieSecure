<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\SecureMessage;
use App\Models\EmailTracking;
use App\Models\EmailTemplate;

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
     * Envoi d'email sécurisé avec chiffrement
     */
    public function sendSecureEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'expires_in_hours' => 'nullable|integer|min:1|max:168', // Max 7 jours
            'require_2fa' => 'boolean',
            'self_destruct' => 'boolean',
            'read_receipt' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Générer un token unique pour le message
            $messageToken = Str::random(64);
            $encryptionKey = Str::random(32);
            
            // Chiffrer le message
            $encryptedMessage = encrypt($request->message);
            
            // Sauvegarder en base
            $secureMessage = SecureMessage::create([
                'sender_id' => auth()->id(),
                'recipient_email' => $request->to,
                'subject' => $request->subject,
                'encrypted_content' => $encryptedMessage,
                'message_token' => $messageToken,
                'encryption_key' => $encryptionKey,
                'expires_at' => $request->expires_in_hours ? 
                    Carbon::now()->addHours($request->expires_in_hours) : null,
                'require_2fa' => $request->require_2fa ?? false,
                'self_destruct' => $request->self_destruct ?? false,
                'read_receipt' => $request->read_receipt ?? false,
            ]);

            // URL sécurisée pour lire le message
            $secureUrl = url("/secure-message/{$messageToken}");

            // Envoyer l'email via Mailgun avec tracking
            $response = $this->sendMailgunEmail([
                'to' => $request->to,
                'subject' => "🔒 Message sécurisé : " . $request->subject,
                'html' => $this->buildSecureEmailTemplate($secureMessage, $secureUrl),
                'text' => $this->buildSecureEmailTextTemplate($secureMessage, $secureUrl),
                'o:tracking' => 'yes',
                'o:tracking-clicks' => 'yes',
                'o:tracking-opens' => 'yes',
                'o:tag' => ['secure-message', 'encrypted'],
                'v:message_id' => $secureMessage->id,
                'v:sender_name' => auth()->user()->prenom . ' ' . auth()->user()->nom,
            ]);

            if ($response['success']) {
                $secureMessage->update(['mailgun_id' => $response['mailgun_id']]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Message sécurisé envoyé avec succès',
                    'message_id' => $secureMessage->id,
                    'expires_at' => $secureMessage->expires_at,
                ]);
            }

            return response()->json(['error' => 'Erreur lors de l\'envoi'], 500);

        } catch (\Exception $e) {
            Log::error('Erreur envoi email sécurisé', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur système'], 500);
        }
    }

    /**
     * Envoi d'email de notification sécurisée
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'type' => 'required|in:login_alert,password_change,account_update',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        $templates = [
            'login_alert' => [
                'subject' => '🔔 Nouvelle connexion à votre compte',
                'template' => 'buildLoginAlertTemplate'
            ],
            'password_change' => [
                'subject' => '✅ Mot de passe modifié avec succès',
                'template' => 'buildPasswordChangeTemplate'
            ],
            'account_update' => [
                'subject' => '📝 Informations de compte mises à jour',
                'template' => 'buildAccountUpdateTemplate'
            ]
        ];

        $config = $templates[$request->type];
        
        $response = $this->sendMailgunEmail([
            'to' => $request->email,
            'subject' => $config['subject'],
            'html' => $this->{$config['template']}($user, $request->data ?? []),
            'o:tag' => ['notification', $request->type],
            'o:tracking' => 'yes',
        ]);

        if ($response['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Notification envoyée',
            ]);
        }

        return response()->json(['error' => 'Erreur lors de l\'envoi'], 500);
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
                    'is_disposable' => $data['is_disposable_address'] ?? false,
                    'is_role_address' => $data['is_role_address'] ?? false,
                ]);
            }

            return response()->json(['error' => 'Service indisponible'], 503);

        } catch (\Exception $e) {
            Log::error('Erreur validation email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur de validation'], 500);
        }
    }

    /**
     * Webhook Mailgun pour tracking
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // Vérifier la signature Mailgun
        if (!$this->verifyWebhookSignature($request)) {
            return response()->json(['error' => 'Signature invalide'], 401);
        }

        $eventData = $request->all();
        $eventType = $eventData['event-data']['event'] ?? null;

        try {
            switch ($eventType) {
                case 'delivered':
                    $this->handleDelivered($eventData);
                    break;
                case 'opened':
                    $this->handleOpened($eventData);
                    break;
                case 'clicked':
                    $this->handleClicked($eventData);
                    break;
                case 'bounced':
                case 'failed':
                    $this->handleBounced($eventData);
                    break;
                case 'complained':
                    $this->handleSpamComplaint($eventData);
                    break;
                case 'unsubscribed':
                    $this->handleUnsubscribed($eventData);
                    break;
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Erreur webhook Mailgun', ['error' => $e->getMessage(), 'data' => $eventData]);
            return response()->json(['error' => 'Erreur traitement webhook'], 500);
        }
    }

    /**
     * Obtenir les statistiques d'emails
     */
    public function getEmailStats(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        try {
            // Stats depuis Mailgun
            $response = Http::withBasicAuth('api', $this->mailgunSecret)
                ->get("https://{$this->mailgunEndpoint}/v3/{$this->mailgunDomain}/stats/total", [
                    'start' => $startDate->format('D, d M Y H:i:s T'),
                    'end' => Carbon::now()->format('D, d M Y H:i:s T'),
                    'resolution' => 'day',
                ]);

            $mailgunStats = $response->successful() ? $response->json() : null;

            // Stats locales
            $localStats = [
                'secure_messages_sent' => SecureMessage::where('created_at', '>=', $startDate)->count(),
                'secure_messages_read' => SecureMessage::where('read_at', '>=', $startDate)->count(),
                'expired_messages' => SecureMessage::where('expires_at', '<=', Carbon::now())->count(),
                'self_destructed' => SecureMessage::where('self_destructed_at', '>=', $startDate)->count(),
            ];

            return response()->json([
                'mailgun_stats' => $mailgunStats,
                'local_stats' => $localStats,
                'period' => "{$days} derniers jours",
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur stats email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur récupération stats'], 500);
        }
    }

    /**
     * Supprimer un message automatiquement (GDPR/Sécurité)
     */
    public function deleteMessage(Request $request, $messageId): JsonResponse
    {
        $message = SecureMessage::findOrFail($messageId);

        // Vérifier les permissions
        if ($message->sender_id !== auth()->id() && !auth()->user()->admin) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        try {
            // Supprimer de Mailgun si nécessaire
            if ($message->mailgun_id) {
                Http::withBasicAuth('api', $this->mailgunSecret)
                    ->delete("https://{$this->mailgunEndpoint}/v3/{$this->mailgunDomain}/messages/{$message->mailgun_id}");
            }

            $message->update([
                'deleted_at' => Carbon::now(),
                'self_destructed_at' => Carbon::now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Message supprimé']);

        } catch (\Exception $e) {
            Log::error('Erreur suppression message', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur suppression'], 500);
        }
    }

    /**
     * Création et gestion de templates d'emails
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:email_templates',
            'subject' => 'required|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $template = EmailTemplate::create([
                'name' => $request->name,
                'subject' => $request->subject,
                'html_content' => $request->html_content,
                'text_content' => $request->text_content,
                'variables' => $request->variables ?? [],
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'template' => $template,
                'message' => 'Template créé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur création template', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur création template'], 500);
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

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

    private function buildSecureEmailTemplate(SecureMessage $message, string $url): string
    {
        $senderName = User::find($message->sender_id)->prenom ?? 'Expéditeur';
        $expiresText = $message->expires_at ? 
            "Ce message expire le " . $message->expires_at->format('d/m/Y à H:i') : 
            "Ce message n'expire pas";

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2c3e50; margin: 0;'>🔒 MessagerieSecure</h1>
                    <p style='color: #7f8c8d; margin: 10px 0 0 0;'>Message sécurisé et chiffré</p>
                </div>
                
                <div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h2 style='color: #27ae60; margin: 0 0 10px 0; font-size: 18px;'>Nouveau message de {$senderName}</h2>
                    <p style='margin: 0; color: #2c3e50;'><strong>Sujet :</strong> {$message->subject}</p>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$url}' style='display: inline-block; background: #3498db; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>
                        🔓 Lire le message sécurisé
                    </a>
                </div>

                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404; font-size: 14px;'>
                        ⚠️ <strong>Important :</strong> {$expiresText}
                        " . ($message->self_destruct ? "<br>Ce message s'autodétruira après lecture." : "") . "
                    </p>
                </div>

                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #7f8c8d; font-size: 12px; margin: 0;'>
                        Ce message a été envoyé via MessagerieSecure<br>
                        Si vous ne devriez pas recevoir ce message, ignorez-le.
                    </p>
                </div>
            </div>
        </div>";
    }

    private function buildSecureEmailTextTemplate(SecureMessage $message, string $url): string
    {
        $senderName = User::find($message->sender_id)->prenom ?? 'Expéditeur';
        
        return "
MESSAGERIE SECURE - Message chiffré

Nouveau message de : {$senderName}
Sujet : {$message->subject}

Pour lire ce message sécurisé, cliquez sur le lien suivant :
{$url}

IMPORTANT : " . ($message->expires_at ? 
    "Ce message expire le " . $message->expires_at->format('d/m/Y à H:i') : 
    "Ce message n'expire pas"
) . ($message->self_destruct ? "\nCe message s'autodétruira après lecture." : "") . "

---
MessagerieSecure - Vos communications protégées
        ";
    }

    private function buildLoginAlertTemplate(User $user, array $data): string
    {
        $ip = $data['ip'] ?? 'IP inconnue';
        $location = $data['location'] ?? 'Localisation inconnue';
        $device = $data['device'] ?? 'Appareil inconnu';
        $time = $data['time'] ?? Carbon::now()->format('d/m/Y à H:i');

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2c3e50; margin: 0;'>🔔 MessagerieSecure</h1>
                    <p style='color: #7f8c8d; margin: 10px 0 0 0;'>Alerte de sécurité</p>
                </div>
                
                <div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h2 style='color: #856404; margin: 0 0 15px 0; font-size: 18px;'>Nouvelle connexion détectée</h2>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Utilisateur :</strong> {$user->prenom} {$user->nom}</p>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Date/Heure :</strong> {$time}</p>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Adresse IP :</strong> {$ip}</p>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Localisation :</strong> {$location}</p>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Appareil :</strong> {$device}</p>
                </div>

                <div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #155724; font-size: 14px;'>
                        ✅ Si c'était vous, aucune action n'est requise.
                    </p>
                </div>

                <div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #721c24; font-size: 14px;'>
                        ⚠️ Si ce n'était pas vous, changez immédiatement votre mot de passe et contactez notre support.
                    </p>
                </div>

                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #7f8c8d; font-size: 12px; margin: 0;'>
                        MessagerieSecure - Protection de votre compte
                    </p>
                </div>
            </div>
        </div>";
    }

    private function buildPasswordChangeTemplate(User $user, array $data): string
    {
        $time = $data['time'] ?? Carbon::now()->format('d/m/Y à H:i');
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2c3e50; margin: 0;'>✅ MessagerieSecure</h1>
                    <p style='color: #7f8c8d; margin: 10px 0 0 0;'>Confirmation de sécurité</p>
                </div>
                
                <div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h2 style='color: #155724; margin: 0 0 15px 0; font-size: 18px;'>Mot de passe modifié avec succès</h2>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Utilisateur :</strong> {$user->prenom} {$user->nom}</p>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Date/Heure :</strong> {$time}</p>
                </div>

                <div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #721c24; font-size: 14px;'>
                        ⚠️ Si vous n'avez pas effectué cette modification, contactez immédiatement notre support.
                    </p>
                </div>

                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #7f8c8d; font-size: 12px; margin: 0;'>
                        MessagerieSecure - Sécurité de votre compte
                    </p>
                </div>
            </div>
        </div>";
    }

    private function buildAccountUpdateTemplate(User $user, array $data): string
    {
        $changes = $data['changes'] ?? [];
        $time = $data['time'] ?? Carbon::now()->format('d/m/Y à H:i');
        
        $changesHtml = '';
        foreach ($changes as $field => $value) {
            $changesHtml .= "<p style='margin: 5px 0; color: #2c3e50;'><strong>{$field} :</strong> {$value}</p>";
        }

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2c3e50; margin: 0;'>📝 MessagerieSecure</h1>
                    <p style='color: #7f8c8d; margin: 10px 0 0 0;'>Mise à jour du compte</p>
                </div>
                
                <div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h2 style='color: #0c5460; margin: 0 0 15px 0; font-size: 18px;'>Informations mises à jour</h2>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Utilisateur :</strong> {$user->prenom} {$user->nom}</p>
                    <p style='margin: 5px 0; color: #2c3e50;'><strong>Date/Heure :</strong> {$time}</p>
                    " . ($changesHtml ? "<hr style='margin: 15px 0; border: none; border-top: 1px solid #bee5eb;'>{$changesHtml}" : "") . "
                </div>

                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #7f8c8d; font-size: 12px; margin: 0;'>
                        MessagerieSecure - Gestion de votre compte
                    </p>
                </div>
            </div>
        </div>";
    }

    private function verifyWebhookSignature(Request $request): bool
    {
        $timestamp = $request->input('timestamp');
        $token = $request->input('token');
        $signature = $request->input('signature');

        $data = $timestamp . $token;
        $hash = hash_hmac('sha256', $data, $this->mailgunSecret);

        return hash_equals($hash, $signature);
    }

    private function handleDelivered($eventData): void
    {
        $messageId = $eventData['event-data']['user-variables']['message_id'] ?? null;
        if ($messageId) {
            EmailTracking::create([
                'message_id' => $messageId,
                'event_type' => 'delivered',
                'event_data' => $eventData,
                'occurred_at' => Carbon::createFromTimestamp($eventData['event-data']['timestamp']),
            ]);
        }
    }

    private function handleOpened($eventData): void
    {
        $messageId = $eventData['event-data']['user-variables']['message_id'] ?? null;
        if ($messageId) {
            EmailTracking::create([
                'message_id' => $messageId,
                'event_type' => 'opened',
                'event_data' => $eventData,
                'occurred_at' => Carbon::createFromTimestamp($eventData['event-data']['timestamp']),
            ]);

            // Marquer le message comme lu
            SecureMessage::where('id', $messageId)->update(['read_at' => Carbon::now()]);
        }
    }

    private function handleClicked($eventData): void
    {
        $messageId = $eventData['event-data']['user-variables']['message_id'] ?? null;
        if ($messageId) {
            EmailTracking::create([
                'message_id' => $messageId,
                'event_type' => 'clicked',
                'event_data' => $eventData,
                'occurred_at' => Carbon::createFromTimestamp($eventData['event-data']['timestamp']),
            ]);
        }
    }

    private function handleBounced($eventData): void
    {
        $messageId = $eventData['event-data']['user-variables']['message_id'] ?? null;
        if ($messageId) {
            EmailTracking::create([
                'message_id' => $messageId,
                'event_type' => 'bounced',
                'event_data' => $eventData,
                'occurred_at' => Carbon::createFromTimestamp($eventData['event-data']['timestamp']),
            ]);

            // Marquer l'email comme non délivrable
            $email = $eventData['event-data']['recipient'];
            // Vous pourriez ajouter une logique pour blacklister temporairement
        }
    }

    private function handleSpamComplaint($eventData): void
    {
        $email = $eventData['event-data']['recipient'];
        Log::warning('Plainte spam reçue', ['email' => $email, 'data' => $eventData]);
        
        // Logique pour gérer les plaintes spam
        // Par exemple, ajouter à une liste de suppression
    }

    private function handleUnsubscribed($eventData): void
    {
        $email = $eventData['event-data']['recipient'];
        Log::info('Désabonnement', ['email' => $email]);
        
        // Logique pour gérer les désabonnements
    }
}