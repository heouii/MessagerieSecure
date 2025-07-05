<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Email;

trait EmailManagement
{
    /**
     * Récupérer les emails par dossier
     */
    public function getEmails(Request $request, $folder = 'inbox'): JsonResponse
    {
        try {

            $query = Email::where('user_id', auth()->id())
                ->where('folder', $folder)
                ->orderBy('created_at', 'desc');

            if ($folder !== 'trash') {
                $query->whereNull('deleted_at');
            }

            if ($request->has('search') && trim($request->search) !== '') {
                $search = $request->search;
                Log::info('🔍 Filtrage par recherche', ['search' => $search]);

                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('from_email', 'like', "%{$search}%");
                });
            }

            $emails = $query->limit(50)->get();

            $formattedEmails = $emails->map(function ($email) {
                return [
                    'id' => $email->id,
                    'from' => $email->from_email,
                    'from_name' => $email->from_name,
                    'to' => $email->to_email,
                    'subject' => $email->subject,
                    'content' => $email->content,
                    'preview' => $email->preview ?: substr($email->content, 0, 100),
                    'date' => $email->created_at,
                    'read' => $email->is_read,
                    'attachments' => $email->attachments ? json_decode($email->attachments, true) : [],
                    'signature_verified' => $email->signature_verified ?? true,
                ];
            });

            return response()->json([
                'success' => true,
                'emails' => $formattedEmails,
                'folder' => $folder
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération emails', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur récupération emails'], 500);
        }
    }

    /**
     * Autocomplétion des adresses email
     */
    public function getEmailSuggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1'
        ]);

        if ($validator->fails()) {
            Log::warning('⚠️ Validation autocomplétion échouée', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $query = $request->query;
            Log::info('🔍 Recherche suggestions email', ['query' => $query]);

            $suggestions = Email::where('user_id', auth()->id())
                ->where(function ($emailQuery) use ($query) {
                    $emailQuery->where('to_email', 'like', "%{$query}%")
                        ->orWhere('from_email', 'like', "%{$query}%")
                        ->orWhere('from_name', 'like', "%{$query}%");
                })
                ->select('to_email', 'from_email', 'from_name', 'to_name')
                ->distinct()
                ->limit(10)
                ->get();

            Log::info('✅ Suggestions trouvées', ['count' => count($suggestions)]);

            $formattedSuggestions = collect();

            foreach ($suggestions as $email) {
                if ($email->to_email && stripos($email->to_email, $query) !== false) {
                    $formattedSuggestions->push([
                        'email' => $email->to_email,
                        'name' => $email->to_name ?: null,
                        'display' => $email->to_name ? "{$email->to_name} <{$email->to_email}>" : $email->to_email,
                        'type' => 'sent_to'
                    ]);
                }

                if ($email->from_email && stripos($email->from_email, $query) !== false) {
                    $formattedSuggestions->push([
                        'email' => $email->from_email,
                        'name' => $email->from_name ?: null,
                        'display' => $email->from_name ? "{$email->from_name} <{$email->from_email}>" : $email->from_email,
                        'type' => 'received_from'
                    ]);
                }
            }

            $uniqueSuggestions = $formattedSuggestions
                ->unique('email')
                ->take(8)
                ->values();

            return response()->json([
                'success' => true,
                'suggestions' => $uniqueSuggestions,
                'query' => $query
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur autocomplétion', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur autocomplétion'], 500);
        }
    }

    public function markEmailAsRead(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())->findOrFail($emailId);
            $email->update([
                'is_read' => true,
                'read_at' => Carbon::now()
            ]);

            Log::info('✅ Email marqué comme lu', ['email_id' => $emailId]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur marquage lu', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur marquage'], 500);
        }
    }

    public function deleteEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())->findOrFail($emailId);
            $email->update([
                'folder' => 'trash',
                'deleted_at' => Carbon::now()
            ]);

            Log::info('🗑️ Email déplacé dans la corbeille', ['email_id' => $emailId]);

            return response()->json(['success' => true, 'message' => 'Email déplacé vers la corbeille']);

        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur suppression'], 500);
        }
    }

    public function restoreEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())
                ->where('folder', 'trash')
                ->findOrFail($emailId);

            $email->update([
                'folder' => 'inbox',
                'deleted_at' => null
            ]);

            Log::info('♻️ Email restauré', ['email_id' => $emailId]);

            return response()->json(['success' => true, 'message' => 'Email restauré.']);

        } catch (\Exception $e) {
            Log::error('❌ Erreur restauration email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur restauration'], 500);
        }
    }

    public function permanentDeleteEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())
                ->where('folder', 'trash')
                ->findOrFail($emailId);

            $email->delete();

            Log::info('🗑️❌ Email supprimé définitivement', ['email_id' => $emailId]);

            return response()->json(['success' => true, 'message' => 'Email supprimé définitivement.']);

        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression définitive', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur suppression définitive'], 500);
        }
    }

    public function verifyEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())
                ->where('folder', 'unverified')
                ->findOrFail($emailId);

            $domain = substr(strrchr($email->from_email, "@"), 1);
            $trustedDomains = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'protonmail.com'];

            Log::info('🔍 Vérification email', ['email_id' => $emailId, 'domain' => $domain]);

            if (in_array($domain, $trustedDomains)) {
                $email->update([
                    'folder' => 'inbox',
                    'signature_verified' => true
                ]);

                \App\Models\ApprovedSender::firstOrCreate([
                    'user_id' => auth()->id(),
                    'domain' => $domain
                ]);

                Email::where('user_id', auth()->id())
                    ->where('from_email', $email->from_email)
                    ->update(['signature_verified' => true]);

            } else {
                if (!$request->has('force') || $request->input('force') !== '1') {
                    Log::info('⚠️ Domaine inconnu, confirmation requise');
                    return response()->json([
                        'need_confirmation' => true,
                        'message' => "Le domaine «$domain» est inconnu. Confirmation nécessaire."
                    ]);
                }

                $email->update([
                    'folder' => 'inbox',
                    'signature_verified' => true
                ]);

                \App\Models\ApprovedSender::firstOrCreate([
                    'user_id' => auth()->id(),
                    'email' => $email->from_email
                ]);

                Email::where('user_id', auth()->id())
                    ->where('from_email', $email->from_email)
                    ->update(['signature_verified' => true]);
            }

            Log::info('✅ Email vérifié et déplacé', ['email_id' => $emailId]);

            return response()->json([
                'success' => true,
                'message' => 'Email vérifié et déplacé en boîte de réception.'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur vérification'], 500);
        }
    }

    public function replyToEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $originalEmail = Email::where('user_id', auth()->id())->findOrFail($emailId);

            Log::info('✉️ Préparation réponse', ['email_id' => $emailId]);

            $replyData = [
                'to' => $originalEmail->from_email,
                'subject' => 'Re: ' . preg_replace('/^Re: /', '', $originalEmail->subject),
                'original_email' => [
                    'id' => $originalEmail->id,
                    'from' => $originalEmail->from_email,
                    'from_name' => $originalEmail->from_name,
                    'date' => $originalEmail->created_at->format('d/m/Y H:i'),
                    'subject' => $originalEmail->subject,
                    'content' => $originalEmail->content
                ]
            ];

            return response()->json([
                'success' => true,
                'reply_data' => $replyData
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur préparation réponse', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur préparation réponse'], 500);
        }
    }
}
