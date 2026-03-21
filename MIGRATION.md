# Plan de Migración — app.wisascun.com → WIS ASCUN Laravel 12

**Proyecto:** WIS ASCUN
**Origen:** `/home/rdsdev/projects-rds/WISASCUN/app.wisascun.com` (PHP sin framework)
**Destino:** `/home/rdsdev/projects-rds/WISASCUN/WIS-TailAdmin` (Laravel 12)
**Fecha de inicio:** Marzo 2026

---

## Resumen Ejecutivo

El sistema legacy `app.wisascun.com` es un MVC manual en PHP con 5.141 archivos, ~250K líneas de código y vulnerabilidades críticas activas (SQL injection, credenciales expuestas, autenticación débil). Esta migración reescribe el sistema en Laravel 12 con estándares modernos de seguridad, arquitectura en capas, UUID, auditoría completa y roles/permisos.

### Objetivos de la Migración

1. **Seguridad:** Eliminar los 10+ puntos de SQL injection, credenciales en código y autenticación débil
2. **Mantenibilidad:** Arquitectura en capas (Controller → Service → Model), tests con ≥80% cobertura
3. **Escalabilidad:** UUID, multi-tenancy por `institution_id`, soft deletes, auditoría
4. **UX:** Interfaz moderna con TailAdmin + Tailwind v4, dark mode, responsive
5. **Trazabilidad:** Auditoría completa con owen-it/laravel-auditing en todas las entidades

---

## Dependencias Instaladas

```json
{
  "require": {
    "laravel/framework": "^12.0",
    "livewire/livewire": "^4.2",
    "spatie/laravel-permission": "^7.2",
    "owen-it/laravel-auditing": "^14.0",
    "maatwebsite/excel": "^3.1",
    "barryvdh/laravel-dompdf": "^3.1",
    "simplesoftwareio/simple-qrcode": "^4.2"
  }
}
```

---

## Fases de Migración

### Fase 0: Infraestructura Base ✅ COMPLETADA

| Tarea | Estado |
|---|---|
| Instalar dependencias PHP y JS | ✅ |
| Configurar `.env` con MySQL | ✅ |
| Crear base de datos y correr migraciones | ✅ |
| Instalar Livewire v4 | ✅ |
| Instalar spatie/laravel-permission | ✅ |
| Instalar owen-it/laravel-auditing | ✅ |
| Instalar maatwebsite/excel | ✅ |
| Instalar barryvdh/laravel-dompdf | ✅ |
| Instalar simplesoftwareio/simple-qrcode | ✅ |
| Publicar configs de paquetes | ✅ |
| Configurar agentes de Claude Code | ✅ |
| Configurar MCPs (Figma, Notion) | ✅ |
| Actualizar CLAUDE.md con estándares | ✅ |

---

### Fase 1: Fundamentos de Arquitectura

**Rama:** `feature/WIS-001-arquitectura-base`
**Prioridad:** CRÍTICA

#### 1.1 Trait HasUuidPrimaryKey
- [ ] Crear `app/Models/Concerns/HasUuidPrimaryKey.php`

#### 1.2 Configuración global
- [ ] `config/app.php`: `locale = 'es'`, `timezone = 'America/Bogota'`, `faker_locale = 'es_CO'`
- [ ] Crear `lang/es/` con traducciones de validación en español colombiano
- [ ] `config/audit.php`: configurar driver y campos excluidos (`password`)
- [ ] `config/permission.php`: configurar UUID en tablas de permisos

#### 1.3 Base del sistema de usuarios
- [ ] Migration `users` con UUID, `document_number`, `document_issued_at`
- [ ] Modelo `User` con UUID + auditoría + roles (Spatie)
- [ ] `AuthService` con login por documento + fecha + contraseña
- [ ] `LoginRequest` con mensajes en español colombiano
- [ ] `LoginController` (delgado)
- [ ] Vista de login con diseño TailAdmin (`fullscreen-layout`)
- [ ] Tests de autenticación completos

#### 1.4 Layout principal y navegación
- [ ] Menú sidebar con grupos por módulo
- [ ] Breadcrumbs dinámicos
- [ ] Topbar con info del usuario y logout
- [ ] Dashboard inicial (página de inicio post-login)

#### 1.5 Seeders de roles y permisos
- [ ] `RolesAndPermissionsSeeder` con todos los roles del sistema
- [ ] `AdminUserSeeder` para usuario inicial de pruebas

---

### Fase 2: Módulo de Recursos Humanos (RH)

