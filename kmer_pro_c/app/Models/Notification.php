<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'lien',
        'data',
        'lu',
        'date_lecture'
    ];

    protected $casts = [
        'lu' => 'boolean',
        'date_lecture' => 'datetime',
        'data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    public function marquerCommeLu()
    {
        $this->lu = true;
        $this->date_lecture = now();
        $this->save();
        
        return $this;
    }

    public static function creer($userId, $type, $titre, $message, $data = [])
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'data' => $data,
            'lu' => false
        ]);
    }

    public static function notifierNouvelleDemande(Demande $demande)
    {
        return self::creer(
            $demande->professionnel_id,
            'nouvelle_demande',
            'Nouvelle demande de service',
            "Vous avez reçu une nouvelle demande de service de {$demande->client->nom}",
            [
                'demande_id' => $demande->id,
                'service_id' => $demande->service_id,
                'client_id' => $demande->client_id
            ]
        );
    }

    public static function notifierPaiementRecu(Paiement $paiement)
    {
        return self::creer(
            $paiement->professionnel_id,
            'paiement_recu',
            'Paiement reçu',
            "Vous avez reçu un paiement de {$paiement->montant} {$paiement->devise}",
            [
                'paiement_id' => $paiement->id,
                'demande_id' => $paiement->demande_id,
                'montant' => $paiement->montant,
                'devise' => $paiement->devise
            ]
        );
    }

    public static function notifierPaiementConfirme(Paiement $paiement)
    {
        return self::creer(
            $paiement->client_id,
            'paiement_confirme',
            'Paiement confirmé',
            "Votre paiement a été confirmé",
            [
                'paiement_id' => $paiement->id,
                'demande_id' => $paiement->demande_id,
                'montant' => $paiement->montant,
                'devise' => $paiement->devise
            ]
        );
    }

    public static function notifierNouveauMessage(Message $message)
    {
        return self::creer(
            $message->destinataire_id,
            'nouveau_message',
            'Nouveau message',
            "Vous avez reçu un nouveau message",
            [
                'message_id' => $message->id,
                'expediteur_id' => $message->expediteur_id
            ]
        );
    }

    public static function notifierDemandeAcceptee(Demande $demande)
    {
        return self::creer(
            $demande->client_id,
            'demande_acceptee',
            'Demande acceptée',
            "Votre demande a été acceptée",
            [
                'demande_id' => $demande->id,
                'service_id' => $demande->service_id,
                'professionnel_id' => $demande->professionnel_id
            ]
        );
    }

    public static function notifierDemandeRejetee(Demande $demande)
    {
        return self::creer(
            $demande->client_id,
            'demande_rejetee',
            'Demande rejetée',
            "Votre demande a été rejetée",
            [
                'demande_id' => $demande->id,
                'service_id' => $demande->service_id,
                'professionnel_id' => $demande->professionnel_id
            ]
        );
    }
} 