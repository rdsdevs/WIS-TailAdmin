<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_expiring_notifications_log', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('threshold_days');
            $table->timestamp('notified_at');

            $table->unique(['contract_id', 'threshold_days'], 'cenl_contract_threshold_unique');
            $table->index('notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_expiring_notifications_log');
    }
};
