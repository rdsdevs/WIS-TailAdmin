# CLAUDE.md — WIS ASCUN

Este archivo define los estándares, convenciones y guía de arquitectura del proyecto **WIS ASCUN** para todos los agentes y colaboradores.

---

## Descripción del Proyecto

**WIS ASCUN** es el sistema de gestión integral para la Asociación Colombiana de Universidades (ASCUN). Es una migración del sistema legacy `app.wisascun.com` (PHP sin framework) a **Laravel 12** con una arquitectura moderna, segura y mantenible.

**Módulos del sistema:**
- **Autenticación** — Login con número de documento + fecha de expedición + contraseña
- **Recursos Humanos (RH)** — Empleados, contratistas, contratos, cargos
- **Contabilidad** — Nodos, carteras, informes financieros (NDS, SF, RDS, PRS)
- **Inventario** — Suministros (SUBE) y bienes/equipos/enseres
- **Certificados** — Certificados laborales en PDF con código QR
- **Control de Acceso** — Roles y permisos por institución

---

## Idioma de la Aplicación

> **IMPORTANTE: El idioma de la interfaz y todos los mensajes del sistema es español colombiano.**

- Mensajes de validación, errores, notificaciones y labels: **español colombiano**
- Terminología colombiana: "cédula" (no "DNI"), "nómina", "término fijo/indefinido", "liquidación"
- Fechas al usuario: formato `d/m/Y` (ej: `15/06/1995`)
- Moneda: pesos colombianos `$ 1.500.000` (COP)
- Timezone: `America/Bogota`
- Locale: `es` / Faker locale: `es_CO`

---

## Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | Laravel | 12 |
| PHP | PHP | 8.2+ |
| Frontend reactivo | Livewire | 4.x |
| CSS | Tailwind CSS | v4 |
| JS interactividad | Alpine.js | v3 (vía Livewire) |
| Build | Vite | 7 |
| Testing | Pest | 4.x |
| Roles/Permisos | spatie/laravel-permission | 7.x |
| Auditoría | owen-it/laravel-auditing | 14.x |
| Excel | maatwebsite/excel | 3.x |
| PDF | barryvdh/laravel-dompdf | 3.x |
| QR Codes | simplesoftwareio/simple-qrcode | 4.x |
| DB principal | MySQL | 8.0+ |

---

## Comandos de Desarrollo

```bash
# Iniciar todos los servicios (Laravel + Vite HMR + Queue + Logs)
composer run dev

# Servicios individuales
php artisan serve          # Laravel en http://localhost:8000
npm run dev                # Vite con HMR

# Build producción
npm run build

# Tests (Pest)
php artisan test
php artisan test --coverage --min=80
php artisan test --filter=NombreTest

# Migraciones
php artisan migrate
php artisan migrate:fresh --seed

# Livewire
php artisan livewire:make Modulo/NombreComponente

# Estilo de código
./vendor/bin/pint           # Aplicar correcciones
./vendor/bin/pint --test    # Verificar sin modificar

# Seguridad
composer audit
npm audit
```

---

## Autenticación WIS ASCUN

> El sistema NO usa email para el login. Las credenciales son:
> 1. **Número de documento** (`document_number`)
> 2. **Fecha de expedición del documento** (`document_issued_at`) — tipo `date`
> 3. **Contraseña** (`password`) — bcrypt, NUNCA crypt()

El guard usa Eloquent con un índice compuesto en `[document_number, document_issued_at]`.

---

## Arquitectura

### Patrón en Capas

```
Request HTTP
    └─> Middleware (auth, permission)
            └─> Controller (delgado)
                    └─> FormRequest (validación + autorización)
                            └─> Service (lógica de negocio)
                                    └─> Model / Eloquent (datos)
                                            └─> MySQL
```

**Regla fundamental:** Los controllers solo reciben, delegan y responden. Toda lógica vive en los Services.

### Estructura de Directorios

