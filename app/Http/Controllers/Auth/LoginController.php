<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:6',
    ]);

    $credentials = $request->only('email', 'password');

    // Tentative de connexion de l'utilisateur
    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // Vérifier si l'utilisateur a une clé 2FA (obligatoire)
        if ($user->two_factor_secret) {
            // Stocker l'ID de l'utilisateur dans la session pour la vérification 2FA
            session(['2fa:user:id' => $user->id]);

            // Déconnexion de l'utilisateur après la connexion initiale
            Auth::logout();

            // Redirection vers la page de 2FA pour scanner le QR code
            return redirect()->route('two-factor.index');
        }

        return $user->admin
        ? redirect()->intended('/admin/dashboard')
        : redirect()->intended('/dashboard');

    }

    return back()->withErrors([
        'email' => 'Les informations de connexion sont incorrectes.',
    ]);
}


    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}