# Migrar Módulo desde app.wisascun.com

Proceso para migrar un módulo del sistema legacy PHP a Laravel 12.

## Uso

```
/migrar-modulo [nombre-del-modulo]
Ej: /migrar-modulo rh
Ej: /migrar-modulo certificados
```

## Módulos Disponibles para Migración

| Módulo | Archivos Legacy | Estado |
|---|---|---|
| `auth` | UsuariosController.php, Usuarios.php | Pendiente |
| `rh` | EmployeeController.php, Collaborator.php, Contract.php | Pendiente |
| `contabilidad` | CarterasController.php, InformeNDS.php, InformeSF.php | Pendiente |
| `inventario` | ProductsController.php, ElementsController.php | Pendiente |
| `certificados` | CertificatesController.php | Pendiente |

## Proceso de Migración

### Paso 1: Análisis del código legacy

Revisar en `/home/rdsdev/projects-rds/WISASCUN/app.wisascun.com/`:
1. Identificar todas las queries SQL y convertirlas a Eloquent
2. Mapear las tablas existentes al nuevo esquema con UUID
3. Identificar las validaciones actuales (regex manuales) → FormRequests
4. Identificar la lógica de negocio → Service classes
5. Mapear los permisos actuales (`controldeacceso`) → Spatie Roles

### Paso 2: Crear la rama

```bash
git checkout -b feature/WIS-{ticket}-migracion-{modulo}
```

### Paso 3: Crear la migration de datos

Crear migration que:
- Crea la nueva tabla con UUID
- **NO** destruye datos existentes en sandbox
- Incluye `down()` que revierte el schema

### Paso 4: Auditoría de seguridad del código legacy

Antes de migrar cualquier lógica, verificar que NO se arrastren:

- [ ] Variables interpoladas en SQL → usar Eloquent/QueryBuilder
- [ ] `crypt()` para passwords → usar `bcrypt()`
- [ ] `$_POST` sin validar → usar FormRequest
- [ ] `echo` en catch → usar logging con `Log::error()`
- [ ] Output sin escapar → usar `{{ }}` en Blade

### Paso 5: Implementar en Laravel

Seguir el flujo de `/nueva-funcionalidad` para cada entidad del módulo.

### Paso 6: Tests de paridad funcional

Escribir tests que confirmen que el comportamiento es equivalente al sistema legacy:

```php
it('calcula correctamente el saldo de nodos como el sistema legacy', function (): void {
    // ...
});
```

### Paso 7: Verificación final

```bash
php artisan test --filter={Modulo}
./vendor/bin/pint --test
php artisan migrate:status
```

## Issues Críticos de Seguridad del Legacy

Extraídos del análisis QA del sistema `app.wisascun.com`:

### SQL Injection activo (CRÍTICO)
```php
// ❌ Legacy — NO migrar así
$stmt->prepare("SELECT * FROM $tabla WHERE $item = $valor");

// ✅ Laravel correcto
Model::where($column, $value)->get(); // solo si $column es de confianza
// o mejor:
Model::where('document_number', $request->validated('document_number'))->get();
```

### Hash de contraseña débil (CRÍTICO)
```php
// ❌ Legacy — NO migrar así
$encriptar = crypt($_POST["password"], '$2a$07$asxx54ahjppf45sd87a5a4dDDGsystemdev$');

// ✅ Laravel correcto
// En el modelo: protected $casts = ['password' => 'hashed'];
// O manualmente:
Hash::make($request->validated('password'));
```

### Credenciales hardcoded (CRÍTICO)
```php
// ❌ Legacy — NO migrar así
define('DB_PASS_P', '218586Lt_*000');

// ✅ Laravel correcto
config('database.connections.mysql.password') // desde .env
```
