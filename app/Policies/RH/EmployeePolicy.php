<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * super-admin tiene acceso irrestricto a todo.
     */
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

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasRole(['admin', 'rh-manager', 'rh-viewer'])
            && $user->institution_id === $employee->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager']);
    }

    public function update(User $user, ?Employee $employee = null): bool
    {
        if ($employee === null) {
            return $user->hasRole(['admin', 'rh-manager']);
        }

        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $employee->institution_id;
    }

    public function delete(User $user, ?Employee $employee = null): bool
    {
        if ($employee === null) {
            return $user->hasRole(['admin', 'rh-manager']);
        }

        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $employee->institution_id;
    }

    public function export(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager']);
    }
}
