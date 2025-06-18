<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class ProfilController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        return view('profil', compact('user', 'sessions'));
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'tel' => 'required|numeric|digits:10',
            // 'email' => 'required|email|max:255|unique:users,email,' . Auth::id(), // décommente si tu veux éditer l'email
        ]);

        $user = Auth::user();
        $user->update($validatedData);

        if ($request->filled('current_password') || $request->filled('new_password') || $request->filled('new_password_confirmation')) {
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if (!Hash::check($request->input('current_password'), $user->password)) {
                return redirect()->route('profil.show')->with('password_error', 'Ancien mot de passe incorrect.');
            }

            $user->password = Hash::make($request->input('new_password'));
            $user->save();

            return redirect()->route('profil.show')->with('password_success', 'Mot de passe modifié avec succès !');
        }

        return redirect()->route('profil.show')->with('success', 'Profil mis à jour avec succès!');
    }

    public function destroySession(Request $request, $id)
    {
        DB::table('sessions')->where('id', $id)->delete();
        return back()->with('success', 'Session déconnectée.');
    }

    public function enable2FA(Request $request)
    {
        $request->validate(['password_2fa' => 'required']);
        $user = Auth::user();

        if (!Hash::check($request->password_2fa, $user->password)) {
            return back()->with('password_error', 'Mot de passe incorrect.');
        }

        if (!$user->two_factor_secret) {
            $google2fa = new Google2FA();
            $user->two_factor_secret = $google2fa->generateSecretKey();
        }
        $user->two_factor_confirmed_at = now();
        $user->save();

        return back()->with('success', '2FA activé !');
    }

    public function disable2FA(Request $request)
    {
        $request->validate(['password_2fa' => 'required']);
        $user = Auth::user();

        if (!Hash::check($request->password_2fa, $user->password)) {
            return back()->with('password_error', 'Mot de passe incorrect.');
        }

        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back()->with('success', '2FA désactivé.');
    }
}
