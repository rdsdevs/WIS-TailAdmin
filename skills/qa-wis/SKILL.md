---
nombre: qa-wis
descripcion: >
  Asegurar la calidad del código en WIS ASCUN: escribir tests con Pest en español,
  hacer code reviews, auditar seguridad, verificar estándares y validar migraciones
  desde el sistema legacy app.wisascun.com. Usar en PRs, antes de releases, o cuando
  se sospeche de vulnerabilidades. Cobertura mínima objetivo: 80%.
---

# SKILL: QA — WIS ASCUN Laravel 12

Esta guía define los estándares de calidad, seguridad y testing del proyecto WIS ASCUN.
Todo el código que llega a `develop` debe cumplir estas reglas.

> **Idioma de los tests:** Las descripciones de los tests se escriben en **español colombiano**
> para mayor claridad del equipo. El código PHP sigue los estándares de Laravel (en inglés para clases, métodos y variables).

---

## Flujo de QA

1. Antes de cada PR: ejecutar `php artisan test` y `./vendor/bin/pint --test`.
2. Code review: aplicar el checklist de seguridad y arquitectura.
3. Auditoría de migración: verificar que no se arrastró ningún issue del legacy.
4. Antes de release: ejecutar con cobertura `php artisan test --coverage --min=80`.
5. Reportar issues con su nivel de severidad (Crítico / Alto / Medio / Bajo).

---

## Checklist de Code Review

### Seguridad
- [ ] Sin raw SQL con variables interpoladas (`"WHERE id = $id"`)
- [ ] Input del usuario validado en FormRequest antes de usarse
- [ ] Output en Blade usa `{{ }}` — nunca `{!! !!}` sin sanitización previa
- [ ] Sin credenciales hardcoded (passwords, tokens, API keys)
- [ ] `@csrf` en todos los formularios `<form method="POST">`
- [ ] Policy implementada para operaciones CRUD
- [ ] Errores de BD no expuestos al usuario (`catch` usa `Log::error`, no `echo`)

### Autenticación WIS ASCUN
- [ ] Login usa: `document_number` + `document_issued_at` + `password`
- [ ] Password hasheado con `Hash::make()` o cast `'hashed'` — nunca `crypt()`, MD5 o SHA1
- [ ] `document_issued_at` es columna `date` en la BD
- [ ] `session()->regenerate()` se llama después del login exitoso
- [ ] Logout invalida la sesión y regenera el token CSRF

### Arquitectura
- [ ] Controller no tiene lógica de negocio (solo llama al Service)
- [ ] Service tiene la lógica de negocio
- [ ] Model solo tiene scopes, mutators, relaciones y casts
- [ ] `DB::transaction` en operaciones que afectan múltiples tablas
- [ ] `$fillable` explícito en todos los modelos

### Estándares Laravel
- [ ] `declare(strict_types=1)` en todos los archivos PHP
- [ ] Type hints en parámetros y valores de retorno
- [ ] Modelo con UUID + SoftDeletes + implements Auditable
- [ ] Migration tiene `up()` y `down()` implementados
- [ ] Factory existe para el modelo

### Idioma — Español Colombiano
- [ ] Mensajes de validación en español colombiano
- [ ] Mensajes de error de la UI en español colombiano
- [ ] Notificaciones y alertas en español colombiano
- [ ] Fechas en formato `d/m/Y`
- [ ] Moneda en formato `$ 1.500.000` (COP)
- [ ] Sin texto en inglés visible al usuario

---

## Estructura de Tests con Pest

### Organización de archivos

```
tests/
├── Feature/
│   ├── Auth/
│   │   └── LoginTest.php
│   ├── RH/
│   │   ├── EmployeeTest.php
│   │   └── ContractTest.php
│   ├── Contabilidad/
│   ├── Inventario/
│   └── Certificados/
│       └── CertificateGenerationTest.php
└── Unit/
    ├── Services/
    │   ├── Auth/
    │   │   └── AuthServiceTest.php
    │   └── RH/
    │       └── EmployeeServiceTest.php
    └── Models/
```

