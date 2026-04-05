<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Certificados\Certificate;
use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\RH\Position;
use App\Models\User;
use App\Observers\RH\ContractObserver;
use App\Policies\Certificados\CertificatePolicy;
use App\Policies\Certificados\CertificateSignaturePolicy;
use App\Policies\RH\CollaboratorPolicy;
use App\Policies\RH\ContractPolicy;
use App\Policies\RH\PositionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registro de servicios del contenedor.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap de servicios — se ejecuta después de que el framework arranca.
     */
    public function boot(): void
    {
        Contract::observe(ContractObserver::class);

        // Policies del módulo RH
        Gate::policy(Collaborator::class, CollaboratorPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);

        // Policies del módulo de Certificados
        Gate::policy(Certificate::class, CertificatePolicy::class);
        Gate::policy(CertificateSignature::class, CertificateSignaturePolicy::class);

        // Policy de administración de usuarios
        Gate::policy(User::class, UserPolicy::class);
    }
}
