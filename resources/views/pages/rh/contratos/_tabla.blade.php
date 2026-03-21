{{--
    Partial reutilizable de tabla de contratos.
    Variables esperadas: $filas (Collection), $tiposContrato (array)
--}}
<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Persona</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tipo</th>
                <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">Cargo</th>
                <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:table-cell">Inicio</th>
                <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:table-cell">Fin</th>
                <th scope="col" class="hidden px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">Salario</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado</th>
                <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
            @forelse($filas as $contrato)
                @php
                    $venceProximo = $contrato->is_active
                        && $contrato->end_date
                        && $contrato->end_date->isFuture()
                        && $contrato->end_date->diffInDays(now()) <= 30;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $contrato->contractable?->full_name ?? '—' }}
                        </p>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                        {{ $tiposContrato[$contrato->contract_type] ?? $contrato->contract_type }}
                    </td>
                    <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 md:table-cell">
                        {{ $contrato->position ?? '—' }}
                    </td>
                    <td class="hidden px-4 py-3 text-gray-600 dark:text-gray-400 sm:table-cell">
                        {{ $contrato->start_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="hidden px-4 py-3 sm:table-cell">
                        @if($contrato->end_date)
                            <span class="{{ $venceProximo ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ $contrato->end_date->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="text-gray-600 dark:text-gray-400">Indefinido</span>
                        @endif
                    </td>
                    <td class="hidden px-4 py-3 text-right text-gray-600 dark:text-gray-400 lg:table-cell">
                        @if($contrato->salary)
                            $ {{ number_format($contrato->salary, 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($venceProximo)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                Por vencer
                            </span>
                        @elseif($contrato->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                Vigente
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                Finalizado
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $contrato)
                                <a href="{{ route('rh.contratos.edit', $contrato) }}"
                                   class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                                    Editar
                                </a>
                            @endcan

                            @can('update', $contrato)
                                @if($contrato->is_active)
                                    <form method="POST" action="{{ route('rh.contratos.terminate', $contrato) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                                onclick="return confirm('¿Desea terminar este contrato?')">
                                            Terminar
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No se encontraron contratos.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
