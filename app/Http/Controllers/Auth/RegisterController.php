<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

   public function register(Request $request)
{
    $validatedData = $request->validate([
        'prenom' => 'required|string|max:255',
        'nom' => 'required|string|max:255',
        'tel' => 'required|numeric|digits:10',
        'password' => [
            'required',
            'string',
            'min:10',
            'confirmed',
            'regex:/[A-Z]/',        
            'regex:/[a-z]/',         
            'regex:/[0-9]/',        
            'regex:/[@$!%*#?&.]/',    
        ],
    ], [
        'tel.numeric' => 'Le numéro de téléphone doit contenir uniquement des chiffres.',
        'tel.digits' => 'Le numéro de téléphone doit contenir exactement 10 chiffres.',
        'password.min' => 'Le mot de passe doit contenir au moins 10 caractères.',
        'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
        'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
    ]);

    // Créer l'email de l'utilisateur automatiquement
    $email = strtolower($validatedData['prenom'] . '.' . $validatedData['nom'] . '@missive-si.fr');

    $user = User::create([
        'prenom' => $validatedData['prenom'],
        'nom' => $validatedData['nom'],
        'tel' => $validatedData['tel'],
        'email' => $email,
        'password' => Hash::make($validatedData['password']),
    ]);

    // Générer la clé 2FA
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();

    // Enregistrer la clé secrète dans la base de données
    $user->two_factor_secret = encrypt($secret);
    $user->save();

    // Connexion automatique après l'inscription
    Auth::login($user);

    // Redirection vers la page de vérification 2FA
    return redirect()->route('two-factor.index');
}

}

