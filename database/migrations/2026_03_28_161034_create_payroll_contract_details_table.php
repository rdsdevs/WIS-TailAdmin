<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_contract_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('contract_id')->constrained()->cascadeOnDelete();

            // Desglose Salarial
            $table->decimal('base_salary', 15, 2)->nullable();
            $table->decimal('transport_allowance', 15, 2)->nullable();
            $table->decimal('non_statutory_bonuses', 15, 2)->nullable();

            // Parafiscales (Valores por defecto)
            $table->decimal('sena_rate', 5, 2)->default(2.00);
            $table->decimal('icbf_rate', 5, 2)->default(3.00);
            $table->decimal('compensation_fund_rate', 5, 2)->default(4.00);

            // Salud Ocupacional
            $table->date('health_check_verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_contract_details');
    }
};
