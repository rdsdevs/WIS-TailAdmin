<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correcciones sobre committed_values:
 *  - Renombra committed_value → amount para alinear con la interfaz del wizard
 *  - Amplía accounting_account y cost_center a 200 caracteres
 *  - Agrega institution_id para multi-tenancy
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('committed_values', function (Blueprint $table): void {
            // Ampliar longitud de campos de texto antes de renombrar
            $table->string('accounting_account', 200)->change();
            $table->string('cost_center', 200)->change();

            // Renombrar committed_value → amount
            $table->renameColumn('committed_value', 'amount');

            // Agregar institution_id para multi-tenancy (nullable para migrar datos existentes)
            $table->foreignUuid('institution_id')
                ->nullable()
                ->after('id')
                ->constrained('institutions')
                ->nullOnDelete();

            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::table('committed_values', function (Blueprint $table): void {
            $table->dropForeign(['institution_id']);
            $table->dropIndex(['institution_id']);
            $table->dropColumn('institution_id');

            $table->renameColumn('amount', 'committed_value');

            $table->string('accounting_account', 30)->change();
            $table->string('cost_center', 30)->change();
        });
    }
};
