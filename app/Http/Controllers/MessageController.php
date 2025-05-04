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
        $messages = Message::where('receiver_id', Auth::id()) // Changer 'user_id' en 'receiver_id' pour les messages reçus
            ->where('status', 'inbox')
            ->latest()
            ->get();

        return view('messages.inbox', compact('messages'));
    }

    // Voir un message spécifique
    public function show($id)
    {
        // S'assurer que l'utilisateur connecté peut voir ce message
        $message = Message::where('receiver_id', Auth::id()) // L'utilisateur doit être le destinataire
            ->findOrFail($id); // Si le message n'est pas trouvé, une erreur 404 sera lancée

        return view('messages.show', compact('message'));
    }

    // Messages envoyés
    public function sent()
    {
        $messages = Message::where('user_id', Auth::id()) // Changer 'expediteur_id' en 'user_id' pour les messages envoyés par l'utilisateur connecté
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
        $messages = Message::where('receiver_id', Auth::id()) // Changer 'user_id' en 'receiver_id' pour les messages spam reçus
            ->where('status', 'spam')
            ->latest()
            ->get();

        return view('messages.spam', compact('messages'));
    }

    // Messages supprimés
    public function deleted()
    {
        $messages = Message::where('receiver_id', Auth::id()) // Changer 'user_id' en 'receiver_id' pour les messages supprimés reçus
            ->where('status', 'deleted')
            ->latest()
            ->get();

        return view('messages.deleted', compact('messages'));
    }

    // Tous les messages (tableau de bord)
    public function index()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'inbox') // Affiche uniquement les messages reçus
            ->latest()
            ->get();
    
        return view('dashboard', compact('messages'));
    }
    

    // Envoi d'un message
    // Envoi du message
    public function store(Request $request)
    {
        // Validation de la requête
        $request->validate([
            'destinataire' => 'required|email|exists:users,email', // Validation de l'email du destinataire
            'sujet' => 'required|string',
            'contenu' => 'required|string',
            'piece_jointe' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx',
        ]);
    
        // Trouver l'utilisateur destinataire
        $receiver = User::where('email', $request->destinataire)->first();
    
        // Créer le message pour l'expéditeur
        $messageSent = new Message();
        $messageSent->user_id = auth()->id(); // L'utilisateur connecté est l'expéditeur
        $messageSent->receiver_id = $receiver->id; // L'utilisateur destinataire
        $messageSent->sujet = $request->sujet;
        $messageSent->contenu = $request->contenu;
        $messageSent->status = 'sent'; // Statut envoyé pour l'expéditeur
    
        // Gérer la pièce jointe si elle existe
        if ($request->hasFile('piece_jointe')) {
            $messageSent->piece_jointe = $request->file('piece_jointe')->store('pieces_jointes');
        }
    
        // Sauvegarder le message envoyé
        $messageSent->save();
    
        // Créer également le message pour la boîte de réception du destinataire
        $messageReceived = new Message();
        $messageReceived->user_id = $receiver->id; // Le destinataire est l'utilisateur du message reçu
        $messageReceived->receiver_id = auth()->id(); // L'expéditeur est l'utilisateur connecté
        $messageReceived->sujet = $request->sujet;
        $messageReceived->contenu = $request->contenu;
        $messageReceived->status = 'inbox'; // Statut pour la boîte de réception
    
        // Gérer la pièce jointe pour le destinataire si elle existe
        if ($request->hasFile('piece_jointe')) {
            $messageReceived->piece_jointe = $messageSent->piece_jointe;
        }
    
        // Sauvegarder le message reçu
        $messageReceived->save();
    
        // Redirection avec succès
        return redirect()->route('dashboard')->with('success', 'Message envoyé');
    }
    

}

