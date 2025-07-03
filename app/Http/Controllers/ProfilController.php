<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        $user = Auth::user();

        $request->validate([
            'prenom' => 'required|string|max:255',
            'nom'   => 'required|string|max:255',
            'tel'   => 'required|numeric|digits:10',
        ], [
            'tel.numeric' => 'Le numéro de téléphone doit contenir uniquement des chiffres.',
            'tel.digits'  => 'Le numéro de téléphone doit contenir exactement 10 chiffres.',
        ]);

        $user->prenom = $request->input('prenom');
        $user->nom    = $request->input('nom');
        $user->tel    = $request->input('tel');

        if ($request->filled('security_answer')) {
            if (!$request->filled('security_password') || !Hash::check($request->security_password, $user->password)) {
                return redirect()->route('profil.show')->with('security_error', 'Mot de passe incorrect pour changer la question secrète.');
            }
            if ($request->filled('security_question')) {
                $user->security_question = $request->input('security_question');
            }
            $user->security_answer = bcrypt($request->input('security_answer'));
            session()->flash('security_success', 'Question de sécurité mise à jour.');
        }

        if (
            $request->filled('current_password') ||
            $request->filled('new_password') ||
            $request->filled('new_password_confirmation')
        ) {
            $request->validate([
                'current_password' => 'required',
                'new_password' => [
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
                'new_password.min' => 'Le mot de passe doit contenir au moins 10 caractères.',
                'new_password.regex' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
                'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            ]);

            if (!Hash::check($request->input('current_password'), $user->password)) {
                return redirect()->route('profil.show')->with('password_error', 'Ancien mot de passe incorrect.');
            }

            $user->password = Hash::make($request->input('new_password'));
            $user->save();
            return redirect()->route('profil.show')->with('password_success', 'Mot de passe modifié avec succès !');
        }

        $user->save();

        return redirect()->route('profil.show')->with('success', 'Profil mis à jour avec succès!');
    }
}