```
app/
├── Console/Commands/
├── Events/                     # {RecursoAccion}Event.php
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── RH/
│   │   ├── Contabilidad/
│   │   ├── Inventario/
│   │   └── Certificados/
│   ├── Middleware/
│   └── Requests/
│       ├── Auth/
│       ├── RH/
│       └── {Modulo}/
├── Jobs/
├── Listeners/
├── Mail/
├── Models/
│   ├── Concerns/               # HasUuidPrimaryKey y otros traits
│   ├── Auth/
│   ├── RH/
│   ├── Contabilidad/
│   ├── Inventario/
│   └── Certificados/
├── Policies/
├── Providers/
├── Services/
│   ├── Auth/
│   ├── RH/
│   ├── Contabilidad/
│   ├── Inventario/
│   └── Certificados/
└── View/Components/

resources/views/
├── layouts/
│   ├── app.blade.php
│   └── fullscreen-layout.blade.php
├── components/
│   ├── common/
│   └── ui/
└── pages/
    ├── auth/
    ├── rh/
    ├── contabilidad/
    ├── inventario/
    └── certificados/
```

---

## Convenciones de Nombres

### PHP / Laravel

| Elemento | Convención | Ejemplo |
|---|---|---|
| Modelos | `PascalCase` singular | `Employee`, `Contract` |
| Tablas | `snake_case` plural | `employees`, `contracts` |
| Controllers | `PascalCase` + `Controller` | `EmployeeController` |
| Services | `PascalCase` + `Service` | `EmployeeService` |
| Form Requests | Acción + Recurso + `Request` | `CreateEmployeeRequest` |
| Policies | Recurso + `Policy` | `EmployeePolicy` |
| Events | Pasado: recurso + acción | `EmployeeCreated` |
| Jobs | Acción + Sujeto + `Job` | `SendCertificateEmailJob` |
| Migrations | Descriptivas | `create_employees_table` |
| Factories | Recurso + `Factory` | `EmployeeFactory` |

### Frontend

| Elemento | Convención | Ejemplo |
|---|---|---|
| Vistas de página | `kebab-case.blade.php` | `employee-list.blade.php` |
| Componentes Blade | `<x-modulo.nombre />` | `<x-rh.employee-card />` |
| Livewire SFC | `⚡nombre.blade.php` | `⚡employee-form.blade.php` |
| Componentes Livewire | `<livewire:modulo.nombre />` | `<livewire:rh.employee-form />` |

---

## Estándares de Código PHP

### Obligatorio en todos los archivos PHP

```php
<?php

declare(strict_types=1);
```

### Reglas generales

1. **Type hints completos** — parámetros y valor de retorno en todos los métodos
2. **Nunca `mixed`** — salvo que sea estrictamente inevitable
3. **`DB::transaction`** — en toda operación que toque más de una tabla
4. **`SoftDeletes`** — en todos los modelos de entidades de negocio
5. **`HasUuids`** — todos los modelos de negocio usan UUID (no INT autoincrement)
6. **`implements Auditable`** — todos los modelos de negocio registran auditoría
7. **Nunca raw SQL** — usar Eloquent o Query Builder
8. **Nunca lógica en Migrations** — solo DDL (schema)
9. **Nunca lógica en Models** — solo scopes, mutators, relaciones, casts
10. **`$fillable` explícito** — nunca `$guarded = []`

### Modelo base de referencia

```php
<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Employee extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [...];

    protected $casts = [...];
}
```

---

## Seguridad — Reglas No Negociables

| Regla | Descripción |
|---|---|
| **Sin SQL raw** | Usar Eloquent o QueryBuilder. Nunca `DB::statement("... $var ...")` |
| **Validación siempre** | Todo input del usuario pasa por FormRequest |
| **CSRF en formularios** | `@csrf` en todo `<form>` POST/PUT/DELETE |
| **Policy en CRUD** | `$this->authorize()` o `->authorizeResource()` en controllers |
| **Sin credenciales en código** | Passwords y tokens solo en `.env` |
| **bcrypt para passwords** | `Hash::make()` o `protected $casts = ['password' => 'hashed']` |
| **Output escaping** | `{{ $var }}` en Blade, nunca `{!! $var !!}` sin sanitizar |
| **Cookies seguras** | `SESSION_SECURE_COOKIE=true` en producción |
| **display_errors=0** | Siempre en producción |