**Rama:** `feature/WIS-002-modulo-rh`
**Archivos legacy de referencia:**
- `Controllers/EmployeeController.php`
- `Controllers/ContractorController.php`
- `Models/Collaborator.php`
- `Models/Contract.php`
- `ajax/collaborator.ajax.php`
- `ajax/contract.ajax.php`

#### 2.1 Modelos y Migrations

| Modelo | Tabla | Descripción |
|---|---|---|
| `Institution` | `institutions` | Instituciones ASCUN (multi-tenancy) |
| `Department` | `departments` | Dependencias/áreas |
| `Position` | `positions` | Cargos |
| `Employee` | `employees` | Empleados de planta |
| `Contractor` | `contractors` | Contratistas |
| `Contract` | `contracts` | Contratos (planta y contratistas) |

Todos los modelos: UUID + SoftDeletes + Auditable

#### 2.2 Funcionalidades

- [ ] CRUD completo de empleados con validación y autorización
- [ ] CRUD completo de contratistas
- [ ] CRUD completo de contratos con fechas y salario
- [ ] Listado con filtros, búsqueda y paginación (Livewire)
- [ ] Exportación a Excel (maatwebsite/excel)
- [ ] Importación masiva de empleados desde Excel
- [ ] Historial de cambios (auditoría)

#### 2.3 Vistas

- [ ] `pages/rh/empleados/index.blade.php` — listado con DataTable Livewire
- [ ] `pages/rh/empleados/create.blade.php` — formulario de registro
- [ ] `pages/rh/empleados/edit.blade.php` — formulario de edición
- [ ] `pages/rh/empleados/show.blade.php` — detalle del empleado
- [ ] `pages/rh/contratos/` — CRUD de contratos

#### 2.4 Tests

- [ ] Feature tests para cada endpoint (CRUD + autorización)
- [ ] Unit tests para `EmployeeService` y `ContractService`
- [ ] Test de exportación Excel

---

### Fase 3: Módulo de Certificados Laborales

**Rama:** `feature/WIS-003-modulo-certificados`
**Archivos legacy de referencia:**
- `Controllers/CertificatesController.php` (20.2KB)
- `views/modules/certificado/certificate.php`
- `views/modules/certificado/vendor/` (dependencias PDF)

#### 3.1 Modelos

| Modelo | Tabla | Descripción |
|---|---|---|
| `CertificateTemplate` | `certificate_templates` | Plantillas de certificado por institución |
| `Certificate` | `certificates` | Certificados generados (histórico) |

#### 3.2 Funcionalidades

- [ ] Generación de certificado laboral en PDF (DomPDF)
- [ ] Código QR de verificación en el PDF (simple-qrcode)
- [ ] Ruta pública de verificación: `GET /certificados/verificar/{uuid}`
- [ ] Historial de certificados generados por empleado
- [ ] Descarga y vista previa del PDF
- [ ] Envío por email (opcional)

#### 3.3 Plantilla PDF

La plantilla debe incluir:
- Nombre completo del empleado
- Número de documento
- Cargo actual
- Fecha de ingreso
- Salario (solo si se solicita explícitamente)
- Tipo de contrato
- Estado (activo/retirado)
- Código QR enlazando a la verificación
- Firma del responsable de RH
- Fecha y ciudad de expedición

#### 3.4 Tests

- [ ] Test de generación de PDF
- [ ] Test de ruta de verificación pública
- [ ] Test de control de acceso (solo empleado propio o gerente RH)

---

### Fase 4: Módulo de Contabilidad

**Rama:** `feature/WIS-004-modulo-contabilidad`
**Archivos legacy de referencia:**
- `Controllers/CarterasController.php`
- `Controllers/InformesController.php`
- `Models/Carteras.php`
- `Models/InformeNDS.php` (228KB)
- `Models/InformeSF.php` (82KB)
- `Models/InformeRDS.php` (37.7KB)
- `Models/InformePRS.php` (82KB)

#### 4.1 Modelos

| Modelo | Tabla | Descripción |
|---|---|---|
| `Node` | `nodes` | Nodos contables (CDC) |
| `Wallet` | `wallets` | Carteras por nodo |
| `InitialBalance` | `initial_balances` | Saldo inicial bancos por año |
| `FinancialReport` | `financial_reports` | Registro de reportes generados |

#### 4.2 Reportes a Migrar

| Informe | Descripción | Clase Service |
|---|---|---|
| NDS | Informe de Nodos y Distribución de Saldos | `InformeNDSService` |
| SF | Estado de Situación Financiera | `InformeSFService` |
| RDS | Reporte de Distribución de Saldos | `InformeRDSService` |
| PRS | Presupuesto | `InformePRSService` |

