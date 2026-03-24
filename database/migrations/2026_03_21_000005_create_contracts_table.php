<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('collaborator_id')->constrained('collaborators')->cascadeOnDelete();
            $table->string('contract_type', 20); // indefinite, fixed_term, contractor, intern
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 14, 2);
            $table->string('position', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('collaborator_id');
            $table->index('is_active');
            $table->index('end_date');
            $table->index(['is_active', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
