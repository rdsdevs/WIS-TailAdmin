<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommittedValuePolicy
{
    use HandlesAuthorization;

    private const VIEWER_ROLES = ['admin', 'rh-manager', 'rh-viewer', 'employee-manager', 'contractor-manager'];

    private const MANAGER_ROLES = ['admin', 'rh-manager', 'employee-manager', 'contractor-manager'];

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, ?Contract $contract = null): bool
    {
        if (! $user->hasRole(self::VIEWER_ROLES)) {
            return false;
        }

        if ($contract === null) {
            return true;
        }

        return $user->institution_id === $contract->institution_id;
    }

    public function view(User $user, CommittedValue $committedValue): bool
    {
        return $user->hasRole(self::VIEWER_ROLES)
            && $user->institution_id === $committedValue->institution_id;
    }

    public function create(User $user, Contract $contract): bool
    {
        return $user->hasRole(self::MANAGER_ROLES)
            && $user->institution_id === $contract->institution_id
            && $this->contractAllowsManagement($contract);
    }

    public function update(User $user, CommittedValue $committedValue): bool
    {
        if (! $user->hasRole(self::MANAGER_ROLES)) {
            return false;
        }

        if ($user->institution_id !== $committedValue->institution_id) {
            return false;
        }

        $contract = $committedValue->contract;

        return $contract !== null && $this->contractAllowsManagement($contract);
    }

    public function delete(User $user, CommittedValue $committedValue): bool
    {
        return $this->update($user, $committedValue);
    }

    /**
     * Solo se permite gestionar valores de contratos del año actual o futuros,
     * replicando la regla histórica usada por ContractProrogaService. Esto
     * preserva la integridad contable de cierres de años anteriores.
     */
    private function contractAllowsManagement(Contract $contract): bool
    {
        if ($contract->start_date === null) {
            return true;
        }

        return $contract->start_date->year >= now()->year;
    }
}
