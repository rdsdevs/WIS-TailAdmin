---
nombre: frontend-wis
descripcion: >
  Crear interfaces frontend de nivel producción para WIS ASCUN en Laravel 12, usando
  TailAdmin + Tailwind CSS v4 + Livewire v4 + Alpine.js v3. Usar cuando se construyan
  vistas, layouts, componentes Blade, componentes Livewire o cualquier interfaz del sistema.
  Incluye patrones para implementar diseños desde Figma MCP, dark mode, responsive y
  accesibilidad. El idioma de toda la UI es español colombiano.
---

# SKILL: Frontend — WIS ASCUN Laravel 12

Esta guía orienta la construcción de interfaces funcionales y visualmente sólidas para el sistema WIS ASCUN, basado en el template TailAdmin con Tailwind CSS v4.

> **Principio clave:** En sistemas administrativos la claridad ES el diseño.
> Un formulario bien espaciado, una tabla que respira, un estado vacío explicativo —
> vale más que animaciones elaboradas.

---

## Stack Obligatorio

```
Blade + Livewire v4 (SFC)   ← vistas y componentes reactivos
Tailwind CSS v4              ← utility-first, sin tailwind.config.js
Alpine.js v3                 ← vía Livewire (NO importar por separado)
Vite 7                       ← bundler (NO CDN para ninguna librería)
ApexCharts                   ← gráficas (window.ApexCharts global desde app.js)
Flatpickr                    ← date pickers (window.flatpickr global)
FullCalendar                 ← calendario (window.FullCalendar global)
```

> ⚠️ Alpine.js lo gestiona Livewire internamente. **Nunca** importar Alpine ni llamar `Alpine.start()` en `app.js`.
> ⚠️ Ninguna librería JS por CDN. Todo pasa por Vite.

---

## Pensamiento de Diseño para WIS ASCUN

Antes de escribir código, definir:

- **Propósito:** ¿Qué problema resuelve esta pantalla? ¿Quién la usa y con qué frecuencia?
- **Tono del sistema:** Administrativo-profesional, institucional pero accesible. Denso en información sin ser caótico.
- **Módulo:** Cada módulo tiene un color de acento diferenciador (RH → azul, Contabilidad → verde, Inventario → naranja, Certificados → violeta).
- **Idioma:** Todo texto visible al usuario en **español colombiano**. Terminología: "cédula", "nómina", "término fijo/indefinido", "liquidación".

---

## Estructura de Archivos

```
resources/
├── css/
│   └── app.css                        # @import "tailwindcss"; + custom properties
├── js/
│   ├── app.js                         # Solo globals: ApexCharts, flatpickr, FullCalendar
│   └── components/
│       ├── chart/                     # Inicializadores ApexCharts por página
│       └── calendar-init.js
└── views/
    ├── layouts/
    │   ├── app.blade.php              # Layout principal (sidebar + topbar)
    │   └── fullscreen-layout.blade.php # Auth pages (login)
    ├── components/
    │   ├── ui/                        # Botones, inputs, badges, cards, alerts
    │   ├── common/                    # Preloader, breadcrumbs, modales
    │   └── ⚡*.blade.php             # Livewire SFCs
    └── pages/
        ├── auth/                      # login.blade.php
        ├── dashboard/                 # ecommerce.blade.php (página de inicio)
        ├── rh/                        # Módulo Recursos Humanos
        ├── contabilidad/              # Módulo Contabilidad
        ├── inventario/                # Módulo Inventario
        └── certificados/              # Módulo Certificados
```

---

## Dark Mode

Siempre implementar variante `dark:` en clases de color:

```html
<!-- ✅ Correcto -->
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700">

<!-- ✅ Cards -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">

<!-- ✅ Inputs -->
<input class="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white">
```

El store de Alpine `theme` maneja el toggle. Ya está configurado en `app.blade.php`:
```js
// Ya existe en app.blade.php — no recrear
Alpine.store('theme', { toggle() { ... } })
```

---

## Componentes Blade

### Patrón base de componente UI

```blade
{{-- resources/views/components/ui/alert.blade.php --}}
@props(['type' => 'info', 'dismissible' => false])

@php
$classes = match($type) {
    'exito'      => 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border-green-200',
    'error'      => 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border-red-200',
    'advertencia'=> 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 border-yellow-200',
    default      => 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 border-blue-200',
};
@endphp

<div x-data="{ visible: true }" x-show="visible"
     class="flex items-start gap-3 rounded-lg border p-4 {{ $classes }}">
    <div class="flex-1 text-sm">{{ $slot }}</div>
    @if($dismissible)
        <button @click="visible = false" class="text-current opacity-60 hover:opacity-100">
            <svg class="h-4 w-4" ...></svg>
        </button>
    @endif
</div>
```

---

## Componentes Livewire (Single File Components)

### Listado con búsqueda y paginación

