<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('collaborator_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('certificate_signature_id')
                ->constrained('certificate_signatures')
                ->restrictOnDelete();
            $table->foreignUuid('issued_by')->constrained('users')->restrictOnDelete();
            $table->char('verification_code', 36)->unique();
            $table->enum('certificate_type', ['empleado', 'contratista']);
            $table->string('addressed_to', 255)->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->json('collaborator_snapshot');
            $table->json('contracts_snapshot');
            $table->json('options_snapshot');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['institution_id', 'issued_at']);
            $table->index('collaborator_id');
            $table->index('certificate_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
