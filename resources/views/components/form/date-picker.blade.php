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
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
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
                }}"
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
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" class="size-5">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor" />
            </svg>
        </span>
    </div>
</div>
