# CONTEXT.md — WIS ASCUN

Contexto completo del proyecto para orientar nuevas conversaciones y agentes.
**Última actualización:** 30/03/2026

---

## Descripción del Proyecto

**WIS ASCUN** es el sistema de gestión integral para la **Asociación Colombiana de Universidades (ASCUN)**. Es una migración del sistema legacy `app.wisascun.com` (PHP sin framework, con vulnerabilidades críticas: SQL injection, crypt() obsoleto, sin CSRF) a **Laravel 12** con arquitectura moderna, segura y mantenible.

- **Institución principal:** ASCUN — NIT: 860006560
- **Idioma UI:** Español colombiano (fechas: d/m/Y, moneda: COP, timezone: America/Bogota)
- **URL local de desarrollo:** `http://127.0.0.1:8000`

---

## Stack Tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Backend | Laravel | 12.x |
| PHP | PHP | 8.2+ |
| Frontend reactivo | Livewire + Volt (SFC) | 4.2 / 1.10 |
| CSS | Tailwind CSS | v4 |
| JS interactividad | Alpine.js | v3 (vía Livewire) |
| Build | Vite | 7 |
| Testing | Pest | 4.x |
| Roles/Permisos | spatie/laravel-permission | 7.2 |
| Auditoría | owen-it/laravel-auditing | 14.x |
| Excel | maatwebsite/excel | 3.x |
| PDF | barryvdh/laravel-dompdf + carlos-meneses/laravel-mpdf | 3.x / 2.x |
| QR Codes | simplesoftwareio/simple-qrcode + endroid/qr-code | 4.x / 5.x |
| HTML Purifier | mews/purifier | 3.x |
| DB principal | MySQL | 8.0+ |
| DB tests | SQLite in-memory | — |

---

## Estado Actual por Módulo

| Módulo | Estado | Notas |
|--------|--------|-------|
| **Autenticación** | ✅ Completo | Login: cédula + fecha expedición + password. Vista rediseñada con identidad visual ASCUN (gradiente azul, split-screen, notificación de nueva versión) |
| **Recursos Humanos** | ✅ Completo | Colaboradores, contratos, cargos, departamentos, importaciones, notificaciones |
| **Certificados** | ✅ Completo | PDF+QR, firmas digitales, verificación pública por URL. Firmas accesibles para contractor-manager y employee-manager. Auto-fill de firmante desde empleados con buscador estilo contratos |
| **Admin / Usuarios** | ✅ Completo | CRUD usuarios con roles e instituciones |
| **Contabilidad** | 🔶 Parcial | Modelos + migraciones únicamente. Sin controllers, services ni vistas |
| **Inventario** | ❌ Pendiente | Nada implementado |

---

## Autenticación

El sistema NO usa email. Las credenciales son:
1. **Número de documento** (`document_number`)
2. **Fecha de expedición del documento** (`document_issued_at`) — tipo `date`
3. **Contraseña** (`password`) — bcrypt

Guard Eloquent con índice compuesto `[document_number, document_issued_at]`.

---

## Roles del Sistema

```
super-admin         → Acceso total, bypass de todas las policies (before())
admin               → Administración completa de su institución
rh-manager          → Gestión RH completa (empleados + contratistas)
rh-viewer           → Solo consulta módulo RH
employee-manager    → Gestión RH solo de EMPLEADOS (no puede tocar Contratistas)
contractor-manager  → Gestión RH solo de CONTRATISTAS (no puede tocar Empleados)
accounting-manager  → Contabilidad completa
accounting-viewer   → Solo consulta contabilidad
inventory-manager   → Inventario completo
inventory-viewer    → Solo consulta inventario
```

### Restricciones por tipo en CollaboratorPolicy (IMPORTANTE)

Los roles `employee-manager` y `contractor-manager` tienen restricciones de tipo en TODAS las acciones:

| Acción | `employee-manager` | `contractor-manager` |
|--------|-------------------|---------------------|
| `view()` | Solo Empleados | Solo Contratistas |
| `update()` | Solo Empleados | Solo Contratistas |
| `delete()` | Solo Empleados | Solo Contratistas |
| `changeType()` | Solo Empleados | Solo Contratistas |
| `import()` | Solo tipo 'Empleado' | Solo tipo 'Contratista' |

---

## Usuarios del Sistema (Producción ASCUN)

