---
name: mysql-schema-wis
description: Diseño, análisis y fusión de esquemas MySQL para WIS ASCUN. Analiza schemas legacy, propone diseños normalizados con UUID, SoftDeletes y multi-tenancy, y genera migraciones Laravel compatibles con el stack del proyecto.
license: MIT
---

# MySQL Schema Designer — WIS ASCUN

Especialista en diseño de base de datos MySQL para la migración de `app.wisascun.com` (PHP puro) a Laravel 12.

---

## Contexto del Proyecto

**WIS ASCUN** migra desde un sistema legacy PHP sin framework con dos bases de datos:
- `ascunwsi_db_wisascun` — BD de producción legacy (tablas desnormalizadas, sin FKs, sin UUIDs)
- `ascunwsi_sandbox_rh_ascun` — BD sandbox RH (más moderna, con algunas mejoras)

**Destino:** Laravel 12 + MySQL 8.0+, con:
- UUID PKs en todas las tablas de negocio
- `institution_id` UUID para multi-tenancy
- `deleted_at` (SoftDeletes) en entidades de negocio
- `created_at` / `updated_at` siempre presentes
- FKs con `ON DELETE CASCADE` o `RESTRICT` según la lógica de negocio
- `utf8mb4` en todas las tablas
- Índices en FKs y columnas de búsqueda frecuente

---

## Proceso de Trabajo

```
Análisis de schemas legacy
    |
    v
+------------------------------------------+
| Fase 1: INVENTARIO                        |
| * Listar todas las tablas de ambos schemas|
| * Identificar duplicados y equivalencias  |
| * Mapear relaciones implícitas (sin FKs)  |
+------------------------------------------+
    |
    v
+------------------------------------------+
| Fase 2: ANÁLISIS DE CALIDAD              |
| * Detectar antipatrones (TEXT para dinero)|
| * Identificar datos desnormalizados       |
| * Verificar tipos de datos correctos      |
| * Encontrar columnas sin índice necesario |
+------------------------------------------+
    |
    v
+------------------------------------------+
| Fase 3: DISEÑO UNIFICADO                 |
| * Definir tabla base a conservar          |
| * Mapear columnas legacy → nueva columna  |
| * Agregar UUID, institution_id, timestamps|
| * Normalizar datos TEXT → tipos correctos |
| * Diseñar FKs y estrategia ON DELETE      |
+------------------------------------------+
    |
    v
+------------------------------------------+
| Fase 4: PLAN DE MIGRACIÓN                |
| * Crear script de migración de datos      |
| * Orden de creación (respetar FKs)        |
| * Seeders de datos de referencia          |
| * Verificación post-migración             |
+------------------------------------------+
    |
    v
Schema unificado + Migraciones Laravel
```

---

## Estándares WIS ASCUN

### Convenciones de Nombres
| Elemento | Convención | Ejemplo |
|---|---|---|
| Tablas | `snake_case` plural | `collaborators`, `contracts` |
| PKs | UUID | `$table->uuid('id')->primary()` |
| FKs | `{tabla}_id` | `collaborator_id`, `position_id` |
| Timestamps | `created_at`, `updated_at` | `$table->timestamps()` |
| Soft delete | `deleted_at` | `$table->softDeletes()` |
| Multi-tenancy | `institution_id` | `$table->foreignUuid('institution_id')` |

### Tipos de Datos Correctos
| Dato | Tipo correcto | Antipatrón legacy |
|---|---|---|
| Dinero / salario | `DECIMAL(12,2)` | `TEXT`, `INT` |
| Documento de identidad | `VARCHAR(20)` | `INT`, `BIGINT`, `TEXT` |
| Estado | `ENUM(...)` o FK | `TEXT` libre |
| Tipo de documento | FK a `document_types` | `TEXT` libre |
| Tipo de contrato | FK a `contract_types` | `TEXT` libre |
| Fechas | `DATE` | `TEXT`, `'0000-00-00'` |
| Booleanos | `BOOLEAN` | `VARCHAR(6)` ('true'/'false') |

