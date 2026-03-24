<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_extensions', function (Blueprint $table): void {
            $table->enum('extension_type', ['tiempo', 'valor', 'tiempo_y_valor'])
                ->nullable()
                ->after('reason');

            $table->unsignedTinyInteger('extension_months')
                ->nullable()
                ->after('extension_type');

            $table->unsignedSmallInteger('extension_days')
                ->nullable()
                ->after('extension_months');

            $table->decimal('extension_value', 12, 2)
                ->nullable()
                ->after('extension_days');

            $table->date('new_end_date')
                ->nullable()
                ->after('extension_value');

            $table->date('approval_date')
                ->nullable()
                ->after('new_end_date');

            $table->foreignUuid('institution_id')
                ->nullable()
                ->after('approval_date')
                ->constrained('institutions');

            $table->foreignUuid('committed_value_id')
                ->nullable()
                ->after('institution_id')
                ->constrained('committed_values');
        });
    }

    public function down(): void
    {
        Schema::table('contract_extensions', function (Blueprint $table): void {
            $table->dropForeign(['committed_value_id']);
            $table->dropForeign(['institution_id']);
            $table->dropColumn([
                'extension_type',
                'extension_months',
                'extension_days',
                'extension_value',
                'new_end_date',
                'approval_date',
                'institution_id',
                'committed_value_id',
            ]);
        });
    }
};
