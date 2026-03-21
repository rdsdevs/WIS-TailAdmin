---
nombre: mysql-wis
descripcion: >
  Diseñar y revisar el esquema MySQL para WIS ASCUN en Laravel 12. Usar cuando se creen
  o modifiquen migraciones, índices, relaciones o consultas Eloquent. Aplica durante la
  migración desde el sistema legacy (INT auto-increment → UUID, stored procedures → Services),
  al optimizar reportes financieros lentos, o al planificar el schema multi-institución.
---

# SKILL: MySQL — WIS ASCUN Laravel 12

Usa esta guía para tomar decisiones de base de datos seguras y medibles en el proyecto WIS ASCUN.

> **Contexto del proyecto:** WIS ASCUN opera con **2 bases de datos MySQL**:
> - `sandbox_wisascun` — financiero/operativo (conexión principal `mysql`)
> - `sandbox_rh_ascun` — Recursos Humanos (conexión secundaria `rh_ascun`)
>
> Ambas conexiones declaradas en `config/database.php`. Toda consulta debe ejecutarse
> a través de **Eloquent o Query Builder de Laravel**. Nunca SQL en crudo sin justificación explícita.
> Toda entidad de negocio usa **UUID como clave primaria**.

---

## Flujo de Trabajo

1. Revisar el esquema legacy en `/home/rdsdev/projects-rds/WISASCUN/app.wisascun.com/mysql/` antes de diseñar.
2. Proponer el cambio mínimo necesario con sus ventajas y compromisos.
3. Validar con `EXPLAIN` / `EXPLAIN ANALYZE` en consultas de reportes.
4. Para cambios en staging/producción, incluir pasos de rollback (`down()`) y verificación post-deploy.
5. Solicitar aprobación antes de operaciones destructivas (`DROP`, `TRUNCATE`, `DELETE` masivo).

---

## Diseño del Schema

### Claves primarias (OBLIGATORIO)

```php
// ✅ UUID en todas las entidades de negocio
$table->uuid('id')->primary();

// ✅ FK hacia UUID
$table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
```

**Nunca** usar `$table->id()` (BIGINT auto-increment) en modelos de negocio. Solo en tablas de soporte (cache, jobs, audits).

### Multi-institución (multi-tenancy)

Toda tabla de negocio lleva `institution_id`:

```php
$table->uuid('id')->primary();
$table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
// ... resto de columnas ...
$table->index('institution_id'); // índice obligatorio
```

El filtro por `institution_id` se aplica en el Service, no en el Controller ni en el Model scope automático.

### Tipos de datos

- `utf8mb4` / `utf8mb4_0900_ai_ci` — obligatorio para nombres con tildes en español colombiano.
- `DATETIME` en vez de `TIMESTAMP` — evita problemas de zona horaria (`America/Bogota`).
- Montos monetarios en COP: `DECIMAL(14, 2)` — nunca FLOAT para valores financieros.
- Catálogos (tipos de contrato, tipos de documento) → tabla lookup, no `ENUM`.
- `NOT NULL` con valores por defecto explícitos siempre que sea posible.

### Convenciones (alineadas con Laravel)

| Elemento | Convención | Ejemplo |
|---|---|---|
| Tablas | plural snake_case | `employees`, `contract_extensions` |
| FK | `{singular}_id` | `employee_id`, `institution_id` |
| Tablas pivote | orden alfabético | `model_has_roles` (spatie) |
| Auditoría | `created_at`, `updated_at`, `deleted_at` | (soft deletes en entidades de negocio) |

---

## Migrations

