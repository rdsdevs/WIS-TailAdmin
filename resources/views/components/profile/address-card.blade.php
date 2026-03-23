<div class="p-5 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
    <div>
        <h4 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">
            Seguridad de la cuenta
        </h4>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7">
            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Contraseña</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">••••••••••••</p>
            </div>

            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Último acceso</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $user->updated_at->format('d/m/Y H:i') }}
                </p>
            </div>

            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Cuenta creada</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $user->created_at->format('d/m/Y') }}
                </p>
            </div>

            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Estado</p>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    Activo
                </span>
            </div>
        </div>
    </div>
</div>
