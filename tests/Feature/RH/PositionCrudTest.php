<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RH\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ─── Helpers locales ──────────────────────────────────────────────────────────

/**
 * Crea institución y usuario con el rol indicado, y autentica al usuario.
 */
function contextoCargo(string $rol): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $user->assignRole($rol);

    return [$user, $institution];
}

// ─── Ver detalle de cargo ─────────────────────────────────────────────────────

describe('Ver detalle de cargo', function (): void {

    it('rh-manager puede ver el detalle de un cargo individual', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->get(route('rh.cargos.show', $cargo))
            ->assertOk()
            ->assertSee($cargo->name);
    });

    it('rh-viewer puede ver el detalle de un cargo en modo solo lectura', function (): void {
        [$user, $institution] = contextoCargo('rh-viewer');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->get(route('rh.cargos.show', $cargo))
            ->assertOk()
            ->assertSee($cargo->name);
    });

    it('usuario no autenticado es redirigido al login al ver detalle de cargo', function (): void {
        [, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->get(route('rh.cargos.show', $cargo))
            ->assertRedirect(route('login'));
    });

    it('rh-viewer no puede ver el detalle de cargo de otra institución', function (): void {
        [$user] = contextoCargo('rh-viewer');
        $otraInstitucion = Institution::factory()->create();
        $cargo = Position::factory()->create(['institution_id' => $otraInstitucion->id]);

        $this->actingAs($user)
            ->get(route('rh.cargos.show', $cargo))
            ->assertForbidden();
    });
});

// ─── Actualizar cargo ─────────────────────────────────────────────────────────