---

## Base de Datos

### Convenciones de Schema

- **PK:** `$table->uuid('id')->primary()`
- **FK a UUID:** `$table->foreignUuid('user_id')->constrained()->cascadeOnDelete()`
- **Soft deletes:** `$table->softDeletes()` en todas las entidades de negocio
- **Timestamps:** `$table->timestamps()` siempre
- **Índices:** Crear índice en columnas de búsqueda frecuente

### Multi-tenancy

El sistema es multi-institución. La columna `institution_id` (UUID) está presente en todas las entidades de negocio. El filtro se aplica a nivel de Service, **nunca** asumiendo que el usuario actual solo ve sus datos sin verificar.

---

## Roles del Sistema

```
super-admin          → Acceso total al sistema
admin                → Administración de su institución
rh-manager           → Gestión RH completa
rh-viewer            → Solo consulta módulo RH
accounting-manager   → Contabilidad completa
accounting-viewer    → Solo consulta contabilidad
inventory-manager    → Inventario completo
inventory-viewer     → Solo consulta inventario
```

---

## Tests

- **Framework:** Pest 4.x
- **Cobertura mínima:** 80%
- **Ubicación:** `tests/Feature/{Modulo}/` y `tests/Unit/{Modulo}/`
- **Descripción de tests en español** para mayor claridad del equipo colombiano
- **Cubrir siempre:** happy path, errores de validación, autorización denegada

```bash
php artisan test
php artisan test --coverage --min=80
```

---

## GitFlow y Commits

Ver `.claude/agents/gitflow.md` para el flujo completo.

**Resumen:**
- Ramas: `feature/WIS-{n}-{descripcion}`, `fix/WIS-{n}-...`, `hotfix/WIS-{n}-...`
- Commits en **español**, presente imperativo
- Tipos: `feat`, `fix`, `security`, `migration`, `test`, `refactor`, `chore`, `docs`
- Siempre incluir `Refs: WIS-{ticket}` en el footer

**Ejemplo:**
```
feat(rh): agregar generación de certificado laboral en PDF

Implementa la descarga del certificado con firma, cargo,
salario y código QR de verificación.

Refs: WIS-45
```

---

## MCPs Configurados

| MCP | Uso |
|---|---|
| **Figma** | Consultar diseños y exportar tokens para las vistas del sistema |
| **Notion** | Gestión del proyecto, tickets WIS, documentación y planificación de sprints |

Configurar en `.env`:
```
FIGMA_API_KEY=your_figma_token
NOTION_API_TOKEN=your_notion_token
```

---

## Agentes de Claude Code

| Agente | Cuándo usarlo |
|---|---|
| `frontend` | Crear/modificar Blade components, Livewire, vistas, estilos Tailwind |
| `backend` | Modelos, migrations, services, controllers, form requests, policies |
| `gitflow` | Crear ramas, hacer commits, gestionar PRs y releases |
| `qa` | Escribir tests, code reviews, auditoría de seguridad |

---

## Skills Disponibles

| Skill | Descripción |
|---|---|
| `/nueva-funcionalidad` | Guía completa para implementar un nuevo módulo o entidad |
| `/migrar-modulo` | Proceso para migrar un módulo desde app.wisascun.com |
| `/revision-codigo` | Revisión de calidad y seguridad del código |

---

## Agregar Nuevas Páginas

1. Ruta en `routes/web.php` con middleware `auth`
2. Controller en `app/Http/Controllers/{Modulo}/`
3. Service en `app/Services/{Modulo}/`
4. Vista en `resources/views/pages/{modulo}/`
5. Nav item en `app/Helpers/MenuHelper.php`
6. Tests en `tests/Feature/{Modulo}/`
