<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MessageController;
use Laravel\Fortify\Features;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ServerLogController;
use App\Http\Controllers\Admin\AdminConnexionController;


// Page d'accueil
Route::get('/', function () {
    return view('welcome');
});

// Auth
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard (affiche messages)
Route::get('/dashboard', [MessageController::class, 'index'])->middleware('auth')->name('dashboard');

// Envoyer un message
Route::post('/messages', [MessageController::class, 'store'])->middleware('auth')->name('messages.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/messages/inbox', [MessageController::class, 'inbox'])->name('messages.inbox');
    Route::get('/messages/sent', [MessageController::class, 'sent'])->name('messages.sent');
    Route::get('/messages/drafts', [MessageController::class, 'drafts'])->name('messages.drafts');
    Route::get('/messages/spam', [MessageController::class, 'spam'])->name('messages.spam');
    Route::get('/messages/deleted', [MessageController::class, 'deleted'])->name('messages.deleted');
    
    // Afficher les détails d'un message
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
});

// 2MFA
Route::get('/two-factor', [TwoFactorController::class, 'index'])->name('two-factor.index');
Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');

// Desactiver/Réactiver le MFA
Route::get('/profil', [ProfilController::class, 'show'])->name('profil.show');
Route::post('/profil/update', [ProfilController::class, 'update'])->name('profil.update');
Route::post('/profil/2fa/enable', [ProfilController::class, 'enable2FA'])->name('profil.2fa.enable');
Route::post('/profil/2fa/disable', [ProfilController::class, 'disable2FA'])->name('profil.2fa.disable');



//admin
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('logs', [LogController::class, 'index'])->name('logs');
    Route::post('logs/export', [LogController::class, 'export'])->name('logs.export');
    Route::get('server-logs', [ServerLogController::class, 'index'])->name('server.logs');
    Route::post('server-logs/download', [ServerLogController::class, 'download'])->name('server.logs.download');


    Route::get('users', [UserController::class, 'index'])->name('users');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('users/{user}/block', [UserController::class, 'block'])->name('users.block');
    Route::patch('users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('users.toggleAdmin');
    Route::get('connexions', [AdminConnexionController::class, 'index'])->name('connexions');

});




//profil
Route::middleware(['auth'])->group(function () {
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');
});

// pour les sessions actives
Route::post('/profil/sessions/{id}/destroy', [ProfilController::class, 'destroySession'])->name('profil.sessions.destroy');



//parametre

Route::middleware(['auth'])->group(function () {
    Route::get('/parametres', [ParametreController::class, 'index'])->name('parametres');
    Route::post('/parametres/delete', [ParametreController::class, 'delete'])->name('parametre.delete');
    Route::post('/parametres/cancel-deletion', [ParametreController::class, 'cancelDeletion'])->name('parametre.cancelDeletion');
});