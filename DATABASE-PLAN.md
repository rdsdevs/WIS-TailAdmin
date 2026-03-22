Ya tengo toda la información necesaria. Ahora genero el plan de esquema unificado completo.

---

# Plan de Esquema Unificado — WIS ASCUN

## Fase 1: Inventario de Tablas

### Tabla comparativa

| Tabla Sandbox (ascunwsi_sandbox_rh_ascun) | Tabla Legacy (ascunwsi_db_wisascun) | Acción | Tabla Final |
|---|---|---|---|
| `colaboradores` | `empleados` + `contratistas` + `personal` | FUSIONAR (todas en una) | `collaborators` |
| `contratos` | `contracts` + `contratistas` (cols contrato) | FUSIONAR | `contracts` |
| `cargos` | `cargos` (con `codigocargo`) | MEJORAR | `positions` |
| `centros_de_costo` | `centrosdecostos` | MEJORAR (agregar PK UUID) | `cost_centers` |
| `comprometidos` | `comprometidos` (ref por texto) | MEJORAR | `committed_values` |
| `colaborador_comprometido` | — (VIEW) | ELIMINAR → Eloquent scope | — |
| `prorrogas_contratos` | columnas `finprorroga1/2/3` en `contratistas` | MEJORAR | `contract_extensions` |
| `tipos_de_contrato` | `tipodecontrato` | MEJORAR (unificar valores) | `contract_types` |
| `tipos_de_documentos` | `tipodocumento` | MEJORAR | `document_types` |
| `estados_colaborados` | campo `estado` texto libre | MEJORAR | `collaborator_statuses` |
| `historial_cambio_cargo` | — (no existe) | AGREGAR | `position_change_history` |
| `correo_cargo` | — (no existe) | AGREGAR | `position_emails` |
| `funciones_del_cargo` | `funciones` + `funciones_cargos` | FUSIONAR | `position_functions` |
| `firmas_certificados` | — (no existe) | AGREGAR | `certificate_signatures` |
| `validate_certifications` | `certificados` | FUSIONAR | `certifications` |
| `cuentas_contables` | — | MEJORAR | `accounting_accounts` |
| `auxiliar_contable` | `bppcc` | MEJORAR (tipos) | `accounting_ledger` + `budget_balance` |
| — | `carteranodos` + `carteras` | AGREGAR | `portfolios` |
| — | `saldoinicialbancos` | AGREGAR | `bank_initial_balances` |
| — | `presupuesto` | AGREGAR | `budgets` |
| — | `elementos` | AGREGAR | `assets` |
| — | `ingresode_elementos` | AGREGAR | `asset_entries` |
| — | `solicitudde_elementos` | AGREGAR | `asset_assignments` |
| — | `devolucionde_elementos` | AGREGAR | `asset_returns` |
| — | `productos` | AGREGAR | `supplies` |
| — | `ingresode_productos` | AGREGAR | `supply_entries` |
| — | `solicitudde_productos` | AGREGAR | `supply_requests` |
| — | `categorias` | AGREGAR | `asset_categories` |
| — | `ubicaciones` | AGREGAR | `locations` |
| — | `conteodecertificados` | AGREGAR (reemplazar con lógica) | eliminado → contador en `certifications` |
| — | `controldeacceso` / `controldeacceso_2` | DESCARTAR (reemplazado por Spatie) | — |
| — | `usuarios` / `c_usuarios` / `perfilde_usuario` | DESCARTAR (Laravel Auth + users) | — |
| — | `perfiles` / `permisos` | DESCARTAR (Spatie Permission) | — |
| — | `logs` | DESCARTAR (Laravel Auditing) | — |
| — | `new_requests` / `notifications` | DESCARTAR (Laravel Notifications) | — |
| — | `maintenance` | EVALUAR | `maintenance_records` |
| `get_all_collaborators` / `get_all_contracts` / `get_collaborators_position` / `informe_comprometidos` | — | SON VISTAS → reemplazar con Eloquent | — |

---

## Fase 2: Diseño Detallado por Tabla

---

### Tabla 1: `document_types` *(tabla de referencia)*

**Origen:** `tipos_de_documentos` (sandbox) + `tipodocumento` (legacy)

**Cambios:**
- INT PK → UUID
- `utf8mb3` → `utf8mb4`
- Agregar `institution_id`, `deleted_at`, `created_at`, `updated_at`
- Unificar valores de ambas fuentes (idénticos, mismo catálogo)

**Estructura propuesta:**
```sql
CREATE TABLE `document_types` (
  `id`           CHAR(36)     NOT NULL,
  `institution_id` CHAR(36)  NOT NULL,
  `code`         VARCHAR(4)   NOT NULL,
  `name`         VARCHAR(60)  NOT NULL,
  `deleted_at`   TIMESTAMP    NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_document_types_institution (institution_id)`
- `UNIQUE INDEX uq_document_types_code_institution (institution_id, code)`

**FKs:**
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

**Datos de referencia (seeder):**
```php
// DatabaseSeeder o DocumentTypeSeeder
$types = [
    ['code' => 'CC',  'name' => 'Cédula de Ciudadanía'],
    ['code' => 'CE',  'name' => 'Cédula de Extranjería'],
    ['code' => 'NIT', 'name' => 'Número de Identificación Tributaria'],
    ['code' => 'NIP', 'name' => 'Número de Identificación Personal'],
    ['code' => 'PAP', 'name' => 'Pasaporte'],
    ['code' => 'TI',  'name' => 'Tarjeta de Identidad'],
];
```

**Script de migración:**
```sql
INSERT INTO document_types (id, institution_id, code, name, created_at, updated_at)
SELECT UUID(), '<institution_uuid>', codigo, descripcion, NOW(), NOW()
FROM tipos_de_documentos;
```

---

### Tabla 2: `collaborator_statuses` *(tabla de referencia)*

**Origen:** `estados_colaborados` (sandbox) — campo `estado` texto libre en legacy

**Cambios:**
- INT PK → UUID
- Agregar `institution_id`, `deleted_at`, timestamps
- `icono` → `icon_class` VARCHAR(80) — renombrar para claridad

**Estructura propuesta:**
```sql
CREATE TABLE `collaborator_statuses` (
  `id`             CHAR(36)    NOT NULL,
  `institution_id` CHAR(36)    NOT NULL,
  `name`           VARCHAR(60) NOT NULL,
  `icon_class`     VARCHAR(80) NULL,
  `deleted_at`     TIMESTAMP   NULL,
  `created_at`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_collab_statuses_institution (institution_id)`

**FKs:**
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

**Datos de referencia (seeder):**
```php
$statuses = [
    ['name' => 'Activo',       'icon_class' => 'fa-regular fa-user-check'],
    ['name' => 'Extrabajador', 'icon_class' => 'fa-regular fa-user-xmark'],
    ['name' => 'Fallecido',    'icon_class' => 'fa-regular fa-tombstone'],
    ['name' => 'Inactivo',     'icon_class' => 'fa-regular fa-user-xmark'],
    ['name' => 'Pensionado',   'icon_class' => 'fa-regular fa-island-tropical'],
];
```

---

### Tabla 3: `contract_types` *(tabla de referencia)*

**Origen:** `tipos_de_contrato` (sandbox, 10 tipos) + `tipodecontrato` (legacy, 9 tipos con código)

**Cambios:**
- INT PK → UUID
- `descripcion` → `name`
- Agregar `code` VARCHAR(10) (del legacy), `description` TEXT NULL
- Unificar: sandbox tiene "Mano de obra" que legacy no tiene; legacy tiene "Compraventa" que sandbox tiene
- Agregar `institution_id`, timestamps

**Tipos unificados (sandbox + legacy fusionados):**

