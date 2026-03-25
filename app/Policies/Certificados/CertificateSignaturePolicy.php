<?php

declare(strict_types=1);

namespace App\Policies\Certificados;

use App\Models\RH\CertificateSignature;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificateSignaturePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    private const MANAGERS = ['admin', 'rh-manager'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function view(User $user, CertificateSignature $signature): bool
    {
        return $user->hasAnyRole(self::MANAGERS)
            && $user->institution_id === $signature->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function update(User $user, CertificateSignature $signature): bool
    {
        return $user->hasAnyRole(self::MANAGERS)
            && $user->institution_id === $signature->institution_id;
    }

    public function delete(User $user, CertificateSignature $signature): bool
    {
        return $user->hasAnyRole(self::MANAGERS)
            && $user->institution_id === $signature->institution_id;
    }
}
