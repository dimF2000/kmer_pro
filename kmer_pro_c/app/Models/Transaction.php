<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'demande_id',
        'montant',
        'reference',
        'statut',
        'methode_paiement',
        'details_paiement'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'details_paiement' => 'array'
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }
} 