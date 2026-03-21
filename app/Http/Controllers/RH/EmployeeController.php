<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateEmployeeRequest;
use App\Http\Requests\RH\UpdateEmployeeRequest;
use App\Models\RH\Department;
use App\Models\RH\Employee;
use App\Models\RH\Position;
use App\Services\RH\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $institutionId = auth()->user()->institution_id;
        $filters = $request->only(['buscar', 'activo', 'department_id', 'position_id']);
        $empleados = $this->service->getAll($institutionId, $filters);

        return view('pages.rh.empleados.index', compact('empleados', 'filters'));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        $institutionId = auth()->user()->institution_id;
        $departamentos = Department::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $cargos = Position::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.rh.empleados.create', compact('departamentos', 'cargos'));
    }

    public function store(CreateEmployeeRequest $request): RedirectResponse
    {
        $empleado = $this->service->create($request);

        return redirect()->route('rh.empleados.show', $empleado)
            ->with('exito', 'Empleado registrado correctamente.');
    }

    public function show(Employee $empleado): View
    {
        $this->authorize('view', $empleado);

        $empleado->load(['position', 'department', 'contracts' => fn ($q) => $q->orderBy('start_date', 'desc')]);

        return view('pages.rh.empleados.show', compact('empleado'));
    }

    public function edit(Employee $empleado): View
    {
        $this->authorize('update', $empleado);

        $institutionId = auth()->user()->institution_id;
        $departamentos = Department::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $cargos = Position::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.rh.empleados.edit', compact('empleado', 'departamentos', 'cargos'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $empleado): RedirectResponse
    {
        $this->service->update($empleado, $request);

        return redirect()->route('rh.empleados.show', $empleado)
            ->with('exito', 'Empleado actualizado correctamente.');
    }

    public function destroy(Employee $empleado): RedirectResponse
    {
        $this->authorize('delete', $empleado);
        $this->service->delete($empleado);

        return redirect()->route('rh.empleados.index')
            ->with('exito', 'Empleado eliminado correctamente.');
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('export', Employee::class);

        $institutionId = auth()->user()->institution_id;
        $nombreArchivo = 'empleados_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download($this->service->export($institutionId), $nombreArchivo);
    }
}
