<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sezione;
use App\Models\User;

class SezionePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('sezioni:view-any');
    }

    public function view(User $user, Sezione $sezione): bool
    {
        return $user->hasPermissionTo('sezioni:view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Sezione $sezione): bool
    {
        return false;
    }

    public function delete(User $user, Sezione $sezione): bool
    {
        return false;
    }

    public function syncExcel(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('sezioni:sync-excel');
    }
}
