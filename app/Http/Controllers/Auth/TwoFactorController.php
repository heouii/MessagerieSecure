<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;



class TwoFactorController extends Controller
{
    public function show()
    {
        $userId = session('2fa:user:id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // Récupérer l'utilisateur depuis la base de données
        $user = User::findOrFail($userId);
        $secret = decrypt($user->two_factor_secret);

        // Initialiser Google2FA
        $google2fa = new Google2FA();

        // Générer l'URL du QR code (pour scanner avec Google Authenticator)
        $QRUrl = $google2fa->getQRCodeUrl(
            config('app.url'), 
            $user->email,
            $secret
        );

        // Créer le QR code avec l'URL générée
$result = Builder::create()
    ->writer(new PngWriter())
    ->data($QRUrl)
    ->encoding(new Encoding('UTF-8'))
    ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
    ->size(300)
    ->margin(10)
    ->build();

$qrCodeImage = $result->getDataUri();


        // Pour déboguer, enregistrer l'image QR Code en base64 dans les logs
        \Log::info('QR Code Image Base64: ' . $qrCodeImage);

        // Retourner la vue avec l'image QR Code générée
        return view('auth.two-factor', compact('qrCodeImage'));
    }

    public function verify(Request $request)
    {
        // Validation du code de vérification
        $request->validate([
            'code' => 'required|numeric',
        ]);

        // Récupérer l'utilisateur de la session
        $userId = session('2fa:user:id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // Récupérer l'utilisateur depuis la base de données
        $user = User::findOrFail($userId);
        $secret = decrypt($user->two_factor_secret);
        
        // Initialiser Google2FA
        $google2fa = new Google2FA();

        // Vérifier le code de vérification
        if ($google2fa->verifyKey($secret, $request->code)) {
            session()->forget('2fa:user:id');  // Supprimer l'utilisateur de la session
            Auth::login($user);  // Connecter l'utilisateur

            // Rediriger vers le tableau de bord ou l'admin en fonction du rôle
            return $user->admin
                ? redirect()->intended('/admin')
                : redirect()->intended('/dashboard');
        }

        // Retourner un message d'erreur si le code est incorrect
        return back()->withErrors([
            'code' => 'Le code de vérification est incorrect.',
        ]);
    }
}