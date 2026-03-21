---
nombre: gitflow-wis
descripcion: >
  Gestionar el versionado del proyecto WIS ASCUN con GitFlow durante la migración a
  Laravel 12. Usar esta skill siempre que se vaya a crear una rama, hacer un commit,
  abrir un PR, cerrar una fase del plan de migración, publicar un release o resolver
  conflictos. Los mensajes de commit son en español colombiano siguiendo Conventional Commits.
---

# SKILL: GitFlow — WIS ASCUN Laravel 12

Esta guía define el flujo de trabajo Git para mantener el proyecto WIS ASCUN versionado, trazable y colaborativo durante toda la migración.

> **Regla de oro:** Nunca hacer commits directamente sobre `main` ni `develop`.
> Todo cambio entra por Pull Request.

---

## Estructura de Ramas

```
main                         ← producción — solo releases estables
develop                      ← integración — rama base de todo desarrollo
│
├── feature/WIS-{n}-{desc}   ← nuevas funcionalidades
├── fix/WIS-{n}-{desc}        ← corrección de bugs en develop
├── hotfix/WIS-{n}-{desc}     ← correcciones urgentes en producción
└── release/v{X.Y.Z}          ← preparación de release
```

### Nomenclatura de Ramas

| Tipo | Patrón | Ejemplo |
|---|---|---|
| Nueva funcionalidad | `feature/WIS-{n}-{descripcion-kebab}` | `feature/WIS-42-crud-empleados` |
| Corrección bug | `fix/WIS-{n}-{descripcion-kebab}` | `fix/WIS-89-error-calculo-salario` |
| Hotfix producción | `hotfix/WIS-{n}-{descripcion-kebab}` | `hotfix/WIS-101-sesion-no-persiste` |
| Release | `release/v{X.Y.Z}` | `release/v0.2.0` |
| Experimento | `experiment/{descripcion}` | `experiment/livewire-certificado` |

---

## Conventional Commits en Español

### Formato

```
{tipo}({ámbito}): {descripción en presente imperativo}

[cuerpo opcional — qué y por qué, no cómo]

Refs: WIS-{n}
```

### Tipos

| Tipo | Cuándo usarlo |
|---|---|
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de bug |
| `refactor` | Reestructura sin cambio de comportamiento |
| `migration` | Migration de tabla, seeder o dato legado |
| `security` | Corrección de vulnerabilidad de seguridad |
| `test` | Agregar o corregir tests |
| `docs` | Documentación |
| `chore` | Configuración, dependencias, CI |
| `style` | Cambios de formato/Tailwind sin lógica |
| `perf` | Mejora de rendimiento |
| `revert` | Revertir commit anterior |

### Ámbitos del Proyecto

```
auth, rh, contabilidad, inventario, certificados,
bd, permisos, pdf, livewire, config, tests, deps
```

### Ejemplos de Commits Válidos

```bash
git commit -m "feat(auth): implementar login con cédula, fecha de expedición y contraseña"
git commit -m "migration(bd): crear tabla empleados con UUID como clave primaria"
git commit -m "security(auth): reemplazar crypt() por Hash::make() en autenticación"
git commit -m "feat(livewire): crear componente listado de empleados con paginación"
git commit -m "fix(contabilidad): corregir N+1 en consulta de informe NDS"
git commit -m "refactor(rh): extraer lógica de contratos a ContractService"
git commit -m "feat(permisos): mapear roles legacy a spatie/laravel-permission"
git commit -m "test(auth): agregar pruebas de integración para flujo de login"
git commit -m "feat(certificados): generar PDF con QR de verificación vía DomPDF"
git commit -m "chore(deps): instalar owen-it/laravel-auditing v14"
git commit -m "style(rh): aplicar dark mode en vista de detalle de empleado"
git commit -m "perf(contabilidad): agregar índice compuesto en tabla nodos"
git commit -m "docs: agregar plan de migración en MIGRATION.md"
```

### Breaking Changes

```
feat(auth)!: reemplazar autenticación por email con cédula + fecha + contraseña

BREAKING CHANGE: Las sesiones existentes quedan invalidadas al hacer deploy.
Los usuarios deben iniciar sesión nuevamente con sus credenciales actualizadas.

Refs: WIS-15
```

---

## Flujo de Trabajo por Tipo de Tarea

### Nueva funcionalidad

```bash
# 1. Actualizar develop
git checkout develop && git pull origin develop

# 2. Crear rama
git checkout -b feature/WIS-{n}-{descripcion}
git push -u origin feature/WIS-{n}-{descripcion}

# 3. Commits atómicos durante el desarrollo
git add app/Models/RH/Employee.php
git commit -m "feat(rh): agregar modelo Empleado con UUID y auditoría"

git add database/migrations/
git commit -m "migration(bd): crear tabla empleados con UUID"

git add app/Services/RH/EmployeeService.php
git commit -m "feat(rh): implementar EmployeeService con operaciones CRUD"

git add tests/
git commit -m "test(rh): agregar pruebas de integración para gestión de empleados"

# 4. Actualizar desde develop antes del PR
git fetch origin && git rebase origin/develop

# 5. Push final y abrir PR
git push origin feature/WIS-{n}-{descripcion}
```