| code | name |
|---|---|
| APRE | Aprendizaje |
| ARRE | Arrendamiento |
| COMPR | Compraventa |
| CASE | Contrato de Asesoría |
| FIAA | Fijo Inferior a un año |
| IDFD | Indefinido |
| OPS | OPS |
| PTCT | Practicante |
| CPS | Prestador de Servicios |
| MO | Mano de obra |

**Estructura propuesta:**
```sql
CREATE TABLE `contract_types` (
  `id`             CHAR(36)     NOT NULL,
  `institution_id` CHAR(36)     NOT NULL,
  `code`           VARCHAR(10)  NOT NULL,
  `name`           VARCHAR(60)  NOT NULL,
  `description`    TEXT         NULL,
  `deleted_at`     TIMESTAMP    NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `UNIQUE INDEX uq_contract_types_code_institution (institution_id, code)`

**FKs:**
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

---

### Tabla 4: `departments` *(ya existe en Laravel)*

**Origen:** No existe explícitamente en legacy ni sandbox. Se infiere del campo `codigocargo` (ej: `AAR-018` → prefijo `AAR` = área). El modelo Laravel ya existe.

**Cambios al modelo actual:**
- Ya tiene: `id`, `institution_id`, `name`, `description`, `is_active`, timestamps, softDeletes ✓
- Agregar: `parent_id` CHAR(36) NULL (para jerarquía de departamentos/sub-áreas)
- Agregar: `code` VARCHAR(20) NULL (para migrar prefijos de cargo como "AAR", "CAF", etc.)

**Estructura propuesta:**
```sql
CREATE TABLE `departments` (
  `id`             CHAR(36)      NOT NULL,
  `institution_id` CHAR(36)      NOT NULL,
  `parent_id`      CHAR(36)      NULL,
  `code`           VARCHAR(20)   NULL,
  `name`           VARCHAR(100)  NOT NULL,
  `description`    TEXT          NULL,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `deleted_at`     TIMESTAMP     NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_departments_institution (institution_id)`
- `INDEX idx_departments_parent (parent_id)`

**FKs:**
- `institution_id` → `institutions(id)` ON DELETE RESTRICT
- `parent_id` → `departments(id)` ON DELETE SET NULL

---

### Tabla 5: `positions` *(ya existe en Laravel — reemplaza `cargos`)*

**Origen:** `cargos` (sandbox, 59 cargos sin código) + `cargos` (legacy, con `codigocargo`)

**Cambios al modelo actual:**
- Ya tiene: `id`, `institution_id`, `department_id`, `name`, `description`, `is_active`, timestamps, softDeletes ✓
- Agregar: `code` VARCHAR(20) NULL — para conservar `codigocargo` del legacy (ej: `DEJ-001`)
- Agregar: `email` VARCHAR(100) NULL — absorbe tabla `correo_cargo` del sandbox (relación 1:1 por ahora)
- NOTA: `funciones_del_cargo` → tabla separada `position_functions`

**Estructura propuesta:**
```sql
CREATE TABLE `positions` (
  `id`             CHAR(36)      NOT NULL,
  `institution_id` CHAR(36)      NOT NULL,
  `department_id`  CHAR(36)      NULL,
  `code`           VARCHAR(20)   NULL,
  `name`           VARCHAR(150)  NOT NULL,
  `email`          VARCHAR(100)  NULL,
  `description`    TEXT          NULL,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `deleted_at`     TIMESTAMP     NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_positions_institution (institution_id)`
- `INDEX idx_positions_department (department_id)`
- `UNIQUE INDEX uq_positions_code_institution (institution_id, code)` — solo si `code IS NOT NULL`

**FKs:**
- `institution_id` → `institutions(id)` ON DELETE RESTRICT
- `department_id` → `departments(id)` ON DELETE SET NULL

**Script de migración:**
```sql
-- Desde sandbox (cargos sin código)
INSERT INTO positions (id, institution_id, code, name, created_at, updated_at)
SELECT UUID(), '<institution_uuid>', NULL, cargo, NOW(), NOW()
FROM cargos;

-- Desde legacy (cargos con código)
-- Primero cruzar por nombre para evitar duplicados:
INSERT INTO positions (id, institution_id, code, name, created_at, updated_at)
SELECT UUID(), '<institution_uuid>', l.codigocargo, l.cargo, NOW(), NOW()
FROM cargos l
WHERE NOT EXISTS (
  SELECT 1 FROM positions p
  WHERE p.name = l.cargo AND p.institution_id = '<institution_uuid>'
);
-- Luego actualizar el code donde ya existía por nombre:
UPDATE positions p
JOIN (SELECT codigocargo, cargo FROM cargos) l ON p.name = l.cargo
SET p.code = l.codigocargo
WHERE p.institution_id = '<institution_uuid>' AND p.code IS NULL;
```

---

### Tabla 6: `position_functions`

**Origen:** `funciones_del_cargo` (sandbox) + `funciones` + `funciones_cargos` (legacy)

**Cambios:**
- INT PK → UUID
- `cargo_id` INT → `position_id` CHAR(36) UUID FK
- `funciones` TEXT → conservar como TEXT
- Agregar `institution_id`, `order` SMALLINT para ordenar funciones, timestamps, softDeletes

**Estructura propuesta:**
```sql
CREATE TABLE `position_functions` (
  `id`             CHAR(36)   NOT NULL,
  `institution_id` CHAR(36)   NOT NULL,
  `position_id`    CHAR(36)   NOT NULL,
  `description`    TEXT       NOT NULL,
  `order`          SMALLINT   NOT NULL DEFAULT 0,
  `deleted_at`     TIMESTAMP  NULL,
  `created_at`     TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_position_functions_position (position_id)`

**FKs:**
- `position_id` → `positions(id)` ON DELETE CASCADE
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

**Script de migración:**
```sql
-- Requiere tener el mapeo de id_cargo_sandbox → id_position_nueva en tabla temporal
INSERT INTO position_functions (id, institution_id, position_id, description, `order`, created_at, updated_at)
SELECT UUID(), '<institution_uuid>', pm.new_position_id, f.funciones, ROW_NUMBER() OVER (PARTITION BY f.cargo_id ORDER BY f.id), NOW(), NOW()
FROM funciones_del_cargo f
JOIN position_migration_map pm ON pm.old_sandbox_cargo_id = f.cargo_id;
```

---

### Tabla 7: `collaborators` *(FUSIÓN CENTRAL)*

**Origen:**
- `colaboradores` (sandbox): 1 tabla con `tipo_colaborador` ENUM('Contratista','Empleado'), `es_empresa` VARCHAR(6)
- `empleados` (legacy): datos personales + contrato desnormalizados
- `contratistas` (legacy): datos personales + contrato desnormalizados
- `personal` (legacy): tabla de personas normalizada (base más limpia, con `tipode_personal` INT)

**Decisión clave:** Se fusiona en una sola tabla `collaborators`. El tipo (empleado/contratista/empresa) se controla con FK a `collaborator_types`. El contrato va en tabla separada `contracts`.

**Antipatrones a corregir:**
- `numdoc` BIGINT(10) → `document_number` VARCHAR(20) (soporta NIT, CE, pasaportes)
- `tipodoc` INT FK a sandbox / TEXT en legacy → `document_type_id` UUID FK a `document_types`
- `es_empresa` VARCHAR(6) ('false'/'true') → `is_company` TINYINT(1) BOOLEAN
- `estado_colaborador` INT → `status_id` UUID FK a `collaborator_statuses`
- `tipo_colaborador` ENUM → `collaborator_type` ENUM('Empleado','Contratista') (simple y suficiente)
- Datos de contrato en `empleados`/`contratistas` (salario, cargo, fechas) → van a `contracts`
- Agregar: `birth_date` DATE NULL, `gender` ENUM('F','M','O') NULL
- Agregar: `address` VARCHAR(255) NULL, `phone` VARCHAR(30) NULL
- `razon_social` → `company_name` VARCHAR(150) NULL
- `representante_legal` → `legal_representative` VARCHAR(100) NULL
- Agregar `institution_id`, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `collaborators` (
  `id`                   CHAR(36)                        NOT NULL,
  `institution_id`       CHAR(36)                        NOT NULL,
  `document_type_id`     CHAR(36)                        NOT NULL,
  `status_id`            CHAR(36)                        NOT NULL,
  `collaborator_type`    ENUM('Empleado','Contratista')  NOT NULL DEFAULT 'Contratista',
  `is_company`           TINYINT(1)                      NOT NULL DEFAULT 0,
  `document_number`      VARCHAR(20)                     NOT NULL,
  `document_issued_at`   DATE                            NULL,
  `first_name`           VARCHAR(80)                     NULL COMMENT 'NULL si es_empresa=1',
  `last_name`            VARCHAR(80)                     NULL COMMENT 'NULL si es_empresa=1',
  `company_name`         VARCHAR(150)                    NULL COMMENT 'Solo si es_empresa=1',
  `legal_representative` VARCHAR(100)                    NULL,
  `birth_date`           DATE                            NULL,
  `gender`               ENUM('F','M','O')               NULL,
  `email`                VARCHAR(100)                    NOT NULL DEFAULT '',
  `phone`                VARCHAR(30)                     NULL,
  `address`              VARCHAR(255)                    NULL,
  `deleted_at`           TIMESTAMP                       NULL,
  `created_at`           TIMESTAMP                       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP                       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_collaborators_institution (institution_id)`
- `INDEX idx_collaborators_document (institution_id, document_number)` — búsqueda frecuente
- `INDEX idx_collaborators_status (status_id)`
- `INDEX idx_collaborators_type (collaborator_type)`
- `UNIQUE INDEX uq_collaborators_doc (institution_id, document_type_id, document_number)` — evitar duplicados

**FKs:**
- `institution_id` → `institutions(id)` ON DELETE RESTRICT
- `document_type_id` → `document_types(id)` ON DELETE RESTRICT
- `status_id` → `collaborator_statuses(id)` ON DELETE RESTRICT

**Script de migración:**
```sql
-- 1. Desde sandbox colaboradores (ya normalizados)
INSERT INTO collaborators (
  id, institution_id, document_type_id, status_id,
  collaborator_type, is_company, document_number, document_issued_at,
  first_name, last_name, company_name, birth_date, gender,
  email, phone, address, created_at, updated_at
)
SELECT
  UUID(),
  '<institution_uuid>',
  (SELECT id FROM document_types WHERE code = CASE c.tipodoc WHEN 1 THEN 'CC' WHEN 2 THEN 'CE' WHEN 4 THEN 'NIT' ELSE 'CC' END LIMIT 1),
  (SELECT id FROM collaborator_statuses WHERE name = CASE c.estado_colaborador WHEN 1 THEN 'Activo' WHEN 2 THEN 'Extrabajador' ELSE 'Inactivo' END LIMIT 1),
  c.tipo_colaborador,
  IF(c.es_empresa = 'true', 1, 0),
  CAST(c.numdoc AS CHAR),
  c.expdoc,
  c.nombres,
  c.apellidos,
  c.razon_social,
  c.fecha_de_nacimiento,
  c.sexo,
  c.correo,
  c.telefono,
  c.direccion,
  c.created_at,
  c.updated_at
FROM colaboradores c;

-- 2. Desde legacy personal (contratistas que no estén ya en sandbox)
-- (Requiere verificación por document_number para evitar duplicados)
INSERT INTO collaborators (
  id, institution_id, document_type_id, status_id,
  collaborator_type, is_company, document_number, document_issued_at,
  first_name, last_name, birth_date, gender, email, created_at, updated_at
)
SELECT
  UUID(), '<institution_uuid>',
  (SELECT id FROM document_types WHERE code = p.tipodoc LIMIT 1),
  (SELECT id FROM collaborator_statuses WHERE name = 'Activo' LIMIT 1),
  IF(p.tipode_personal = 1, 'Empleado', 'Contratista'),
  IF(p.tipodoc = 'NIT', 1, 0),
  p.numdoc, p.expdoc, p.nombres, p.apellidos,
  p.fecha_nacimiento, p.sexo, p.email, NOW(), NOW()
FROM personal p
WHERE NOT EXISTS (
  SELECT 1 FROM collaborators nc
  WHERE nc.document_number = p.numdoc AND nc.institution_id = '<institution_uuid>'
);
```

---

### Tabla 8: `contracts` *(FUSIÓN)*

**Origen:**
- `contratos` (sandbox): tabla normalizada con FK a `colaborador_id` y `cargo_id`
- `contracts` (legacy): tabla con `numdoc` texto, `codigocargo`/`cargo` desnormalizados
- `contratistas` (legacy): datos de contrato mezclados con datos personales

**Antipatrones a corregir:**
- `honorarios` INT → `fee` DECIMAL(12,2) — renombrar y tipificar
- `salario` INT → `salary` DECIMAL(12,2)
- `cargo_id` / `cargo` texto → `position_id` UUID FK a `positions`
- `tipocontrato` TEXT → `contract_type_id` UUID FK a `contract_types`
- `estado_contrato` ENUM limitado → ampliar ENUM con todos los estados del negocio
- `colaborador_id` INT → `collaborator_id` UUID FK a `collaborators`
- `correo_cargo` → mover a `positions.email`
- Agregar `num_contract` VARCHAR(20) — número de contrato visible (ej: "001-2025")
- `objeto` TEXT → `object` TEXT
- `obligaciones` TEXT → `obligations` TEXT
- Agregar `institution_id`, `deleted_at`
- `fecha_fin` DATE NULL (contratos indefinidos no tienen fecha fin)
- Prórrogas (`finprorroga1/2/3` de `contratistas`) → tabla `contract_extensions`

**Estructura propuesta:**
```sql
CREATE TABLE `contracts` (
  `id`               CHAR(36)                                                      NOT NULL,
  `institution_id`   CHAR(36)                                                      NOT NULL,
  `collaborator_id`  CHAR(36)                                                      NOT NULL,
  `contract_type_id` CHAR(36)                                                      NOT NULL,
  `position_id`      CHAR(36)                                                      NULL,
  `num_contract`     VARCHAR(30)                                                   NULL COMMENT 'Ej: 001-2025',
  `contract_code`    VARCHAR(20)                                                   NULL COMMENT 'Código interno: CPS_00001',
  `object`           TEXT                                                          NULL,
  `obligations`      TEXT                                                          NULL,
  `start_date`       DATE                                                          NOT NULL,
  `end_date`         DATE                                                          NULL COMMENT 'NULL para contratos indefinidos',
  `salary`           DECIMAL(12,2)                                                 NOT NULL DEFAULT 0.00,
  `fee`              DECIMAL(12,2)                                                 NOT NULL DEFAULT 0.00 COMMENT 'Honorarios (contratistas)',
  `status`           ENUM('Vigente','Terminado','Liquidado','Cambio de cargo')     NOT NULL DEFAULT 'Vigente',
  `deleted_at`       TIMESTAMP                                                     NULL,
  `created_at`       TIMESTAMP                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP                                                     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_contracts_institution (institution_id)`
- `INDEX idx_contracts_collaborator (collaborator_id)`
- `INDEX idx_contracts_position (position_id)`
- `INDEX idx_contracts_status (status)`
- `INDEX idx_contracts_dates (start_date, end_date)` — para consultas de vigencia

**FKs:**
- `institution_id` → `institutions(id)` ON DELETE RESTRICT
- `collaborator_id` → `collaborators(id)` ON DELETE RESTRICT
- `contract_type_id` → `contract_types(id)` ON DELETE RESTRICT
- `position_id` → `positions(id)` ON DELETE SET NULL

**Script de migración:**
```sql
-- Desde sandbox contratos
INSERT INTO contracts (
  id, institution_id, collaborator_id, contract_type_id, position_id,
  num_contract, contract_code, object, obligations,
  start_date, end_date, salary, fee, status, created_at, updated_at
)
SELECT
  UUID(),
  '<institution_uuid>',
  (SELECT id FROM collaborators WHERE institution_id = '<institution_uuid>'
   AND document_number = CAST(col.numdoc AS CHAR) LIMIT 1),
  (SELECT id FROM contract_types WHERE institution_id = '<institution_uuid>'
   AND id = (SELECT id FROM contract_types WHERE institution_id = '<institution_uuid>'
   AND code = (SELECT codigotdc FROM tipodecontrato WHERE tipodecontrato = tc.descripcion LIMIT 1) LIMIT 1) LIMIT 1),
  (SELECT id FROM positions WHERE institution_id = '<institution_uuid>'
   AND id = (SELECT id FROM positions WHERE institution_id = '<institution_uuid>'
   ORDER BY created_at LIMIT 1) LIMIT 1), -- Mapeo por cargo_id
  sc.num_contrato,
  sc.codigo_contrato,
  sc.objeto,
  sc.obligaciones,
  sc.fecha_inicio,
  sc.fecha_fin,
  IFNULL(sc.salario, 0),
  IFNULL(sc.honorarios, 0),
  sc.estado_contrato,
  NOW(), NOW()
FROM contratos sc
JOIN colaboradores col ON col.id = sc.colaborador_id
JOIN tipos_de_contrato tc ON tc.id = sc.tipo_contrato_id;

-- Nota: Para legacy contracts y contratistas, requiere mapeo similar
-- con JOIN por document_number hacia la nueva tabla collaborators.
```

---

### Tabla 9: `contract_extensions` *(reemplaza `prorrogas_contratos` y columnas legacy)*

**Origen:**
- `prorrogas_contratos` (sandbox): tabla normalizada — USAR COMO BASE
- `finprorroga1`, `finprorroga2`, `finprorroga3` en `contratistas` (legacy): columnas desnormalizadas

**Cambios:**
- INT PK → UUID
- `contrato_id` INT → `contract_id` CHAR(36) UUID FK
- `fecha_prorroga` DATE → `new_end_date` DATE (la nueva fecha final)
- `motivo_causa` TEXT → `reason` TEXT
- Agregar `added_value` DECIMAL(12,2) NULL — valor adicionado en la prórroga
- Agregar `institution_id`, `deleted_at`, timestamps

**Estructura propuesta:**
```sql
CREATE TABLE `contract_extensions` (
  `id`              CHAR(36)      NOT NULL,
  `institution_id`  CHAR(36)      NOT NULL,
  `contract_id`     CHAR(36)      NOT NULL,
  `new_end_date`    DATE          NOT NULL,
  `added_value`     DECIMAL(12,2) NULL DEFAULT 0.00,
  `reason`          TEXT          NULL,
  `deleted_at`      TIMESTAMP     NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_contract_extensions_contract (contract_id)`
- `INDEX idx_contract_extensions_institution (institution_id)`

**FKs:**
- `contract_id` → `contracts(id)` ON DELETE CASCADE
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

**Script de migración:**
```sql
-- Desde sandbox prorrogas_contratos
INSERT INTO contract_extensions (id, institution_id, contract_id, new_end_date, reason, created_at, updated_at)
SELECT
  UUID(),
  '<institution_uuid>',
  (SELECT nc.id FROM contracts nc WHERE nc.collaborator_id IN (
     SELECT c.id FROM collaborators c JOIN contratos co ON co.colaborador_id =
     (SELECT id FROM colaboradores WHERE id = co.colaborador_id)
     WHERE co.id = p.contrato_id
   ) LIMIT 1),
  p.fecha_prorroga,
  p.motivo_causa,
  NOW(), NOW()
FROM prorrogas_contratos p;

-- Desde legacy contratistas (finprorroga1/2/3)
INSERT INTO contract_extensions (id, institution_id, contract_id, new_end_date, created_at, updated_at)
SELECT UUID(), '<institution_uuid>',
  (SELECT id FROM contracts WHERE collaborator_id =
    (SELECT id FROM collaborators WHERE document_number = ct.numdoc LIMIT 1)
  ORDER BY start_date DESC LIMIT 1),
  ct.finprorroga1, NOW(), NOW()
FROM contratistas ct WHERE ct.finprorroga1 IS NOT NULL AND ct.finprorroga1 != '0000-00-00';
-- Repetir para finprorroga2 y finprorroga3
```

---

### Tabla 10: `committed_values` *(mejora de `comprometidos`)*

**Origen:**
- `comprometidos` (sandbox): `contrato_id` INT, `cuenta_contable` TEXT, `centro_de_costo` TEXT, `valor_comprometido` DOUBLE
- `comprometidos` (legacy): `codigocontrato` TEXT, `valor_contrato` TEXT, `cuenta_contable` TEXT, `centro_decosto` TEXT

**Antipatrones a corregir:**
- `valor_comprometido` DOUBLE → `committed_amount` DECIMAL(12,2)
- `valor_contrato` TEXT → `committed_amount` DECIMAL(12,2)
- `cuenta_contable` TEXT → `accounting_account_id` CHAR(36) FK
- `centro_de_costo` TEXT → `cost_center_id` CHAR(36) FK
- `codigocontrato` TEXT (legacy) → `contract_id` CHAR(36) FK
- Agregar `institution_id`, timestamps, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `committed_values` (
  `id`                    CHAR(36)      NOT NULL,
  `institution_id`        CHAR(36)      NOT NULL,
  `contract_id`           CHAR(36)      NOT NULL,
  `accounting_account_id` CHAR(36)      NULL,
  `cost_center_id`        CHAR(36)      NULL,
  `committed_amount`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deleted_at`            TIMESTAMP     NULL,
  `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_committed_values_contract (contract_id)`
- `INDEX idx_committed_values_cost_center (cost_center_id)`
- `INDEX idx_committed_values_institution (institution_id)`

**FKs:**
- `contract_id` → `contracts(id)` ON DELETE RESTRICT
- `institution_id` → `institutions(id)` ON DELETE RESTRICT
- `accounting_account_id` → `accounting_accounts(id)` ON DELETE SET NULL
- `cost_center_id` → `cost_centers(id)` ON DELETE SET NULL

---

### Tabla 11: `position_change_history`

**Origen:** `historial_cambio_cargo` (sandbox) — no existe en legacy

**Cambios:**
- INT PK → UUID
- `contrato_id` INT → `contract_id` CHAR(36)
- `cargo_anterior_id` / `cargo_actual_id` INT → UUID FK a `positions`
- `salario_anterior` / `salario_actual` DECIMAL(10,2) ✓ (ya correcto)
- `update_at` typo → `updated_at`
- Agregar `institution_id`

**Estructura propuesta:**
```sql
CREATE TABLE `position_change_history` (
  `id`                  CHAR(36)      NOT NULL,
  `institution_id`      CHAR(36)      NOT NULL,
  `contract_id`         CHAR(36)      NOT NULL,
  `previous_position_id` CHAR(36)    NOT NULL,
  `new_position_id`     CHAR(36)      NOT NULL,
  `previous_salary`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `new_salary`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `changed_at`          DATE          NOT NULL,
  `observations`        VARCHAR(255)  NULL,
  `created_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_position_history_contract (contract_id)`
- `INDEX idx_position_history_institution (institution_id)`

**FKs:**
- `contract_id` → `contracts(id)` ON DELETE CASCADE
- `previous_position_id` → `positions(id)` ON DELETE RESTRICT
- `new_position_id` → `positions(id)` ON DELETE RESTRICT
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

---

### Tabla 12: `certificate_signatures`

**Origen:** `firmas_certificados` (sandbox) — charset `latin1` → convertir a `utf8mb4`

**Cambios:**
- INT PK → UUID
- `firma` TEXT → `signature_path` VARCHAR(500) (ruta al archivo, no base64 en DB)
- `nombre_firma` → `signer_name` VARCHAR(100)
- `cargo_firma` → `signer_position` VARCHAR(100)
- `nombre_reemplazo` → `substitute_name` VARCHAR(100) NULL
- `cargo_reemplazo` → `substitute_position` VARCHAR(100) NULL
- `firma_reemplazo` TEXT → `substitute_signature_path` VARCHAR(500) NULL
- `latin1` → `utf8mb4`
- Agregar `institution_id`, `is_active`, timestamps, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `certificate_signatures` (
  `id`                         CHAR(36)      NOT NULL,
  `institution_id`             CHAR(36)      NOT NULL,
  `signer_name`                VARCHAR(100)  NOT NULL,
  `signer_position`            VARCHAR(100)  NOT NULL,
  `signature_path`             VARCHAR(500)  NOT NULL,
  `substitute_name`            VARCHAR(100)  NULL,
  `substitute_position`        VARCHAR(100)  NULL,
  `substitute_signature_path`  VARCHAR(500)  NULL,
  `is_active`                  TINYINT(1)    NOT NULL DEFAULT 1,
  `deleted_at`                 TIMESTAMP     NULL,
  `created_at`                 TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                 TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_cert_signatures_institution (institution_id)`

---

### Tabla 13: `certifications` *(FUSIÓN de `validate_certifications` + `certificados`)*

**Origen:**
- `validate_certifications` (sandbox): más completa — USAR COMO BASE
- `certificados` (legacy): más simple, redundante

**Antipatrones a corregir:**
- `verificacion_code` CHAR(32) MD5 → `verify_code` CHAR(36) UUID (o mantener CHAR(32) pero generado con uuid)
- `colaborador` VARCHAR(50) / `identificacion` BIGINT → reemplazar con FK a `collaborators`
- `contrato` JSON → reemplazar con FK a `contracts` (los datos del contrato se consultan en tiempo real)
- `latin1` → `utf8mb4`
- `identificacion` BIGINT → guardar solo como referencia, usar FK real
- Agregar `institution_id`, `deleted_at`
- `conteodecertificados` → contador derivado (contar registros por tipo, NO tabla separada)
- Agregar `signature_id` FK a `certificate_signatures`
- Agregar `issued_by` (usuario que emitió)

**Estructura propuesta:**
```sql
CREATE TABLE `certifications` (
  `id`               CHAR(36)     NOT NULL,
  `institution_id`   CHAR(36)     NOT NULL,
  `collaborator_id`  CHAR(36)     NOT NULL,
  `contract_id`      CHAR(36)     NULL COMMENT 'Contrato específico, NULL = todos los contratos',
  `signature_id`     CHAR(36)     NULL,
  `verify_code`      CHAR(36)     NOT NULL UNIQUE,
  `addressed_to`     VARCHAR(100) NULL DEFAULT 'a quien interese',
  `issued_by_user_id` CHAR(36)    NULL COMMENT 'FK a users',
  `issued_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`       TIMESTAMP    NULL,
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `UNIQUE INDEX uq_certifications_verify_code (verify_code)`
- `INDEX idx_certifications_collaborator (collaborator_id)`
- `INDEX idx_certifications_institution (institution_id)`

**FKs:**
- `collaborator_id` → `collaborators(id)` ON DELETE RESTRICT
- `contract_id` → `contracts(id)` ON DELETE SET NULL
- `signature_id` → `certificate_signatures(id)` ON DELETE SET NULL
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

---

### Tabla 14: `cost_centers` *(mejora de `centros_de_costo`)*

**Origen:** `centros_de_costo` (sandbox, sin PK) + `centrosdecostos` (legacy, sin PK)

**Antipatrones a corregir:**
- Sin PK → UUID PK
- `id` INT NULL → PK UUID
- Datos TEXT → tipos correctos
- La estructura tiene dos niveles: `nombre_cdc` (área) y `nombre_sbcdc` (subcentro)
- `id_cdc` INT → referencia al área padre
- `centro_decostos` VARCHAR → código como `1-1`, `4-70` (se mantiene como `code` VARCHAR(20))
- Rediseñar como jerarquía: tabla padre/hijo auto-referencial

**Estructura propuesta:**
```sql
CREATE TABLE `cost_centers` (
  `id`             CHAR(36)     NOT NULL,
  `institution_id` CHAR(36)     NOT NULL,
  `parent_id`      CHAR(36)     NULL COMMENT 'NULL = nivel superior (área)',
  `legacy_id`      SMALLINT     NULL COMMENT 'id_cdc del legacy para migración',
  `code`           VARCHAR(20)  NOT NULL COMMENT 'Ej: 1-1, 4-70, 60-2',
  `name`           VARCHAR(200) NOT NULL,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `deleted_at`     TIMESTAMP    NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `UNIQUE INDEX uq_cost_centers_code_institution (institution_id, code)`
- `INDEX idx_cost_centers_parent (parent_id)`
- `INDEX idx_cost_centers_legacy_id (legacy_id)`

**FKs:**
- `parent_id` → `cost_centers(id)` ON DELETE SET NULL
- `institution_id` → `institutions(id)` ON DELETE RESTRICT

**Script de migración (2 pasos: primero padres, luego hijos):**
```sql
-- Paso 1: Insertar centros padre (únicos id_cdc)
INSERT INTO cost_centers (id, institution_id, parent_id, legacy_id, code, name, created_at, updated_at)
SELECT DISTINCT UUID(), '<institution_uuid>', NULL, id_cdc,
  CAST(id_cdc AS CHAR), nombre_cdc, NOW(), NOW()
FROM centros_de_costo
WHERE id_cdc IS NOT NULL
GROUP BY id_cdc;

-- Paso 2: Insertar sub-centros con referencia al padre
INSERT INTO cost_centers (id, institution_id, parent_id, legacy_id, code, name, created_at, updated_at)
SELECT UUID(), '<institution_uuid>',
  (SELECT p.id FROM cost_centers p WHERE p.legacy_id = c.id_cdc AND p.institution_id = '<institution_uuid>' LIMIT 1),
  NULL,
  c.centro_decostos,
  c.nombre_sbcdc,
  NOW(), NOW()
FROM centros_de_costo c
WHERE c.id IS NOT NULL; -- excluir los NULL del sandbox
```

---

### Tabla 15: `accounting_accounts`

**Origen:** `cuentas_contables` (sandbox) — sin PK real

**Cambios:**
- Sin PK → UUID PK
- `codigo_cuenta` BIGINT → `code` VARCHAR(15) (los códigos son 6-10 dígitos pero TEXT es incorrecto)
- `nombre_cuenta` TEXT → `name` VARCHAR(200)
- Agregar `institution_id`, timestamps, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `accounting_accounts` (
  `id`             CHAR(36)     NOT NULL,
  `institution_id` CHAR(36)     NOT NULL,
  `code`           VARCHAR(15)  NOT NULL,
  `name`           VARCHAR(200) NOT NULL,
  `deleted_at`     TIMESTAMP    NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `UNIQUE INDEX uq_accounting_accounts_code_institution (institution_id, code)`

---

### Tabla 16: `budget_balance` *(reemplaza `bppcc` del legacy)*

**Origen:** `bppcc` (legacy): balance de presupuesto por período, cuenta contable y centro de costo

**Antipatrones a corregir:**
- Todos los campos monetarios TEXT → DECIMAL(15,2)
- `transaccional` TEXT ('Si'/'No') → `is_transactional` TINYINT(1)
- `codigo_cuenta_contable` TEXT → `accounting_account_code` VARCHAR(15) + FK
- `mombre_centro_costo` typo → `cost_center_name` VARCHAR(200)
- Agregar `institution_id`, `period` YEAR, timestamps

**Estructura propuesta:**
```sql
CREATE TABLE `budget_balance` (
  `id`                    CHAR(36)      NOT NULL,
  `institution_id`        CHAR(36)      NOT NULL,
  `cost_center_id`        CHAR(36)      NULL,
  `accounting_account_id` CHAR(36)      NULL,
  `period`                YEAR          NOT NULL COMMENT 'Año fiscal',
  `level`                 VARCHAR(20)   NOT NULL COMMENT 'Clase/Grupo/Cuenta/Subcuenta/Auxiliar',
  `account_code`          VARCHAR(15)   NOT NULL,
  `account_name`          VARCHAR(200)  NOT NULL,
  `cost_center_code`      VARCHAR(20)   NULL,
  `cost_center_name`      VARCHAR(200)  NULL,
  `is_transactional`      TINYINT(1)    NOT NULL DEFAULT 0,
  `opening_balance`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `debit_movement`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `credit_movement`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `closing_balance`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_budget_balance_institution_period (institution_id, period)`
- `INDEX idx_budget_balance_cost_center (cost_center_id)`
- `INDEX idx_budget_balance_account (accounting_account_id)`

---

### Tabla 17: `accounting_ledger` *(basada en `auxiliar_contable` del sandbox)*

**Origen:** `auxiliar_contable` (sandbox) — ya tiene DECIMAL correcto

**Cambios:**
- BIGINT PK → UUID
- `codigo_contable` TEXT → `account_code` VARCHAR(15)
- `cuenta_contable` TEXT → `account_name` VARCHAR(200)
- `identificacion` BIGINT → `third_party_doc` VARCHAR(20)
- `nombre_del_tercero` TEXT → `third_party_name` VARCHAR(200)
- `centro_de_costo` TEXT → `cost_center_id` CHAR(36) FK + `cost_center_code` VARCHAR(20) de referencia
- `debito`/`credito` DECIMAL(12,2) ✓ — conservar
- Agregar `institution_id`, `period`, timestamps, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `accounting_ledger` (
  `id`                    CHAR(36)      NOT NULL,
  `institution_id`        CHAR(36)      NOT NULL,
  `accounting_account_id` CHAR(36)      NULL,
  `cost_center_id`        CHAR(36)      NULL,
  `account_code`          VARCHAR(15)   NOT NULL,
  `account_name`          VARCHAR(200)  NOT NULL,
  `voucher`               VARCHAR(30)   NULL COMMENT 'Comprobante contable',
  `entry_date`            DATE          NULL,
  `third_party_doc`       VARCHAR(20)   NULL,
  `third_party_name`      VARCHAR(200)  NULL,
  `cost_center_code`      VARCHAR(20)   NULL COMMENT 'Código de referencia para informes',
  `debit`                 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `credit`                DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deleted_at`            TIMESTAMP     NULL,
  `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_ledger_institution_date (institution_id, entry_date)`
- `INDEX idx_ledger_cost_center (cost_center_id)`
- `INDEX idx_ledger_third_party (third_party_doc)`

---

### Tabla 18: `portfolios` *(de `carteranodos` + `carteras` del legacy)*

**Origen:** `carteranodos` (legacy): montos TEXT por tipo de nodo; `carteras` (legacy): montos anuales

**Antipatrones a corregir:**
- Valores monetarios TEXT → DECIMAL(12,2)
- Columnas pivote (`administrativo`, `deportes`, `cultura`, `desarrollo`) → normalizar a filas
- Agregar `institution_id`, timestamps

**Estructura propuesta:**
```sql
CREATE TABLE `portfolios` (
  `id`             CHAR(36)      NOT NULL,
  `institution_id` CHAR(36)      NOT NULL,
  `cost_center_id` CHAR(36)      NULL,
  `year`           SMALLINT      NOT NULL,
  `description`    VARCHAR(200)  NOT NULL,
  `amount`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Script de migración (desnormalización de columnas → filas):**
```sql
-- Desde carteranodos (pivotear columnas a filas)
INSERT INTO portfolios (id, institution_id, cost_center_id, year, description, amount, created_at, updated_at)
SELECT UUID(), '<institution_uuid>',
  (SELECT id FROM cost_centers WHERE legacy_id = id_cdc AND institution_id = '<institution_uuid>' AND parent_id IS NOT NULL LIMIT 1),
  YEAR(NOW()), 'Cartera Administrativo', CAST(REPLACE(administrativo,'0','0') AS DECIMAL(12,2)),
  NOW(), NOW()
FROM carteranodos
WHERE administrativo != '0';
-- Repetir para deportes, cultura, desarrollo
```

---

### Tabla 19: `bank_initial_balances`

**Origen:** `saldoinicialbancos` (legacy): misma estructura pivote desnormalizada

**Antipatrones a corregir:**
- Valores TEXT → DECIMAL(12,2)
- Columnas pivote → filas
- Agregar `institution_id`, timestamps

**Estructura propuesta:**
```sql
CREATE TABLE `bank_initial_balances` (
  `id`             CHAR(36)      NOT NULL,
  `institution_id` CHAR(36)      NOT NULL,
  `cost_center_id` CHAR(36)      NULL,
  `year`           SMALLINT      NOT NULL,
  `category`       VARCHAR(50)   NOT NULL COMMENT 'administrativo/deportes/cultura/desarrollo',
  `amount`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Tabla 20: `asset_categories` *(de `categorias`)*

**Origen:** `categorias` (legacy, 11 registros)

**Cambios:**
- INT PK → UUID
- `categoria` TEXT → `name` VARCHAR(60)
- Agregar `institution_id`, timestamps, `deleted_at`
- `utf8mb3` → `utf8mb4`

**Estructura propuesta:**
```sql
CREATE TABLE `asset_categories` (
  `id`             CHAR(36)    NOT NULL,
  `institution_id` CHAR(36)    NOT NULL,
  `name`           VARCHAR(60) NOT NULL,
  `deleted_at`     TIMESTAMP   NULL,
  `created_at`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Datos de referencia (seeder):**
```php
$categories = ['ACCESORIOS','ASEO','AUDIO Y VIDEO','CAFETERIA','COCINA',
  'ELECTRODOMESTICOS','ENSERES','EQUIPOS DE COMPUTO','PAPELERIA','PERIFERICOS','TELECOMUNICACIONES'];
```

---

### Tabla 21: `assets` *(de `elementos`)*

**Origen:** `elementos` (legacy): `codigo_elemento` VARCHAR(8) PK, `elemento` TEXT, `categoria` TEXT, `estado` INT, `fecha_creacion` DATE

**Cambios:**
- PK `codigo_elemento` VARCHAR → conservar como `legacy_code` + agregar UUID PK
- `elemento` TEXT → `name` VARCHAR(150)
- `categoria` TEXT → `category_id` CHAR(36) FK a `asset_categories`
- `estado` INT → `is_active` TINYINT(1)
- Agregar `institution_id`, `deleted_at`, timestamps

**Estructura propuesta:**
```sql
CREATE TABLE `assets` (
  `id`             CHAR(36)     NOT NULL,
  `institution_id` CHAR(36)     NOT NULL,
  `category_id`    CHAR(36)     NULL,
  `legacy_code`    VARCHAR(10)  NULL UNIQUE COMMENT 'Código original: ADA-1541',
  `name`           VARCHAR(150) NOT NULL,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`     DATE         NULL,
  `deleted_at`     TIMESTAMP    NULL,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Nota:** El campo `created_at` se mantiene como DATE para preservar la fecha de ingreso del activo. Se agrega `registered_at` TIMESTAMP para el timestamp del registro en el sistema.

---

### Tabla 22: `locations` *(de `ubicaciones`)*

**Origen:** `ubicaciones` (legacy, 48 registros)

**Estructura propuesta:**
```sql
CREATE TABLE `locations` (
  `id`             CHAR(36)     NOT NULL,
  `institution_id` CHAR(36)     NOT NULL,
  `name`           VARCHAR(150) NOT NULL,
  `deleted_at`     TIMESTAMP    NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Tabla 23: `asset_entries` *(de `ingresode_elementos`)*

**Origen:** `ingresode_elementos` (legacy): vinculado a `elementos` por `codigo_elemento`

**Cambios:**
- INT PK → UUID
- `codigo_elemento` → `asset_id` CHAR(36) FK
- `dependencia` TEXT → `department_id` CHAR(36) FK NULL + `dependency_name` VARCHAR(100) para migración
- `estado_mtto` INT → `maintenance_status` TINYINT(1)
- `estado` INT → `is_active` TINYINT(1)
- Campos TEXT → VARCHAR donde apropiado
- `utf8mb3` → `utf8mb4`
- `fecha_ingreso` DATE NULL (muchos son `0000-00-00`)
- Agregar `institution_id`, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `asset_entries` (
  `id`                 CHAR(36)     NOT NULL,
  `institution_id`     CHAR(36)     NOT NULL,
  `asset_id`           CHAR(36)     NOT NULL,
  `dependency_name`    VARCHAR(100) NULL,
  `model`              VARCHAR(100) NULL,
  `brand`              VARCHAR(100) NULL,
  `serial`             VARCHAR(100) NOT NULL,
  `invoice`            VARCHAR(100) NULL,
  `warranty`           VARCHAR(100) NULL,
  `maintenance_date`   DATETIME     NULL,
  `maintenance_status` TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `entry_date`         DATE         NULL,
  `deleted_at`         TIMESTAMP    NULL,
  `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_asset_entries_asset (asset_id)`
- `UNIQUE INDEX uq_asset_entries_serial (serial)` — serial único por elemento
- `INDEX idx_asset_entries_institution (institution_id)`

---

### Tabla 24: `asset_assignments` *(de `solicitudde_elementos`)*

**Origen:** `solicitudde_elementos` (legacy): asignación de equipos a personas

**Cambios:**
- INT PK → UUID
- `numdoc_responsable` INT + `nombre_responsabe` TEXT → `assigned_by_id` CHAR(36) FK a `collaborators`
- `numdoc_recibe` INT + `nombre_recibe` TEXT → `assigned_to_id` CHAR(36) FK a `collaborators`
- `serial` TEXT → `asset_entry_id` CHAR(36) FK a `asset_entries`
- `puestode_tabajo` typo TEXT → `work_position` VARCHAR(100)
- `estado` INT → `status` TINYINT(1)
- Agregar `institution_id`, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `asset_assignments` (
  `id`              CHAR(36)     NOT NULL,
  `institution_id`  CHAR(36)     NOT NULL,
  `asset_entry_id`  CHAR(36)     NOT NULL,
  `assigned_by_id`  CHAR(36)     NULL,
  `assigned_to_id`  CHAR(36)     NOT NULL,
  `work_position`   VARCHAR(100) NULL,
  `status`          TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '0=activa, 1=devuelta',
  `observations`    TEXT         NULL,
  `evidence_path`   VARCHAR(500) NULL,
  `assigned_date`   DATE         NOT NULL,
  `return_date`     DATE         NULL,
  `deleted_at`      TIMESTAMP    NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices:**
- `INDEX idx_asset_assignments_entry (asset_entry_id)`
- `INDEX idx_asset_assignments_assigned_to (assigned_to_id)`
- `INDEX idx_asset_assignments_institution (institution_id)`

---

### Tabla 25: `asset_returns` *(de `devolucionde_elementos`)*

**Origen:** `devolucionde_elementos` (legacy): devoluciones de activos

**Cambios:** Mismos patrones que `asset_assignments`

**Estructura propuesta:**
```sql
CREATE TABLE `asset_returns` (
  `id`              CHAR(36)     NOT NULL,
  `institution_id`  CHAR(36)     NOT NULL,
  `asset_entry_id`  CHAR(36)     NOT NULL,
  `returned_by_id`  CHAR(36)     NULL,
  `received_by_id`  CHAR(36)     NULL,
  `status`          TINYINT(1)   NOT NULL DEFAULT 1,
  `observations`    TEXT         NULL,
  `evidence_path`   VARCHAR(500) NULL,
  `return_date`     DATE         NOT NULL,
  `deleted_at`      TIMESTAMP    NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Tabla 26: `supplies` *(de `productos`)*

**Origen:** `productos` (legacy): insumos de oficina/aseo

**Cambios:**
- INT PK → UUID
- `codigoproducto` TEXT → `code` VARCHAR(15) + `legacy_code` para migración
- `producto` TEXT → `name` VARCHAR(150)
- `categoria` TEXT → `category_id` CHAR(36) FK
- `ubicacion` TEXT → `location_id` CHAR(36) FK a `locations`
- `presentacion` TEXT → `unit` VARCHAR(30)
- `es_perecedero` INT → `is_perishable` TINYINT(1)
- `utf8mb3` → `utf8mb4`
- Agregar `institution_id`, `deleted_at`

**Estructura propuesta:**
```sql
CREATE TABLE `supplies` (
  `id`              CHAR(36)     NOT NULL,
  `institution_id`  CHAR(36)     NOT NULL,
  `category_id`     CHAR(36)     NULL,
  `location_id`     CHAR(36)     NULL,
  `code`            VARCHAR(15)  NULL,
  `name`            VARCHAR(150) NOT NULL,
  `unit`            VARCHAR(30)  NULL,
  `detail`          TEXT         NULL,
  `minimum_stock`   INT          NOT NULL DEFAULT 5,
  `current_stock`   INT          NOT NULL DEFAULT 0,
  `is_perishable`   TINYINT(1)   NOT NULL DEFAULT 0,
  `deleted_at`      TIMESTAMP    NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Fase 3: Orden de Creación de Tablas (respetando FKs)

```
Nivel 0 — Sin dependencias externas (solo institution_id de tabla ya existente):
  1. institutions  (tabla de Laravel ya existe)
  2. users         (tabla de Laravel ya existe)
  3. document_types
  4. collaborator_statuses
  5. contract_types
  6. asset_categories
  7. locations

Nivel 1 — Dependen de Nivel 0:
  8. departments
  9. cost_centers (auto-referencial: crear tabla, luego insertar padres, luego hijos)
  10. accounting_accounts

Nivel 2 — Dependen de Nivel 1:
  11. positions          (→ departments)
  12. collaborators      (→ document_types, collaborator_statuses)

Nivel 3 — Dependen de Nivel 2:
  13. contracts          (→ collaborators, contract_types, positions)
  14. assets             (→ asset_categories)
  15. supplies           (→ asset_categories, locations)
  16. certificate_signatures
  17. budget_balance     (→ cost_centers, accounting_accounts)
  18. accounting_ledger  (→ cost_centers, accounting_accounts)
  19. portfolios         (→ cost_centers)
  20. bank_initial_balances (→ cost_centers)

Nivel 4 — Dependen de Nivel 3:
  21. position_functions     (→ positions)
  22. contract_extensions    (→ contracts)
  23. committed_values       (→ contracts, accounting_accounts, cost_centers)
  24. position_change_history (→ contracts, positions)
  25. certifications         (→ collaborators, contracts, certificate_signatures)
  26. asset_entries          (→ assets)

Nivel 5 — Dependen de Nivel 4:
  27. asset_assignments      (→ asset_entries, collaborators)
  28. asset_returns          (→ asset_entries, collaborators)
```

---

## Fase 4: Análisis de Impacto en el Proyecto Laravel Actual

### Modelos que deben modificarse

| Modelo actual | Situación | Cambio requerido |
|---|---|---|
| `App\Models\RH\Employee` | REEMPLAZAR | Eliminar el modelo `Employee` — unificarlo con `Contractor` en `Collaborator` |
| `App\Models\RH\Contractor` | REEMPLAZAR | Eliminar el modelo `Contractor` — unificarlo en `Collaborator` |
| `App\Models\RH\Contract` | MODIFICAR | Quitar relación polimórfica `morphTo` → reemplazar con `belongsTo(Collaborator)` directo |
| `App\Models\RH\Position` | MODIFICAR | Agregar campos `code`, `email`; ajustar relaciones |
| `App\Models\RH\Department` | MODIFICAR | Agregar `parent_id`, `code`; agregar relación `parent()` y `children()` |

**Detalle crítico — Contrato polimórfico vs. directo:**

El modelo `Contract` actual usa una relación polimórfica `contractable` (morphTo) para apuntar a `Employee` o `Contractor`. Con la unificación en `Collaborator`, esto se simplifica a una FK directa:

```php
// ANTES (polimórfico — eliminar)
public function contractable(): MorphTo { return $this->morphTo(); }

// DESPUÉS (directo — reemplazar con)
public function collaborator(): BelongsTo {
    return $this->belongsTo(Collaborator::class);
}
```

**Migración de colaboradores a modificar:**

| Archivo | Cambio |
|---|---|
| `2026_03_21_000003_create_employees_table.php` | ELIMINAR — reemplazar por `create_collaborators_table.php` |
| `2026_03_21_000004_create_contractors_table.php` | ELIMINAR — consolidado en `collaborators` |
| `2026_03_21_000005_create_contracts_table.php` | MODIFICAR — quitar morphs, agregar `collaborator_id` directo, `contract_type_id`, `fee`, `num_contract`, `contract_code` |
| `2026_03_21_000002_create_positions_table.php` | MODIFICAR — agregar `code`, `email` |
| `2026_03_21_000001_create_departments_table.php` | MODIFICAR — agregar `parent_id`, `code` |

**Nuevas migraciones a crear:**

```
create_document_types_table
create_collaborator_statuses_table
create_contract_types_table
create_collaborators_table          ← reemplaza employees + contractors
create_contracts_table              ← modificada
create_contract_extensions_table
create_committed_values_table
create_position_functions_table
create_position_change_history_table
create_certificate_signatures_table
create_certifications_table
create_cost_centers_table
create_accounting_accounts_table
create_budget_balance_table
create_accounting_ledger_table
create_portfolios_table
create_bank_initial_balances_table
create_asset_categories_table
create_locations_table
create_assets_table
create_asset_entries_table
create_asset_assignments_table
create_asset_returns_table
create_supplies_table
```

### Relaciones Eloquent que cambian

```php
// Collaborator — reemplaza Employee + Contractor
class Collaborator extends Model {
    // Relaciones:
    public function documentType(): BelongsTo  // → document_types
    public function status(): BelongsTo        // → collaborator_statuses
    public function contracts(): HasMany        // → contracts
    public function certifications(): HasMany   // → certifications
    public function assetAssignments(): HasMany // → asset_assignments
}

// Contract — simplificado (sin morph)
class Contract extends Model {
    public function collaborator(): BelongsTo    // → collaborators
    public function contractType(): BelongsTo   // → contract_types
    public function position(): BelongsTo       // → positions
    public function extensions(): HasMany       // → contract_extensions
    public function committedValues(): HasMany  // → committed_values
    public function positionHistory(): HasMany  // → position_change_history
}

// Position — ampliado
class Position extends Model {
    public function functions(): HasMany        // → position_functions
    public function contracts(): HasMany        // → contracts
    // Scope para contratos vigentes:
    public function activeContracts(): HasMany  // → contracts WHERE status='Vigente'
}
```

### Vistas del sandbox a reemplazar con Eloquent

| Vista legacy/sandbox | Equivalente Eloquent propuesto |
|---|---|
| `get_all_collaborators` | `Collaborator::with(['status','documentType','contracts.position'])->where('institution_id', ...)` |
| `get_all_contracts` | `Contract::with(['collaborator','contractType','position','extensions'])->where('institution_id', ...)` |
| `get_collaborators_position` | `Contract::where('status','Vigente')->with(['collaborator','position'])` |
| `informe_comprometidos` | `CommittedValue::with(['contract.collaborator','accountingAccount','costCenter'])->whereHas(...)` |
| `colaborador_comprometido` | Scope en `CommittedValue` o Query Builder en `CommittedValueService` |
| `PS_CONSOLIDADO_NODOS` (stored proc) | `BudgetBalance::consolidatedByNode($costCenterId)` — scope en modelo |
| `PS_CONSOLIDADO_PROYECTOS` (stored proc) | `BudgetBalance::consolidatedByProject($costCenterId)` — scope en modelo |
| `PS_CONSOLIDADO_REDES` (stored proc) | `BudgetBalance::consolidatedByNetwork($costCenterId)` — scope en modelo |

---

## Resumen de Antipatrones Corregidos

| Antipatrón | Dónde aparecía | Solución aplicada |
|---|---|---|
| `TEXT` para dinero (`salario`, `honorarios`, `valor_contrato`) | `empleados`, `contratistas`, `comprometidos` legacy | `DECIMAL(12,2)` |
| `INT`/`BIGINT` para número de documento | `empleados.numdoc`, `colaboradores.numdoc` | `VARCHAR(20)` |
| `TEXT` para tipos y estados | `empleados.tipocontrato`, `empleados.estado` | FKs a tablas de referencia |
| Fechas `'0000-00-00'` | `empleados.fechafin`, `ingresode_elementos.fecha_ingreso` | `DATE NULL` |
| Prórrogas como columnas | `contratistas.finprorroga1/2/3` | Tabla `contract_extensions` |
| Cargo desnormalizado en contrato | `empleados.cargo`, `contracts.cargo` | FK `position_id` a `positions` |
| `es_empresa VARCHAR(6)` ('true'/'false') | `colaboradores.es_empresa` | `is_company TINYINT(1)` |
| Sin PK en tabla | `centros_de_costo`, `cuentas_contables` | UUID PK |
| `utf8mb3` | Todas las tablas legacy | `utf8mb4 COLLATE utf8mb4_unicode_ci` |
| `latin1` | `firmas_certificados`, `validate_certifications` | `utf8mb4` |
| Sin `deleted_at` (SoftDeletes) | Todas las tablas de negocio | `deleted_at TIMESTAMP NULL` |
| Sin `institution_id` | Todas las tablas | `institution_id CHAR(36) NOT NULL` |
| Relación polimórfica innecesaria | `contracts` (morphTo Employee/Contractor) | FK directa a `collaborators` |
| Datos personales y de contrato mezclados | `empleados`, `contratistas` | Separación en `collaborators` + `contracts` |
| Stored procedures con lógica de negocio | `PS_CONSOLIDADO_*` | Eloquent scopes en `BudgetBalance` |
| Valores DOUBLE para dinero | `comprometidos.valor_comprometido` | `DECIMAL(12,2)` |
| `nombre_del_tercero` repetido en auxiliar | `auxiliar_contable` | Campo de referencia + FK a `collaborators` cuando aplique |

---

**Este plan está listo para revisión. Una vez aprobado, se procederá con la implementación de las migraciones Laravel en el orden indicado en la Fase 3.**
