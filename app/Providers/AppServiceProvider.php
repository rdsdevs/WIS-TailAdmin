<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\RH\Contract;
use App\Models\RH\Contractor;
use App\Models\RH\Employee;
use App\Policies\RH\ContractorPolicy;
use App\Policies\RH\ContractPolicy;
use App\Policies\RH\EmployeePolicy;
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
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Contractor::class, ContractorPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
    }
}
