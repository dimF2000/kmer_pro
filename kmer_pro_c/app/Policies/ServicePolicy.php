<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user)
    {
        return true;
    }

    public function view(?User $user, Service $service)
    {
        return true;
    }

    public function create(User $user)
    {
        return $user->isProfessionnel();
    }

    public function update(User $user, Service $service)
    {
        return $user->id === $service->user_id;
    }

    public function delete(User $user, Service $service)
    {
        return $user->id === $service->user_id;
    }

    public function restore(User $user, Service $service)
    {
        return $user->id === $service->user_id;
    }

    public function forceDelete(User $user, Service $service)
    {
        return $user->id === $service->user_id;
    }
} 