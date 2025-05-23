<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'numero',
        'chemin',
        'statut',
        'commentaire'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 