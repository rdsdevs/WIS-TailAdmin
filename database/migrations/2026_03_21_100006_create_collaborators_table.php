<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaborators', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->string('document_number', 20);
            $table->date('document_issued_at')->nullable();
            $table->string('first_name', 60);
            $table->string('second_name', 60)->nullable();
            $table->string('first_surname', 60);
            $table->string('second_surname', 60)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['F', 'M', 'O'])->nullable();
            $table->boolean('is_company')->default(false);
            $table->string('company_name', 100)->nullable();
            $table->string('legal_representative', 60)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->enum('type', ['Empleado', 'Contratista'])->default('Empleado');
            $table->foreignUuid('status_id')->constrained('collaborator_statuses')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['institution_id', 'document_number']);
            $table->index(['institution_id', 'type']);
            $table->index(['institution_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaborators');
    }
};
