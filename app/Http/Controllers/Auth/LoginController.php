<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    // Afficher le formulaire de connexion
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Gérer la connexion
    public function login(Request $request)
    {
        // Validation des données
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
    
        // Tentative de connexion
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
    
            // Vérifier si l'utilisateur a activé la vérification 2FA
            if ($user->two_factor_secret && !$user->two_factor_confirmed_at) {
                // Si la 2FA est activée mais pas confirmée, rediriger vers la page 2FA
                return redirect()->route('two-factor.index');
            }
    
            // Si la 2FA est confirmée, ou non activée, rediriger vers le tableau de bord
            return redirect()->intended('/dashboard');
        }
    
        // Si la connexion échoue, retourner à la page de connexion avec un message d'erreur
        return back()->withErrors([
            'email' => 'Les informations de connexion sont incorrectes.',
        ]);
    }
    

    // Gérer la déconnexion
    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}
