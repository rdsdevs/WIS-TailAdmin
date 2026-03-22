<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->restrictOnDelete();
            $table->string('code', 4);
            $table->string('name', 60);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
