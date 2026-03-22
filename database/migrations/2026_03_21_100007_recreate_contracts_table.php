<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('contracts');

        Schema::create('contracts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('collaborator_id')->constrained('collaborators')->restrictOnDelete();
            $table->foreignUuid('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->foreignUuid('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('contract_number', 10)->nullable();
            $table->string('contract_code', 20)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('object')->nullable();
            $table->text('obligations')->nullable();
            $table->decimal('salary', 12, 2)->default(0);
            $table->decimal('fees', 12, 2)->default(0);
            $table->string('position_email', 100)->nullable();
            $table->enum('status', ['Vigente', 'Liquidado', 'Terminado', 'Cambio de cargo'])->default('Vigente');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'collaborator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
