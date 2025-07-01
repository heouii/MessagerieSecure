<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ParametreController extends Controller
{
    public function index()
    {
        return view('parametres');
    }

    public function delete(Request $request)
    {

        $request->validate([
            'password' => 'required',
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Le mot de passe est incorrect.']);
        }

        $user = Auth::user();
        $user->delete(); 

        Auth::logout();

        return redirect('/')->with('success', 'Votre compte sera définitivement supprimé dans 30 jours. Vous pouvez annuler cette suppression pendant ce délai.');
    }

    public function cancelDeletion()
    {
        $user = Auth::user();


        if ($user->deleted_at && Carbon::parse($user->deleted_at)->addDays(30)->isFuture()) {
            $user->restore(); 
            return redirect()->route('parametres')->with('success', 'La suppression de votre compte a été annulée.');
        }

        return redirect()->route('parametres')->withErrors(['error' => 'Il n\'est plus possible d\'annuler la suppression de votre compte.']);
    }
}

