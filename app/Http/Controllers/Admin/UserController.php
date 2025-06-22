<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {

        $users = User::where('email', '!=', 'micael.test@missive-si.fr')->get();
        return view('admin.users', compact('users'));
    }

    public function show(User $user)
    {
        return view('admin.infos', compact('user'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'Utilisateur supprimé avec succès.');
    }

public function block(Request $request, User $user)
{
    $request->validate([
        'block_duration' => 'required|integer|min:0',
    ]);

    $days = (int) $request->block_duration;

    if ($days === 0) {
        $user->is_blocked = false;
        $user->blocked_until = null;
        $message = "Utilisateur débloqué.";
    } else {
        $user->is_blocked = true;
        $user->blocked_until = now()->addDays($days);
        $message = "Utilisateur bloqué pour $days jour(s).";
    }
    
    $user->save();

    return redirect()->route('admin.users')->with('success', $message);
}

public function toggleAdmin(User $user)
{

    if (auth()->id() === $user->id) {
        return back()->with('error', "Vous ne pouvez pas modifier votre propre rôle admin.");
    }

    $user->admin = !$user->admin;
    $user->save();

    $message = $user->admin ? "Utilisateur promu admin." : "Rôle admin retiré.";

    return back()->with('success', $message);
}


}
