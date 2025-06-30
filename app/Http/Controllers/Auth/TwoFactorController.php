<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                $user->two_factor_secret // <-- PAS de decrypt
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

        $isValid = $google2fa->verifyKey(
            $user->two_factor_secret,
            $request->input('code')
        );

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

    public function reset(Request $request)
    {
        $userId = session('2fa:user:id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect('/login')->withErrors(['email' => 'Session expirée. Veuillez vous reconnecter.']);
        }

        $request->validate([
            'security_answer' => 'required|string',
        ]);

        if (!$user->security_question || !$user->security_answer) {
            return back()->with('reset2fa_error', 'Aucune question de sécurité n\'est enregistrée sur ce compte.');
        }

        if (!\Hash::check($request->input('security_answer'), $user->security_answer)) {
            return back()->with('reset2fa_error', 'Réponse à la question de sécurité incorrecte.');
        }

        $google2fa = app('pragmarx.google2fa');
        $user->two_factor_secret = $google2fa->generateSecretKey(); // <-- PAS d'encrypt
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back()->with('reset2fa_success', 'MFA réinitialisé. Veuillez scanner le nouveau QR code.');
    }
}
