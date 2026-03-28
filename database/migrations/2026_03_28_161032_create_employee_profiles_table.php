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
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collaborator_id')->constrained()->cascadeOnDelete();

            // Seguridad Social
            $table->string('eps', 100)->nullable();
            $table->string('pension_fund', 100)->nullable();
            $table->string('arl', 100)->nullable();
            $table->string('compensation_fund', 100)->nullable();
            $table->string('severance_fund', 100)->nullable();

            // Datos de Salud
            $table->string('blood_type', 5)->nullable();

            // Contacto de Emergencia
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();

            // Verificación Ley 1918
            $table->date('background_check_verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
