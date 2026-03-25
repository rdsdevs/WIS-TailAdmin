@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Editar Firma Digital</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Actualice los datos de la firma de {{ $signature->signer_name }}.
            </p>
        </div>
        <nav aria-label="Migas de pan">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Inicio
                        <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <a href="{{ route('certificados.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Certificados</a>
                    <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none">
                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </li>
                <li class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <a href="{{ route('certificados.firmas.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Firmas</a>
                    <svg class="h-4 w-4 stroke-current" viewBox="0 0 17 16" fill="none">
                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </li>
                <li class="text-sm font-medium text-gray-800 dark:text-white/90">Editar</li>
            </ol>
        </nav>
    </div>

    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('certificados.firmas.update', $signature) }}"
              x-data="{
                  sigPreview: '{{ $signature->signature_image ?? '' }}',
                  toBase64(file, hiddenInput) {
                      const reader = new FileReader();
                      reader.onload = e => {
                          this.sigPreview = e.target.result;
                          hiddenInput.value = e.target.result;
                      };
                      reader.readAsDataURL(file);
                  }
              }">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-5">

                {{-- Nombre del firmante --}}
                <div>
                    <label for="signer_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre del firmante <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="signer_name" id="signer_name"
                           value="{{ old('signer_name', $signature->signer_name) }}"
                           required maxlength="200"
                           placeholder="Ej: María García López"
                           class="w-full rounded-xl border {{ $errors->has('signer_name') ? 'border-red-400' : 'border-gray-300' }} bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    @error('signer_name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Cargo del firmante --}}
                <div>
                    <label for="signer_position" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Cargo del firmante <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="signer_position" id="signer_position"
                           value="{{ old('signer_position', $signature->signer_position) }}"
                           required maxlength="200"
                           placeholder="Ej: Directora de Gestión Humana"
                           class="w-full rounded-xl border {{ $errors->has('signer_position') ? 'border-red-400' : 'border-gray-300' }} bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    @error('signer_position')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Imagen de firma --}}
                <div>
                    <p class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Imagen de firma
                    </p>
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                        Suba una nueva imagen para reemplazar la actual. Formato PNG con fondo transparente.
                    </p>

                    <div class="rounded-xl border-2 border-dashed border-gray-300 p-4 text-center dark:border-gray-600"
                         @dragover.prevent
                         @drop.prevent="
                             const f = $event.dataTransfer.files[0];
                             if (f) toBase64(f, $el.querySelector('[name=signature_image]'));
                         ">
                        <input type="hidden" name="signature_image" value="{{ old('signature_image', $signature->signature_image) }}">

                        <template x-if="sigPreview">
                            <img :src="sigPreview" alt="Vista previa de la firma" class="mx-auto h-24 object-contain mb-3">
                        </template>
                        <template x-if="!sigPreview">
                            <svg class="mx-auto h-10 w-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                            </svg>
                        </template>

                        <label for="sig-upload" class="cursor-pointer text-sm text-blue-600 hover:underline dark:text-blue-400">
                            {{ $signature->signature_image ? 'Cambiar imagen' : 'Seleccionar imagen' }}
                        </label>
                        <input type="file" accept="image/*" class="hidden" id="sig-upload"
                               @change="const f = $event.target.files[0]; if (f) toBase64(f, $el.closest('[x-data]').querySelector('[name=signature_image]'))">
                        <p class="text-xs text-gray-400 mt-1">o arrastre aquí</p>
                    </div>
                    @error('signature_image')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sección reemplazo --}}
                <div x-data="{ showReplacement: {{ $signature->replacement_name ? 'true' : 'false' }} }">
                    <button type="button" @click="showReplacement = !showReplacement"
                            class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                            :aria-expanded="showReplacement">
                        <svg class="h-4 w-4 transition-transform" :class="showReplacement ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                        Firmante de reemplazo (opcional)
                    </button>

                    <div x-show="showReplacement" x-transition class="mt-4 space-y-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-700/30">
                        <div>
                            <label for="replacement_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del reemplazo</label>
                            <input type="text" name="replacement_name" id="replacement_name"
                                   value="{{ old('replacement_name', $signature->replacement_name) }}"
                                   maxlength="200" placeholder="Nombre del reemplazo"
                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                        </div>
                        <div>
                            <label for="replacement_position" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cargo del reemplazo</label>
                            <input type="text" name="replacement_position" id="replacement_position"
                                   value="{{ old('replacement_position', $signature->replacement_position) }}"
                                   maxlength="200" placeholder="Cargo del reemplazo"
                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                        </div>
                    </div>
                </div>

                {{-- Estado activo --}}
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $signature->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Firma activa (disponible para emitir certificados)
                    </label>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <a href="{{ route('certificados.firmas.index') }}"
                   class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
@endsection
