<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\Collaborator;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollaboratorPolicy
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
        if ($user->hasRole(['admin', 'rh-manager', 'rh-viewer'])) {
            return true;
        }

        return $user->hasRole(['contractor-manager', 'employee-manager']);
    }

    public function view(User $user, Collaborator $collaborator): bool
    {
        if (! ($user->institution_id === $collaborator->institution_id)) {
            return false;
        }

        if ($user->hasRole(['admin', 'rh-manager', 'rh-viewer'])) {
            return true;
        }

        if ($user->hasRole('contractor-manager') && $collaborator->type === 'Contratista') {
            return true;
        }

        if ($user->hasRole('employee-manager') && $collaborator->type === 'Empleado') {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager', 'contractor-manager', 'employee-manager']);
    }

    public function update(User $user, ?Collaborator $collaborator = null): bool
    {
        if ($collaborator === null) {
            return $user->hasRole(['admin', 'rh-manager', 'contractor-manager', 'employee-manager']);
        }

        if (! ($user->institution_id === $collaborator->institution_id)) {
            return false;
        }

        if ($user->hasRole(['admin', 'rh-manager'])) {
            return true;
        }

        if ($user->hasRole('contractor-manager') && $collaborator->type === 'Contratista') {
            return true;
        }

        if ($user->hasRole('employee-manager') && $collaborator->type === 'Empleado') {
            return true;
        }

        return false;
    }

    public function delete(User $user, ?Collaborator $collaborator = null): bool
    {
        if ($collaborator === null) {
            return $user->hasRole(['admin', 'rh-manager']);
        }

        return $user->hasRole(['admin', 'rh-manager'])
            && $user->institution_id === $collaborator->institution_id;
    }

    public function export(User $user): bool
    {
        return $user->hasRole(['admin', 'rh-manager']);
    }

    public function import(User $user, ?string $type = null): bool
    {
        if ($user->hasAnyRole(['admin', 'rh-manager'])) {
            return true;
        }
        if ($user->hasRole('contractor-manager')) {
            return $type === null || $type === 'Contratista';
        }
        if ($user->hasRole('employee-manager')) {
            return $type === null || $type === 'Empleado';
        }

        return false;
    }

    /**
     * Determina si el usuario puede cambiar el tipo de un colaborador.
     * Los roles especializados solo pueden hacerlo si el colaborador
     * es de su tipo y no tiene contratos vigentes.
     */
    public function changeType(User $user, Collaborator $collaborator): bool
    {
        // Las empresas (is_company) nunca pueden cambiar de tipo
        if ($collaborator->is_company) {
            return false;
        }

        if ($user->institution_id !== $collaborator->institution_id) {
            return false;
        }

        if ($user->hasRole(['admin', 'rh-manager'])) {
            return ! $collaborator->contracts()->where('status', 'Vigente')->exists();
        }

        if ($user->hasRole('contractor-manager') && $collaborator->type === 'Contratista') {
            return ! $collaborator->contracts()->where('status', 'Vigente')->exists();
        }

        if ($user->hasRole('employee-manager') && $collaborator->type === 'Empleado') {
            return ! $collaborator->contracts()->where('status', 'Vigente')->exists();
        }

        return false;
    }
}
