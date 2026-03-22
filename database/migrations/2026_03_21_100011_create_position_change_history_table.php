<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_change_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignUuid('previous_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignUuid('new_position_id')->constrained('positions')->restrictOnDelete();
            $table->decimal('new_salary', 12, 2);
            $table->string('new_position_email', 100)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_change_history');
    }
};
