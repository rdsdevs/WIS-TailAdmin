<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte la tabla users al esquema WIS ASCUN:
     * - PK UUID en lugar de BIGINT autoincrement
     * - Login por document_number + document_issued_at + password
     * - institution_id para multi-tenancy
     * - SoftDeletes para preservar historial
     */
    public function up(): void
    {
        // Eliminar dependencias antes de recrear la tabla
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type', 10)->default('CC');
            $table->string('document_number', 30)->unique();
            $table->date('document_issued_at');
            $table->string('name', 150);
            $table->string('email', 150)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            // Índice compuesto crítico para el login
            $table->index(['document_number', 'document_issued_at'], 'users_login_index');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Revierte la tabla users al esquema original de Laravel.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
