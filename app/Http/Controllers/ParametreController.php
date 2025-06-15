<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ParametreController extends Controller
{
    // Affichage de la page des paramètres
    public function index()
    {
        return view('parametres');
    }

    // Demander la suppression différée
    public function delete(Request $request)
    {
        // Vérification du mot de passe
        $request->validate([
            'password' => 'required',
        ]);

        // Vérifier si le mot de passe est correct
        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Le mot de passe est incorrect.']);
        }

        // Marquer l'utilisateur comme supprimé (soft delete)
        $user = Auth::user();
        $user->delete(); // Cela va remplir la colonne 'deleted_at' avec l'heure actuelle

        // Se déconnecter de l'utilisateur
        Auth::logout();

        // Rediriger avec un message
        return redirect('/')->with('success', 'Votre compte sera définitivement supprimé dans 30 jours. Vous pouvez annuler cette suppression pendant ce délai.');
    }

    // Annuler la suppression
    public function cancelDeletion()
    {
        $user = Auth::user();

        // Vérifier si la suppression est encore dans le délai de 30 jours
        if ($user->deleted_at && Carbon::parse($user->deleted_at)->addDays(30)->isFuture()) {
            $user->restore(); // Annuler la suppression (restore l'utilisateur)
            return redirect()->route('parametres')->with('success', 'La suppression de votre compte a été annulée.');
        }

        return redirect()->route('parametres')->withErrors(['error' => 'Il n\'est plus possible d\'annuler la suppression de votre compte.']);
    }
}

