<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\User;
use App\Services\Certificados\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function escenarioContratista(): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole('rh-manager');

    $contratista = Collaborator::factory()->contratista()->create([
        'institution_id' => $institution->id,
    ]);

    $contract = Contract::factory()->create([
        'institution_id' => $institution->id,
        'collaborator_id' => $contratista->id,
        'object' => 'Prestar asesoría jurídica',
        'obligations' => 'Asistir reuniones, redactar conceptos',
        'salary' => 5_000_000,
        'status' => 'Vigente',
    ]);

    $signature = CertificateSignature::factory()->create([
        'institution_id' => $institution->id,
    ]);

    return [$user, $contratista, $contract, $signature];
}

// ─── Snapshot de opciones del contratista ─────────────────────────────────────

describe('CertificateService::generateContractor — options snapshot', function (): void {

    it('guarda todas las opciones en false cuando ninguna se envía (checkbox sin marcar)', function (): void {
        [$user, $contratista, $contract, $signature] = escenarioContratista();

        $certificate = app(CertificateService::class)->generateContractor(
            $contratista,
            $signature,
            [$contract->id],
            [], // sin opciones marcadas
            null,
            $user,
        );

        expect($certificate->options_snapshot)->toBe([
            'show_object' => false,
            'show_obligations' => false,
            'show_value' => false,
            'show_prorrogas' => false,
            'show_early_termination' => false,
        ]);
    });

    it('respeta cada opción cuando se marca individualmente', function (): void {
        [$user, $contratista, $contract, $signature] = escenarioContratista();

        $certificate = app(CertificateService::class)->generateContractor(
            $contratista,
            $signature,
            [$contract->id],
            ['show_value' => '1', 'show_object' => true],
            null,
            $user,
        );

        expect($certificate->options_snapshot['show_value'])->toBeTrue();
        expect($certificate->options_snapshot['show_object'])->toBeTrue();
        expect($certificate->options_snapshot['show_obligations'])->toBeFalse();
        expect($certificate->options_snapshot['show_prorrogas'])->toBeFalse();
        expect($certificate->options_snapshot['show_early_termination'])->toBeFalse();
    });

    it('persiste el snapshot del colaborador y del contrato', function (): void {
        [$user, $contratista, $contract, $signature] = escenarioContratista();

        $certificate = app(CertificateService::class)->generateContractor(
            $contratista,
            $signature,
            [$contract->id],
            [],
            null,
            $user,
        );

        expect($certificate->collaborator_snapshot)->toHaveKey('document_number');
        expect($certificate->contracts_snapshot)->toHaveCount(1);
        expect($certificate->contracts_snapshot[0]['object'])->toBe('Prestar asesoría jurídica');
        expect($certificate->verification_code)->not->toBeEmpty();
    });
});

// ─── loadSignatureBase64 (privado, vía reflection) ────────────────────────────

describe('CertificateService::loadSignatureBase64', function (): void {

    it('retorna null cuando el archivo de la firma no existe en disco', function (): void {
        $service = app(CertificateService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('loadSignatureBase64');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'firmas/inexistente.png');

        expect($result)->toBeNull();
    });

    it('retorna null y loggea cuando la extensión no está en la whitelist', function (): void {
        Storage::disk('public')->put('firmas/raro.gif', 'contenido');

        $service = app(CertificateService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('loadSignatureBase64');
        $method->setAccessible(true);

        expect($method->invoke($service, 'firmas/raro.gif'))->toBeNull();
    });

    it('retorna data URL base64 válido cuando el archivo PNG existe', function (): void {
        $binary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII=');
        Storage::disk('public')->put('firmas/ok.png', $binary);

        $service = app(CertificateService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('loadSignatureBase64');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'firmas/ok.png');

        expect($result)->toStartWith('data:image/png;base64,');
    });
});

// ─── findByVerificationCode ───────────────────────────────────────────────────

describe('CertificateService::findByVerificationCode', function (): void {

    it('retorna null cuando el código no existe', function (): void {
        $result = app(CertificateService::class)->findByVerificationCode('codigo-falso-123');

        expect($result)->toBeNull();
    });

    it('retorna el certificado cuando el código existe', function (): void {
        [$user, $contratista, $contract, $signature] = escenarioContratista();

        $certificate = app(CertificateService::class)->generateContractor(
            $contratista, $signature, [$contract->id], [], null, $user,
        );

        $found = app(CertificateService::class)->findByVerificationCode($certificate->verification_code);

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($certificate->id);
    });
});