```php
<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RH\Employee;

new class extends Component {
    use WithPagination;

    public string $buscar = '';
    public string $ordenarPor = 'first_name';
    public string $direccion = 'asc';

    public function updatingBuscar(): void { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.rh.employee-list', [
            'empleados' => Employee::query()
                ->with('position')
                ->when($this->buscar, fn ($q) =>
                    $q->where('first_name', 'like', "%{$this->buscar}%")
                      ->orWhere('last_name', 'like', "%{$this->buscar}%")
                      ->orWhere('document_number', 'like', "%{$this->buscar}%")
                )
                ->orderBy($this->ordenarPor, $this->direccion)
                ->paginate(15),
        ]);
    }
};
?>

<div>
    {{-- Barra de herramientas --}}
    <div class="flex items-center justify-between gap-4 mb-4">
        <input wire:model.live.debounce.300ms="buscar"
               type="text"
               placeholder="Buscar empleado..."
               class="w-full max-w-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm">
        <a href="{{ route('rh.empleados.create') }}"
           class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
            Nuevo empleado
        </a>
    </div>

    {{-- Estado de carga --}}
    <div wire:loading.delay class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Cargando...
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"
         wire:loading.class="opacity-50">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">Empleado</th>
                    <th class="px-4 py-3 text-left">Documento</th>
                    <th class="px-4 py-3 text-left">Cargo</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse($empleados as $empleado)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $empleado->full_name }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $empleado->document_number }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $empleado->position->name }}
                        </td>
                        <td class="px-4 py-3">
                            @if($empleado->is_active)
                                <span class="rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">Activo</span>
                            @else
                                <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('rh.empleados.show', $empleado) }}"
                               class="text-blue-600 hover:underline text-xs">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron empleados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="mt-4">
        {{ $empleados->links() }}
    </div>
</div>
```

### Formulario con validación en tiempo real

```blade
{{-- Campos con feedback inmediato --}}
<div class="space-y-1">
    <label for="document_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Número de cédula
    </label>
    <input wire:model.blur="document_number"
           id="document_number"
           type="text"
           class="w-full rounded-lg border px-3 py-2 text-sm
                  @error('document_number') border-red-500 bg-red-50 dark:bg-red-900/20 @else border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 @enderror
                  text-gray-900 dark:text-white">
    @error('document_number')
        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
```

### Modal de confirmación para acciones destructivas

```blade
{{-- Siempre confirmar antes de eliminar --}}
<div x-data="{ abierto: false }">
    <button @click="abierto = true"
            class="rounded-lg bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700">
        Eliminar
    </button>

    <div x-show="abierto" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @keydown.escape.window="abierto = false">
        <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-800 p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Confirmar eliminación
            </h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                ¿Está seguro de que desea eliminar este registro?
                Esta acción no se puede deshacer.
            </p>
            <div class="mt-4 flex justify-end gap-3">
                <button @click="abierto = false"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm">
                    Cancelar
                </button>
                <button wire:click="eliminar" @click="abierto = false"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## Implementación desde Figma MCP

Cuando se tenga acceso a un diseño de Figma via MCP:

1. Extraer tokens: colores, tipografía, espaciados, radios de borde.
2. Mapear a clases Tailwind existentes; si no existe, agregar como CSS custom property en `app.css`.
3. Implementar mobile-first.
4. Aplicar dark mode en todas las clases de color.
5. Verificar contraste de color (WCAG AA mínimo).

---

## Layouts

### `app.blade.php` — Uso correcto

```blade
@extends('layouts.app')

@section('content')
    {{-- Todo el contenido de la página aquí --}}
    <div class="space-y-6">
        <x-common.breadcrumb :items="[['Inicio', route('dashboard')], ['RH', null], ['Empleados', null]]" />

        <livewire:rh.employee-list />
    </div>
@endsection

@push('scripts')
    {{-- Solo si la página necesita JS específico --}}
@endpush
```

### Páginas de autenticación → `fullscreen-layout`

```blade
@extends('layouts.fullscreen-layout')

@section('content')
    {{-- Formulario de login centrado --}}
@endsection
```

---

## Responsive

- **Mobile-first**: estilos base para móvil, luego `sm:`, `md:`, `lg:`, `xl:`
- Sidebar se colapsa en móvil (`< 1280px`) — el store `$store.sidebar` lo maneja

```html
<!-- Grid responsive -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

<!-- Tabla horizontal en móvil -->
<div class="overflow-x-auto">
    <table class="min-w-full">
```

---

## Pantallas por Módulo

| Módulo | Vistas clave | Componente Livewire |
|---|---|---|
| Auth | `auth/login.blade.php` | — |
| Dashboard | `dashboard/ecommerce.blade.php` | `⚡dashboard-metrics` |
| RH | `rh/empleados/index`, `create`, `show` | `⚡employee-list`, `⚡employee-form` |
| Contabilidad | `contabilidad/carteras`, `informes/nds` | `⚡report-filter`, `⚡node-table` |
| Inventario | `inventario/suministros`, `elementos` | `⚡product-stock`, `⚡element-tracker` |
| Certificados | `certificados/index`, `generar` | `⚡certificate-generator` |

---

## Guardrails

- **Nunca CDN** para librerías JS. Todo por Vite.
- **Nunca `{!! !!}`** sin sanitización previa. Usar siempre `{{ }}` (Blade escapa XSS automáticamente).
- **Siempre estado vacío** en `@forelse` — nunca tabla vacía sin mensaje.
- **Siempre `wire:loading`** en acciones Livewire que consultan la BD.
- Los layouts de **PDF** (certificados) van en `resources/views/pdfs/` — layout propio sin sidebar ni topbar.
- **Siempre `@csrf`** en formularios `<form method="POST">` que no sean Livewire.
- No usar `<form>` HTML estándar en componentes Livewire — usar `wire:submit`.
- Todo texto visible al usuario en **español colombiano**. Nunca inglés en la UI.
