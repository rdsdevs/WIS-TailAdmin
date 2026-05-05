<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_signatures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->restrictOnDelete();
            $table->string('signer_name', 80);
            $table->string('signer_position', 80);
            $table->string('signature_image', 500);
            $table->string('replacement_name', 80)->nullable();
            $table->string('replacement_position', 80)->nullable();
            $table->string('replacement_signature_image', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_signatures');
    }
};
