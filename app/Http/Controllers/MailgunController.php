<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Traits\EmailSending;
use App\Http\Controllers\Traits\EmailReceiving;
use App\Http\Controllers\Traits\EmailManagement;
use App\Http\Controllers\Traits\FileUtilities;
use Illuminate\Support\Facades\Log; 
use App\Models\User;

class MailgunController extends Controller
{
    use EmailSending, EmailReceiving, EmailManagement, FileUtilities;
    

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
        return view('messaging');
    }

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
        
        $user = User::where('email', $cleanEmail)->first();
        
        if ($user) {
            return $user->id;
        }
        
        Log::info('Utilisateur non trouvé pour l\'email', ['email' => $cleanEmail]);
        return null;
    }

    public function storeDraft(Request $request): JsonResponse
{
    try {
        $validator = Validator::make($request->all(), [
            'to' => 'nullable|email',
            'cc' => 'nullable|email', 
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        $draft = Email::create([
            'user_id' => auth()->id(),
            'folder' => 'drafts',
            'from_email' => auth()->user()->email,
            'from_name' => auth()->user()->prenom . ' ' . auth()->user()->nom,
            'to_email' => $request->to,
            'cc_email' => $request->cc,
            'subject' => $request->subject ?: 'Brouillon sans objet',
            'content' => $request->content ?: '',
            'preview' => substr($request->content ?: '', 0, 100),
            'is_html' => false,
            'is_read' => true,
            'signature_verified' => true,
            'attachments' => json_encode([]),
        ]);

        Log::info('✅ Brouillon sauvegardé', [
            'draft_id' => $draft->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brouillon sauvegardé avec succès',
            'draft_id' => $draft->id
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Erreur sauvegarde brouillon', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Erreur lors de la sauvegarde du brouillon'
        ], 500);
    }
}
}