describe('Actualizar cargo', function (): void {

    it('rh-manager puede actualizar un cargo con datos válidos', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->put(route('rh.cargos.update', $cargo), [
                'name' => 'Cargo Actualizado',
                'is_active' => true,
            ])
            ->assertRedirect(route('rh.cargos.index'));

        expect($cargo->fresh()->name)->toBe('Cargo Actualizado');
    });

    it('rh-manager puede desactivar un cargo existente', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create([
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('rh.cargos.update', $cargo), [
                'name' => $cargo->name,
                'is_active' => false,
            ])
            ->assertRedirect(route('rh.cargos.index'));

        expect($cargo->fresh()->is_active)->toBeFalse();
    });

    it('rh-viewer no puede actualizar un cargo', function (): void {
        [$user, $institution] = contextoCargo('rh-viewer');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->put(route('rh.cargos.update', $cargo), [
                'name' => 'Intento Prohibido',
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    it('rechaza la actualización de cargo de otra institución', function (): void {
        [$user] = contextoCargo('rh-manager');
        $otraInstitucion = Institution::factory()->create();
        $cargo = Position::factory()->create(['institution_id' => $otraInstitucion->id]);

        $this->actingAs($user)
            ->put(route('rh.cargos.update', $cargo), [
                'name' => 'Intento Prohibido',
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    it('rechaza la actualización sin el campo nombre', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->put(route('rh.cargos.update', $cargo), [
                'is_active' => true,
            ])
            ->assertSessionHasErrors('name');
    });

    it('rechaza la actualización con nombre duplicado en la misma institución', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');

        // Cargo con el nombre que queremos "robar"
        Position::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Nombre Ya Existente',
        ]);

        // El cargo que vamos a intentar actualizar con ese nombre
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->put(route('rh.cargos.update', $cargo), [
                'name' => 'Nombre Ya Existente',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('name');
    });
});

// ─── Eliminar cargo ───────────────────────────────────────────────────────────

describe('Eliminar cargo', function (): void {

    it('rh-manager puede hacer eliminación lógica del cargo', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->delete(route('rh.cargos.destroy', $cargo))
            ->assertRedirect(route('rh.cargos.index'));

        $this->assertSoftDeleted('positions', ['id' => $cargo->id]);
    });

    it('el cargo eliminado no aparece en el listado', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Cargo Para Eliminar',
        ]);

        $this->actingAs($user)
            ->delete(route('rh.cargos.destroy', $cargo));

        $this->actingAs($user)
            ->get(route('rh.cargos.index'))
            ->assertOk()
            ->assertDontSee('Cargo Para Eliminar');
    });

    it('rh-viewer no puede eliminar un cargo', function (): void {
        [$user, $institution] = contextoCargo('rh-viewer');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->actingAs($user)
            ->delete(route('rh.cargos.destroy', $cargo))
            ->assertForbidden();

        $this->assertNotSoftDeleted('positions', ['id' => $cargo->id]);
    });

    it('rechaza la eliminación de cargo de otra institución', function (): void {
        [$user] = contextoCargo('rh-manager');
        $otraInstitucion = Institution::factory()->create();
        $cargo = Position::factory()->create(['institution_id' => $otraInstitucion->id]);

        $this->actingAs($user)
            ->delete(route('rh.cargos.destroy', $cargo))
            ->assertForbidden();

        $this->assertNotSoftDeleted('positions', ['id' => $cargo->id]);
    });
});

// ─── Crear cargo — validación ─────────────────────────────────────────────────

describe('Validación al crear cargo', function (): void {

    it('no puede crear un cargo sin el campo nombre', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');

        $this->actingAs($user)
            ->post(route('rh.cargos.store'), [
                'institution_id' => $institution->id,
                'is_active' => true,
                // 'name' omitido intencionalmente
            ])
            ->assertSessionHasErrors('name');
    });

    it('no puede crear un cargo con nombre duplicado en la misma institución', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');

        // Cargo preexistente
        Position::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Cargo Duplicado',
        ]);

        $this->actingAs($user)
            ->post(route('rh.cargos.store'), [
                'institution_id' => $institution->id,
                'name' => 'Cargo Duplicado',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('name');
    });

    it('puede crear un cargo con el mismo nombre en una institución diferente', function (): void {
        [$user, $institution] = contextoCargo('rh-manager');
        $otraInstitucion = Institution::factory()->create();

        // Cargo en OTRA institución — no debe bloquear la creación en la propia
        Position::factory()->create([
            'institution_id' => $otraInstitucion->id,
            'name' => 'Cargo Compartido',
        ]);

        $this->actingAs($user)
            ->post(route('rh.cargos.store'), [
                'institution_id' => $institution->id,
                'name' => 'Cargo Compartido',
                'is_active' => true,
            ])
            ->assertRedirect(route('rh.cargos.index'));

        $this->assertDatabaseHas('positions', [
            'institution_id' => $institution->id,
            'name' => 'Cargo Compartido',
        ]);
    });
});

// ─── Seguridad ────────────────────────────────────────────────────────────────

describe('Seguridad en el módulo de Cargos', function (): void {

    it('usuario no autenticado es redirigido al login al acceder al listado', function (): void {
        $this->get(route('rh.cargos.index'))
            ->assertRedirect(route('login'));
    });

    it('usuario no autenticado es redirigido al login al intentar crear un cargo', function (): void {
        $this->post(route('rh.cargos.store'), [
            'name' => 'Cargo Sin Sesión',
        ])->assertRedirect(route('login'));
    });

    it('usuario no autenticado es redirigido al login al intentar eliminar un cargo', function (): void {
        [, $institution] = contextoCargo('rh-manager');
        $cargo = Position::factory()->create(['institution_id' => $institution->id]);

        $this->delete(route('rh.cargos.destroy', $cargo))
            ->assertRedirect(route('login'));
    });

    it('escapa correctamente caracteres XSS en el nombre del cargo', function (): void {
        [$user, $institution] = contextoCargo('rh-viewer');
        $cargo = Position::factory()->create([
            'institution_id' => $institution->id,
            'name' => '<script>alert("xss")</script>',
        ]);

        $this->actingAs($user)
            ->get(route('rh.cargos.show', $cargo))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert', false);
    });
});
