<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paiement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'demande_id',
        'client_id',
        'professionnel_id',
        'montant',
        'devise',
        'statut',
        'methode',
        'reference',
        'date_confirmation',
        'date_annulation',
        'commentaire',
        'details'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_confirmation' => 'datetime',
        'date_annulation' => 'datetime',
        'details' => 'array'
    ];

    // Relations
    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professionnel()
    {
        return $this->belongsTo(User::class, 'professionnel_id');
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeConfirme($query)
    {
        return $query->where('statut', 'confirme');
    }

    public function scopeAnnule($query)
    {
        return $query->where('statut', 'annule');
    }

    public function scopeComplete($query)
    {
        return $query->where('statut', 'complete');
    }

    // Méthodes
    public function estComplete()
    {
        return $this->statut === 'complete';
    }

    public function peutEtreAnnule()
    {
        return in_array($this->statut, ['en_attente', 'en_cours']);
    }

    public function peutEtreRembourse()
    {
        return $this->estComplete() && $this->date_confirmation->diffInDays(now()) <= 7;
    }

    public function genererReference()
    {
        return 'PAY-' . strtoupper(uniqid());
    }

    public function isClient(User $user)
    {
        return $this->client_id === $user->id;
    }

    public function isProfessionnel(User $user)
    {
        return $this->professionnel_id === $user->id;
    }
} 