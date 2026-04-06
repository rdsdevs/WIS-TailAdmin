{{--
    Barra de navegación interna del módulo Recursos Humanos.
    Resalta el link activo usando request()->routeIs().
--}}
<nav class="mb-6 overflow-x-auto">
    <div class="flex min-w-max gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800/50">

        {{-- Colaboradores (unificado — empleados y contratistas) --}}
        <a href="{{ route('rh.colaboradores.index') }}"
           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs('rh.colaboradores.*')
                     ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400'
                     : 'text-gray-600 hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white' }}">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            <span>Colaboradores</span>
        </a>

        {{-- Contratos --}}
        <a href="{{ route('rh.contratos.index') }}"
           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs('rh.contratos.*')
                     ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400'
                     : 'text-gray-600 hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white' }}">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <span>Contratos</span>
        </a>

{{-- Cargos: roles con gestión estructural + employee-manager (necesita ver cargos al crear empleados) --}}
        @if(auth()->check() && auth()->user()->hasAnyRole(['super-admin', 'admin', 'rh-manager', 'employee-manager']))
            <a href="{{ route('rh.cargos.index') }}"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('rh.cargos.*')
                         ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400'
                         : 'text-gray-600 hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                </svg>
                <span>Cargos</span>
            </a>
        @endif

    </div>
</nav>
