---
nombre: mysql-wis
descripcion: >
  Planificar y revisar el esquema MySQL/InnoDB, indexación, optimización de
  consultas, transacciones y operaciones durante la migración de WIS a Laravel 11.
  Usar cuando se creen o modifiquen tablas, índices o consultas; al diagnosticar
  lentitud o bloqueos; al planificar las migraciones de Fase 1; o al resolver
  problemas de conexión entre las dos bases de datos del proyecto.
---

# SKILL: MySQL — Migración WIS → Laravel 11

Usa esta guía para realizar cambios seguros y medibles en MySQL/InnoDB durante la migración.

> **Contexto del proyecto:** WIS opera con **2 bases de datos**:
> - `ascunwsi_db_wisascun` — financiero/operativo (conexión principal en Laravel)
> - `ascunwsi_sandbox_rh_ascun` — RR.HH. (conexión secundaria `rh_ascun`, transitoria durante la migración)
>
> Ambas conexiones deben declararse en `config/database.php`. Toda consulta debe ejecutarse
> a través del **Query Builder o Eloquent de Laravel**; nunca SQL en crudo sin justificación explícita.

---

## Flujo de Trabajo

1. Definir la carga de trabajo y restricciones (mix lectura/escritura, volumen de datos, versión de MySQL).
2. Consultar el esquema real de la base de datos vía **Laravel Boost** (Fase 0 del plan) antes de proponer cambios.
3. Proponer el cambio mínimo que resuelva el problema, incluyendo sus ventajas y compromisos.
4. Validar con evidencia: `EXPLAIN`, `EXPLAIN ANALYZE`, métricas de bloqueo.
5. Para cambios en producción, incluir pasos de rollback y verificación post-despliegue.

---

## Diseño del Esquema

### Claves primarias
- Usar `BIGINT UNSIGNED AUTO_INCREMENT` para tablas de alta escritura (entradas contables `bppcc`, movimientos de inventario).
- Usar **UUID** como clave primaria en **entidades operativas** (usuarios, colaboradores, contratos, certificaciones) — tal como define el plan de migración.
- Si se usan UUIDs como PK clustered, considerar el impacto en rendimiento de escritura; evaluar tener el UUID en columna `UNIQUE` secundaria y un `id BIGINT` como PK interna en tablas de muy alto volumen.

### Tipos de datos y codificación
- Siempre `utf8mb4` / `utf8mb4_0900_ai_ci` — obligatorio para nombres con tildes y caracteres especiales del español.
- Preferir `NOT NULL` con valores por defecto explícitos.
- Usar `DATETIME` en vez de `TIMESTAMP` (evita problemas de zona horaria en informes financieros).
- Tablas de lookup (catálogos de cargos, centros de costo, tipos de contrato) en vez de columnas `ENUM`.
- Normalizar a 3FN; desnormalizar solo en rutas calientes medidas (e.g., totales precalculados en informes NDS/RDS).

### Convenciones de nomenclatura (alineadas con Laravel)
- Tablas en **plural snake_case**: `users`, `collaborators`, `contract_extensions`.
- Claves foráneas: `{modelo_singular}_id` — e.g., `collaborator_id`, `contract_id`.
- Tablas pivote en orden alfabético: `permission_role`, `model_has_roles` (spatie estándar).
- Columnas de auditoría: `created_at`, `updated_at`, `deleted_at` (soft deletes donde aplique).

---

## Indexación

- **Orden en índices compuestos:** igualdad primero, luego rango/orden (regla del prefijo más a la izquierda).
- Los predicados de rango detienen el uso del índice para columnas subsecuentes — tenerlo en cuenta en filtros de informes financieros por fecha + centro de costo.
- Los índices secundarios incluyen la PK implícitamente. Usar índices de prefijo para columnas de texto largo.
- Auditar índices no usados vía `performance_schema` — eliminar los que tengan `count_read = 0`.

### Índices prioritarios para WIS
```sql
-- Filtros frecuentes en informes financieros
CREATE INDEX idx_bppcc_centro_fecha ON bppcc (centro_de_costo, fecha);
CREATE INDEX idx_bppcc_cuenta ON bppcc (cuenta_contable);

-- Búsquedas de contratos por colaborador y vigencia
CREATE INDEX idx_contracts_collaborator ON contracts (collaborator_id, fecha_fin);

-- Búsquedas de usuarios por rol (spatie usa esta tabla intensamente)
CREATE INDEX idx_model_has_roles_model ON model_has_roles (model_id, model_type);
```

