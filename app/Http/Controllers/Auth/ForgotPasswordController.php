<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function handleForgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
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

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('recovery_error', 'Aucun utilisateur avec cet email.')->withInput();
        }

        if (!$user->security_question || !$user->security_answer) {
            return back()->with('recovery_error', 'Ce compte ne dispose pas de question secrète.')->withInput();
        }

        session([
            'email_for_recovery' => $user->email,
            'ask_question' => $user->security_question,
            'pending_new_password' => $request->new_password,
            'pending_new_password_confirmation' => $request->new_password_confirmation,
        ]);


        return redirect()->route('password.forgot.question');
    }

    public function showSecretQuestionForm(Request $request)
    {
        if (!session('email_for_recovery') || !session('ask_question')) {
            return redirect()->route('password.forgot');
        }
        return view('auth.forgot-password-question', [
            'question' => session('ask_question'),
            'email'    => session('email_for_recovery'),
        ]);
    }

    public function handleVerify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'security_answer' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->security_question || !$user->security_answer) {
            return redirect()->route('password.forgot')->with('recovery_error', 'Compte introuvable ou question non configurée.');
        }

        if (!Hash::check($request->security_answer, $user->security_answer)) {
            return back()->with('recovery_error', 'Réponse à la question secrète incorrecte.');
        }

        $newPassword = session('pending_new_password');

        if (!$newPassword) {
            return redirect()->route('password.forgot')->with('recovery_error', 'Erreur interne, recommencez.');
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        session()->forget(['email_for_recovery', 'ask_question', 'pending_new_password', 'pending_new_password_confirmation']);

        return redirect()->route('login')->with('success', 'Mot de passe réinitialisé ! Connectez-vous avec votre nouveau mot de passe.');
    }
}
