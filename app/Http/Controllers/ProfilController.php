<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfilController extends Controller
{
    // Afficher le profil de l'utilisateur
    public function show()
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Passer les informations utilisateur à la vue
        return view('profil', compact('user'));
    }

    // Mettre à jour le profil de l'utilisateur
    public function update(Request $request)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'tel' => 'required|numeric|digits:10',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Mettre à jour les informations dans la base de données
        $user->update($validatedData);

        // Rediriger avec un message de succès
        return redirect()->route('profil.show')->with('success', 'Profil mis à jour avec succès!');
    }
}

