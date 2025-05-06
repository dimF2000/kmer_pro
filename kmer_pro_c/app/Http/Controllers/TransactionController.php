<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Demande;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['demande']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $transactions = $query->latest()->paginate(10);

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'demande_id' => 'required|exists:demandes,id',
            'methode_paiement' => 'required|in:mobile_money,credit_card,bank_transfer'
        ]);

        $demande = Demande::findOrFail($request->demande_id);

        // Vérifier si la demande appartient à l'utilisateur
        if ($demande->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette transaction'
            ], 403);
        }

        // Vérifier si la demande est acceptée
        if ($demande->statut !== 'acceptee') {
            return response()->json([
                'message' => 'Cette demande n\'est pas encore acceptée'
            ], 400);
        }

        // Vérifier si une transaction existe déjà
        if ($demande->transaction) {
            return response()->json([
                'message' => 'Une transaction existe déjà pour cette demande'
            ], 400);
        }

        $transaction = Transaction::create([
            'demande_id' => $demande->id,
            'montant' => $demande->service->prix,
            'reference' => 'TRX-' . Str::random(10),
            'statut' => 'en_attente',
            'methode_paiement' => $request->methode_paiement,
            'details_paiement' => []
        ]);

        // Ici, vous pouvez intégrer votre logique de paiement
        // Par exemple, rediriger vers une page de paiement ou initialiser un paiement mobile

        return response()->json([
            'message' => 'Transaction créée avec succès',
            'transaction' => $transaction
        ], 201);
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);
        
        $transaction->load(['demande']);
        return response()->json($transaction);
    }

    public function validateTransaction(Transaction $transaction)
    {
        $this->authorize('validate', $transaction);

        if ($transaction->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette transaction ne peut plus être validée'
            ], 400);
        }

        // Ici, vous pouvez ajouter votre logique de validation de paiement
        // Par exemple, vérifier avec l'API de paiement

        $transaction->update([
            'statut' => 'completee',
            'details_paiement' => [
                'validated_at' => now(),
                'payment_reference' => 'PAY-' . Str::random(10)
            ]
        ]);

        // Mettre à jour le statut de la demande
        $transaction->demande->update(['statut' => 'en_cours']);

        // Créer des notifications
        $transaction->demande->user->notifications()->create([
            'type' => 'paiement_valide',
            'message' => 'Votre paiement a été validé',
            'lien' => '/demandes/' . $transaction->demande_id
        ]);

        $transaction->demande->professionnel->notifications()->create([
            'type' => 'paiement_valide',
            'message' => 'Le paiement a été validé pour une de vos demandes',
            'lien' => '/demandes/' . $transaction->demande_id
        ]);

        return response()->json([
            'message' => 'Transaction validée avec succès',
            'transaction' => $transaction
        ]);
    }
} 