### Hotfix urgente en producción

```bash
git checkout main && git pull origin main
git checkout -b hotfix/WIS-{n}-{descripcion}

git commit -m "security(auth): corregir inyección SQL crítica en consulta de login"

# Merge a main
git checkout main && git merge --no-ff hotfix/WIS-{n}-{descripcion}
git tag -a v{X.Y.Z+1} -m "hotfix: corregir inyección SQL en login"
git push origin main --tags

# Sincronizar en develop
git checkout develop && git merge --no-ff hotfix/WIS-{n}-{descripcion}
git push origin develop

# Limpiar
git branch -d hotfix/WIS-{n}-{descripcion}
git push origin --delete hotfix/WIS-{n}-{descripcion}
```

### Release

```bash
git checkout develop && git pull origin develop
git checkout -b release/v{X.Y.Z}

# Ajustes finales: CHANGELOG, .env.example, revisión
git commit -m "chore(release): preparar versión v{X.Y.Z}"

# Merge a main con tag
git checkout main && git merge --no-ff release/v{X.Y.Z}
git tag -a v{X.Y.Z} -m "release: v{X.Y.Z} — {descripción del contenido}"
git push origin main --tags

# Sincronizar en develop
git checkout develop && git merge --no-ff release/v{X.Y.Z}
git push origin develop

# Limpiar
git branch -d release/v{X.Y.Z}
git push origin --delete release/v{X.Y.Z}
```

---

## Ciclo de Vida por Fase

```
develop
  │
  ├──► feature/WIS-001-arquitectura-base   → PR → merge a develop
  ├──► feature/WIS-015-autenticacion       → PR → merge a develop
  │         ↓ (auth + infra estables)
  │    release/v0.1.0  → main + tag v0.1.0
  │
  ├──► feature/WIS-030-modulo-rh           → PR → merge a develop
  ├──► feature/WIS-045-certificados        → PR → merge a develop
  │         ↓
  │    release/v0.2.0  → main + tag v0.2.0
  │
  ├──► feature/WIS-060-contabilidad        → PR → merge a develop
  ├──► feature/WIS-080-inventario          → PR → merge a develop
  │         ↓
  │    release/v0.3.0 ... → v1.0.0 (migración completa)
```

---

## Pull Request Template

**Título:** `[WIS-{n}] {descripción breve en español}`

**Body:**
```markdown
## Descripción
Qué hace este PR y por qué es necesario.

## Cambios realizados
- [ ] ...

## Checklist
- [ ] `php artisan test` pasa sin errores
- [ ] `./vendor/bin/pint --test` sin violaciones
- [ ] Sin credenciales en el código fuente
- [ ] Migrations tienen `down()` implementado
- [ ] Mensajes de interfaz en español colombiano
- [ ] Models nuevos tienen UUID + SoftDeletes + Auditable
- [ ] Acciones CRUD protegidas con Policy

Refs: WIS-{n}
```

---

## Versionado Semántico

```
v{MAYOR}.{MENOR}.{PARCHE}

MAYOR → cambio de arquitectura incompatible con versión anterior
MENOR → nueva fase o módulo funcional completo
PARCHE → correcciones, hotfixes, ajustes menores
```

**Hitos planeados:**

| Tag | Contenido |
|---|---|
| `v0.1.0` | Infraestructura base + Autenticación (brechas críticas resueltas) |
| `v0.2.0` | Módulo RH + Certificados |
| `v0.3.0` | Módulo Contabilidad |
| `v0.4.0` | Módulo Inventario |
| `v1.0.0` | Migración completa + Frontend final |

---

## Archivos que Nunca Deben Commitearse

Verificar que `.gitignore` incluya:

```gitignore
.env
.env.backup
/vendor/
/node_modules/
/public/hot
/public/storage
/storage/app/
/storage/logs/
/storage/framework/
/bootstrap/cache/
```

> ⚠️ Si `.env` fue commiteado alguna vez, rotar **todas** las credenciales antes de continuar.

---

## Comandos de Referencia Rápida

```bash
# Ver estado del repositorio
git status
git log --oneline --graph --decorate --all

# Sincronizar con develop antes de trabajar
git fetch origin && git rebase origin/develop

# Limpiar ramas locales ya mergeadas
git branch --merged develop | grep -v "develop\|main" | xargs git branch -d

# Ver qué hay en develop pendiente de release
git log main..develop --oneline
```

---

## Guardrails

- **Nunca `git push --force`** sobre `main` o `develop`.
- **Nunca commitear `.env`** — rotar credenciales si ocurre accidentalmente.
- **No mezclar cambios de múltiples módulos** en una sola rama.
- **Pedir aprobación antes de `git rebase`** sobre ramas compartidas — reescribe historia.
- **Cada merge a `develop` debe pasar `php artisan test`** — nunca mergear con tests en rojo.
- Un commit = un cambio lógico atómico. No agrupar todo en un solo commit gigante.
