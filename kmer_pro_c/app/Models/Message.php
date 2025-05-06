<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expediteur_id',
        'destinataire_id',
        'demande_id',
        'contenu',
        'lu',
        'date_lecture',
        'pieces_jointes'
    ];

    protected $casts = [
        'lu' => 'boolean',
        'date_lecture' => 'datetime',
        'pieces_jointes' => 'array'
    ];

    public function expediteur()
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }

    public function scopeNonLus($query)
    {
        return $query->where('lu', false);
    }

    public function scopeConversation($query, $user1Id, $user2Id)
    {
        return $query->where(function($q) use ($user1Id, $user2Id) {
            $q->where('expediteur_id', $user1Id)
              ->where('destinataire_id', $user2Id);
        })->orWhere(function($q) use ($user1Id, $user2Id) {
            $q->where('expediteur_id', $user2Id)
              ->where('destinataire_id', $user1Id);
        });
    }

    public function marquerCommeLu()
    {
        if (!$this->lu) {
            $this->update([
                'lu' => true,
                'date_lecture' => now()
            ]);
        }
    }

    public function ajouterPieceJointe($piece)
    {
        $pieces = $this->pieces_jointes ?? [];
        $pieces[] = $piece;
        $this->update(['pieces_jointes' => $pieces]);
    }

    public function supprimerPieceJointe($index)
    {
        $pieces = $this->pieces_jointes ?? [];
        if (isset($pieces[$index])) {
            unset($pieces[$index]);
            $this->update(['pieces_jointes' => array_values($pieces)]);
        }
    }
} 