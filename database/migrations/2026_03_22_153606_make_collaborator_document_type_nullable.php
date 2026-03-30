<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collaborators', function (Blueprint $table): void {
            $table->foreignUuid('document_type_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table): void {
            $table->foreignUuid('document_type_id')->nullable(false)->change();
        });
    }
};
