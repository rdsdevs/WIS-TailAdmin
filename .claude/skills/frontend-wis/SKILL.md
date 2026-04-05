---
nombre: frontend-diseño-wis
descripcion: >
  Crear interfaces frontend de nivel de producción para el sistema WIS en Laravel 11,
  usando Blade + Livewire 3 + Tailwind CSS + template Premium Akademi. Usar cuando
  se construyan vistas, layouts, componentes Blade, componentes Livewire o cualquier
  interfaz de usuario del sistema. Genera código funcional con alta calidad visual,
  coherente con la identidad del sistema administrativo de ASCUN.
---

# SKILL: Frontend — Migración WIS → Laravel 11

Esta guía orienta la creación de interfaces funcionales y visualmente sólidas para el sistema WIS, usando el stack definido en el plan de migración. Evita los patrones genéricos de dashboards administrativos; cada pantalla debe sentirse **diseñada intencionalmente** para el contexto de ASCUN.

---

## Stack Obligatorio

```
Blade + Livewire 3 + Volt   ← vistas y componentes reactivos
Tailwind CSS                ← utilidades de estilo (clases base disponibles)
SASS                        ← estilos personalizados sobre el template Akademi
Vite                        ← bundler (npm packages, NO CDN)
Select2                     ← selects mejorados (via npm + Vite)
FullCalendar 5              ← calendario (via npm + Vite)
Fontawesome                 ← iconografía
```

> ⚠️ No usar CDN para ninguna librería JS. Todo debe pasar por Vite.

---

## Pensamiento de Diseño

Antes de escribir código, entender el contexto de cada pantalla y comprometerse con una dirección estética clara:

- **Propósito:** ¿Qué problema resuelve esta pantalla? ¿Quién la usa y con qué frecuencia?
- **Tono del sistema WIS:** Administrativo-profesional, no corporativo genérico. Denso en información pero sin ser caótico. Institucional pero accesible.
- **Restricciones:** Blade + Livewire (no React/Vue), Tailwind sin compilador externo, compatibilidad con template Premium Akademi.
- **Diferenciador:** ¿Qué hace que esta pantalla sea clara, rápida de leer y fácil de operar?

**Principio clave:** En sistemas administrativos la claridad ES el diseño. Un formulario bien espaciado, una tabla que respira, un estado vacío explicativo — eso vale más que animaciones elaboradas.

---

## Estructura de Archivos (alineada con el plan de migración)

```
resources/views/
├── layouts/
│   ├── app.blade.php           ← layout principal con sidebar (Premium Akademi)
│   └── auth.blade.php          ← layout limpio para login/recuperación
├── components/
│   ├── data-table.blade.php    ← tabla reutilizable con paginación
│   ├── modal.blade.php         ← modal genérico
│   ├── alert.blade.php         ← alertas de éxito/error/advertencia
│   └── permission-badge.blade.php ← badge de rol/permiso
├── auth/
│   └── login.blade.php
├── dashboard/
│   └── index.blade.php
├── users/
├── hr/
├── finance/
├── inventory/
└── certifications/
```

---

## Guía de Estética para WIS

### Tipografía
- Usar las fuentes del template Premium Akademi como base.
- Para titulares de módulo y encabezados de sección: fuente display con carácter (no Inter, no Roboto).
- Para cuerpo de texto, tablas y formularios: fuente de lectura cómoda, no system-font genérica.
- Jerarquía clara: título de página → subtítulo de sección → etiqueta de campo → valor de dato.

### Color y tema
- Respetar la paleta base del template Akademi, pero aplicarla con intención.
- Usar variables CSS para consistencia entre módulos.
- Un color de acento dominante por módulo ayuda a la orientación: e.g., un matiz diferente para Finanzas vs RR.HH. vs Inventario.
- Estados de alerta con significado claro: rojo = error/peligro, amarillo = advertencia, verde = éxito, azul = informativo.

### Movimiento y microinteracciones
- Priorizar soluciones CSS puras para HTML/Blade.
- Livewire ya provee reactividad — los estados de carga (`wire:loading`) son la microinteracción más importante: siempre indicar cuando una acción está en proceso.
- Transiciones suaves en modales, dropdowns y alertas.
- No animar elementos de tabla o formulario innecesariamente — la densidad de datos pide estabilidad, no movimiento.

```blade
{{-- Ejemplo: botón con estado de carga Livewire --}}
<button wire:click="save" wire:loading.attr="disabled" class="btn-primary">
    <span wire:loading.remove>Guardar</span>
    <span wire:loading>Guardando...</span>
</button>
```

