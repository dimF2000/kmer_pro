<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'ville',
        'region',
        'pays'
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
} 