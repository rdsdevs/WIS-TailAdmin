<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Policies\RH\CollaboratorPolicy;
use App\Policies\RH\ContractPolicy;
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
        // Policies del módulo RH
        Gate::policy(Collaborator::class, CollaboratorPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
    }
}
