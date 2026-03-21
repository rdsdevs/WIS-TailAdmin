# Revisión de Código WIS ASCUN

Realiza una revisión completa de calidad y seguridad sobre el código modificado o especificado.

## Uso

```
/revision-codigo
/revision-codigo [ruta/al/archivo.php]
```

## Qué Revisar

Ejecuta una revisión exhaustiva verificando:

### 1. Seguridad

- **SQL Injection:** ¿Hay variables PHP interpoladas directamente en queries?
- **XSS:** ¿El output en Blade usa `{{ }}` y no `{!! !!}` sin sanitización?
- **CSRF:** ¿Todos los formularios POST tienen `@csrf`?
- **Autenticación:** ¿Las rutas sensibles tienen middleware `auth`?
- **Autorización:** ¿Las acciones CRUD usan `$this->authorize()` o Policy?
- **Credenciales:** ¿Hay passwords, tokens o API keys en el código?

### 2. Arquitectura Laravel

- ¿El controller tiene lógica de negocio? (Debe estar en el Service)
- ¿El model tiene lógica de negocio? (Solo scopes, mutators, relaciones)
- ¿La validación está en un FormRequest?
- ¿Las operaciones multi-tabla están dentro de `DB::transaction`?

### 3. Estándares del Proyecto

- `declare(strict_types=1)` presente en todos los archivos PHP
- Type hints en parámetros y valores de retorno
- Modelo extiende con UUID + SoftDeletes + Auditable
- Migrations tienen `down()` implementado

### 4. Idioma y UX

- ¿Los mensajes al usuario están en español colombiano?
- ¿Las fechas se muestran en formato `d/m/Y`?
- ¿Los valores monetarios muestran formato COP (`$ 1.500.000`)?

### 5. Tests

- ¿El código nuevo tiene tests asociados?
- ¿Se cubre happy path, errores de validación y autorización denegada?

## Salida Esperada

Generar un reporte con:

```
## Revisión de Código — {archivo o módulo}

### Problemas Críticos (bloquean el merge)
- ...

### Problemas Altos (deben corregirse en este PR)
- ...

### Sugerencias (mejoras no bloqueantes)
- ...

### Hallazgos Positivos
- ...

### Veredicto
✅ Aprobado / ❌ Requiere cambios
```
