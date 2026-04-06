---
nombre: gitflow-wis
descripcion: >
  Gestionar el versionado del proyecto WIS con GitFlow durante la migración a Laravel 11.
  Usar esta skill siempre que se vaya a crear una rama, hacer un commit, abrir un PR,
  cerrar una fase del plan de migración, publicar un release, o resolver conflictos.
  También aplicar cuando se trabaje en paralelo sobre múltiples módulos (Finanzas, RR.HH.,
  Inventario, etc.) o cuando se necesite coordinar cambios entre el sistema WIS legado
  y el nuevo proyecto Laravel. Cargar antes de cualquier operación Git del proyecto.
---

# SKILL: GitFlow — Migración WIS → Laravel 11

Esta guía define el flujo de trabajo Git para mantener el proyecto versionado, trazable
y colaborativo durante toda la migración. Cada fase del plan tiene su rama, cada módulo
su ciclo de vida, y cada deploy su etiqueta.

---

## Estructura de Ramas

```
main                    ← producción — solo releases estables
develop                 ← integración — rama base de todo desarrollo
│
├── feature/fase-0-infraestructura
├── feature/fase-1-migraciones-db
├── feature/fase-2-auth
├── feature/fase-3-modelos-eloquent
├── feature/fase-4-controladores-rutas
├── feature/fase-5-livewire
├── feature/fase-6-pdf-exportaciones
├── feature/fase-7-informes-financieros
├── feature/fase-8-auditoria
├── feature/fase-9-frontend
│
├── feature/{modulo}-{descripcion-corta}   ← tareas dentro de una fase
│
├── hotfix/{descripcion-corta}             ← correcciones urgentes en main
└── release/v{X.Y.Z}                       ← preparación de release
```

> **Regla de oro:** Nunca hacer commits directamente sobre `main` ni `develop`.
> Todo cambio entra por Pull Request.

---

## Flujo de Trabajo por Tarea

### 1. Iniciar trabajo en una fase nueva

```bash
# Asegurarse de tener develop actualizado
git checkout develop
git pull origin develop

# Crear rama de feature desde develop
git checkout -b feature/fase-2-auth
git push -u origin feature/fase-2-auth
```

### 2. Convención de nombres de ramas

| Tipo | Patrón | Ejemplo |
|------|--------|---------|
| Fase completa | `feature/fase-{N}-{nombre}` | `feature/fase-2-auth` |
| Tarea de módulo | `feature/{modulo}-{descripcion}` | `feature/rrhh-contrato-form` |
| Corrección urgente | `hotfix/{descripcion}` | `hotfix/sql-injection-usuarios` |
| Release | `release/v{X.Y.Z}` | `release/v1.0.0` |
| Experimento | `experiment/{descripcion}` | `experiment/livewire-volt-cert` |

### 3. Commits: Conventional Commits

