<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SecureMessage;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques pour le dashboard
        $stats = [
            'secure_messages_sent' => SecureMessage::where('sender_id', Auth::id())->count(),
            'secure_messages_received' => SecureMessage::where('recipient_email', Auth::user()->email)->count(),
            'secure_messages_read' => SecureMessage::where('recipient_email', Auth::user()->email)
                ->whereNotNull('read_at')->count(),
        ];

        // Messages récents (remplace l'ancien système)
        $messages = SecureMessage::where('recipient_email', Auth::user()->email)
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact('stats', 'messages'));
    }
}