### Template de Migración Laravel
```php
<?php
declare(strict_types=1);

return new class extends Migration {
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_id')->constrained()->restrictOnDelete();
            // ... columnas de negocio
            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index(['institution_id', 'columna_busqueda']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

---

## Módulos del Sistema

### RH (Recursos Humanos)
- **Colaboradores** (`collaborators`) — fusión de `empleados` + `contratistas` + `colaboradores`
  - `tipo_colaborador` → FK a tabla `collaborator_types` o ENUM
  - `es_empresa` → `BOOLEAN`
  - `estado_colaborador` → FK a `collaborator_statuses`
- **Contratos** (`contracts`) — fusión de `contracts` + `contratos`
  - Separar datos de persona de datos del contrato
  - `cargo_id` → FK a `positions`
  - `tipo_contrato_id` → FK a `contract_types`
  - `estado_contrato` → ENUM o FK
- **Prórrogas** (`contract_extensions`) — ya existe en sandbox como `prorrogas_contratos`
- **Comprometidos** (`committed_values`) — presupuesto comprometido por contrato
- **Cargos** (`positions`) — normalizar con `department_id` FK
- **Departamentos** (`departments`) — jerarquía organizacional
- **Historial de cargo** (`position_change_history`) — ya existe en sandbox
- **Firmas certificados** (`certificate_signatures`) — ya existe en sandbox

### Contabilidad
- **Nodos** / **Carteras** (`nodes`, `portfolios`) — datos financieros por período
- **Centros de costo** (`cost_centers`) — normalizar la tabla actual sin PK
- **Cuentas contables** (`accounting_accounts`) — catálogo de cuentas
- **Auxiliar contable** (`accounting_ledger`) — movimientos
- **Saldo inicial bancos** (`bank_initial_balances`) — saldos de apertura

### Inventario
- **Elementos / Bienes** (`assets`) — de `elementos`
- **Productos / Suministros** (`supplies`) — de `productos`
- **Ingresos de elementos** (`asset_entries`) — de `ingresode_elementos`
- **Solicitudes de elementos** (`asset_requests`) — de `solicitudde_elementos`
- **Devoluciones** (`asset_returns`) — de `devolucionde_elementos`

### Certificados
- **Certificados** (`certifications`) — de `certificados` + `validate_certifications`
- **Conteo de certificados** (`certification_counts`) — de `conteodecertificados`

### Sistema
- **Usuarios** → tabla `users` de Laravel (ya migrada en Fase 1)
- **Roles** → via `spatie/laravel-permission` (ya migrado en Fase 1)
- **Logs** → via `owen-it/laravel-auditing` (ya migrado)
- **Notificaciones** → via `notifications` tabla de Laravel

---

## Antipatrones Legacy a Corregir

| Problema | Tabla(s) afectada(s) | Solución |
|---|---|---|
| `TEXT` para dinero | `empleados.salario`, `contratistas.honorarios` | `DECIMAL(12,2)` |
| `INT` para número de documento | `empleados.numdoc` | `VARCHAR(20)` |
| Fechas `'0000-00-00'` | `empleados.fechafin` | `DATE NULL` |
| Prórrogas como columnas | `contratistas.finprorroga1/2/3` | Tabla `contract_extensions` |
| Cargo desnormalizado en contrato | `empleados.cargo`, `contracts.cargo` | FK a `positions` |
| Tipo contrato como TEXT libre | Múltiples tablas | FK a `contract_types` |
| Sin PK en centros_de_costo | `centros_de_costo` | UUID PK |
| `es_empresa VARCHAR(6)` | `colaboradores` | `BOOLEAN` |
| Sin `deleted_at` | Todas las tablas | `softDeletes()` |
| Sin `institution_id` | Todas las tablas | Multi-tenancy UUID |
| charset `utf8mb3` | Tablas legacy | `utf8mb4` |

---

## Comandos de Verificación

```bash
# Ver estructura de tabla en MySQL
DESCRIBE table_name;
SHOW CREATE TABLE table_name;

# Verificar datos para diseño
SELECT DISTINCT tipodoc FROM empleados;
SELECT DISTINCT tipocontrato FROM empleados;
SELECT DISTINCT estado FROM empleados;

# Verificar rangos numéricos
SELECT MIN(salario), MAX(salario) FROM empleados;
SELECT MIN(numdoc), MAX(numdoc) FROM empleados;

# Verificar duplicados
SELECT numdoc, COUNT(*) FROM colaboradores GROUP BY numdoc HAVING COUNT(*) > 1;
```

---

## Formato del Plan de Esquema

El agente debe producir un plan en este formato:

```markdown
## Plan de Esquema Unificado — WIS ASCUN

### 1. Tabla: {nombre_tabla}
**Origen:** {tabla(s) del legacy que fusiona}
**Cambios:** {lista de cambios respecto al legacy}
**Estructura propuesta:**
```sql
CREATE TABLE `nombre_tabla` (
  `id` CHAR(36) PRIMARY KEY,
  ...
);
```
**Índices:** {lista de índices}
**FKs:** {lista de foreign keys}
**Datos de referencia:** {seeders necesarios}
**Notas de migración:** {cómo migrar los datos existentes}
```

---

## Restricciones

- **NO usar INT autoincrement** como PK — usar UUID (CHAR(36))
- **NO usar `TEXT`** para dinero, estados, tipos — usar tipos precisos
- **NO hacer DROP TABLE** de datos de producción sin script de migración seguro
- **SIEMPRE** incluir `institution_id`, `deleted_at`, `created_at`, `updated_at`
- **SIEMPRE** crear script de migración de datos del legacy al nuevo esquema
- El plan DEBE ser aprobado por el usuario antes de implementar
