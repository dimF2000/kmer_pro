<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'titre' => 'required|string|max:255',
            'message' => 'required|string',
            'lien' => 'nullable|string',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'type' => $request->type,
            'titre' => $request->titre,
            'message' => $request->message,
            'lien' => $request->lien,
            'data' => $request->data,
            'lu' => false
        ]);

        return response()->json([
            'message' => 'Notification créée avec succès',
            'notification' => $notification
        ], 201);
    }

    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        
        // Vérifier que l'utilisateur est bien le destinataire
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json($notification);
    }

    public function marquerCommeLu($id)
    {
        $notification = Notification::findOrFail($id);
        
        // Vérifier que l'utilisateur est bien le destinataire
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $notification->update(['lu' => true]);

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'notification' => $notification
        ]);
    }

    public function marquerToutesCommeLues()
    {
        Notification::where('user_id', auth()->id())
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        
        // Vérifier que l'utilisateur est bien le destinataire
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification supprimée avec succès'
        ]);
    }

    public function destroyAll()
    {
        Notification::where('user_id', auth()->id())->delete();
        
        return response()->json([
            'message' => 'Toutes les notifications ont été supprimées'
        ]);
    }

    public function nonLues()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('lu', false)
            ->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function getStatistiques()
    {
        $total = Notification::where('user_id', Auth::id())->count();
        $nonLues = Notification::where('user_id', Auth::id())->nonLues()->count();
        $parType = Notification::where('user_id', Auth::id())
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->get();

        return response()->json([
            'total' => $total,
            'non_lues' => $nonLues,
            'par_type' => $parType
        ]);
    }
} 