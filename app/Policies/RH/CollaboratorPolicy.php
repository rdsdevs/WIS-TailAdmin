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
        return $user->hasAnyRole(['admin', 'rh-manager', 'rh-viewer']);
    }

    public function view(User $user, Collaborator $collaborator): bool
    {
        if (! ($user->institution_id === $collaborator->institution_id)) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'rh-manager', 'rh-viewer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'rh-manager']);
    }

    public function update(User $user, ?Collaborator $collaborator = null): bool
    {
        if ($collaborator === null) {
            return $user->hasAnyRole(['admin', 'rh-manager']);
        }

        if (! ($user->institution_id === $collaborator->institution_id)) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'rh-manager']);
    }

    public function delete(User $user, ?Collaborator $collaborator = null): bool
    {
        if ($collaborator === null) {
            return $user->hasAnyRole(['admin', 'rh-manager']);
        }

        return $user->hasAnyRole(['admin', 'rh-manager'])
            && $user->institution_id === $collaborator->institution_id;
    }

    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'rh-manager']);
    }

    public function import(User $user, ?string $type = null): bool
    {
        return $user->hasAnyRole(['admin', 'rh-manager']);
    }

    /**
     * Determina si el usuario puede cambiar el tipo de un colaborador.
     * Solo posible si no tiene contratos vigentes.
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

        if ($user->hasAnyRole(['admin', 'rh-manager'])) {
            return ! $collaborator->contracts()->where('status', 'Vigente')->exists();
        }

        return false;
    }
}
