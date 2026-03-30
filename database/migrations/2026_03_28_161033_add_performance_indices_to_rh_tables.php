<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->index(['document_number', 'document_issued_at'], 'idx_collab_doc_search');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['contract_number', 'start_date'], 'idx_contract_num_start');
            $table->index(['status', 'end_date'], 'idx_contract_status_end');
        });
    }

    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->dropIndex('idx_collab_doc_search');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('idx_contract_num_start');
            $table->dropIndex('idx_contract_status_end');
        });
    }
};
