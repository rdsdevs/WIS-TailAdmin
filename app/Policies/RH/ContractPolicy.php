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
        return $user->hasRole(['admin', 'rh-manager', 'rh-viewer']);
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->hasRole(['admin', 'rh-manager', 'rh-viewer'])
            && $user->institution_id === $contract->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager']);
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $contract->institution_id;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $contract->institution_id;
    }

    public function terminate(User $user, Contract $contract): bool
    {
        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $contract->institution_id;
    }
}
