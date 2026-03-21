<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('position_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('department_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 10);
            $table->string('document_number', 30);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('salary', 14, 2);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Cédula única por institución
            $table->unique(['institution_id', 'document_number']);

            $table->index('institution_id');
            $table->index('position_id');
            $table->index('department_id');
            $table->index(['document_type', 'document_number']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
