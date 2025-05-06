<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'client_id',
        'professionnel_id',
        'statut',
        'description',
        'date_souhaitee',
        'adresse',
        'montant',
        'date_acceptation',
        'date_fin',
        'note',
        'commentaire'
    ];

    protected $casts = [
        'date_souhaitee' => 'datetime',
        'date_acceptation' => 'datetime',
        'date_fin' => 'datetime',
        'montant' => 'decimal:2',
        'note' => 'integer'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professionnel()
    {
        return $this->belongsTo(User::class, 'professionnel_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeAcceptee($query)
    {
        return $query->where('statut', 'acceptee');
    }

    public function scopeRefusee($query)
    {
        return $query->where('statut', 'refusee');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTerminee($query)
    {
        return $query->where('statut', 'terminee');
    }

    public function scopeAnnulee($query)
    {
        return $query->where('statut', 'annulee');
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