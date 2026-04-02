<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreatePositionRequest;
use App\Http\Requests\RH\UpdatePositionRequest;
use App\Models\RH\Department;
use App\Models\RH\Position;
use App\Services\RH\PositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function __construct(private readonly PositionService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', Position::class);

        $institutionId = auth()->user()->institution_id;
        $cargos = $this->service->getAll($institutionId);

        return view('pages.rh.cargos.index', compact('cargos'));
    }

    public function create(): View
    {
        $this->authorize('create', Position::class);

        $institutionId = auth()->user()->institution_id;
        $departamentos = Department::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.rh.cargos.create', compact('departamentos'));
    }

    public function store(CreatePositionRequest $request): RedirectResponse
    {
        $this->service->create($request);

        return redirect()->route('rh.cargos.index')
            ->with('exito', 'Cargo registrado correctamente.');
    }

    public function show(Position $cargo): View
    {
        $this->authorize('view', $cargo);

        $cargo->load(['department', 'emails', 'functions']);

        return view('pages.rh.cargos.show', compact('cargo'));
    }

    public function edit(Position $cargo): View
    {
        $this->authorize('update', $cargo);

        $institutionId = auth()->user()->institution_id;
        $departamentos = Department::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $cargo->load(['emails', 'functions']);

        return view('pages.rh.cargos.edit', compact('cargo', 'departamentos'));
    }

    public function update(UpdatePositionRequest $request, Position $cargo): RedirectResponse
    {
        $this->service->update($cargo, $request);

        return redirect()->route('rh.cargos.index')
            ->with('exito', 'Cargo actualizado correctamente.');
    }

    public function destroy(Position $cargo): RedirectResponse
    {
        $this->authorize('delete', $cargo);
        $this->service->delete($cargo);

        return redirect()->route('rh.cargos.index')
            ->with('exito', 'Cargo eliminado correctamente.');
    }
}
