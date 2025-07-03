<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\MailgunController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ServerLogController;
use App\Http\Controllers\Admin\AdminConnexionController;
use App\Http\Controllers\Admin\BlacklistController;

// Page d'accueil
Route::get('/', function () {
    return view('welcome');
});

// Authentification
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Mot de passe oublié
Route::get('/mot-de-passe-oublie', [ForgotPasswordController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/mot-de-passe-oublie', [ForgotPasswordController::class, 'handleForgot'])->name('password.forgot.post');
Route::get('/mot-de-passe-oublie/question', [ForgotPasswordController::class, 'showSecretQuestionForm'])->name('password.forgot.question');
Route::post('/mot-de-passe-oublie/question', [ForgotPasswordController::class, 'handleVerify'])->name('password.forgot.verify');

// Dashboard principal
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

// Double authentification (2FA)
    Route::get('/two-factor', [TwoFactorController::class, 'index'])->name('two-factor.index');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/2fa/reset', [TwoFactorController::class, 'reset'])->name('two-factor.reset');

// Profil utilisateur
Route::middleware(['auth'])->group(function () {
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::post('/profil/2fa/enable', [ProfilController::class, 'enable2FA'])->name('profil.2fa.enable');
    Route::post('/profil/2fa/disable', [ProfilController::class, 'disable2FA'])->name('profil.2fa.disable');
    Route::post('/profil/sessions/{id}/destroy', [ProfilController::class, 'destroySession'])->name('profil.sessions.destroy');
});

// Paramètres
Route::middleware(['auth'])->group(function () {
    Route::get('/parametres', [ParametreController::class, 'index'])->name('parametres');
    Route::post('/parametres/delete', [ParametreController::class, 'delete'])->name('parametre.delete');
    Route::post('/parametres/cancel-deletion', [ParametreController::class, 'cancelDeletion'])->name('parametre.cancelDeletion');
});

// Interface Mailgun
Route::get('/mailgun', function () {
    return view('mailgun.interface');
})->middleware('auth')->name('mailgun.interface');

// Administration
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Gestion des utilisateurs
    Route::get('users', [UserController::class, 'index'])->name('users');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    Route::patch('users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('users.toggleAdmin');
    
    // Logs et connexions
    Route::get('logs', [LogController::class, 'index'])->name('logs');
    Route::post('logs/export', [LogController::class, 'export'])->name('logs.export');
    Route::get('server-logs', [ServerLogController::class, 'index'])->name('server.logs');
    Route::post('server-logs/download', [ServerLogController::class, 'download'])->name('server.logs.download');
    Route::get('connexions', [AdminConnexionController::class, 'index'])->name('connexions');
    
    // Blacklist
    Route::resource('blacklists', BlacklistController::class)->names([
        'index' => 'blacklists.index',
        'create' => 'blacklists.create',
        'store' => 'blacklists.store',
        'edit' => 'blacklists.edit',
        'update' => 'blacklists.update',
        'destroy' => 'blacklists.destroy',
    ]);
});

// Contact
Route::get('/contact', [MailController::class, 'showForm'])->name('contact.form');
Route::post('/contact', [MailController::class, 'sendMail'])->name('contact.send');

// Messagerie sécurisée Mailgun
Route::prefix('mailgun')->middleware(['auth'])->group(function () {
    Route::get('/', [MailgunController::class, 'index'])->name('mailgun.index');
    Route::post('/send-secure', [MailgunController::class, 'sendSecureEmail'])->name('mailgun.send.secure');
    Route::post('/send-notification', [MailgunController::class, 'sendNotification'])->name('mailgun.send.notification');
    Route::get('/stats', [MailgunController::class, 'getEmailStats'])->name('mailgun.stats');
});

// Route web pour l'envoi d'emails (après les autres routes mailgun)
Route::post('/mailgun-send', [MailgunController::class, 'sendEmail'])
    ->middleware('auth')
    ->name('mailgun.web.send');

// Routes web pour l'interface mailgun
Route::get('/mailgun-emails/{folder}', [MailgunController::class, 'getEmails'])
    ->middleware('auth')
    ->name('mailgun.web.emails');

Route::post('/mailgun-read/{emailId}', [MailgunController::class, 'markEmailAsRead'])
    ->middleware('auth')
    ->name('mailgun.web.read');

Route::post('/mailgun-demo', [MailgunController::class, 'createDemoEmails'])
    ->middleware('auth')
    ->name('mailgun.web.demo');

// Routes pour l'interface web (à placer après les autres routes mailgun)
Route::middleware(['auth'])->group(function () {
    Route::get('/mailgun-emails/{emailId}/reply', [MailgunController::class, 'replyToEmail'])
        ->name('mailgun.web.reply');
    Route::get('/mailgun-email-history', [MailgunController::class, 'getEmailHistory'])
        ->name('mailgun.web.email_history');
    Route::get('/mailgun-email-suggestions', [MailgunController::class, 'getEmailSuggestions'])
        ->name('mailgun.web.email_suggestions');
});

//
// Utilitaires
Route::get('/check-email-exists', function (\Illuminate\Http\Request $request) {
    $exists = \App\Models\User::where('email', $request->query('email'))->exists();
    return response()->json($exists);
});

Route::get('/download-attachment/{path}', function($path) {
    $fullPath = storage_path('app/public/attachments/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    return response()->download($fullPath);
})->middleware('auth');

Route::middleware(['auth'])->group(function () {
    // Interface principale
    Route::get('/mailgun-send', [MailgunController::class, 'index'])->name('mailgun.interface');
    Route::post('/mailgun-send', [MailgunController::class, 'sendEmail']);
    Route::get('/mailgun-emails/{folder}', [MailgunController::class, 'getEmails']);
    Route::post('/mailgun-read/{emailId}', [MailgunController::class, 'markEmailAsRead']);

    // Routes pour les nouvelles fonctionnalités
    Route::get('/mailgun-emails/{emailId}/reply', [MailgunController::class, 'replyToEmail']);
    Route::get('/mailgun-email-suggestions', [MailgunController::class, 'getEmailSuggestions']);
    Route::get('/mailgun-email-history', [MailgunController::class, 'getEmailHistory']);
});