| Nombre | Documento | Rol |
|--------|-----------|-----|
| SUPER ADMINISTRADOR | 72295936 | super-admin |
| HILIANET BARBOSA REYES | 37324165 | accounting-manager |
| CAROLINA HENAO MONTOYA | 63549971 | admin |
| ANA ISABEL REYES TORRES | 52857025 | contractor-manager |
| PAULA ANDREA VELASCO | 53135875 | inventory-manager |
| JOAN SEBASTIÁN AREVALO | 1014233042 | admin |
| YESENIA KATERIN ROJAS MORENO | 1026283309 | employee-manager |
| INGRID TATIANA CAICEDO | 1033815362 | admin |
| JORGE BERNAL | 80759183 | accounting-manager |
| JOHANNA MONTAÑEZ | 1012410970 | accounting-manager |

Contraseña temporal de desarrollo: `Wis2026*`

---

## Arquitectura

### Patrón en Capas
```
HTTP Request
  → Middleware (auth, permission)
    → Controller (delgado — solo recibe, delega y responde)
      → FormRequest (validación + autorización básica)
        → Service (toda la lógica de negocio)
          → Model / Eloquent
            → MySQL
```

### Livewire Volt SFC
Los componentes Livewire usan **Single File Components (SFC)** con Volt. La clase PHP va embebida en el archivo `.blade.php`:

```php
<?php
use Livewire\Volt\Component;

new class extends Component {
    // lógica del componente
};
?>
<div>
    {{-- template blade --}}
</div>
```

**VoltServiceProvider** monta los directorios:
- `resources/views/livewire` — componentes reactivos
- `resources/views/pages` — páginas con componentes Volt

---

## Modelos y Tablas

### Módulo RH

| Modelo | Tabla | Notas |
|--------|-------|-------|
| `Collaborator` | `collaborators` | type: Empleado/Contratista, is_company |
| `Contract` | `contracts` | status: Vigente/Liquidado/Terminado Anticipadamente |
| `ContractExtension` | `contract_extensions` | Prórrogas de contrato |
| `ContractType` | `contract_types` | Tipos de contrato institucionales |
| `CommittedValue` | `committed_values` | Valores comprometidos por contrato |
| `Department` | `departments` | Dependencias institucionales |
| `Position` | `positions` | Cargos por departamento |
| `PositionEmail` | `position_emails` | Correos por cargo (SoftDeletes+Auditable) |
| `PositionFunction` | `position_functions` | Funciones por cargo |
| `PositionChangeHistory` | `position_change_history` | Historial de cambios de cargo en contratos |
| `DocumentType` | `document_types` | Tipos de documento por institución |
| `CollaboratorStatus` | `collaborator_statuses` | Estados de colaborador |
| `CertificateSignature` | `certificate_signatures` | Firmas para certificados |
| `EmployeeProfile` | `employee_profiles` | Perfil de seguridad social del empleado |
| `PayrollContractDetail` | `payroll_contract_details` | Desglose salarial y parafiscales |

### Módulo Certificados

| Modelo | Tabla | Notas |
|--------|-------|-------|
| `Certificate` | `certificates` | Certificados laborales generados |

### Módulo Contabilidad (solo modelos, sin implementar)

| Modelo | Tabla | Notas |
|--------|-------|-------|
| `AccountingAccount` | `accounting_accounts` | Cuentas contables |
| `CostCenter` | `cost_centers` | Centros de costo |

### Modelos Base

| Modelo | Tabla | Notas |
|--------|-------|-------|
| `User` | `users` | Autenticación WIS (document_number + document_issued_at) |
| `Institution` | `institutions` | Instituciones educativas ASCUN |

### Convenciones de todos los modelos de negocio

```php
class MiModelo extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;  // UUID como PK
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [...]; // SIEMPRE explícito
}
```

---

## Migraciones (37 archivos)

