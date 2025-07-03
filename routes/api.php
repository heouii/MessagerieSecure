<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailgunController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('mailgun')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/send-secure', [MailgunController::class, 'sendSecureEmail']);
    Route::post('/send-notification', [MailgunController::class, 'sendNotification']);
    Route::post('/validate-email', [MailgunController::class, 'validateEmail']);
    Route::get('/stats', [MailgunController::class, 'getEmailStats']);
    Route::delete('/message/{messageId}', [MailgunController::class, 'deleteMessage']);
    Route::post('/template', [MailgunController::class, 'createTemplate']);
    Route::get('/templates', [MailgunController::class, 'getTemplates']);
    Route::put('/template/{id}', [MailgunController::class, 'updateTemplate']);
    Route::delete('/template/{id}', [MailgunController::class, 'deleteTemplate']);
});

Route::prefix('mailgun')->middleware(['auth'])->group(function () {
    Route::post('/send-email', [MailgunController::class, 'sendEmail']);
    Route::get('/emails/{folder}', [MailgunController::class, 'getEmails']);
    Route::post('/emails/{emailId}/read', [MailgunController::class, 'markEmailAsRead']);
    Route::delete('/emails/{emailId}', [MailgunController::class, 'deleteEmail']);
    Route::post('/create-demo', [MailgunController::class, 'createDemoEmails']);
});

// Webhook sans auth (reste à l'extérieur)
Route::post('/webhooks/mailgun', [MailgunController::class, 'handleWebhook']);

// Webhook pour emails entrants
Route::post('/webhooks/mailgun-incoming', [MailgunController::class, 'handleIncomingEmail'])
    ->name('mailgun.webhook.incoming');

    // routes/api.php
Route::get('/webhooks/mailgun-test', [MailgunController::class, 'testWebhook']);

Route::get('/debug-emails', function() {
    try {
        $users = \App\Models\User::all();
        $emails = \App\Models\Email::all();
        
        return response()->json([
            'users_count' => $users->count(),
            'emails_count' => $emails->count(),
            'users' => $users->map(function($u) {
                return ['id' => $u->id, 'email' => $u->email];
            }),
            'emails' => $emails->map(function($e) {
                return [
                    'id' => $e->id,
                    'user_id' => $e->user_id,
                    'from' => $e->from_email,
                    'to' => $e->to_email,
                    'subject' => $e->subject,
                    'folder' => $e->folder,
                    'created_at' => $e->created_at
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});