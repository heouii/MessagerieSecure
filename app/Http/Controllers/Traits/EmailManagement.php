<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;  // ← MANQUANT
use Carbon\Carbon;  // ← MANQUANT
use App\Models\Email;

trait EmailManagement
{
    /**
     * Récupérer les emails par dossier - FONCTION CORRIGÉE
     */
    public function getEmails(Request $request, $folder = 'inbox'): JsonResponse
    {
        try {
            $query = Email::where('user_id', auth()->id())
                ->where('folder', $folder)
                ->orderBy('created_at', 'desc');

            // Si le dossier n'est pas la corbeille, exclure les supprimés
            if ($folder !== 'trash') {
                $query->whereNull('deleted_at');
            }

            // Recherche optionnelle
            if ($request->has('search') && trim($request->search) !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('from_email', 'like', "%{$search}%");
                });
            }

            $emails = $query->limit(50)->get();

            // Formater pour l'interface
            $formattedEmails = $emails->map(function($email) {
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
            Log::error('Erreur récupération emails', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur récupération emails'], 500);
        }
    }

    /**
     * Autocomplétion des adresses email - NOUVELLE FONCTION
     */
    public function getEmailSuggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $query = $request->query;
            
            // Chercher dans les emails envoyés et reçus
            $suggestions = Email::where('user_id', auth()->id())
                ->where(function($emailQuery) use ($query) {
                    $emailQuery->where('to_email', 'like', "%{$query}%")
                              ->orWhere('from_email', 'like', "%{$query}%")
                              ->orWhere('from_name', 'like', "%{$query}%");
                })
                ->select('to_email', 'from_email', 'from_name', 'to_name')
                ->distinct()
                ->limit(10)
                ->get();

            // Formater les suggestions
            $formattedSuggestions = collect();
            
            foreach ($suggestions as $email) {
                // Ajouter l'email de destination s'il match
                if ($email->to_email && stripos($email->to_email, $query) !== false) {
                    $formattedSuggestions->push([
                        'email' => $email->to_email,
                        'name' => $email->to_name ?: null,
                        'display' => $email->to_name ? "{$email->to_name} <{$email->to_email}>" : $email->to_email,
                        'type' => 'sent_to'
                    ]);
                }
                
                // Ajouter l'email expéditeur s'il match
                if ($email->from_email && stripos($email->from_email, $query) !== false) {
                    $formattedSuggestions->push([
                        'email' => $email->from_email,
                        'name' => $email->from_name ?: null,
                        'display' => $email->from_name ? "{$email->from_name} <{$email->from_email}>" : $email->from_email,
                        'type' => 'received_from'
                    ]);
                }
            }

            // Supprimer les doublons et limiter
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
            Log::error('Erreur autocomplétion', ['error' => $e->getMessage()]);
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
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Erreur marquage lu', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur marquage'], 500);
        }
    }

    public function deleteEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $email = Email::where('user_id', auth()->id())->findOrFail($emailId);
            $email->update([
                'folder' => 'trash',
                'deleted_at' => Carbon::now()  // ← CORRIGÉ
            ]);

            return response()->json(['success' => true, 'message' => 'Email déplacé vers la corbeille']);

        } catch (\Exception $e) {
            Log::error('Erreur suppression email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur suppression'], 500);
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

            if (in_array($domain, $trustedDomains)) {
                $email->update([
                    'folder' => 'inbox',
                    'signature_verified' => true
                ]);

                \App\Models\ApprovedSender::firstOrCreate([
                    'user_id' => auth()->id(),
                    'domain' => $domain
                ]);

            } else {
                if (!$request->has('force') || $request->input('force') !== '1') {
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
            }

            return response()->json([
                'success' => true,
                'message' => 'Email vérifié et déplacé en boîte de réception.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification email', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la vérification.'], 500);
        }
    }

    public function replyToEmail(Request $request, $emailId): JsonResponse
    {
        try {
            $originalEmail = Email::where('user_id', auth()->id())->findOrFail($emailId);
            
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
            Log::error('Erreur préparation réponse', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la préparation de la réponse'], 500);
        }
    }
}