<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function showForm()
    {
        return view('contact');
    }

    public function sendMail(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // Logique d'envoi d'email de contact
        Mail::raw($request->message, function ($message) use ($request) {
            $message->from($request->email, $request->name)
                   ->to(config('mail.from.address'))
                   ->subject('Nouveau message de contact');
        });

        return back()->with('success', 'Message envoyé avec succès !');
    }
}
