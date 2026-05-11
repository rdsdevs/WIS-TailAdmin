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
});

it('renderiza la página de crear cargo con el formulario Livewire', function (): void {
    $this->get(route('rh.cargos.create'))
        ->assertOk()
        ->assertViewIs('pages.rh.cargos.create')
        ->assertSeeLivewire('rh.position-form');
});

it('crea un cargo válido con correos y funciones', function (): void {
    Livewire::test('rh.position-form')
        ->set('name', 'Coordinador de Talento Humano')
        ->set('isActive', true)
        ->set('emails.0', 'coordinador@ascun.org.co')
        ->call('addEmail')
        ->set('emails.1', 'th@ascun.org.co')
        ->set('functions.0', 'Coordinar el proceso de selección y contratación.')
        ->call('addFunction')
        ->set('functions.1', 'Administrar el plan de bienestar laboral.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $position = Position::where('name', 'Coordinador de Talento Humano')->first();

    expect($position)->not->toBeNull()
        ->and($position->institution_id)->toBe($this->institution->id)
        ->and($position->is_active)->toBeTrue()
        ->and(PositionEmail::where('position_id', $position->id)->count())->toBe(2)
        ->and(PositionFunction::where('position_id', $position->id)->count())->toBe(2);
});

it('crea un cargo sin correos ni funciones cuando ambos están vacíos', function (): void {
    Livewire::test('rh.position-form')
        ->set('name', 'Cargo Mínimo')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $position = Position::where('name', 'Cargo Mínimo')->first();

    expect($position)->not->toBeNull()
        ->and(PositionEmail::where('position_id', $position->id)->count())->toBe(0)
        ->and(PositionFunction::where('position_id', $position->id)->count())->toBe(0);
});

it('valida que el nombre del cargo es obligatorio', function (): void {
    Livewire::test('rh.position-form')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('valida que cada correo tenga formato de email válido', function (): void {
    Livewire::test('rh.position-form')
        ->set('name', 'Cargo Prueba')
        ->set('emails.0', 'esto-no-es-email')
        ->call('save')
        ->assertHasErrors(['emails.0' => 'email']);
});

it('rechaza correos duplicados dentro del mismo formulario', function (): void {
    Livewire::test('rh.position-form')
        ->set('name', 'Cargo Prueba')
        ->set('emails.0', 'mismo@x.com')
        ->call('addEmail')
        ->set('emails.1', 'mismo@x.com')
        ->call('save')
        ->assertHasErrors(['emails.1' => 'distinct']);
});

it('valida unicidad del nombre dentro de la misma institución', function (): void {
    Position::factory()->create([
        'institution_id' => $this->institution->id,
        'name' => 'Cargo Existente',
    ]);

    Livewire::test('rh.position-form')
        ->set('name', 'Cargo Existente')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('permite repetir nombre en otra institución', function (): void {
    $otra = Institution::factory()->create();
    Position::factory()->create([
        'institution_id' => $otra->id,
        'name' => 'Cargo Compartido',
    ]);

    Livewire::test('rh.position-form')
        ->set('name', 'Cargo Compartido')
        ->call('save')
        ->assertHasNoErrors();

    expect(Position::where('institution_id', $this->institution->id)->where('name', 'Cargo Compartido')->exists())
        ->toBeTrue();
});

it('niega acceso a usuarios sin permiso para crear cargos', function (): void {
    $viewer = User::factory()->create(['institution_id' => $this->institution->id]);
    $viewer->assignRole('rh-viewer');

    $this->actingAs($viewer)
        ->get(route('rh.cargos.create'))
        ->assertForbidden();
});
