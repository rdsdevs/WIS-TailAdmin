<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateContractorRequest;
use App\Http\Requests\RH\UpdateContractorRequest;
use App\Models\RH\Contractor;
use App\Services\RH\ContractorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractorController extends Controller
{
    public function __construct(private readonly ContractorService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Contractor::class);

        $institutionId = auth()->user()->institution_id;
        $filters = $request->only(['buscar', 'activo']);
        $contratistas = $this->service->getAll($institutionId, $filters);

        return view('pages.rh.contratistas.index', compact('contratistas', 'filters'));
    }

    public function create(): View
    {
        $this->authorize('create', Contractor::class);

        return view('pages.rh.contratistas.create');
    }

    public function store(CreateContractorRequest $request): RedirectResponse
    {
        $contratista = $this->service->create($request);

        return redirect()->route('rh.contratistas.show', $contratista)
            ->with('exito', 'Contratista registrado correctamente.');
    }

    public function show(Contractor $contratista): View
    {
        $this->authorize('view', $contratista);

        $contratista->load(['contracts' => fn ($q) => $q->orderBy('start_date', 'desc')]);

        return view('pages.rh.contratistas.show', compact('contratista'));
    }

    public function edit(Contractor $contratista): View
    {
        $this->authorize('update', $contratista);

        return view('pages.rh.contratistas.edit', compact('contratista'));
    }

    public function update(UpdateContractorRequest $request, Contractor $contratista): RedirectResponse
    {
        $this->service->update($contratista, $request);

        return redirect()->route('rh.contratistas.show', $contratista)
            ->with('exito', 'Contratista actualizado correctamente.');
    }

    public function destroy(Contractor $contratista): RedirectResponse
    {
        $this->authorize('delete', $contratista);
        $this->service->delete($contratista);

        return redirect()->route('rh.contratistas.index')
            ->with('exito', 'Contratista eliminado correctamente.');
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('export', Contractor::class);

        $institutionId = auth()->user()->institution_id;
        $nombreArchivo = 'contratistas_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download($this->service->export($institutionId), $nombreArchivo);
    }
}
