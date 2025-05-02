<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    // Afficher la boîte de réception
    public function inbox()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'inbox')
            ->latest()
            ->get();

        return view('messages.inbox', compact('messages'));
    }

    // Messages envoyés
    public function sent()
    {
        $messages = Message::where('expediteur_id', Auth::id())
            ->where('status', 'sent')
            ->latest()
            ->get();

        return view('messages.sent', compact('messages'));
    }

    // Brouillons
    public function drafts()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'draft')
            ->latest()
            ->get();

        return view('messages.drafts', compact('messages'));
    }

    // Spams
    public function spam()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'spam')
            ->latest()
            ->get();

        return view('messages.spam', compact('messages'));
    }

    // Messages supprimés
    public function deleted()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'deleted')
            ->latest()
            ->get();

        return view('messages.deleted', compact('messages'));
    }

    // Tous les messages
    public function index()
    {
        $messages = Message::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('dashboard', compact('messages'));
    }

    // Envoi d'un message
    public function store(Request $request)
    {
        $request->validate([
            'destinataire' => 'required|email|exists:users,email',
            'sujet' => 'required|string|max:255',
            'contenu' => 'required|string',
            'piece_jointe' => 'nullable|file|max:5120',
        ]);

        $destinataire = User::where('email', $request->destinataire)->firstOrFail();

        $cheminFichier = null;
        if ($request->hasFile('piece_jointe')) {
            $cheminFichier = $request->file('piece_jointe')->store('pieces_jointes', 'public');
        }

        // Message côté destinataire
        Message::create([
            'user_id' => $destinataire->id,
            'expediteur_id' => Auth::id(),
            'sujet' => $request->sujet,
            'contenu' => $request->contenu,
            'piece_jointe' => $cheminFichier,
            'status' => 'inbox',
        ]);

        // Copie côté expéditeur
        Message::create([
            'user_id' => Auth::id(),
            'expediteur_id' => Auth::id(),
            'sujet' => $request->sujet,
            'contenu' => $request->contenu,
            'piece_jointe' => $cheminFichier,
            'status' => 'sent',
        ]);

        return redirect()->route('dashboard')->with('success', 'Message envoyé !');
    }
}