```
0001_01_01_000000  create_users_table
0001_01_01_000001  create_cache_table
0001_01_01_000002  create_jobs_table
2026_03_20_223902  create_permission_tables
2026_03_20_230000  create_institutions_table
2026_03_20_230001  modify_users_table_for_wis_auth
2026_03_21_000001  create_departments_table
2026_03_21_000002  create_positions_table
2026_03_21_100001  create_document_types_table
2026_03_21_100002  create_collaborator_statuses_table
2026_03_21_100003  create_contract_types_table
2026_03_21_100004  add_columns_to_departments_table
2026_03_21_100005  add_columns_to_positions_table
2026_03_21_100006  create_collaborators_table
2026_03_21_100007  recreate_contracts_table
2026_03_21_100008  create_contract_extensions_table
2026_03_21_100009  create_committed_values_table
2026_03_21_100010  create_position_functions_table
2026_03_21_100011  create_position_change_history_table
2026_03_21_100012  create_certificate_signatures_table
2026_03_22_000001  update_committed_values_table
2026_03_22_140746  create_audits_table
2026_03_22_153343  make_collaborator_name_fields_nullable
2026_03_22_153606  make_collaborator_document_type_nullable
2026_03_22_200001  create_accounting_accounts_table
2026_03_22_200002  create_cost_centers_table
2026_03_23_153010  create_notifications_table
2026_03_24_000001  extend_contract_extensions_for_prorogas
2026_03_24_000002  add_early_termination_to_contracts
2026_03_25_100001  create_certificates_table
2026_03_25_100002  add_logo_to_institutions_table
2026_03_28_161032  create_employee_profiles_table
2026_03_28_161033  add_performance_indices_to_rh_tables
2026_03_28_161034  create_payroll_contract_details_table
2026_03_28_161035  create_position_emails_table          ← sin softDeletes (agregado por 190019)
2026_03_28_190019  add_soft_deletes_to_position_emails_table
```

> **Nota:** `position_emails` tiene SoftDeletes en dos pasos: la tabla fue creada sin él y se añadió con la migración `190019` para corregir la BD de producción. Los tests corren ambas en SQLite fresh sin problema.

---

## Controllers

| Controller | Ruta base | Descripción |
|-----------|-----------|-------------|
| `Auth/LoginController` | `/login` | Login WIS con cédula+fecha+password |
| `Admin/UserController` | `/admin/usuarios` | CRUD de usuarios |
| `DashboardController` | `/dashboard` | Panel principal |
| `RH/CollaboratorController` | `/rh/colaboradores` | CRUD colaboradores + cambio de tipo |
| `RH/CollaboratorImportController` | `/rh/colaboradores/importar` | Importación masiva Excel |
| `RH/ContractController` | `/rh/contratos` | CRUD contratos + terminación anticipada |
| `RH/ContractImportController` | `/rh/contratos/importar` | Importación masiva Excel |
| `RH/DepartmentController` | `/rh/departamentos` | CRUD departamentos |
| `RH/PositionController` | `/rh/cargos` | CRUD cargos |
| `Certificados/CertificateController` | `/certificados` | Generación y descarga PDF |
| `Certificados/CertificateSignatureController` | `/certificados/firmas` | CRUD firmas digitales |
| `Certificados/CertificateVerificationController` | `/verificar/{uuid}` | Verificación pública (sin auth) |

---

## Services

| Service | Descripción |
|---------|-------------|
| `Auth/AuthService` | Lógica de login y logout |
| `UserService` | Creación/actualización de usuarios |
| `RH/CollaboratorService` | CRUD + changeType + export colaboradores |
| `RH/ContractService` | CRUD + terminación anticipada de contratos |
| `RH/ContractProrogaService` | Aplicación de prórrogas |
| `RH/PositionService` | CRUD cargos |
| `RH/DepartmentService` | CRUD departamentos |
| `RH/ImportResult` | DTO resultado de importación colaboradores |
| `RH/ImportContractResult` | DTO resultado de importación contratos |
| `Certificados/CertificateService` | Generación PDF + QR de certificados |
| `Certificados/CertificateSignatureService` | CRUD firmas digitales |

---

## Jobs Asíncronos

| Job | Descripción | Timeout |
|-----|-------------|---------|
| `RH/ImportCollaboratorsJob` | Importa colaboradores desde Excel, guarda resultado en caché, notifica al usuario | 300s |
| `RH/ImportContractsJob` | Importa contratos + valores comprometidos, guarda resultado en caché, notifica | 600s |
| `RH/TerminateExpiredContractsJob` | Termina automáticamente contratos vencidos | — |

**Storage:** Todos los jobs usan `Storage::disk('local')->path($filePath)` — NO usar `storage_path()` para respetar `Storage::fake()` en tests.

---

## Policies

| Policy | Modelos protegidos |
|--------|-------------------|
| `RH/CollaboratorPolicy` | Collaborator — incluye restricciones por tipo para employee-manager/contractor-manager |
| `RH/ContractPolicy` | Contract — contractor-manager NO puede hacer terminación anticipada |
| `RH/PositionPolicy` | Position |
| `RH/DepartmentPolicy` | Department |
| `Certificados/CertificatePolicy` | Certificate |
| `Certificados/CertificateSignaturePolicy` | CertificateSignature — `MANAGERS = ['admin', 'rh-manager', 'contractor-manager', 'employee-manager']` |
| `UserPolicy` | User |

