<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vaciar las columnas porque el contenido base64 previo ya no es compatible
        // con el nuevo formato (path al archivo en storage/app/public/firmas/).
        DB::table('certificate_signatures')->update([
            'signature_image' => '',
            'replacement_signature_image' => null,
        ]);

        Schema::table('certificate_signatures', function (Blueprint $table): void {
            $table->string('signature_image', 500)->change();
            $table->string('replacement_signature_image', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_signatures', function (Blueprint $table): void {
            $table->text('signature_image')->change();
            $table->text('replacement_signature_image')->nullable()->change();
        });
    }
};
