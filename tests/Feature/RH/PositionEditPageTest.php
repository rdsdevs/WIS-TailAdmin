<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Position;
use App\Models\RH\PositionEmail;
use App\Models\RH\PositionFunction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->gerente = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->gerente->assignRole('rh-manager');
    $this->actingAs($this->gerente);

    $this->cargo = Position::factory()->create([
        'institution_id' => $this->institution->id,
        'name' => 'Cargo Original',
        'is_active' => true,
    ]);

    PositionEmail::create(['position_id' => $this->cargo->id, 'email' => 'inicial@x.com']);
    PositionFunction::create(['position_id' => $this->cargo->id, 'description' => 'Función inicial']);
});

it('renderiza la página de editar cargo con los datos prellenados', function (): void {
    $response = $this->get(route('rh.cargos.edit', $this->cargo->id))
        ->assertOk()
        ->assertViewIs('pages.rh.cargos.edit')
        ->assertSeeLivewire('rh.position-form');

    Livewire::test('rh.position-form', ['cargoId' => $this->cargo->id])
        ->assertSet('name', 'Cargo Original')
        ->assertSet('isActive', true)
        ->assertSet('emails.0', 'inicial@x.com')
        ->assertSet('functions.0', 'Función inicial');
});

it('actualiza un cargo y reemplaza correos y funciones', function (): void {
    Livewire::test('rh.position-form', ['cargoId' => $this->cargo->id])
        ->set('name', 'Cargo Modificado')
        ->set('isActive', false)
        ->set('emails.0', 'nuevo1@x.com')
        ->call('addEmail')
        ->set('emails.1', 'nuevo2@x.com')
        ->set('functions.0', 'Nueva función A')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->cargo->refresh()->load(['emails', 'functions']);

    expect($this->cargo->name)->toBe('Cargo Modificado')
        ->and($this->cargo->is_active)->toBeFalse()
        ->and($this->cargo->emails->pluck('email')->all())->toEqualCanonicalizing(['nuevo1@x.com', 'nuevo2@x.com'])
        ->and($this->cargo->functions->pluck('description')->all())->toBe(['Nueva función A']);
});

it('valida que no pueda duplicar un nombre de otro cargo en la misma institución', function (): void {
    Position::factory()->create([
        'institution_id' => $this->institution->id,
        'name' => 'Cargo Ya Existente',
    ]);

    Livewire::test('rh.position-form', ['cargoId' => $this->cargo->id])
        ->set('name', 'Cargo Ya Existente')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('permite guardar manteniendo el mismo nombre del cargo en edición', function (): void {
    Livewire::test('rh.position-form', ['cargoId' => $this->cargo->id])
        ->set('name', 'Cargo Original')
        ->call('save')
        ->assertHasNoErrors();
});

it('niega edición a usuarios sin permiso', function (): void {
    $viewer = User::factory()->create(['institution_id' => $this->institution->id]);
    $viewer->assignRole('rh-viewer');

    $this->actingAs($viewer)
        ->get(route('rh.cargos.edit', $this->cargo->id))
        ->assertForbidden();
});

it('niega edición si el cargo pertenece a otra institución', function (): void {
    $otra = Institution::factory()->create();
    $cargoAjeno = Position::factory()->create([
        'institution_id' => $otra->id,
        'name' => 'Ajeno',
    ]);

    $this->get(route('rh.cargos.edit', $cargoAjeno->id))
        ->assertForbidden();
});
