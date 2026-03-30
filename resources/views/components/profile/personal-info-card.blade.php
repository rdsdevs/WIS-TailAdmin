<div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
    <div>
        <h4 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">
            Información personal
        </h4>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7">
            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Nombre completo</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</p>
            </div>

            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Correo electrónico</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->email }}</p>
            </div>

            @if($user->document_number)
            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">
                    {{ $user->document_type ?? 'Documento' }}
                </p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $user->document_number }}
                </p>
            </div>
            @endif

            @if($user->document_issued_at)
            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Fecha de expedición</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $user->document_issued_at->format('d/m/Y') }}
                </p>
            </div>
            @endif

            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Roles asignados</p>
                <div class="flex flex-wrap gap-1 mt-1">
                    @forelse($user->roles as $role)
                        <span class="inline-block rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            {{ \App\Helpers\MenuHelper::rolLabel($role->name) }}
                        </span>
                    @empty
                        <span class="text-sm text-gray-500">Sin roles asignados</span>
                    @endforelse
                </div>
            </div>

            @if($user->institution && !$user->hasRole('super-admin'))
            <div>
                <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Institución</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ $user->institution->name }}
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
