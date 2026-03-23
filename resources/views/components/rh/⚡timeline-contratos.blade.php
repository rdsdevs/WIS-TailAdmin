<?php

declare(strict_types=1);

use App\Models\RH\Collaborator;
use Livewire\Component;

new class extends Component {

    public string $collaboratorId = '';

    public function getContratosProperty(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();

        $query = Collaborator::query()->where('id', $this->collaboratorId);

        if (! $user->hasRole('super-admin')) {
            $query->where('institution_id', $user->institution_id);
        }

        $collaborator = $query->firstOrFail();

        return $collaborator->contracts()
            ->with(['contractType', 'position.department', 'extensions'])
            ->orderByDesc('start_date')
            ->get();
    }
};

?>

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

    {{-- Título del card --}}
    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Historial laboral</h4>
    </div>

    @if($this->contratos->isEmpty())
        <div class="flex flex-col items-center gap-2 px-5 py-10 text-center">
            <svg class="h-9 w-9 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <p class="text-sm text-gray-400 dark:text-gray-500">Sin historial de contratos registrados.</p>
        </div>
    @else
        <div class="px-5 py-4">
            @foreach($this->contratos as $index => $contrato)
                @php
                    $isLast = $loop->last;

                    $iconBg = match(true) {
                        in_array($contrato->status, ['Vigente'])                    => 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700',
                        in_array($contrato->status, ['Terminado'])                  => 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-700',
                        in_array($contrato->status, ['Liquidado', 'Vencido'])       => 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-700',
                        in_array($contrato->status, ['Borrador'])                   => 'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-600',
                        default                                                     => 'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-600',
                    };

                    $iconColor = match(true) {
                        in_array($contrato->status, ['Vigente'])                    => 'text-green-600 dark:text-green-400',
                        in_array($contrato->status, ['Terminado'])                  => 'text-red-500 dark:text-red-400',
                        in_array($contrato->status, ['Liquidado', 'Vencido'])       => 'text-yellow-600 dark:text-yellow-400',
                        default                                                     => 'text-gray-400 dark:text-gray-500',
                    };

                    $badgeClass = match(true) {
                        in_array($contrato->status, ['Vigente'])                    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        in_array($contrato->status, ['Terminado'])                  => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                        in_array($contrato->status, ['Liquidado', 'Vencido'])       => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                        in_array($contrato->status, ['Borrador'])                   => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                        default                                                     => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                    };
                @endphp

                <div class="flex gap-4 {{ $isLast ? '' : 'mb-0' }}">

                    {{-- Icono + línea vertical --}}
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border {{ $iconBg }}">
                            <svg class="h-5 w-5 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        @unless($isLast)
                            <div class="my-1 w-px flex-1 border-l border-dashed border-gray-200 dark:border-gray-700" aria-hidden="true"></div>
                        @endunless
                    </div>

                    {{-- Contenido --}}
                    <div class="flex min-w-0 flex-1 items-start justify-between gap-2 pb-5">
                        <div class="min-w-0">
                            {{-- Título: tipo de contrato + badge --}}
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                    {{ $contrato->contractType?->name ?? 'Sin tipo' }}
                                </p>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ $contrato->status ?? '—' }}
                                </span>
                            </div>

                            {{-- Subtítulo: cargo + departamento --}}
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                @if($contrato->position)
                                    {{ $contrato->position->name }}
                                    @if($contrato->position->department)
                                        &bull; {{ $contrato->position->department->name }}
                                    @endif
                                @elseif($contrato->contract_code)
                                    Ref. {{ $contrato->contract_code }}
                                @else
                                    Sin cargo asignado
                                @endif
                            </p>

                            {{-- Valor --}}
                            @if($contrato->salary || $contrato->fees)
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    @if($contrato->salary)
                                        Salario: <span class="font-medium text-gray-600 dark:text-gray-300">$ {{ number_format((float) $contrato->salary, 0, ',', '.') }}</span>
                                    @else
                                        Honorarios: <span class="font-medium text-gray-600 dark:text-gray-300">$ {{ number_format((float) $contrato->fees, 0, ',', '.') }}</span>
                                    @endif
                                </p>
                            @endif

                            {{-- Prórrogas --}}
                            @if($contrato->extensions->count() > 0)
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    {{ $contrato->extensions->count() }} prórroga(s) &bull;
                                    hasta {{ $contrato->extensions->sortByDesc('extension_date')->first()->extension_date?->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>

                        {{-- Fechas (derecha) --}}
                        <div class="shrink-0 text-right">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $contrato->start_date?->format('d/m/Y') ?? '—' }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                {{ $contrato->end_date ? $contrato->end_date->format('d/m/Y') : 'Indefinido' }}
                            </p>
                            @can('update', $contrato)
                                <a href="{{ route('rh.contratos.edit', $contrato) }}"
                                   class="mt-1.5 inline-block rounded border border-gray-200 px-2 py-0.5 text-xs text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500"
                                   aria-label="Editar contrato">
                                    Editar
                                </a>
                            @endcan
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>
