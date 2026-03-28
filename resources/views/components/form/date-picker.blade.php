@props([
    'id'           => 'datepicker-' . uniqid(),
    'name'         => null,
    'wireModel'    => null,
    'wireModifier' => '',
    'value'        => null,
    'label'        => null,
    'placeholder'  => 'dd/mm/aaaa',
    'required'     => false,
    'error'        => false,
    'inputClasses' => null,
])

<div x-data="{
    value: '{{ $value ?? '' }}',
    fpInstance: null,
    init() {
        this.$nextTick(() => {
            this.fpInstance = flatpickr(this.$refs.fp, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                defaultDate: this.value || null,
                locale: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
                        longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
                    },
                    months: {
                        shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                        longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
                    }
                },
                onChange: (dates, dateStr) => {
                    this.value = dateStr;
                    @if($wireModel)
                    this.$dispatch('wire-date-changed', { field: '{{ $wireModel }}', value: dateStr });
                    @endif
                },
                onReady: (dates, dateStr, instance) => {
                    // Hacer clic en el ícono del calendario abre el picker
                    const icon = this.$refs.calIcon;
                    if (icon) {
                        icon.addEventListener('click', () => instance.open());
                    }
                    // Aplicar borde de error al altInput si existe
                    @if($error)
                    const altEl = instance.altInput;
                    if (altEl) {
                        altEl.classList.add('border-red-500', 'dark:border-red-500');
                        altEl.classList.remove('border-gray-300', 'dark:border-gray-700');
                    }
                    @endif
                    
                    // Aplicar clases personalizadas al altInput
                    @if($inputClasses)
                    const altElCustom = instance.altInput;
                    if (altElCustom) {
                        const customClasses = '{{ $inputClasses }}'.split(' ');
                        customClasses.forEach(c => {
                            if (c) altElCustom.classList.add(c);
                        });
                    }
                    @endif
                }
            });
        });
    },
    destroy() {
        if (this.fpInstance) {
            this.fpInstance.destroy();
            this.fpInstance = null;
        }
    }
}" x-init="init()" x-destroy="destroy()">

    @if($label)
        <label for="{{ $id }}" class="mb-2 block text-xs font-bold text-gray-600 uppercase dark:text-gray-400">
            {{ $label }}@if($required)<span class="ml-0.5 text-red-500" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <div class="relative">
        {{-- Input real — flatpickr lo oculta y crea el altInput visible --}}
        <input
            x-ref="fp"
            id="{{ $id }}"
            type="text"
            placeholder="{{ $placeholder }}"
            class="h-11 w-full rounded-lg border appearance-none px-4 py-2.5 pr-11 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 bg-transparent text-gray-800 dark:text-white/90 dark:placeholder:text-white/30 dark:bg-gray-900
                {{ $error
                    ? 'border-red-500 dark:border-red-500 focus:border-red-400 focus:ring-red-500/20'
                    : 'border-gray-300 dark:border-gray-700 focus:border-brand-300 focus:ring-brand-500/20 dark:focus:border-brand-800'
                }}
                {{ $inputClasses }}"
            autocomplete="off"
            readonly
        />

        {{-- Input hidden para envío en formularios HTML (solo si no es Livewire) --}}
        @if(!$wireModel && $name)
            <input type="hidden" name="{{ $name }}" :value="value" />
        @endif

        {{-- Ícono calendario --}}
        <span
            x-ref="calIcon"
            class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
            aria-hidden="true"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
        </span>
    </div>
</div>
