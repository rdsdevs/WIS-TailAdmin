<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\Position;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PositionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager', 'rh-viewer']);
    }

    public function view(User $user, Position $position): bool
    {
        return $user->hasRole(['admin', 'rh-manager', 'rh-viewer'])
            && $user->institution_id === $position->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager']);
    }

    public function update(User $user, Position $position): bool
    {
        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $position->institution_id;
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $position->institution_id;
    }

    public function import(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager']);
    }
}
