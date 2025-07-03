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

    protected function generateUniqueEmail($prenom, $nom)
    {
        $base = strtolower($prenom . '.' . $nom . '@mg.missive-si.fr');
        $email = $base;
        $i = 2;
        while (User::where('email', $email)->exists()) {
            $email = strtolower($prenom . '.' . $nom . $i . '@mg.missive-si.fr');
            $i++;
        }
        return $email;
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'tel' => 'required|numeric|digits:10',
            'security_question' => 'required|string|max:255',
            'security_answer'   => 'required|string|max:255',
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

        $email = $this->generateUniqueEmail($validatedData['prenom'], $validatedData['nom']);

        $user = User::create([
            'prenom' => $validatedData['prenom'],
            'nom' => $validatedData['nom'],
            'tel' => $validatedData['tel'],
            'email' => $email,
            'security_question' => $validatedData['security_question'],
            'security_answer' => bcrypt($validatedData['security_answer']),
            'password' => Hash::make($validatedData['password']),
        ]);

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $user->two_factor_secret = $secret;
        $user->save();

        Auth::login($user);
        session(['2fa:user:id' => $user->id]);
        return redirect()->route('two-factor.index');
    }
}
