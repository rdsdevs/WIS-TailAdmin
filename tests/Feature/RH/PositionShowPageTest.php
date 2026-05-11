<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\DocumentType;
use App\Models\RH\Position;
use App\Models\RH\PositionEmail;
use App\Models\RH\PositionFunction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->gerente = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->gerente->assignRole('rh-manager');
    $this->actingAs($this->gerente);

    $this->cargo = Position::factory()->create([
        'institution_id' => $this->institution->id,
        'name' => 'Director Académico',
        'is_active' => true,
    ]);

    PositionEmail::create(['position_id' => $this->cargo->id, 'email' => 'director@ascun.org.co']);
    PositionFunction::create(['position_id' => $this->cargo->id, 'description' => 'Coordinar las áreas académicas.']);
});

it('muestra el detalle del cargo con correos y funciones', function (): void {
    $this->get(route('rh.cargos.show', $this->cargo->id))
        ->assertOk()
        ->assertViewIs('pages.rh.cargos.show')
        ->assertSee('Director Académico')
        ->assertSee('director@ascun.org.co')
        ->assertSee('Coordinar las áreas académicas.')
        ->assertSee('Activo');
});

it('muestra mensaje cuando el cargo no tiene correos ni funciones', function (): void {
    $vacio = Position::factory()->create([
        'institution_id' => $this->institution->id,
        'name' => 'Cargo Vacío',
    ]);

    $this->get(route('rh.cargos.show', $vacio->id))
        ->assertOk()
        ->assertSee('Este cargo no tiene correos asociados.')
        ->assertSee('Este cargo no tiene funciones registradas.');
});

it('muestra estado Inactivo cuando is_active es false', function (): void {
    $inactivo = Position::factory()->inactive()->create([
        'institution_id' => $this->institution->id,
        'name' => 'Cargo Inactivo',
    ]);

    $this->get(route('rh.cargos.show', $inactivo->id))
        ->assertOk()
        ->assertSee('Inactivo');
});

it('muestra el conteo de contratos vigentes y el link al listado filtrado', function (): void {
    $documentType = DocumentType::factory()->create([
        'institution_id' => $this->institution->id,
        'code' => 'CC',
    ]);
    $status = CollaboratorStatus::factory()->create([
        'institution_id' => $this->institution->id,
        'name' => 'Activo',
    ]);
    $contractType = ContractType::factory()->create([
        'institution_id' => $this->institution->id,
    ]);

    foreach (range(1, 2) as $i) {
        $colaborador = Collaborator::factory()->create([
            'institution_id' => $this->institution->id,
            'document_type_id' => $documentType->id,
            'status_id' => $status->id,
        ]);
        Contract::factory()->create([
            'institution_id' => $this->institution->id,
            'collaborator_id' => $colaborador->id,
            'position_id' => $this->cargo->id,
            'contract_type_id' => $contractType->id,
            'status' => 'Vigente',
        ]);
    }

    $this->get(route('rh.cargos.show', $this->cargo->id))
        ->assertOk()
        ->assertSee(route('rh.contratos.index', ['cargo' => $this->cargo->id]), escape: false);
});

it('muestra el botón Editar solo si el usuario tiene permiso', function (): void {
    $this->get(route('rh.cargos.show', $this->cargo->id))
        ->assertOk()
        ->assertSee('Editar cargo');

    $viewer = User::factory()->create(['institution_id' => $this->institution->id]);
    $viewer->assignRole('rh-viewer');

    $this->actingAs($viewer)
        ->get(route('rh.cargos.show', $this->cargo->id))
        ->assertOk()
        ->assertDontSee('Editar cargo');
});

it('niega acceso a usuarios de otra institución', function (): void {
    $otra = Institution::factory()->create();
    $cargoAjeno = Position::factory()->create([
        'institution_id' => $otra->id,
        'name' => 'Ajeno',
    ]);

    $this->get(route('rh.cargos.show', $cargoAjeno->id))
        ->assertForbidden();
});
