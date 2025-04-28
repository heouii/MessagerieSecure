<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // Afficher la boîte de réception
    public function inbox()
    {
        $messages = Message::where('user_id', Auth::id())->where('status', 'inbox')->latest()->get();
        return view('messages.inbox', compact('messages'));
    }

    // Afficher les messages envoyés
    public function sent()
    {
        $messages = Message::where('user_id', Auth::id())->where('status', 'sent')->latest()->get();
        return view('messages.sent', compact('messages'));
    }

    // Afficher les brouillons
    public function drafts()
    {
        $messages = Message::where('user_id', Auth::id())->where('status', 'draft')->latest()->get();
        return view('messages.drafts', compact('messages'));
    }

    // Afficher les spams
    public function spam()
    {
        $messages = Message::where('user_id', Auth::id())->where('status', 'spam')->latest()->get();
        return view('messages.spam', compact('messages'));
    }

    // Afficher les messages supprimés
    public function deleted()
    {
        $messages = Message::where('user_id', Auth::id())->where('status', 'deleted')->latest()->get();
        return view('messages.deleted', compact('messages'));
    }

    // Index (par défaut afficher tous les messages récents)
    public function index()
    {
        $messages = Message::where('user_id', Auth::id())->latest()->get();
        return view('dashboard', compact('messages'));
    }

    // Envoi d'un message
    public function store(Request $request)
    {
        $request->validate([
            'sujet' => 'required|string|max:255',
            'contenu' => 'required|string',
        ]);

        Message::create([
            'user_id' => Auth::id(),
            'sujet' => $request->sujet,
            'contenu' => $request->contenu,
            'status' => 'sent', // Statut par défaut des messages envoyés
        ]);

        return redirect()->route('dashboard')->with('success', 'Message envoyé !');
    }
}

