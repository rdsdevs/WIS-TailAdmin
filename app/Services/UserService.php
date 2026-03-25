<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\Auth\PasswordResetByAdminNotification;
use App\Notifications\Auth\UserCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UserService
{
    /**
     * Lista paginada de usuarios con filtros opcionales.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAll(string $institutionId, bool $isSuperAdmin, array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with(['institution', 'roles']);

        if (! $isSuperAdmin) {
            $query->where('institution_id', $institutionId);
        }

        if (! empty($filters['search'])) {
            $buscar = $filters['search'];
            $query->where(function ($q) use ($buscar): void {
                $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%")
                    ->orWhere('document_number', 'like', "%{$buscar}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $filters['role']));
        }

        return $query->orderBy('name')->paginate(15);
    }

    /**
     * Crea un nuevo usuario del sistema.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): User
    {
        return DB::transaction(function () use ($data, $creator): User {
            // Si el creador es admin (no super-admin), fuerza su institution_id
            if (! $creator->hasRole('super-admin')) {
                $data['institution_id'] = $creator->institution_id;
            }

            $roles = $data['roles'] ?? [];
            unset($data['roles'], $data['password_confirmation']);

            $user = User::create($data);
            $user->assignRole($roles);

            // Notificar al nuevo usuario (solo si tiene email)
            if ($user->email) {
                $temporaryPassword = $data['password'] ?? ''; // Ya fue hasheado, usar el raw si está disponible
                $user->notify(new UserCreatedNotification(
                    userName: $user->name,
                    userEmail: $user->email,
                    temporaryPassword: '(ver con el administrador)',
                    roleName: implode(', ', $roles),
                ));
            }

            return $user;
        });
    }

    /**
     * Actualiza los datos de un usuario.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $roles = $data['roles'] ?? [];
            unset($data['roles'], $data['password_confirmation']);

            // Si el password viene vacío, no actualizarlo
            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user->update($data);
            $user->syncRoles($roles);

            return $user->fresh(['institution', 'roles']);
        });
    }

    /**
     * Restablece la contraseña de un usuario y notifica al afectado.
     */
    public function resetPassword(User $user, string $newPassword, User $admin): User
    {
        return DB::transaction(function () use ($user, $newPassword, $admin): User {
            $user->update(['password' => Hash::make($newPassword)]);
            $user->notify(new PasswordResetByAdminNotification(
                userName: $user->name,
                temporaryPassword: $newPassword,
                adminName: $admin->name,
            ));

            return $user->fresh();
        });
    }

    /**
     * Elimina lógicamente un usuario.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
