<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 10);
            $table->string('document_number', 30);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('company_name', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Documento único por institución
            $table->unique(['institution_id', 'document_number']);

            $table->index('institution_id');
            $table->index(['document_type', 'document_number']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
