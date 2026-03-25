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

    /** Roles con acceso completo a gestión de contratos. */
    private const MANAGERS = ['admin', 'rh-manager', 'employee-manager', 'contractor-manager'];

    /** Roles con acceso de solo lectura a contratos. */
    private const VIEWERS = ['admin', 'rh-manager', 'rh-viewer', 'employee-manager', 'contractor-manager'];

    /** Roles restringidos solo a contratos de contratistas. */
    private const CONTRACTOR_ONLY = ['contractor-manager'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEWERS);
    }

    public function view(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(self::VIEWERS)) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function update(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function delete(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function terminate(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function import(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function terminateMassExpired(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function earlyTerminate(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        if ($user->institution_id !== $contract->institution_id) {
            return false;
        }

        // contractor-manager solo puede terminar anticipadamente contratos de contratistas
        if ($user->hasAnyRole(self::CONTRACTOR_ONLY)) {
            $collaborator = $contract->relationLoaded('collaborator')
                ? $contract->collaborator
                : $contract->collaborator()->first();

            return $collaborator !== null && $collaborator->type === 'Contratista';
        }

        return true;
    }

    public function applyProroga(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        if ($user->institution_id !== $contract->institution_id) {
            return false;
        }

        // Contratos históricos (Terminado + año anterior) solo por flujo avanzado
        if (! $contract->canBeProrrogated()) {
            return false;
        }

        if ($user->hasAnyRole(self::CONTRACTOR_ONLY)) {
            $collaborator = $contract->relationLoaded('collaborator')
                ? $contract->collaborator
                : $contract->collaborator()->first();

            return $collaborator !== null && $collaborator->type === 'Contratista';
        }

        return true;
    }

    /**
     * Prórroga avanzada: solo para contratos Terminado + año anterior.
     * Disponible únicamente desde el módulo de opciones avanzadas.
     */
    public function applyProrrogaAdvanced(User $user, Contract $contract): bool
    {
        if (! $user->hasAnyRole(self::MANAGERS)) {
            return false;
        }

        if ($user->institution_id !== $contract->institution_id) {
            return false;
        }

        // Solo aplica para contratos históricos terminados
        if (! ($contract->status === 'Terminado' && $contract->isFromPreviousYear())) {
            return false;
        }

        if ($user->hasAnyRole(self::CONTRACTOR_ONLY)) {
            $collaborator = $contract->relationLoaded('collaborator')
                ? $contract->collaborator
                : $contract->collaborator()->first();

            return $collaborator !== null && $collaborator->type === 'Contratista';
        }

        return true;
    }
}
