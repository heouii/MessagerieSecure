<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:6',
    ]);

    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        if (is_null($user->two_factor_secret) || is_null($user->two_factor_confirmed_at)) {
            session(['2fa:user:id' => $user->id]);
            Auth::logout();
            return redirect()->route('two-factor.index');
        }

        session(['2fa:user:id' => $user->id]);
        Auth::logout();
        return redirect()->route('two-factor.index');
    }

    return back()->withErrors([
        'email' => 'Les informations de connexion sont incorrectes.',
    ]);
}


    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}