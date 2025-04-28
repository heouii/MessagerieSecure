<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;


class TwoFactorController extends Controller
{
    // Afficher le formulaire avec le QR Code
    public function show()
    {
        $google2fa = new Google2FA();
        $user = Auth::user();

        // Générer un secret 2FA pour l'utilisateur (si ce n'est pas déjà fait)
        if (!$user->two_factor_secret) {
            $secret = $google2fa->generateSecretKey();
            $user->two_factor_secret = encrypt($secret);  // Stocker le secret dans la base de données
            $user->save();
        } else {
            $secret = decrypt($user->two_factor_secret);
        }

        // Générer l'URL du QR Code
        $QRUrl = $google2fa->getQRCodeUrl(
            config('app.name'), // Nom de l'application (ex: "MonApp")
            $user->email,       // Identifiant de l'utilisateur (email dans ce cas)
            $secret
        );

        // Passer l'URL du QR code à la vue
        return view('auth.two-factor', compact('QRUrl'));
    }

    // Vérifier le code 2FA
    public function verify(Request $request)
    {
        // Valider le code 2FA
        $request->validate([
            'code' => 'required|numeric',
        ]);

        $google2fa = new Google2FA();
        $user = Auth::user();
        $secret = decrypt($user->two_factor_secret);

        // Vérifier le code 2FA
        if ($google2fa->verifyKey($secret, $request->code)) {
            // Vérification réussie
            return redirect()->intended('/dashboard');
        }

        // Retourner à la page de 2FA avec un message d'erreur si la vérification échoue
        return back()->withErrors([
            'code' => 'Le code de vérification est incorrect.',
        ]);
    }
}



