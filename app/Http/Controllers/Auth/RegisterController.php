<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Validation des données
        $validatedData = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'tel' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Création de l'email automatiquement
        $email = strtolower($validatedData['prenom'] . '.' . $validatedData['nom'] . '@missive-si.fr');

        // Créer un nouvel utilisateur
        $user = User::create([
            'prenom' => $validatedData['prenom'],
            'nom' => $validatedData['nom'],
            'tel' => $validatedData['tel'],
            'email' => $email,
            'password' => Hash::make($validatedData['password']),
        ]);

        // Activation automatique de la 2FA (générer une clé secrète)
        $user->forceFill([
            'two_factor_secret' => encrypt(Str::random(32)),  // Utiliser une clé secrète pour la 2FA
        ])->save();

        // Connecter l'utilisateur
        Auth::login($user);

        // Rediriger vers la page de vérification 2FA
        return redirect()->route('two-factor.index');
    }
}