### Composición espacial
- Generosidad en padding de secciones — los módulos financieros tienen mucha data; darle espacio para respirar.
- Sidebar fijo con navegación por módulo claramente diferenciada.
- Cards para KPIs en el dashboard; tablas para listados operativos.
- Formularios de varios pasos (como `ContractForm.php`) con indicador de progreso visible.

### Fondos y detalles visuales
- Evitar fondos blancos puros sin textura — un gris muy suave o un tinte institucional del template ayuda.
- Bordes sutiles entre secciones en vez de sombras exageradas.
- Íconos de Fontawesome acompañando etiquetas en acciones clave (no solo texto, no solo ícono).

---

## Patrones de Componentes para WIS

### Tablas de datos (Livewire)
Las tablas generadas por componentes como `UserTable`, `CollaboratorTable`, `ElementTracker` deben:

```blade
{{-- Estructura base de tabla Livewire --}}
<div>
    {{-- Barra de búsqueda y filtros --}}
    <div class="table-toolbar">
        <input wire:model.live.debounce.300ms="search" placeholder="Buscar...">
        <select wire:model.live="perPage">...</select>
    </div>

    {{-- Estado de carga --}}
    <div wire:loading class="loading-overlay">Cargando...</div>

    {{-- Tabla --}}
    <table wire:loading.class="opacity-50">
        <thead>...</thead>
        <tbody>
            @forelse($items as $item)
                <tr>...</tr>
            @empty
                <tr><td colspan="X">Sin resultados.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Paginación --}}
    {{ $items->links() }}
</div>
```

### Formularios con validación en tiempo real
```blade
{{-- Campos con feedback inmediato de error --}}
<div class="form-group">
    <label for="nombre">Nombre completo</label>
    <input wire:model.blur="nombre" id="nombre" type="text"
           class="{{ $errors->has('nombre') ? 'input-error' : '' }}">
    @error('nombre')
        <span class="error-message">{{ $message }}</span>
    @enderror
</div>
```

### Modales de confirmación para acciones destructivas
```blade
{{-- Siempre confirmar antes de eliminar o revocar permisos --}}
<x-modal wire:model="confirmingDelete">
    <x-slot:title>Confirmar eliminación</x-slot:title>
    <p>¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.</p>
    <x-slot:footer>
        <button wire:click="$set('confirmingDelete', false)">Cancelar</button>
        <button wire:click="delete" class="btn-danger">Eliminar</button>
    </x-slot:footer>
</x-modal>
```

### Badges de permisos y roles
```blade
{{-- permission-badge.blade.php --}}
@props(['permission', 'active' => false])
<span class="badge {{ $active ? 'badge-active' : 'badge-inactive' }}">
    {{ $permission }}
</span>
```

---

## Pantallas Prioritarias por Módulo

| Módulo | Vistas clave | Componente Livewire asociado |
|--------|-------------|------------------------------|
| Auth | `login.blade.php` | — |
| Usuarios | `users/index`, `users/show` | `UserTable`, `UserForm`, `PermissionMatrix` |
| RR.HH. | `hr/collaborators`, `hr/contracts` | `CollaboratorTable`, `ContractForm` |
| Finanzas | `finance/er`, `finance/sf`, `finance/nds` | `ReportFilter` |
| Inventario | `inventory/elements`, `inventory/products` | `ElementTracker`, `ProductStock` |
| Certificaciones | `certifications/index` | `CertGenerator` |
| Solicitudes | `requests/board` | `RequestBoard` |

---

## Guardrails

- **No usar CDN** para ninguna librería JS/CSS. Todo debe instalarse vía npm y compilarse con Vite.
- **No usar `<form>` HTML estándar** en componentes Livewire — usar `wire:submit` en su lugar.
- Siempre mostrar **estados vacíos** informativos (`@empty` en `@forelse`) — nunca tablas vacías sin mensaje.
- Incluir **indicadores de carga** (`wire:loading`) en toda acción Livewire que haga query a BD.
- Los componentes de **PDF** (certificados, contratos) viven en `resources/views/pdf/` — usar layout separado sin sidebar ni navegación.
- Verificar que Select2 y FullCalendar estén correctamente inicializados vía Vite después de reemplazar el CDN actual del sistema WIS.
- Ante dudas sobre clases de Tailwind disponibles o directivas de Livewire 3, consultar la documentación vía **Laravel Boost** antes de proponer código.
