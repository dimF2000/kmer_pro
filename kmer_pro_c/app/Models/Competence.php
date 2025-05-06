<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competence extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'categorie'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_competences');
    }
} 