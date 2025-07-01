<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailgunController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route pour récupérer l'utilisateur authentifié
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes Mailgun avec authentification
Route::prefix('mailgun')->middleware(['auth:sanctum'])->group(function () {
    
    // Envoi d'emails sécurisés
    Route::post('/send-secure', [MailgunController::class, 'sendSecureEmail']);
    
    // Notifications de sécurité
    Route::post('/send-notification', [MailgunController::class, 'sendNotification']);
    
    // Validation d'emails
    Route::post('/validate-email', [MailgunController::class, 'validateEmail']);
    
    // Statistiques
    Route::get('/stats', [MailgunController::class, 'getEmailStats']);
    
    // Gestion des messages
    Route::delete('/message/{messageId}', [MailgunController::class, 'deleteMessage']);
    
    // Templates
    Route::post('/template', [MailgunController::class, 'createTemplate']);
    Route::get('/templates', [MailgunController::class, 'getTemplates']);
    Route::put('/template/{id}', [MailgunController::class, 'updateTemplate']);
    Route::delete('/template/{id}', [MailgunController::class, 'deleteTemplate']);
});

// Webhook Mailgun (sans authentification car vient de Mailgun)
Route::post('/webhooks/mailgun', [MailgunController::class, 'handleWebhook']);

// Routes pour l'API de messagerie (exemple d'extension)
Route::prefix('messages')->middleware(['auth:sanctum'])->group(function () {
    
    // Lister les messages envoyés par l'utilisateur
    Route::get('/sent', function (Request $request) {
        return $request->user()->secureMessages()->latest()->paginate(20);
    });
    
    // Lister les messages reçus par l'utilisateur
    Route::get('/received', function (Request $request) {
        return \App\Models\SecureMessage::where('recipient_email', $request->user()->email)
            ->latest()
            ->paginate(20);
    });
    
    // Détails d'un message spécifique
    Route::get('/{id}', function (Request $request, $id) {
        $message = \App\Models\SecureMessage::findOrFail($id);
        
        // Vérifier les permissions
        if ($message->sender_id !== $request->user()->id && 
            $message->recipient_email !== $request->user()->email) {
            abort(403);
        }
        
        return $message->load(['sender', 'tracking']);
    });
});

// Routes pour la gestion des templates (admin seulement)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    
    // Statistiques globales
    Route::get('/stats/global', function () {
        return [
            'total_messages' => \App\Models\SecureMessage::count(),
            'messages_today' => \App\Models\SecureMessage::whereDate('created_at', today())->count(),
            'messages_this_week' => \App\Models\SecureMessage::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'expired_messages' => \App\Models\SecureMessage::expired()->count(),
            'active_users' => \App\Models\User::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    });
    
    // Gestion des utilisateurs
    Route::get('/users', function () {
        return \App\Models\User::latest()->paginate(50);
    });
    
    // Logs des messages
    Route::get('/messages/logs', function () {
        return \App\Models\SecureMessage::with(['sender', 'tracking'])
            ->latest()
            ->paginate(100);
    });
});
