<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sottosezione;
use App\Models\User;

class SottosezionePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('sottosezioni:view-any');
    }

    public function view(User $user, Sottosezione $sottosezione): bool
    {
        return $user->hasPermissionTo('sottosezioni:view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Sottosezione $sottosezione): bool
    {
        return false;
    }

    public function delete(User $user, Sottosezione $sottosezione): bool
    {
        return false;
    }
}
