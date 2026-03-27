@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navegación de paginación" class="flex items-center justify-between py-4">

            {{-- Texto de resultados (oculto en mobile) --}}
            <p class="hidden text-sm text-gray-500 dark:text-gray-400 sm:block">
                Mostrando
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $paginator->firstItem() }}</span>
                al
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $paginator->lastItem() }}</span>
                de
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $paginator->total() }}</span>
                resultados
            </p>

            {{-- Mobile: prev / página actual / next --}}
            <div class="flex items-center gap-2 sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500" aria-disabled="true">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                        </svg>
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        aria-label="{{ __('pagination.previous') }}"
                        dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                @endif

                <span class="flex h-9 min-w-9 items-center justify-center rounded-full border border-brand-500 bg-brand-500 px-2 text-sm font-semibold text-white">
                    {{ $paginator->currentPage() }}
                </span>

                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        aria-label="{{ __('pagination.next') }}"
                        dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500" aria-disabled="true">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </span>
                @endif
            </div>

            {{-- Desktop: controles completos --}}
            <div class="hidden items-center gap-1 sm:flex">

                {{-- Botón anterior --}}
                @if ($paginator->onFirstPage())
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        aria-disabled="true"
                        aria-label="{{ __('pagination.previous') }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                        </svg>
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        aria-label="{{ __('pagination.previous') }}"
                        dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                @endif

                {{-- Elementos de paginación --}}
                @foreach ($elements as $element)
                    {{-- Separador "..." --}}
                    @if (is_string($element))
                        <span aria-disabled="true" class="flex h-9 w-9 items-center justify-center text-sm text-gray-400 dark:text-gray-500">
                            {{ $element }}
                        </span>
                    @endif

                    {{-- Array de páginas --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span
                                        aria-current="page"
                                        class="flex h-9 w-9 items-center justify-center rounded-full border border-brand-500 bg-brand-500 text-sm font-semibold text-white">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button
                                        type="button"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                        aria-label="{{ __('Ir a la página :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                {{-- Botón siguiente --}}
                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        aria-label="{{ __('pagination.next') }}"
                        dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                @else
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        aria-disabled="true"
                        aria-label="{{ __('pagination.next') }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </span>
                @endif

            </div>
        </nav>
    @endif
</div>
