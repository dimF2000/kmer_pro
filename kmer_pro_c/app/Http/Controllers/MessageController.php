<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Demande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $messages = Message::where('expediteur_id', Auth::id())
            ->orWhere('destinataire_id', Auth::id())
            ->with(['expediteur', 'destinataire', 'demande'])
            ->latest()
            ->paginate(20);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'destinataire_id' => 'required|exists:users,id',
            'contenu' => 'required|string|max:1000',
            'demande_id' => 'nullable|exists:demandes,id',
            'pieces_jointes' => 'nullable|array',
            'pieces_jointes.*' => 'file|max:5120' // 5MB max par fichier
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $pieces_jointes = [];
            if ($request->hasFile('pieces_jointes')) {
                foreach ($request->file('pieces_jointes') as $file) {
                    $path = $file->store('messages/pieces_jointes');
                    $pieces_jointes[] = [
                        'nom' => $file->getClientOriginalName(),
                        'chemin' => $path,
                        'type' => $file->getMimeType(),
                        'taille' => $file->getSize()
                    ];
                }
            }

            $message = Message::create([
                'expediteur_id' => Auth::id(),
                'destinataire_id' => $request->destinataire_id,
                'demande_id' => $request->demande_id,
                'contenu' => $request->contenu,
                'pieces_jointes' => $pieces_jointes
            ]);

            // TODO: Envoyer une notification au destinataire

            return response()->json([
                'message' => 'Message envoyé avec succès',
                'data' => $message->load(['expediteur', 'destinataire', 'demande'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'envoi du message'], 500);
        }
    }

    public function show(Message $message)
    {
        // Vérifier que l'utilisateur est concerné par le message
        if (!in_array(Auth::id(), [$message->expediteur_id, $message->destinataire_id])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Marquer le message comme lu si l'utilisateur est le destinataire
        if (Auth::id() === $message->destinataire_id) {
            $message->marquerCommeLu();
        }

        return response()->json($message->load(['expediteur', 'destinataire', 'demande']));
    }

    public function getConversations()
    {
        $conversations = Message::where('expediteur_id', Auth::id())
            ->orWhere('destinataire_id', Auth::id())
            ->with(['expediteur', 'destinataire'])
            ->get()
            ->groupBy(function ($message) {
                return $message->expediteur_id === Auth::id() 
                    ? $message->destinataire_id 
                    : $message->expediteur_id;
            })
            ->map(function ($messages) {
                $lastMessage = $messages->sortByDesc('created_at')->first();
                $unreadCount = $messages->where('destinataire_id', Auth::id())
                    ->where('lu', false)
                    ->count();
                
                return [
                    'user' => $lastMessage->expediteur_id === Auth::id() 
                        ? $lastMessage->destinataire 
                        : $lastMessage->expediteur,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount
                ];
            });

        return response()->json($conversations);
    }

    public function getConversationWith(User $user)
    {
        $messages = Message::conversation(Auth::id(), $user->id)
            ->with(['expediteur', 'destinataire', 'demande'])
            ->latest()
            ->paginate(20);

        // Marquer tous les messages non lus comme lus
        Message::where('expediteur_id', $user->id)
            ->where('destinataire_id', Auth::id())
            ->where('lu', false)
            ->update([
                'lu' => true,
                'date_lecture' => now()
            ]);

        return response()->json($messages);
    }

    public function destroy(Message $message)
    {
        // Vérifier que l'utilisateur est l'expéditeur
        if ($message->expediteur_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Supprimer les pièces jointes
        if ($message->pieces_jointes) {
            foreach ($message->pieces_jointes as $piece) {
                Storage::delete($piece['chemin']);
            }
        }

        $message->delete();

        return response()->json(['message' => 'Message supprimé avec succès']);
    }

    public function marquerCommeLu(Message $message)
    {
        if ($message->destinataire_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $message->marquerCommeLu();

        return response()->json(['message' => 'Message marqué comme lu']);
    }

    public function marquerTousCommeLus(User $user)
    {
        Message::where('expediteur_id', $user->id)
            ->where('destinataire_id', Auth::id())
            ->where('lu', false)
            ->update([
                'lu' => true,
                'date_lecture' => now()
            ]);

        return response()->json(['message' => 'Tous les messages ont été marqués comme lus']);
    }

    public function marquerToutLu(Request $request)
    {
        $userId = auth()->id();
        
        Message::where('destinataire_id', $userId)
            ->where('lu', false)
            ->update(['lu' => true]);
            
        return response()->json([
            'message' => 'Tous les messages ont été marqués comme lus'
        ]);
    }
} 