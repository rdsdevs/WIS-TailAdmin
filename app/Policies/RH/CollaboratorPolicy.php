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

    /** Roles con acceso completo a gestión RH (crear, editar, eliminar). */
    private const MANAGERS = ['admin', 'rh-manager', 'employee-manager', 'contractor-manager'];

    /** Roles con acceso de solo lectura a RH. */
    private const VIEWERS = ['admin', 'rh-manager', 'rh-viewer', 'employee-manager', 'contractor-manager'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEWERS);
    }

    public function view(User $user, Collaborator $collaborator): bool
    {
        if ($user->institution_id !== $collaborator->institution_id) {
            return false;
        }

        if (! $user->hasAnyRole(self::VIEWERS)) {
            return false;
        }

        // contractor-manager solo puede ver contratistas
        if ($user->hasRole('contractor-manager') && $collaborator->type !== 'Contratista') {
            return false;
        }

        // employee-manager solo puede ver empleados
        if ($user->hasRole('employee-manager') && $collaborator->type !== 'Empleado') {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function update(User $user, ?Collaborator $collaborator = null): bool
    {
        if ($collaborator === null) {
            return $user->hasAnyRole(self::MANAGERS);
        }

        if ($user->institution_id !== $collaborator->institution_id) {
            return false;
        }

        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        // contractor-manager solo puede editar contratistas
        if ($user->hasRole('contractor-manager') && $collaborator->type !== 'Contratista') {
            return false;
        }

        // employee-manager solo puede editar empleados
        if ($user->hasRole('employee-manager') && $collaborator->type !== 'Empleado') {
            return false;
        }

        return true;
    }

    public function delete(User $user, ?Collaborator $collaborator = null): bool
    {
        if ($collaborator === null) {
            return $user->hasAnyRole(self::MANAGERS);
        }

        if ($user->institution_id !== $collaborator->institution_id) {
            return false;
        }

        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        // contractor-manager solo puede eliminar contratistas
        if ($user->hasRole('contractor-manager') && $collaborator->type !== 'Contratista') {
            return false;
        }

        // employee-manager solo puede eliminar empleados
        if ($user->hasRole('employee-manager') && $collaborator->type !== 'Empleado') {
            return false;
        }

        return true;
    }

    public function export(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function import(User $user, ?string $type = null): bool
    {
        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        // contractor-manager solo puede importar contratistas
        if ($user->hasRole('contractor-manager') && $type === 'Empleado') {
            return false;
        }

        // employee-manager solo puede importar empleados
        if ($user->hasRole('employee-manager') && $type === 'Contratista') {
            return false;
        }

        return true;
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

        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        // contractor-manager solo puede cambiar tipo de contratistas
        if ($user->hasRole('contractor-manager') && $collaborator->type !== 'Contratista') {
            return false;
        }

        // employee-manager solo puede cambiar tipo de empleados
        if ($user->hasRole('employee-manager') && $collaborator->type !== 'Empleado') {
            return false;
        }

        return ! $collaborator->contracts()->where('status', 'Vigente')->exists();
    }
}
