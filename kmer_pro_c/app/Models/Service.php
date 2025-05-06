<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'categorie_id',
        'zone_id',
        'titre',
        'description',
        'prix',
        'disponible',
        'disponibilite',
        'zones_couvertes',
        'note_moyenne',
        'nombre_avis'
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'disponibilite' => 'boolean',
        'zones_couvertes' => 'array',
        'note_moyenne' => 'decimal:1',
        'prix' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function zones()
    {
        return $this->belongsToMany(Zone::class);
    }

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function favoris()
    {
        return $this->hasMany(Favori::class);
    }

    public function competences()
    {
        return $this->belongsToMany(Competence::class, 'service_competences');
    }

    public function scopeDisponible($query)
    {
        return $query->where('disponible', true)
                    ->where('disponibilite', true);
    }

    public function scopeByCategorie($query, $categorie)
    {
        return $query->where('categorie_id', $categorie);
    }

    public function scopeByZone($query, $zone)
    {
        return $query->whereJsonContains('zones_couvertes', $zone);
    }

    public function scopeByPrix($query, $min, $max)
    {
        return $query->whereBetween('prix', [$min, $max]);
    }

    public function toggleDisponibilite()
    {
        $this->disponible = !$this->disponible;
        $this->disponibilite = !$this->disponibilite;
        $this->save();
        return $this;
    }
} 