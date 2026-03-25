<?php

declare(strict_types=1);

namespace App\Policies\Certificados;

use App\Models\Certificados\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificatePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /** Roles que pueden ver el historial de certificados. */
    private const VIEWERS = ['admin', 'rh-manager', 'rh-viewer', 'employee-manager', 'contractor-manager'];

    /** Roles que pueden generar certificados de empleados. */
    private const EMPLOYEE_GENERATORS = ['admin', 'rh-manager', 'employee-manager'];

    /** Roles que pueden generar certificados de contratistas. */
    private const CONTRACTOR_GENERATORS = ['admin', 'rh-manager', 'contractor-manager'];

    /** Roles que pueden eliminar certificados del historial. */
    private const DELETERS = ['admin', 'rh-manager'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEWERS);
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if (! $user->hasAnyRole(self::VIEWERS)) {
            return false;
        }

        return $user->institution_id === $certificate->institution_id;
    }

    public function generateEmployee(User $user): bool
    {
        return $user->hasAnyRole(self::EMPLOYEE_GENERATORS);
    }

    public function generateContractor(User $user): bool
    {
        return $user->hasAnyRole(self::CONTRACTOR_GENERATORS);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        if (! $user->hasAnyRole(self::DELETERS)) {
            return false;
        }

        return $user->institution_id === $certificate->institution_id;
    }
}