### Feature test — CRUD completo

```php
<?php

declare(strict_types=1);

use App\Models\RH\Employee;
use App\Models\User;

describe('Gestión de Empleados', function (): void {

    beforeEach(function (): void {
        $this->gerenteRH = User::factory()->create();
        $this->gerenteRH->assignRole('rh-manager');
        $this->actingAs($this->gerenteRH);
    });

    it('puede ver el listado de empleados', function (): void {
        Employee::factory()->count(3)->create([
            'institution_id' => $this->gerenteRH->institution_id,
        ]);

        $this->get(route('rh.empleados.index'))
            ->assertOk()
            ->assertViewIs('pages.rh.empleados.index');
    });

    it('puede registrar un empleado con datos válidos', function (): void {
        $datos = Employee::factory()->make([
            'institution_id' => $this->gerenteRH->institution_id,
        ])->toArray();

        $this->post(route('rh.empleados.store'), $datos)
            ->assertRedirect(route('rh.empleados.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('employees', [
            'document_number' => $datos['document_number'],
        ]);
    });

    it('rechaza el registro sin número de cédula', function (): void {
        $datos = Employee::factory()->make()->toArray();
        unset($datos['document_number']);

        $this->post(route('rh.empleados.store'), $datos)
            ->assertSessionHasErrors('document_number');
    });

    it('impide que un consultor registre empleados', function (): void {
        $consultor = User::factory()->create();
        $consultor->assignRole('rh-viewer');

        $this->actingAs($consultor)
            ->post(route('rh.empleados.store'), Employee::factory()->make()->toArray())
            ->assertForbidden();
    });

    it('hace eliminación lógica y registra auditoría', function (): void {
        $empleado = Employee::factory()->create([
            'institution_id' => $this->gerenteRH->institution_id,
        ]);

        $this->delete(route('rh.empleados.destroy', $empleado))
            ->assertRedirect(route('rh.empleados.index'))
            ->assertSessionHas('exito');

        $this->assertSoftDeleted('employees', ['id' => $empleado->id]);

        expect(\OwenIt\Auditing\Models\Audit::where('auditable_id', $empleado->id)->exists())
            ->toBeTrue();
    });
});
```

### Feature test — Autenticación WIS ASCUN

```php
<?php

declare(strict_types=1);

use App\Models\User;

describe('Autenticación WIS ASCUN', function (): void {

    it('inicia sesión con cédula, fecha de expedición y contraseña', function (): void {
        $usuario = User::factory()->create([
            'document_number'    => '12345678',
            'document_issued_at' => '1995-06-15',
            'password'           => bcrypt('Clave123*'),
        ]);

        $this->post(route('login'), [
            'document_number'    => '12345678',
            'document_issued_at' => '1995-06-15',
            'password'           => 'Clave123*',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario);
    });

    it('rechaza el acceso con fecha de expedición incorrecta', function (): void {
        User::factory()->create([
            'document_number'    => '12345678',
            'document_issued_at' => '1995-06-15',
            'password'           => bcrypt('Clave123*'),
        ]);

        $this->post(route('login'), [
            'document_number'    => '12345678',
            'document_issued_at' => '2000-01-01',
            'password'           => 'Clave123*',
        ])->assertSessionHasErrors('document_number');

        $this->assertGuest();
    });

    it('rechaza el acceso de usuarios inactivos', function (): void {
        User::factory()->create([
            'document_number'    => '12345678',
            'document_issued_at' => '1995-06-15',
            'password'           => bcrypt('Clave123*'),
            'is_active'          => false,
        ]);

        $this->post(route('login'), [
            'document_number'    => '12345678',
            'document_issued_at' => '1995-06-15',
            'password'           => 'Clave123*',
        ])->assertSessionHasErrors('document_number');
    });

    it('regenera el ID de sesión tras un login exitoso', function (): void {
        $idAntes = session()->getId();
        $usuario = User::factory()->create(['password' => bcrypt('Clave123*')]);

        $this->post(route('login'), [
            'document_number'    => $usuario->document_number,
            'document_issued_at' => $usuario->document_issued_at->format('Y-m-d'),
            'password'           => 'Clave123*',
        ]);

        expect(session()->getId())->not->toBe($idAntes);
    });

    it('invalida la sesión al cerrar sesión', function (): void {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });
});
```