Todas registradas en `AppServiceProvider` con `Gate::policy()`.

---

## Notificaciones (15 total)

**Auth (3):**
- `UserCreatedNotification`
- `PasswordChangedByUserNotification`
- `PasswordResetByAdminNotification`

**RH (12):**
- `CollaboratorCreatedNotification`
- `CollaboratorUpdatedNotification`
- `CollaboratorDeletedNotification`
- `CollaboratorTypeChangedNotification`
- `CollaboratorImportCompletedNotification`
- `ContractCreatedNotification`
- `ContractUpdatedNotification` (requiere: contract_code, contract_id, collaborator_name)
- `ContractTerminatedNotification` (requiere: contract_code, contract_id, collaborator_name)
- `ContractEarlyTerminatedNotification`
- `ContractDeletedNotification`
- `ContractProrogaAppliedNotification`
- `ContractImportCompletedNotification`

---

## Componentes Livewire Volt (SFC)

```
resources/views/livewire/
├── counter.blade.php                       (demo)
├── command-palette.blade.php
├── header/
│   └── notification-dropdown.blade.php
├── profile/
│   └── change-password.blade.php
└── rh/
    ├── collaborator-form.blade.php         (formulario crear/editar colaborador)
    ├── collaborator-list.blade.php         (lista con filtros)
    ├── collaborator-import.blade.php       (importación masiva)
    ├── contract-form.blade.php             (formulario crear/editar contrato)
    ├── contract-list.blade.php             (lista contratos de un colaborador)
    ├── contract-import.blade.php           (importación masiva contratos)
    ├── contract-proroga-modal.blade.php    (modal prórroga)
    ├── contract-early-termination-modal.blade.php  (modal terminación anticipada)
    ├── timeline-contratos.blade.php        (timeline visual de contratos)
    ├── position-change-modal.blade.php     (modal cambio de cargo — Gate::authorize en save())
    ├── position-manager.blade.php          (gestión de cargos)
    └── employee-dashboard.blade.php        (dashboard empleado)
```

### Lógica de wizards (pasos condicionales)

#### `collaborator-form` — flujo según tipo

| Tipo | Flujo de pasos |
|------|----------------|
| Empleado | 1 Tipo → 2 Datos → 3 Seguridad Social → 4 Contacto |
| Contratista | 1 Tipo → 2 Datos → 3 Contacto (paso 3 interno = 4) |

- **Seguridad Social (paso 3):** solo visible si `$type === 'Empleado'`
- Toggle `$employeeProfileEnabled` (off por defecto): habilita EPS, pensión, ARL, caja, cesantías, grupo sanguíneo, contacto emergencia, verificación Ley 1918
- Sueldo base siempre editable cuando el paso es visible
- `updatedType()` resetea `$step = 1` y limpia campos al cambiar a Contratista
- `save()` guarda `employeeProfile` solo si `type === 'Empleado' && employeeProfileEnabled`
- En edición: si hay `employeeProfile` existente, `$employeeProfileEnabled` se inicializa en `true`

#### `contract-form` — flujo según tipo y año

| Tipo | Año inicio | Flujo de pasos |
|------|-----------|----------------|
| Empleado | cualquiera | 1 Colaborador → 2 Contrato → 3 Nómina → guardar |
| Contratista | >= 2025 | 1 Colaborador → 2 Contrato → 4 Contable → guardar |
| Contratista | < 2025 | 1 Colaborador → 2 Contrato → guardar |

- **Nómina (paso 3):** solo visible si `$collaboratorType === 'Empleado'`
  - "Sueldo Básico" siempre editable en el paso
  - Toggle `$payrollDetailEnabled` (off por defecto): habilita Auxilio de Transporte, Bonificaciones No Prestacionales, Parafiscales (SENA/ICBF/Caja), Examen Médico de Ingreso
- **Contable (paso 4):** solo visible si `$collaboratorType === 'Contratista'` y `contractYear >= 2025` (`needsCommitted`)
- `save()` tiene guard: si `$collaboratorType` vacío → error y redirige a paso 1
- `save()` guarda `payrollDetail` solo si `collaboratorType === 'Empleado' && payrollDetailEnabled`
- En edición: `payrollDetail()->delete()` cuando el toggle está desactivado
- "Atrás" desde paso 4 siempre regresa al paso 2 (no al 3)

