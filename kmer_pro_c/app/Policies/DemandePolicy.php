<?php

namespace App\Policies;

use App\Models\Demande;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DemandePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Demande $demande)
    {
        return $user->id === $demande->user_id || $user->id === $demande->professionnel_id;
    }

    public function update(User $user, Demande $demande)
    {
        return $user->id === $demande->professionnel_id;
    }

    public function cancel(User $user, Demande $demande)
    {
        return $user->id === $demande->user_id;
    }
} 