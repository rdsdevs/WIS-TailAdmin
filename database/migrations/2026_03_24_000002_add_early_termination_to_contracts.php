<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->date('early_termination_date')
                ->nullable()
                ->after('end_date');

            $table->text('early_termination_reason')
                ->nullable()
                ->after('early_termination_date');

            $table->foreignUuid('early_terminated_by')
                ->nullable()
                ->after('early_termination_reason')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('early_terminated_at')
                ->nullable()
                ->after('early_terminated_by');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropForeign(['early_terminated_by']);
            $table->dropColumn([
                'early_termination_date',
                'early_termination_reason',
                'early_terminated_by',
                'early_terminated_at',
            ]);
        });
    }
};