---

## Particionamiento

- Particionar tablas de series de tiempo con más de 50M filas, o tablas generales con más de 100M filas.
- Planificar temprano — hacer retrofit implica reconstrucción completa.
- Incluir la columna de partición en todo índice único/PK. Agregar siempre una partición `MAXVALUE` de captura.

### Candidatos en WIS
- `bppcc` (doble entrada contable) — particionar por año fiscal si el volumen histórico es alto.
- `audits` (owen-it/laravel-auditing) — particionar por mes una vez que el sistema esté en producción.

---

## Optimización de Consultas

- Revisar `EXPLAIN` — señales de alerta: `type: ALL`, `Using filesort`, `Using temporary`.
- Usar **paginación por cursor**, no por `OFFSET` — crítico para los DataTables de Livewire (UserTable, CollaboratorTable, etc.).
- Evitar funciones sobre columnas indexadas en `WHERE` (e.g., `YEAR(fecha)` — usar rangos explícitos).
- Inserción en lotes de 500–5000 filas. Usar `UNION ALL` en vez de `UNION` cuando no se necesite deduplicar.

### Problema N+1 en Eloquent
Los modelos con relaciones deben usar **eager loading** obligatoriamente:
```php
// ❌ N+1 — una query por cada contrato
$contracts = Contract::all();
foreach ($contracts as $c) { echo $c->collaborator->nombre; }

// ✅ Correcto
$contracts = Contract::with('collaborator')->get();

// Para informes financieros con múltiples relaciones
$entries = AccountingEntry::with(['costCenter', 'account'])->where(...)->get();
```

### Reemplazar stored procedures
Los 5 stored procedures del sistema WIS (SP_CREAR_USUARIO_WIS, SP_PERMISOS_DE_USUARIO, SP_CHANGE_POSITION, etc.) deben migrarse a métodos de servicio en Laravel:
```php
// En vez de CALL SP_CREAR_USUARIO_WIS(...)
// Usar UserService::create(CreateUserDTO $dto)
// con validación en StoreUserRequest + lógica en el servicio
```

---

## Transacciones y Bloqueos

- Nivel por defecto: `REPEATABLE READ` (genera gap locks). Usar `READ COMMITTED` en tablas de alta contención (inventario con asignaciones simultáneas).
- Acceder a filas siempre en el mismo orden para prevenir deadlocks. Reintentar error 1213 con backoff exponencial.
- Hacer I/O fuera de las transacciones. Usar `SELECT ... FOR UPDATE` con criterio.

### Transacciones en Laravel para WIS
```php
// Operaciones que afectan múltiples tablas (contratos + comprometidos + auditoría)
DB::transaction(function () use ($data) {
    $contract = Contract::create($data);
    $contract->commitments()->create([...]);
    // owen-it registra el audit automáticamente dentro de la transacción
});

// Con manejo de deadlock
DB::transaction(function () { ... }, attempts: 3);
```

---

## Operaciones y Mantenimiento

- Usar DDL en línea (`ALGORITHM=INPLACE`) cuando sea posible; probar en réplicas primero.
- Ajustar el pool de conexiones — evitar el agotamiento de `max_connections` bajo carga.
- Monitorear el lag de replicación; evitar lecturas obsoletas de réplicas durante escrituras.

### Migración de datos legados
Durante la **Fase 1** (migraciones de BD), al poblar las nuevas tablas desde las tablas legadas:
```php
// En seeders/migrations de datos: usar chunking para tablas grandes
DB::connection('wisascun_legacy')
    ->table('usuarios')
    ->orderBy('id')
    ->chunk(500, function ($rows) {
        foreach ($rows as $row) {
            User::create([
                'id'       => Str::uuid(),
                'email'    => $row->email,
                'password' => Hash::make($row->password_temp), // re-hash obligatorio
                // ...
            ]);
        }
    });
```

---

## Guardrails

- Preferir evidencia medida sobre reglas generales.
- Indicar el comportamiento específico de la versión de MySQL cuando se dé una recomendación.
- **Solicitar aprobación humana explícita** antes de operaciones destructivas (`DROP`, `DELETE`, `TRUNCATE`) — especialmente en las tablas financieras (`bppcc`, `carteras`) y de RR.HH. (`contratos`, `colaboradores`).
- Nunca ejecutar queries directas sobre producción sin haber probado en un entorno de réplica o staging.
- Las consultas vía **Laravel Boost son de solo lectura** — no usar Tinker en producción.
