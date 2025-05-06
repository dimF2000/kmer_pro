<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
        'icone',
        'description',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function services()
    {
        return $this->hasMany(Service::class);
    }
} 