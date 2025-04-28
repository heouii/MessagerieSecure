<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MessageController;
use Laravel\Fortify\Features;
use App\Http\Controllers\Auth\TwoFactorController;

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
});

//2MFA
Route::middleware(['auth'])->group(function () {
    Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor.index');
    Route::post('two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
});
