<?php

declare(strict_types=1);

use App\Models\User;

describe('Autenticación WIS ASCUN', function (): void {

    it('muestra el formulario de inicio de sesión', function (): void {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    });

    it('inicia sesión con cédula, fecha de expedición y contraseña correctas', function (): void {
        $usuario = User::factory()->create([
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => bcrypt('Clave123*'),
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => 'Clave123*',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario);
    });

    it('rechaza el acceso con fecha de expedición incorrecta', function (): void {
        User::factory()->create([
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => bcrypt('Clave123*'),
        ]);

        $this->post(route('login'), [
            'document_number' => '12345678',
            'document_issued_at' => '2000-01-01',
            'password' => 'Clave123*',
        ])->assertSessionHasErrors('document_number');

        $this->assertGuest();
    });

    it('rechaza el acceso con contraseña incorrecta', function (): void {
        User::factory()->create([
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => bcrypt('Clave123*'),
        ]);

        $this->post(route('login'), [
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => 'ContraseniaIncorrecta',
        ])->assertSessionHasErrors('document_number');

        $this->assertGuest();
    });

    it('rechaza el acceso de usuarios inactivos', function (): void {
        User::factory()->create([
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => bcrypt('Clave123*'),
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => 'Clave123*',
        ])->assertSessionHasErrors('document_number');

        $this->assertGuest();
    });

    it('regenera el ID de sesión tras un login exitoso', function (): void {
        $idAntes = session()->getId();

        $usuario = User::factory()->create([
            'password' => bcrypt('Clave123*'),
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'document_number' => $usuario->document_number,
            'document_issued_at' => $usuario->document_issued_at->format('Y-m-d'),
            'password' => 'Clave123*',
        ]);

        expect(session()->getId())->not->toBe($idAntes);
    });

    it('actualiza la fecha de último acceso tras un login exitoso', function (): void {
        $usuario = User::factory()->create([
            'password' => bcrypt('Clave123*'),
            'is_active' => true,
            'last_login_at' => null,
        ]);

        $this->post(route('login'), [
            'document_number' => $usuario->document_number,
            'document_issued_at' => $usuario->document_issued_at->format('Y-m-d'),
            'password' => 'Clave123*',
        ]);

        expect($usuario->fresh()->last_login_at)->not->toBeNull();
    });

    it('invalida la sesión y redirige al login al cerrar sesión', function (): void {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('redirige al login cuando se accede al dashboard sin autenticar', function (): void {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });

    it('redirige al dashboard si ya está autenticado al acceder al login', function (): void {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    });

    it('falla la validación cuando el número de cédula no se proporciona', function (): void {
        $this->post(route('login'), [
            'document_issued_at' => '1995-06-15',
            'password' => 'Clave123*',
        ])->assertSessionHasErrors('document_number');
    });

    it('falla la validación cuando la fecha de expedición no se proporciona', function (): void {
        $this->post(route('login'), [
            'document_number' => '12345678',
            'password' => 'Clave123*',
        ])->assertSessionHasErrors('document_issued_at');
    });

    it('falla la validación cuando la fecha de expedición es futura', function (): void {
        $this->post(route('login'), [
            'document_number' => '12345678',
            'document_issued_at' => now()->addDay()->toDateString(),
            'password' => 'Clave123*',
        ])->assertSessionHasErrors('document_issued_at');
    });

    it('falla la validación cuando la contraseña no se proporciona', function (): void {
        $this->post(route('login'), [
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
        ])->assertSessionHasErrors('password');
    });

    it('falla la validación cuando la contraseña tiene menos de 6 caracteres', function (): void {
        $this->post(route('login'), [
            'document_number' => '12345678',
            'document_issued_at' => '1995-06-15',
            'password' => '123',
        ])->assertSessionHasErrors('password');
    });

});
