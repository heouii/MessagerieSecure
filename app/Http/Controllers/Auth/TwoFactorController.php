<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorController extends Controller
{

public function index(Request $request)
{
    $userId = session('2fa:user:id');
    $user = \App\Models\User::find($userId);

    if (!$user) {
        return redirect('/login')->withErrors(['email' => 'Session expirée. Veuillez vous reconnecter.']);
    }

    if (!$user->two_factor_secret) {
        $google2fa = app('pragmarx.google2fa');
        $user->two_factor_secret = $google2fa->generateSecretKey();
        $user->save();
    }

        $showQr = false;
$qrCode = null;

// Si l'utilisateur n'a PAS confirmé le 2FA, on affiche le QR code (première activation)
if (!$user->two_factor_confirmed_at) {
    $google2fa = app('pragmarx.google2fa');
    $qrCode = $google2fa->getQRCodeInline(
        config('app.name'),
        $user->email,
        $user->two_factor_secret
    );
    $showQr = true;
}

return view('auth.two-factor', [
    'qrCode' => $qrCode,
    'showQr' => $showQr,
    'user' => $user,
]);

}

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $userId = session('2fa:user:id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect('/login')->withErrors(['email' => 'Session expirée. Veuillez vous reconnecter.']);
        }

        $google2fa = app('pragmarx.google2fa');

        $isValid = $google2fa->verifyKey($user->two_factor_secret, $request->input('code'));

        if ($isValid) {
            $user->two_factor_confirmed_at = now();
            $user->save();

            Auth::login($user);

            $request->session()->forget('2fa:user:id');

            return $user->admin
                ? redirect()->intended('/admin/dashboard')->with('success', 'Connecté avec 2FA !')
                : redirect()->intended('/dashboard')->with('success', 'Connecté avec 2FA !');
        } else {
            return back()->withErrors(['code' => 'Code 2FA invalide.']);
        }
    }
}
