# Nueva Funcionalidad WIS ASCUN

Guía paso a paso para implementar una nueva funcionalidad siguiendo los estándares del proyecto.

## Uso

```
/nueva-funcionalidad [módulo] [nombre]
Ej: /nueva-funcionalidad rh gestión-de-vacaciones
```

## Pasos a Ejecutar

### 1. Crear la rama

```bash
git checkout develop && git pull origin develop
git checkout -b feature/WIS-{ticket}-{nombre-kebab}
```

### 2. Crear el modelo con UUID y auditoría

```bash
php artisan make:model {Modulo}/{NombreModelo} -mf
```

El modelo debe:
- Implementar `Auditable` de owen-it/laravel-auditing
- Usar `HasUuidPrimaryKey` trait
- Usar `SoftDeletes`
- Tener `$fillable` explícito
- Tener `$casts` definidos

### 3. Completar la migration

- Clave primaria: `$table->uuid('id')->primary()`
- Soft deletes: `$table->softDeletes()`
- Implementar `down()` correctamente

### 4. Crear el FormRequest

```bash
php artisan make:request {Modulo}/Crear{NombreModelo}Request
php artisan make:request {Modulo}/Actualizar{NombreModelo}Request
```

Mensajes de validación en **español colombiano**.

### 5. Crear la Policy

```bash
php artisan make:policy {NombreModelo}Policy --model={Modulo}/{NombreModelo}
```

### 6. Crear el Service

Archivo: `app/Services/{Modulo}/{NombreModelo}Service.php`

- Lógica de negocio aquí, NO en el controller
- `DB::transaction` para operaciones multi-tabla
- Disparar eventos para efectos secundarios

### 7. Crear el Controller

```bash
php artisan make:controller {Modulo}/{NombreModelo}Controller --resource
```

- Delgado: solo llama al Service
- Usa FormRequest para validación
- Usa Policy para autorización

### 8. Crear el componente Livewire (si aplica)

```bash
php artisan livewire:make {Modulo}/{NombreModelo}Form
php artisan livewire:make {Modulo}/{NombreModelo}List
```

### 9. Crear las vistas Blade

Ubicación: `resources/views/pages/{modulo}/`
- Usar componentes existentes de la UI (`<x-ui.card>`, `<x-ui.table>`, etc.)
- Dark mode en todas las clases de color
- Responsive mobile-first
- Textos en **español colombiano**

### 10. Registrar la ruta

En `routes/web.php`:
```php
Route::resource('{modulo}/{nombre}', {Modulo}\{NombreModelo}Controller::class)
    ->middleware(['auth'])
    ->names([...]);
```

### 11. Agregar al menú sidebar

En `app/Helpers/MenuHelper.php`, agregar el item de navegación.

### 12. Escribir los tests

```bash
php artisan make:test {Modulo}/{NombreModelo}Test
```

Cubrir:
- Listado con y sin permisos
- Creación exitosa y con errores de validación
- Actualización y autorización
- Eliminación lógica y auditoría

### 13. Commit y PR

```bash
# Commits atómicos por cada paso
git commit -m "feat({modulo}): agregar modelo {NombreModelo} con UUID y auditoría"
git commit -m "migration({modulo}): crear tabla {nombre_tabla} con clave UUID"
git commit -m "feat({modulo}): implementar servicio y controller de {NombreModelo}"
git commit -m "test({modulo}): agregar pruebas de integración para {NombreModelo}"

# Verificaciones finales
./vendor/bin/pint
php artisan test

git push origin feature/WIS-{ticket}-{nombre}
```

Abrir PR hacia `develop` con el template estándar.