Cada servicio de informe debe extraer y modularizar la lógica del archivo monolítico legacy.

#### 4.3 Funcionalidades

- [ ] Gestión de nodos y carteras (CRUD)
- [ ] Selección de período (año/mes) con Flatpickr
- [ ] Generación de reportes en pantalla (ApexCharts para gráficas)
- [ ] Exportación a Excel
- [ ] Exportación a PDF
- [ ] Filtros por nodo, período e institución

---

### Fase 5: Módulo de Inventario

**Rama:** `feature/WIS-005-modulo-inventario`
**Archivos legacy de referencia:**
- `Controllers/ProductsController.php`
- `Controllers/ElementsController.php`
- `Models/Products.php`
- `Models/Elements.php`
- `ajax/product.ajax.php`
- `ajax/element.ajax.php`

#### 5.1 Modelos

| Modelo | Tabla | Descripción |
|---|---|---|
| `ProductCategory` | `product_categories` | Categorías de suministros |
| `Product` | `products` | Suministros (SUBE) |
| `ProductMovement` | `product_movements` | Entradas/salidas de suministros |
| `ElementCategory` | `element_categories` | Categorías de bienes/equipos |
| `Element` | `elements` | Bienes, equipos y enseres |
| `ElementAssignment` | `element_assignments` | Asignación de elementos a personas |

#### 5.2 Funcionalidades

- [ ] CRUD suministros con control de stock
- [ ] Registro de entradas y salidas
- [ ] CRUD equipos/bienes con estado y asignación
- [ ] Asignación de elementos a empleados
- [ ] Exportación a Excel
- [ ] Alertas de stock mínimo (Livewire)

---

### Fase 6: Módulo de Reportes y Dashboard

**Rama:** `feature/WIS-006-dashboard-reportes`

- [ ] Dashboard principal con métricas por módulo (ApexCharts)
- [ ] Total empleados activos / contratistas
- [ ] Contratos próximos a vencer (30/60/90 días)
- [ ] Últimas certificaciones generadas
- [ ] Inventario con stock crítico
- [ ] Widgets configurables por rol

---

## Consideraciones de Seguridad por Fase

Antes de hacer merge de **cualquier fase**, verificar:

```
□ Cero variables interpoladas en SQL
□ Cero credenciales en archivos PHP
□ bcrypt en todos los campos password
□ @csrf en todos los formularios
□ Policy implementada para operaciones CRUD
□ Tests de autorización incluidos
□ Mensajes de error genéricos (no exponer detalles técnicos)
□ display_errors=false verificado
```

---

## Mapeo de Rutas Legacy → Laravel

| Legacy (`?ruta=`) | Laravel (named route) |
|---|---|
| `?ruta=inicio` | `dashboard` |
| `?ruta=empleados` | `rh.empleados.index` |
| `?ruta=contratos` | `rh.contratos.index` |
| `?ruta=certificados` | `certificados.index` |
| `?ruta=carteras` | `contabilidad.carteras.index` |
| `?ruta=informes` | `contabilidad.informes.index` |
| `?ruta=suministros` | `inventario.suministros.index` |
| `?ruta=elementos` | `inventario.elementos.index` |

---

## Criterios de Aceptación por Módulo

Cada módulo migrado debe cumplir:

1. ✅ Sin SQL injection (0 variables interpoladas en queries)
2. ✅ Tests con cobertura ≥ 80%
3. ✅ Todos los mensajes de UI en español colombiano
4. ✅ Pint sin violaciones
5. ✅ Modelos con UUID + SoftDeletes + Auditable
6. ✅ Roles y permisos implementados con Spatie
7. ✅ Diseño responsive con dark mode
8. ✅ Exportación Excel (si aplica)
9. ✅ PR aprobado por al menos un revisor

---

## Estado General

| Fase | Módulo | Estado | Sprint |
|---|---|---|---|
| 0 | Infraestructura Base | ✅ Completada | Sprint 1 |
| 1 | Fundamentos + Auth | 🔄 En progreso | Sprint 1-2 |
| 2 | Recursos Humanos | ⏳ Pendiente | Sprint 2-3 |
| 3 | Certificados | ⏳ Pendiente | Sprint 3 |
| 4 | Contabilidad | ⏳ Pendiente | Sprint 4-5 |
| 5 | Inventario | ⏳ Pendiente | Sprint 5-6 |
| 6 | Dashboard y Reportes | ⏳ Pendiente | Sprint 6 |