---

## Tests (216 tests, 438 assertions — 100% verde)

```
tests/Feature/
├── Auth/LoginTest.php
├── Admin/UserTest.php
├── Notifications/NotificationTest.php
├── RH/
│   ├── CollaboratorTest.php           (CRUD + permisos generales)
│   ├── CollaboratorTypeTest.php       (visibilidad/edición/eliminación por tipo de rol)
│   ├── CollaboratorImportTest.php     (importación masiva)
│   ├── ContractTest.php               (CRUD + terminación anticipada)
│   ├── ContractHistoricalRulesTest.php (reglas históricas)
│   ├── ContractImportTest.php         (importación masiva)
│   └── DepartmentPositionTest.php     (cargos y departamentos)
└── ExampleTest.php
```

---

## Rutas Principales (`routes/web.php`)

```
GET  /login                              → Auth/LoginController@create
POST /login                              → Auth/LoginController@store
POST /logout                             → Auth/LoginController@destroy

GET  /dashboard                          → DashboardController@index

# Admin
GET|POST         /admin/usuarios         → Admin/UserController (resource)
GET|PUT|DELETE   /admin/usuarios/{user}

# RH — Colaboradores
GET  /rh/colaboradores                   → CollaboratorController@index
GET  /rh/colaboradores/importar          → CollaboratorImportController@create
POST /rh/colaboradores/importar          → CollaboratorImportController@store
GET  /rh/colaboradores/plantilla/{tipo}  → CollaboratorImportController@template
GET  /rh/colaboradores/exportar/{tipo}   → CollaboratorController@export
POST /rh/colaboradores/{collaborator}/change-type → CollaboratorController@changeType
resource /rh/colaboradores               → CollaboratorController (parámetro: {collaborator})

# RH — Contratos
GET  /rh/contratos/importar              → ContractImportController@create
POST /rh/contratos/importar              → ContractImportController@store
GET  /rh/contratos/plantilla             → ContractImportController@template
resource /rh/contratos                   → ContractController (parámetro: {contract})

# RH — Departamentos y Cargos
resource /rh/departamentos               → DepartmentController
resource /rh/cargos                      → PositionController

# Certificados
resource /certificados                   → CertificateController
resource /certificados/firmas            → CertificateSignatureController
GET /verificar/{uuid}                    → CertificateVerificationController@show (público)
```

---

## Git

- **Rama activa:** `feature/WIS-002-modulo-rh`
- **Rama principal:** `main`
- **Estado:** Limpio — solo `.claude/skills/` y `plan_ajuste_rh_nomina.md` sin trackear (excluidos intencionalmente)

### Commits recientes (últimos en esta rama)

```
fa4076a  feat(certificados): reemplazar select de firmante por buscador con autocompletado
266b545  feat(certificados): auto-completar firmante desde empleados en gestión de firmas
22f3c96  feat(certificados): ampliar acceso a firmas digitales para contractor-manager y employee-manager
26c8246  fix(dashboard): revertir colores del button group del rol contractor-manager al estilo estándar
9b69db2  feat(dashboard): unificar accesos rápidos de contractor-manager con estilo btn-primary
c61f4e6  feat(dashboard): mejorar métricas y navegación del rol contractor-manager
de3f190  fix(auth): ampliar ancho del banner de nueva versión a max-w-lg
1ccdcef  fix(auth): corregir posición del banner de nueva versión en login
53c05b9  feat(auth): rediseñar vista de login con identidad visual ASCUN
699b15b  fix(rh): corregir wizard de colaboradores y formulario de contratos
47ee57f  feat(rh): condicionar pasos wizard según tipo de colaborador
4164b9d  docs: agregar CONTEXT.md con estado completo del proyecto
8f9fd14  fix(migration): evitar columna deleted_at duplicada en position_emails
bfa18f2  chore(auth): actualizar roles de usuarios en UserSeeder
```

---

## Próximos Módulos a Implementar

### Contabilidad (modelos y migraciones existen)
Falta implementar:
- Controllers: `ContabilidadController` (nodos, carteras, informes NDS/SF/RDS/PRS)
- Services: `ContabilidadService`
- Views: `resources/views/pages/contabilidad/`
- Rutas en `routes/web.php`
- Nav item ya existe en `MenuHelper::getContabilidadNavItems()` (verificar)

