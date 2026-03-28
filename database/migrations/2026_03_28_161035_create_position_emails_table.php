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
        Schema::create('position_emails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('position_id')->constrained()->cascadeOnDelete();
            $table->string('email', 150);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_emails');
    }
};