### Tests de seguridad

```php
describe('Seguridad', function (): void {

    it('bloquea el acceso no autenticado a rutas protegidas', function (): void {
        $rutas = [
            ['GET',    route('rh.empleados.index')],
            ['POST',   route('rh.empleados.store')],
            ['DELETE', route('rh.empleados.destroy', '00000000-0000-0000-0000-000000000000')],
        ];

        foreach ($rutas as [$metodo, $ruta]) {
            $this->{strtolower($metodo)}($ruta)->assertRedirect(route('login'));
        }
    });

    it('no expone errores SQL al usuario', function (): void {
        $this->actingAs(User::factory()->create())
            ->get(route('rh.empleados.index', ['buscar' => "' OR '1'='1"]))
            ->assertOk()
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('syntax error');
    });

    it('escapa correctamente el XSS en el nombre del empleado', function (): void {
        $empleado = Employee::factory()->create([
            'first_name' => '<script>alert("xss")</script>',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('rh.empleados.show', $empleado))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert', false);
    });
});
```

---

## Issues del Legacy — Lista de Verificación

Al migrar código de `app.wisascun.com`, ninguno de estos problemas debe aparecer en el nuevo código:

| Nivel | Issue | Patrón incorrecto | Patrón correcto |
|---|---|---|---|
| CRÍTICO | SQL Injection | `"WHERE id = $id"` | `->where('id', $id)` |
| CRÍTICO | Credenciales en código | `define('DB_PASS', '...')` | `env('DB_PASSWORD')` |
| CRÍTICO | Hash débil | `crypt($pass, '$2a$07$...')` | `Hash::make($pass)` |
| ALTO | Sin CSRF | `<form>` sin `@csrf` | `<form> @csrf` |
| ALTO | XSS | `echo $_POST['nombre']` | `{{ $nombre }}` |
| ALTO | Errores expuestos | `echo $e->getMessage()` | `Log::error($e)` |
| ALTO | display_errors activo | `ini_set('display_errors', 1)` | `.env APP_DEBUG=false` |
| MEDIO | Sesión sin regenerar | — | `session()->regenerate()` |
| MEDIO | UI en inglés | `"User not found"` | `"Usuario no encontrado"` |

---

## Métricas de Calidad Objetivo

| Métrica | Objetivo |
|---|---|
| Cobertura de tests | ≥ 80% |
| Pint violations | 0 |
| SQL injection points | 0 |
| Credenciales en código | 0 |
| Formularios sin CSRF | 0 |
| Mensajes de UI en inglés | 0 |
| Models sin UUID | 0 (entidades de negocio) |
| Controllers sin Policy | 0 (operaciones CRUD) |

---

## Comandos de QA

```bash
# Ejecutar todos los tests
php artisan test

# Tests con cobertura (requiere Xdebug o PCOV)
php artisan test --coverage --min=80

# Test específico
php artisan test --filter="LoginTest"

# Solo tests de un módulo
php artisan test tests/Feature/RH/

# Verificar estilo de código
./vendor/bin/pint --test

# Aplicar correcciones de estilo
./vendor/bin/pint

# Auditar vulnerabilidades en dependencias
composer audit
npm audit
```

---

## Guardrails

- Nunca marcar un PR como listo sin que `php artisan test` pase en verde.
- Nunca mergear con cobertura por debajo del 80% en el módulo que se agrega.
- El análisis de seguridad es obligatorio en cualquier código que viene del sistema legacy.
- Reportar siempre los issues con nivel de severidad — nunca "hay que arreglar algo".
- Si se encuentra una vulnerabilidad crítica en producción, abrir un `hotfix/` inmediatamente.
