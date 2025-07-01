<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function inbox()
    {
        $messages = Message::where('receiver_id', Auth::id())
            ->where('status', 'inbox')
            ->latest()
            ->get();

        return view('messages.inbox', compact('messages'));
    }

    public function show($id)
    {
        $message = Message::where('receiver_id', Auth::id())
            ->findOrFail($id);

        return view('messages.show', compact('message'));
    }

    public function sent()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'sent')
            ->latest()
            ->get();

        return view('messages.sent', compact('messages'));
    }

    public function drafts()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'draft')
            ->latest()
            ->get();

        return view('messages.drafts', compact('messages'));
    }

    public function spam()
    {
        $messages = Message::where('receiver_id', Auth::id())
            ->where('status', 'spam')
            ->with('user') // On charge l'expéditeur
            ->latest()
            ->get();

        return view('messages.spam', compact('messages'));
    }

    public function deleted()
    {
        $messages = Message::where('receiver_id', Auth::id())
            ->where('status', 'deleted')
            ->latest()
            ->get();

        return view('messages.deleted', compact('messages'));
    }

    public function index()
    {
        $messages = Message::where('user_id', Auth::id())
            ->where('status', 'inbox')
            ->latest()
            ->get();

        return view('dashboard', compact('messages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'destinataire' => 'required|email|exists:users,email',
            'sujet' => 'required|string',
            'contenu' => 'required|string',
            'piece_jointe' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx',
        ]);

        $receiver = User::where('email', $request->destinataire)->first();

        $messageSent = new Message();
        $messageSent->user_id = auth()->id();
        $messageSent->receiver_id = $receiver->id;
        $messageSent->sujet = $request->sujet;
        $messageSent->contenu = $request->contenu;
        $messageSent->status = 'sent';

        if ($request->hasFile('piece_jointe')) {
            $messageSent->piece_jointe = $request->file('piece_jointe')->store('pieces_jointes');
        }

        $messageSent->save();

        $messageReceived = new Message();
        $messageReceived->user_id = $receiver->id;
        $messageReceived->receiver_id = auth()->id();
        $messageReceived->sujet = $request->sujet;
        $messageReceived->contenu = $request->contenu;
        $messageReceived->status = 'inbox';

        if ($request->hasFile('piece_jointe')) {
            $messageReceived->piece_jointe = $messageSent->piece_jointe;
        }

        $messageReceived->save();

        return redirect()->route('dashboard')->with('success', 'Message envoyé');
    }

    public function unspam(Message $message)
    {
        if ($message->receiver_id !== auth()->id()) {
            abort(403);
        }

        $message->update(['status' => 'inbox']);

        return redirect()->route('messages.inbox')->with('success', 'Le message a été déplacé dans la boîte de réception.');
    }

    public function destroy(Message $message)
    {
        if ($message->receiver_id !== auth()->id()) {
            abort(403);
        }

        $message->update(['status' => 'deleted']);

        return back()->with('success', 'Le message a été déplacé dans les éléments supprimés.');
    }
}
