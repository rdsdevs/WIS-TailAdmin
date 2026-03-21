<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateDepartmentRequest;
use App\Http\Requests\RH\UpdateDepartmentRequest;
use App\Models\RH\Department;
use App\Services\RH\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\RH\Employee::class);

        $institutionId = auth()->user()->institution_id;
        $departamentos = $this->service->getAll($institutionId);

        return view('pages.rh.departamentos.index', compact('departamentos'));
    }

    public function create(): View
    {
        $this->authorize('create', \App\Models\RH\Employee::class);

        return view('pages.rh.departamentos.create');
    }

    public function store(CreateDepartmentRequest $request): RedirectResponse
    {
        $this->service->create($request);

        return redirect()->route('rh.departamentos.index')
            ->with('exito', 'Dependencia registrada correctamente.');
    }

    public function show(Department $departamento): View
    {
        $this->authorize('viewAny', \App\Models\RH\Employee::class);

        return view('pages.rh.departamentos.show', compact('departamento'));
    }

    public function edit(Department $departamento): View
    {
        $this->authorize('update', \App\Models\RH\Employee::class);

        return view('pages.rh.departamentos.edit', compact('departamento'));
    }

    public function update(UpdateDepartmentRequest $request, Department $departamento): RedirectResponse
    {
        $this->service->update($departamento, $request);

        return redirect()->route('rh.departamentos.index')
            ->with('exito', 'Dependencia actualizada correctamente.');
    }

    public function destroy(Department $departamento): RedirectResponse
    {
        $this->authorize('delete', \App\Models\RH\Employee::class);
        $this->service->delete($departamento);

        return redirect()->route('rh.departamentos.index')
            ->with('exito', 'Dependencia eliminada correctamente.');
    }
}
