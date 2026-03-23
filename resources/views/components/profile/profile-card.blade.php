<div class="mb-6 rounded-2xl border border-gray-200 p-5 lg:p-6 dark:border-gray-800">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex w-full flex-col items-center gap-6 xl:flex-row">
            {{-- Avatar con iniciales --}}
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-blue-600 text-2xl font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>

            <div>
                <h4 class="mb-1 text-center text-lg font-semibold text-gray-800 xl:text-left dark:text-white/90">
                    {{ $user->name }}
                </h4>
                <div class="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left">
                    {{-- Rol principal --}}
                    @if($user->roles->isNotEmpty())
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $user->hasRole('super-admin') ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' }}">
                            {{ $user->roles->first()->name }}
                        </span>
                        @if(!$user->hasRole('super-admin'))
                            <div class="hidden h-3.5 w-px bg-gray-300 xl:block dark:bg-gray-700"></div>
                        @endif
                    @endif
                    {{-- Institución --}}
                    @if($user->institution && !$user->hasRole('super-admin'))
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->institution->name }}
                        </p>
                    @elseif($user->hasRole('super-admin'))
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ASCUN — Acceso global
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
