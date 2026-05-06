<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\CertificateSignature;
use App\Models\User;
use App\Services\Certificados\CertificateSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

// ─── Helpers locales ──────────────────────────────────────────────────────────

/** PNG válido de 1x1 transparente codificado en base64. */
function pngDataUrl(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII=';
}

function jpegDataUrl(): string
{
    // JPEG mínimo válido (no es decodificable como imagen real, pero pasa el regex)
    return 'data:image/jpeg;base64,'.base64_encode('fakejpegcontent');
}

function contextoFirma(string $rol = 'rh-manager'): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    return [$user, $institution];
}

// ─── Crear firma vía service (atomicidad) ─────────────────────────────────────

describe('CertificateSignatureService::create', function (): void {

    it('crea una firma y guarda el archivo en disk public', function (): void {
        [$user, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        $signature = $service->create([
            'signer_name' => 'Juan Pérez',
            'signer_position' => 'Director',
            'signature_image' => pngDataUrl(),
        ], $institution->id);

        expect($signature->signature_image)->toStartWith('firmas/')->toEndWith('.png');
        Storage::disk('public')->assertExists($signature->signature_image);
        expect($signature->institution_id)->toBe($institution->id);
    });

    it('crea firma con reemplazo y guarda ambos archivos', function (): void {
        [, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        $signature = $service->create([
            'signer_name' => 'Juan Pérez',
            'signer_position' => 'Director',
            'signature_image' => pngDataUrl(),
            'replacement_name' => 'María Gómez',
            'replacement_position' => 'Subdirectora',
            'replacement_signature_image' => jpegDataUrl(),
        ], $institution->id);

        Storage::disk('public')->assertExists($signature->signature_image);
        Storage::disk('public')->assertExists($signature->replacement_signature_image);
        expect($signature->replacement_signature_image)->toEndWith('.jpg');
    });

    it('lanza excepción y borra archivo huérfano si la imagen del reemplazo es inválida', function (): void {
        [, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        try {
            $service->create([
                'signer_name' => 'Juan Pérez',
                'signer_position' => 'Director',
                'signature_image' => pngDataUrl(),
                'replacement_signature_image' => 'no-es-un-data-url',
            ], $institution->id);

            $this->fail('Se esperaba una InvalidArgumentException.');
        } catch (\InvalidArgumentException $e) {
            // Verificamos que no haya quedado ningún archivo huérfano en el disco fake.
            $files = Storage::disk('public')->allFiles('firmas');
            expect($files)->toBeEmpty();
            expect(CertificateSignature::count())->toBe(0);
        }
    });

    it('rechaza un data URL que no sea PNG ni JPEG', function (): void {
        [, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        $service->create([
            'signer_name' => 'Juan',
            'signer_position' => 'Director',
            'signature_image' => 'data:image/gif;base64,'.base64_encode('gif'),
        ], $institution->id);
    })->throws(\InvalidArgumentException::class);
});

// ─── Update vía service ───────────────────────────────────────────────────────

describe('CertificateSignatureService::update', function (): void {

    it('reemplaza la imagen y borra la anterior tras el commit', function (): void {
        [, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        $signature = $service->create([
            'signer_name' => 'Juan',
            'signer_position' => 'Director',
            'signature_image' => pngDataUrl(),
        ], $institution->id);

        $oldPath = $signature->signature_image;

        $updated = $service->update($signature, [
            'signer_name' => 'Juan Carlos',
            'signature_image' => pngDataUrl(),
            'is_active' => true,
        ]);

        expect($updated->signature_image)->not->toBe($oldPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($updated->signature_image);
        expect($updated->signer_name)->toBe('Juan Carlos');
    });

    it('preserva la imagen existente cuando no se envía signature_image', function (): void {
        [, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        $signature = $service->create([
            'signer_name' => 'Juan',
            'signer_position' => 'Director',
            'signature_image' => pngDataUrl(),
        ], $institution->id);
        $originalPath = $signature->signature_image;

        $updated = $service->update($signature, [
            'signer_name' => 'Juan Modificado',
            'is_active' => true,
        ]);

        expect($updated->signature_image)->toBe($originalPath);
        Storage::disk('public')->assertExists($originalPath);
    });
});

// ─── Delete + Soft delete + Force delete ──────────────────────────────────────

describe('CertificateSignatureService::delete', function (): void {

    it('soft-delete preserva los archivos físicos para permitir restore', function (): void {
        [, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        $signature = $service->create([
            'signer_name' => 'Juan',
            'signer_position' => 'Director',
            'signature_image' => pngDataUrl(),
        ], $institution->id);
        $path = $signature->signature_image;

        $service->delete($signature);

        expect(CertificateSignature::withTrashed()->find($signature->id)->trashed())->toBeTrue();
        Storage::disk('public')->assertExists($path);
    });

    it('forceDelete dispara el observer y borra los archivos del disco', function (): void {
        [, $institution] = contextoFirma();
        $service = app(CertificateSignatureService::class);

        $signature = $service->create([
            'signer_name' => 'Juan',
            'signer_position' => 'Director',
            'signature_image' => pngDataUrl(),
            'replacement_signature_image' => pngDataUrl(),
        ], $institution->id);
        $main = $signature->signature_image;
        $replacement = $signature->replacement_signature_image;

        $signature->forceDelete();

        Storage::disk('public')->assertMissing($main);
        Storage::disk('public')->assertMissing($replacement);
    });
});

// ─── Autorización (FormRequest + Policy) ──────────────────────────────────────

describe('Autorización del módulo de firmas', function (): void {

    it('rh-viewer recibe 403 al intentar crear una firma', function (): void {
        [$user, $institution] = contextoFirma('rh-viewer');

        $this->actingAs($user)
            ->post(route('certificados.firmas.store'), [
                'signer_name' => 'Juan',
                'signer_position' => 'Director',
                'signature_image' => pngDataUrl(),
            ])
            ->assertForbidden();
    });

    it('rh-manager no puede actualizar una firma de otra institución (IDOR)', function (): void {
        [$user] = contextoFirma('rh-manager');
        $otraInstitucion = Institution::factory()->create();
        $signature = CertificateSignature::factory()->create([
            'institution_id' => $otraInstitucion->id,
        ]);

        $this->actingAs($user)
            ->put(route('certificados.firmas.update', $signature), [
                'signer_name' => 'Otro nombre',
                'is_active' => '1',
            ])
            ->assertForbidden();
    });

    it('valida que la imagen sea PNG o JPEG en el FormRequest', function (): void {
        [$user] = contextoFirma('rh-manager');

        $this->actingAs($user)
            ->post(route('certificados.firmas.store'), [
                'signer_name' => 'Juan',
                'signer_position' => 'Director',
                'signature_image' => 'data:image/gif;base64,'.base64_encode('gif'),
            ])
            ->assertSessionHasErrors('signature_image');
    });
});
