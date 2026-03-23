<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
     * Elimina lógicamente un usuario.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
