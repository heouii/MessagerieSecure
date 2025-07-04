<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Traits\EmailSending;
use App\Http\Controllers\Traits\EmailReceiving;
use App\Http\Controllers\Traits\EmailManagement;
use Illuminate\Support\Facades\Log; 
use App\Models\User;

class MailgunController extends Controller
{
    use EmailSending, EmailReceiving, EmailManagement;

    private $mailgunDomain;
    private $mailgunSecret;
    private $mailgunEndpoint;

    public function __construct()
    {
        $this->mailgunDomain = config('services.mailgun.domain');
        $this->mailgunSecret = config('services.mailgun.secret');
        $this->mailgunEndpoint = config('services.mailgun.endpoint');
    }

    public function index()
    {
        return view('interface');
    }

    // Seules les méthodes utilitaires restent ici
    private function isBlacklistedEmail(string $email): bool
    {
        $email = strtolower($email);
        $domain = substr(strrchr($email, "@"), 1);

        return \App\Models\Blacklist::where(function($query) use ($email, $domain) {
            $query->where(function($q) use ($email) {
                $q->where('type', 'email')->where('value', $email);
            })->orWhere(function($q) use ($domain) {
                $q->where('type', 'domain')->where('value', $domain);
            });
        })->exists();
    }

    private function findUserByEmail($email): ?int
    {
        $cleanEmail = $this->extractEmail($email);
        
        // Chercher d'abord une correspondance exacte
        $user = User::where('email', $cleanEmail)->first();
        
        if ($user) {
            return $user->id;
        }
        
        Log::info('Utilisateur non trouvé pour l\'email', ['email' => $cleanEmail]);
        return null;
    }
}