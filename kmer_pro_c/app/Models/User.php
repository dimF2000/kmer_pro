<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'type',
        'adresse',
        'ville',
        'pays',
        'description',
        'competences',
        'experience',
        'diplomes',
        'photo',
        'statut'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'competences' => 'array',
        'diplomes' => 'array'
    ];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }

    public function demandesProfessionnel()
    {
        return $this->hasMany(Demande::class, 'professionnel_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'expediteur_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favoris()
    {
        return $this->hasMany(Favori::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function competences()
    {
        return $this->belongsToMany(Competence::class, 'user_competences');
    }

    public function isProfessionnel()
    {
        return $this->type === 'professionnel';
    }

    public function isClient()
    {
        return $this->type === 'client';
    }
}