### Estructura base para entidad de negocio

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 10);
            $table->string('document_number', 30)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->decimal('salary', 14, 2);
            $table->enum('contract_type', ['indefinite', 'fixed_term', 'contractor']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Índices de búsqueda frecuente
            $table->index('institution_id');
            $table->index(['document_type', 'document_number']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
```

### Tabla `users` — Autenticación WIS ASCUN

```php
Schema::create('users', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
    $table->string('document_type', 10);
    $table->string('document_number', 30);
    $table->date('document_issued_at');        // Segunda credencial de login
    $table->string('name', 150);
    $table->string('email', 150)->nullable()->unique();
    $table->string('password');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->softDeletes();
    $table->timestamps();

    // Índice compuesto para login — crítico para rendimiento
    $table->unique(['document_number']);
    $table->index(['document_number', 'document_issued_at']);
});
```

---

## Indexación

- **Regla del prefijo izquierdo** en índices compuestos: columna de igualdad primero, luego rango/orden.
- Los filtros de rango (`BETWEEN`, `>`, `<`) detienen el uso del índice en columnas subsecuentes.
- Auditar índices no usados vía `performance_schema` — eliminar los que tengan `count_read = 0`.

### Índices prioritarios para WIS ASCUN

```sql
-- Login de usuarios (igualdad + igualdad)
INDEX idx_users_login (document_number, document_issued_at)

-- Reportes financieros por nodo y período
INDEX idx_nodes_institution_period (institution_id, period_year, period_month)

-- Contratos por empleado y vigencia
INDEX idx_contracts_employee_dates (employee_id, start_date, end_date)

-- Certificados por empleado
INDEX idx_certificates_employee (employee_id, created_at)

-- Inventario por institución y categoría
INDEX idx_elements_institution_category (institution_id, category_id, is_active)
```

---

## Optimización de Consultas

### Paginación por cursor (NO offset)

Para DataTables con Livewire — evitar `OFFSET` en tablas grandes:

```php
// ❌ Lento en tablas grandes
Employee::paginate(15); // genera LIMIT 15 OFFSET 150000

// ✅ Cursor pagination para listados grandes
Employee::cursorPaginate(15);
```

### Problema N+1 con Eager Loading

```php
// ❌ N+1 — una query por cada empleado
$employees = Employee::all();
foreach ($employees as $e) { echo $e->position->name; }

// ✅ Correcto con eager loading
$employees = Employee::with(['position', 'institution', 'contracts'])->get();

// Para reportes con múltiples relaciones
$contracts = Contract::with(['employee.position', 'institution'])
    ->where('institution_id', $institutionId)
    ->whereDate('end_date', '>=', now())
    ->get();
```

### Evitar funciones sobre columnas indexadas

```php
// ❌ No usa el índice
->whereRaw('YEAR(start_date) = ?', [2025])

// ✅ Usa el índice
->whereBetween('start_date', ['2025-01-01', '2025-12-31'])
```

---

## Transacciones

Obligatorio en operaciones que afectan múltiples tablas:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($data): void {
    $employee = Employee::create($data['employee']);
    $employee->contracts()->create($data['contract']);
    // owen-it registra auditoría automáticamente dentro de la transacción
});

// Con reintento ante deadlocks (inventario con asignaciones simultáneas)
DB::transaction(function () { ... }, attempts: 3);
```

---

## Migración de Datos Legacy

Al poblar las nuevas tablas desde el sistema `app.wisascun.com`:

```php
// En data migrations — usar chunking para tablas grandes
DB::connection('wisascun_legacy')
    ->table('usuarios')
    ->orderBy('id')
    ->chunk(500, function ($filas): void {
        foreach ($filas as $fila) {
            User::create([
                'id'                 => Str::uuid(),
                'document_number'    => $fila->numdoc,
                'document_issued_at' => $fila->expdoc,
                'name'               => $fila->nombre,
                'password'           => Hash::make($fila->temp_password),
                // Re-hash OBLIGATORIO — nunca migrar crypt() directo
            ]);
        }
    });
```

### Stored Procedures → Services

Los stored procedures del legacy (`SP_PERMISOS_DE_USUARIO`, `SP_CHANGE_POSITION`, etc.) se migran a Service classes:

```php
// ❌ Legacy
$stmt->prepare("CALL SP_PERMISOS_DE_USUARIO($documento_usuario)");

// ✅ Laravel — UserService::getPermissions(string $documentNumber)
```

---

## Conexiones Múltiples

```php
// config/database.php
'connections' => [
    'mysql' => [
        'driver'   => 'mysql',
        'host'     => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'sandbox_wisascun'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'charset'  => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
        'timezone'  => '+00:00',
    ],
    'rh_ascun' => [
        'driver'   => 'mysql',
        'host'     => env('DB_RH_HOST', '127.0.0.1'),
        'database' => env('DB_RH_DATABASE', 'sandbox_rh_ascun'),
        'username' => env('DB_RH_USERNAME'),
        'password' => env('DB_RH_PASSWORD'),
        'charset'  => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
    ],
],
```

En modelos que usen la conexión secundaria:

```php
class RHCollaborator extends Model
{
    protected $connection = 'rh_ascun';
}
```

---

## Guardrails

- Nunca SQL en crudo con variables interpoladas — siempre `?` o `:param` con binding.
- **Solicitar aprobación explícita** antes de `DROP TABLE`, `TRUNCATE` o `DELETE` masivo sobre tablas financieras (`nodes`, `wallets`, `initial_balances`) o de RH (`employees`, `contracts`).
- Nunca ejecutar queries destructivas en producción sin haber probado en staging.
- El `down()` de cada migration debe revertir exactamente lo que hace el `up()`.
- Los UUIDs se generan en Laravel (`HasUuids`), no en MySQL (`UUID()` de MySQL).
- Preferir `EXPLAIN` sobre suposiciones de rendimiento.
