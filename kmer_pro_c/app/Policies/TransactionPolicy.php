<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Transaction $transaction)
    {
        return $user->id === $transaction->demande->user_id || 
               $user->id === $transaction->demande->professionnel_id;
    }

    public function validate(User $user, Transaction $transaction)
    {
        return $user->id === $transaction->demande->professionnel_id;
    }
} 