Todo commit debe seguir el estándar [Conventional Commits](https://www.conventionalcommits.org/):

```
{tipo}({scope}): {descripcion imperativa en español}
```

**Tipos permitidos:**

| Tipo | Cuándo usarlo |
|------|---------------|
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de bug |
| `refactor` | Reestructura sin cambio de comportamiento |
| `migrate` | Migración de tabla, seeder o dato legado |
| `security` | Corrección de brecha de seguridad |
| `test` | Agregar o corregir tests PHPUnit |
| `docs` | Documentación |
| `chore` | Configuración, dependencias, CI |
| `style` | Cambios de formato/Tailwind sin lógica |

**Scopes recomendados para WIS:**

`auth`, `users`, `rrhh`, `finanzas`, `inventario`, `certificaciones`, `solicitudes`,
`db`, `permisos`, `pdf`, `livewire`, `config`, `tests`

**Ejemplos:**

```bash
git commit -m "feat(auth): implementar LoginController con session regeneration"
git commit -m "migrate(db): agregar migración create_collaborators_table con UUID"
git commit -m "security(auth): reemplazar crypt() por Hash::make() en UsersSeeder"
git commit -m "feat(livewire): crear componente UserTable con paginación por cursor"
git commit -m "fix(finanzas): corregir N+1 en consulta InformeER con eager loading"
git commit -m "refactor(rrhh): extraer lógica SP_CHANGE_POSITION a PositionService"
git commit -m "feat(permisos): mapear 12 flags binarios a spatie permissions"
git commit -m "test(auth): agregar PHPUnit suite para login y CSRF"
```

### 4. Integrar trabajo a develop (Pull Request)

Antes de abrir el PR:

```bash
# Traer últimos cambios de develop y resolver conflictos localmente
git fetch origin
git rebase origin/develop

# Verificar que las migraciones corren sin errores
php artisan migrate:fresh --seed

# Correr los tests
php artisan test

# Push final
git push origin feature/fase-2-auth
```

**Checklist del PR** (basado en R6 del plan):

- [ ] Sigue convenciones de nomenclatura Laravel
- [ ] No contiene métodos deprecados
- [ ] Lógica de negocio separada de controladores (en `Services/`)
- [ ] Form Requests para toda validación
- [ ] Tests incluidos si la tarea lo requiere
- [ ] `php artisan migrate:fresh --seed` pasa sin errores
- [ ] `php artisan test` pasa sin fallos
- [ ] Sin credenciales ni `.env` commiteados
- [ ] Sin SQL en crudo sin justificación explícita

---

## Ciclo de Vida por Fase del Plan

```
develop
  │
  ├──► feature/fase-0-infraestructura  ──► PR ──► merge a develop
  │         ↓ (base lista)
  ├──► feature/fase-1-migraciones-db   ──► PR ──► merge a develop
  │         ↓
  ├──► feature/fase-2-auth             ──► PR ──► merge a develop
  │         ↓ (auth estable = hito)
  │    release/v0.1.0  ──► merge a main + tag v0.1.0
  │
  ├──► feature/fase-3-modelos-eloquent ──► PR ──► merge a develop
  ├──► feature/fase-4-controladores    ──► PR ──► merge a develop
  │         ↓ (CRUD base operativo)
  │    release/v0.2.0  ──► merge a main + tag v0.2.0
  │
  ├──► feature/fase-7-informes         ──► PR ──► merge a develop
  │         ↓ (módulo financiero)
  │    release/v0.3.0  ──► merge a main + tag v0.3.0
  │
  └──► ... fases 5, 6, 8, 9 ...
            ↓
       release/v1.0.0  ──► merge a main + tag v1.0.0  (migración completa)
```

---

## Estrategia de Releases

### Versioning semántico para WIS

```
v{MAYOR}.{MENOR}.{PARCHE}

MAYOR  → cambio de arquitectura o BD incompatible con versión anterior
MENOR  → nueva fase o módulo funcional completo
PARCHE → correcciones, hotfixes, ajustes menores
```

**Hitos de release sugeridos:**

| Tag | Contenido |
|-----|-----------|
| `v0.1.0` | Fases 0+1+2 — Infraestructura + Auth (brechas críticas resueltas) |
| `v0.2.0` | Fases 3+4 — Modelos + Controladores + Rutas |
| `v0.3.0` | Fase 7 — Informes financieros operativos |
| `v0.4.0` | Fase 5 — Livewire (reemplaza AJAX handlers) |
| `v0.5.0` | Fase 3 RR.HH. — Módulo contratos y colaboradores |
| `v0.6.0` | Fase 6 — PDF / Excel / QR |
| `v0.7.0` | Fase 8 — Auditoría completa |
| `v1.0.0` | Fase 9 — Frontend final + migración completa |

### Crear un release

```bash
# Desde develop actualizado
git checkout develop
git pull origin develop
git checkout -b release/v0.1.0

# Ajustes finales: versión en config, CHANGELOG, revisión de .env.example
# ... commits de preparación ...

# Merge a main y etiquetar
git checkout main
git merge --no-ff release/v0.1.0
git tag -a v0.1.0 -m "Release v0.1.0: Infraestructura + Auth + Brechas críticas resueltas"
git push origin main --tags

# Merge de vuelta a develop para sincronizar
git checkout develop
git merge --no-ff release/v0.1.0
git push origin develop

# Limpiar rama de release
git branch -d release/v0.1.0
git push origin --delete release/v0.1.0
```

---

## Hotfixes en Producción

Para correcciones urgentes sobre `main` (e.g., una brecha de seguridad detectada):

```bash
# Crear hotfix desde main
git checkout main
git pull origin main
git checkout -b hotfix/csrf-bypass-fix

# Corregir, testear, commitear
git commit -m "security(auth): corregir bypass de CSRF en handler AJAX legado"

# Merge a main con nuevo parche
git checkout main
git merge --no-ff hotfix/csrf-bypass-fix
git tag -a v0.1.1 -m "Hotfix v0.1.1: corrección CSRF bypass"
git push origin main --tags

# Merge también a develop
git checkout develop
git merge --no-ff hotfix/csrf-bypass-fix
git push origin develop

# Limpiar
git branch -d hotfix/csrf-bypass-fix
git push origin --delete hotfix/csrf-bypass-fix
```

---

## Archivos que Nunca Deben Commitearse

Verificar que `.gitignore` incluya:

```gitignore
# Laravel
.env
.env.backup
/vendor/
/node_modules/
/public/hot
/public/storage
/storage/*.key
/storage/app/
/storage/logs/
/storage/framework/
/bootstrap/cache/

# WIS legado (si el repositorio lo incluye temporalmente)
Models/Conexion.php       # contiene credenciales hardcodeadas
config/constantes.php     # puede contener datos sensibles

# IDE
.idea/
.vscode/
*.sublime-project

# OS
.DS_Store
Thumbs.db
```

> ⚠️ Si `.env` fue commiteado en algún momento del sistema WIS legado,
> rotar **todas** las credenciales antes de continuar. El historial de Git
> es público dentro del equipo — tratar credenciales commiteadas como comprometidas.

---

## CHANGELOG

Mantener un `CHANGELOG.md` en la raíz del proyecto, actualizado en cada release:

```markdown
# Changelog — WIS Laravel

## [v0.1.0] - YYYY-MM-DD
### Agregado
- Infraestructura base Laravel 11 (Fase 0)
- Migraciones de BD para tablas principales con UUID (Fase 1)
- Autenticación con CSRF, bcrypt y session regeneration (Fase 2)
- Mapeo de 12 permisos binarios a spatie/laravel-permission

### Seguridad
- Eliminado SQL injection en módulo de usuarios
- Eliminadas credenciales hardcodeadas (movidas a .env)
- Reemplazado crypt() por Hash::make()

## [Unreleased]
### En progreso
- Fase 3: Modelos Eloquent
```

---

## Comandos de Referencia Rápida

```bash
# Ver estado actual
git status
git log --oneline --graph --decorate --all

# Sincronizar con develop antes de trabajar
git fetch origin && git rebase origin/develop

# Limpiar ramas locales ya mergeadas
git branch --merged develop | grep -v "develop\|main" | xargs git branch -d

# Ver qué hay en develop que no está en main (pendiente de release)
git log main..develop --oneline
```

---

## Guardrails

- **Nunca hacer `git push --force` sobre `main` o `develop`** — usar `--force-with-lease` solo si es absolutamente necesario en ramas personales.
- **Nunca commitear `.env`** — rotar credenciales si ocurre accidentalmente.
- **No mezclar cambios de múltiples fases en una sola rama** — una rama por fase o por tarea discreta.
- **Pedir aprobación antes de hacer `git rebase` sobre ramas compartidas** — puede reescribir historia que otros ya tienen.
- **Cada merge a `develop` debe pasar `php artisan test`** — no mergear código con tests en rojo.
- Ante dudas sobre el estado del repositorio, ejecutar `git log --oneline --graph` y revisar antes de actuar.