Modelos disponibles: `AccountingAccount`, `CostCenter`

### Inventario (nada implementado)
Falta todo:
- Modelos: Suministros (SUBE) + Bienes/Equipos/Enseres
- Migraciones
- Controllers, Services, Policies
- Views
- Rutas

---

## Patrones y Decisiones Técnicas Importantes

### Multi-tenancy
Toda entidad de negocio tiene `institution_id`. El filtro se aplica en el Service, no en el Model. Nunca asumir que el usuario solo ve datos de su institución sin verificar explícitamente.

### Storage en Jobs
```php
// CORRECTO — respeta Storage::fake() en tests
$absolutePath = Storage::disk('local')->path($this->filePath);
Storage::delete($this->filePath);

// INCORRECTO — evita el disco configurado
$absolutePath = storage_path('app/private/' . $this->filePath);
```

### Importación de archivos
Los archivos se suben al disco `local` (root: `storage/app/private/`). La ruta relativa del archivo se pasa al Job. El Job limpia el archivo tanto en `handle()` como en `failed()`.

### Cambio de tipo de colaborador
Un colaborador puede cambiar entre `Empleado` y `Contratista` solo si:
1. No es empresa (`is_company = false`)
2. No tiene contratos con `status = 'Vigente'`
3. El usuario tiene permiso según su rol y el tipo actual del colaborador

### Route Model Binding
Los parámetros de ruta y los parámetros del controller deben tener el mismo nombre:
```php
// Ruta: {collaborator}  →  Controller: Collaborator $collaborator  ✓
// Ruta: {collaborator}  →  Controller: Collaborator $colaborador   ✗ (binding falla)
```

### Blade y policies con class string
Usar `@can('create', Model::class)` para acciones sin instancia. **NUNCA** `@can('update', Model::class)` cuando la firma de `update()` requiere instancia (`update(User $user, Model $model)`) — causará `ArgumentCountError`.

### Caché de resultados de importación
Los resultados de importación se guardan en caché con key `import_result_{userId}` (colaboradores) o `import_contracts_result_{userId}` (contratos), TTL 2 horas.

### Patrón de buscador con autocompletado (Alpine.js)
El buscador de colaborador en contratos (`contract-form.blade.php`) es la **referencia visual estándar** para cualquier campo de búsqueda con selección en el sistema. Tiene tres estados:

1. **Sin selección** — input con clases: `w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm ... focus:border-blue-500 focus:ring-1 focus:ring-blue-500`
2. **Seleccionado** — tarjeta azul: `rounded-lg border border-blue-300 bg-blue-50 px-3 py-2.5` con avatar de iniciales (`h-9 w-9 rounded-full bg-blue-100`) + nombre + dato secundario + botón X
3. **Sin resultados** — mensaje `"No se encontraron..."` en dropdown

Este patrón está implementado en `certificados/firmas/create.blade.php` y `edit.blade.php` para el campo "Nombre del firmante" (filtrado client-side con Alpine, datos pre-cargados via `@json($empleados)`).

### Datos interpolados en Alpine `x-data`
Siempre usar `@js()` para interpolar valores PHP dentro de strings JavaScript en `x-data`. Nunca usar comillas simples con `{{ }}` — se rompe con nombres que contengan apóstrofes (ej: `O'Connor`):
```blade
{{-- CORRECTO --}}
selectedNombre: @js(old('signer_name', $model->field)),

{{-- INCORRECTO — quiebra con apóstrofes --}}
selectedNombre: '{{ old('signer_name', $model->field) }}',
```

### Dashboard por rol (`contractor-manager`)
- Métricas: Certificaciones (total / verificadas / por verificar) — proxy: `whereNotNull('certificate_signature_id')` = verificado
- Actividad reciente: últimos 3 contratistas + contratos de los últimos 30 días
- Accesos rápidos: Colaboradores · Contratos · Firmas (estilo estándar blanco/gris)
- Menú "Administración": visible solo para `super-admin` y `admin` (oculto para demás roles)

---

## Comandos de Desarrollo

```bash
# Iniciar todos los servicios
composer run dev

# Tests
php artisan test
php artisan test --coverage --min=80
php artisan test --filter=CollaboratorTypeTest

# Migraciones
php artisan migrate
php artisan migrate:fresh --seed

# Seeders individuales
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder

# Estilo de código
./vendor/bin/pint
```
