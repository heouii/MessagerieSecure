<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    // Afficher la liste des utilisateurs
    public function index()
    {
        $users = User::where('admin', 0)->get();
        return view('admin.users', compact('users'));
    }

    // Afficher détails utilisateur
    public function show(User $user)
    {
        return view('admin.infos', compact('user'));
    }

    // Supprimer utilisateur
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'Utilisateur supprimé avec succès.');
    }

    // Bloquer utilisateur pour une durée donnée
public function block(Request $request, User $user)
{
    $request->validate([
        'block_duration' => 'required|integer|min:0',
    ]);

    $days = (int) $request->block_duration;

    if ($days === 0) {
        // Débloquer l'utilisateur (plus de blocage)
        $user->is_blocked = false;
        $user->blocked_until = null;
        $message = "Utilisateur débloqué.";
    } else {
        // Bloquer l'utilisateur pour X jours
        $user->is_blocked = true;
        $user->blocked_until = now()->addDays($days);
        $message = "Utilisateur bloqué pour $days jour(s).";
    }
    
    $user->save();

    return redirect()->route('admin.users')->with('success', $message);
}

}
