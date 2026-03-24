<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\Contract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
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
        return $user->hasAnyRole(['admin', 'rh-manager', 'rh-viewer']);
    }

    public function view(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(['admin', 'rh-manager', 'rh-viewer'])) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'rh-manager']);
    }

    public function update(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(['admin', 'rh-manager'])) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function delete(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(['admin', 'rh-manager'])) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function terminate(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(['admin', 'rh-manager'])) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function import(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'rh-manager']);
    }

    public function terminateMassExpired(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'rh-manager']);
    }

    public function earlyTerminate(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(['super-admin', 'admin', 'rh-manager'])) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function applyProroga(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(['admin', 'rh-manager'])) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }
}
