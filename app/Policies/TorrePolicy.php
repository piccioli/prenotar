<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Torre;
use App\Models\User;

class TorrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('torri:view-any');
    }

    public function view(User $user, Torre $torre): bool
    {
        return $user->hasPermissionTo('torri:view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('torri:create');
    }

    public function update(User $user, Torre $torre): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('torri:update');
    }

    public function delete(User $user, Torre $torre): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('torri:delete');
    }
}
