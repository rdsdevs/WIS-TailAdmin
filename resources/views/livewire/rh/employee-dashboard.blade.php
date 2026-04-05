<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use App\Models\RH\Contract;
use App\Models\RH\Collaborator;
use App\Models\RH\Position;

new class extends Component {
    
    public function with(): array
    {
        $institutionId = auth()->user()->institution_id;

        // Vencimientos en los próximos 30 días (solo empleados)
        $vencimientos = Contract::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Vigente')
            ->whereHas('collaborator', fn($q) => $q->where('type', 'Empleado'))
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays(30))
            ->with(['collaborator', 'position'])
            ->orderBy('end_date')
            ->get();

        // Verificaciones Ley 1918 pendientes (empleados activos sin fecha de verificación)
        $pendientesLey1918 = Collaborator::query()
            ->where('institution_id', $institutionId)
            ->where('type', 'Empleado')
            ->whereHas('status', fn($q) => $q->where('name', 'Activo'))
            ->whereHas('employeeProfile', fn($q) => $q->whereNull('background_check_verified_at'))
            ->with(['activeContract.position'])
            ->get();

        return [
            'vencimientos' => $vencimientos,
            'pendientesLey1918' => $pendientesLey1918,
            'totalEmpleados' => Collaborator::where('institution_id', $institutionId)->where('type', 'Empleado')->count(),
            'totalCargos' => Position::where('institution_id', $institutionId)->count(),
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Grid de Alertas Críticas --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        
        {{-- Próximos Vencimientos --}}
        <div class="rounded-2xl border border-red-100 bg-white shadow-sm dark:border-red-900/20 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white">
                    <span class="flex h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                    Vencimientos Próximos (30 días)
                </h3>
                <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    {{ $vencimientos->count() }} contratos
                </span>
            </div>
            <div class="p-0">
                <div class="max-h-[300px] overflow-y-auto">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($vencimientos as $v)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $v->collaborator->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $v->position->name ?? 'Sin cargo' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="font-semibold text-red-600 dark:text-red-400">{{ $v->end_date->format('d/m/Y') }}</div>
                                        <div class="text-[10px] uppercase tracking-wider text-gray-400">En {{ (int) now()->diffInDays($v->end_date) }} días</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 italic">
                                        No hay vencimientos próximos.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pendientes Ley 1918 --}}
        <div class="rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-amber-900/20 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white">
                    <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Verificaciones Ley 1918 Pendientes
                </h3>
                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    {{ $pendientesLey1918->count() }} personas
                </span>
            </div>
            <div class="p-0">
                <div class="max-h-[300px] overflow-y-auto">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($pendientesLey1918 as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $p->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $p->activeContract->position->name ?? 'Sin cargo' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('rh.colaboradores.edit', $p) }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                            Completar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 italic">
                                        Todas las verificaciones están al día.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Estadísticas y Accesos Rápidos --}}
    <div class="grid grid-cols-1 gap-6">

        {{-- Accesos Rápidos de Gestión --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Gestión Directa</h3>
            <div class="grid grid-cols-1 gap-3">
                <a href="{{ route('rh.cargos.index') }}" class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:hover:bg-gray-700/50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Estructura Org.</div>
                        <div class="text-xs text-gray-500">Cargos y funciones</div>
                    </div>
                </a>
                
                <a href="{{ route('rh.colaboradores.create') }}" class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:hover:bg-gray-700/50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Nuevo Empleado</div>
                        <div class="text-xs text-gray-500">Ingreso a nómina</div>
                    </div>
                </a>

                <div class="mt-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-700/30">
                    <div class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-2">Resumen Planta</div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalEmpleados }}</span>
                        <span class="text-sm text-gray-500">Empleados</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Registrados en la institución</div>
                </div>
            </div>
        </div>
    </div>
</div>
