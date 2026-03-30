<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Institution;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $authUser = auth()->user();
        $isSuperAdmin = $authUser->hasRole('super-admin');
        $filters = $request->only(['search', 'role']);

        $usuarios = $this->service->getAll(
            institutionId: $authUser->institution_id ?? '',
            isSuperAdmin: $isSuperAdmin,
            filters: $filters,
        );

        $roles = Role::orderBy('name')->get();

        return view('pages.admin.usuarios.index', compact('usuarios', 'filters', 'roles', 'isSuperAdmin'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $authUser = auth()->user();
        $isSuperAdmin = $authUser->hasRole('super-admin');

        $instituciones = $isSuperAdmin
            ? Institution::orderBy('name')->get()
            : collect();

        $roles = Role::orderBy('name')
            ->when(! $isSuperAdmin, fn ($q) => $q->where('name', '!=', 'super-admin'))
            ->get();

        return view('pages.admin.usuarios.create', compact('instituciones', 'roles', 'isSuperAdmin'));
    }

    public function store(CreateUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $usuario = $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.usuarios.show', $usuario)
            ->with('exito', 'Usuario registrado correctamente.');
    }

    public function show(User $usuario): View
    {
        $this->authorize('view', $usuario);

        $usuario->load(['institution', 'roles']);

        $auditorias = $usuario->audits()
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('pages.admin.usuarios.show', compact('usuario', 'auditorias'));
    }

    public function edit(User $usuario): View
    {
        $this->authorize('update', $usuario);

        $authUser = auth()->user();
        $isSuperAdmin = $authUser->hasRole('super-admin');

        $instituciones = $isSuperAdmin
            ? Institution::orderBy('name')->get()
            : collect();

        $roles = Role::orderBy('name')
            ->when(! $isSuperAdmin, fn ($q) => $q->where('name', '!=', 'super-admin'))
            ->get();

        $usuario->load(['institution', 'roles']);

        return view('pages.admin.usuarios.edit', compact('usuario', 'instituciones', 'roles', 'isSuperAdmin'));
    }

    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);
        $this->service->update($usuario, $request->validated());

        return redirect()->route('admin.usuarios.show', $usuario)
            ->with('exito', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        $this->authorize('delete', $usuario);
        $this->service->delete($usuario);

        return redirect()->route('admin.usuarios.index')
            ->with('exito', 'Usuario eliminado correctamente.');
    }
}
