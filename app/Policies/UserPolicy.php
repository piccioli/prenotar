<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:view-any');
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:delete');
    }

    public function impersonate(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:impersonate');
    }

    public function assignRole(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:assign-role');
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('users:reset-password');
    }
}
