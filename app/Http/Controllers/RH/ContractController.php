<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateContractRequest;
use App\Http\Requests\RH\UpdateContractRequest;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
use App\Models\RH\Position;
use App\Services\RH\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', Contract::class);

        $institutionId = auth()->user()->institution_id;
        $contratos = $this->service->getActive($institutionId);
        $porVencer = $this->service->getExpiringSoon($institutionId, 30);

        $stats = [
            'total' => Contract::count(),
            'vigentes' => Contract::where('status', 'Vigente')->count(),
            'porVencer' => Contract::where('status', 'Vigente')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>=', now())
                ->count(),
            'contratistas' => Contract::where('status', 'Vigente')
                ->whereHas('collaborator', fn ($q) => $q->where('type', 'Contratista'))
                ->count(),
        ];

        return view('pages.rh.contratos.index', compact('contratos', 'porVencer', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', Contract::class);

        $institutionId = auth()->user()->institution_id;

        $colaboradores = Collaborator::query()
            ->where('institution_id', $institutionId)
            ->whereHas('status', fn ($q) => $q->where('name', 'Activo'))
            ->orderBy('first_surname')
            ->get(['id', 'first_name', 'second_name', 'first_surname', 'second_surname', 'company_name', 'is_company', 'document_number', 'type']);

        $tiposContrato = ContractType::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get();

        $cargos = Position::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.rh.contratos.create', compact('colaboradores', 'tiposContrato', 'cargos'));
    }

    public function store(CreateContractRequest $request): RedirectResponse
    {
        $contrato = $this->service->create($request);

        return redirect()->route('rh.contratos.show', $contrato)
            ->with('exito', 'Contrato registrado correctamente.');
    }

    public function show(Contract $contrato): View
    {
        $this->authorize('view', $contrato);

        $contrato->load(['collaborator.documentType', 'contractType', 'position', 'extensions', 'committedValues']);

        return view('pages.rh.contratos.show', compact('contrato'));
    }

    public function edit(Contract $contrato): View
    {
        $this->authorize('update', $contrato);

        $institutionId = auth()->user()->institution_id;

        $tiposContrato = ContractType::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get();

        $cargos = Position::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $contract = $contrato->load('collaborator');

        return view('pages.rh.contratos.edit', compact('contract', 'tiposContrato', 'cargos'));
    }

    public function update(UpdateContractRequest $request, Contract $contrato): RedirectResponse
    {
        $this->service->update($contrato, $request);

        return redirect()->route('rh.contratos.show', $contrato)
            ->with('exito', 'Contrato actualizado correctamente.');
    }

    public function destroy(Contract $contrato): RedirectResponse
    {
        $this->authorize('delete', $contrato);
        $contrato->delete();

        return redirect()->route('rh.contratos.index')
            ->with('exito', 'Contrato eliminado correctamente.');
    }

    /**
     * Termina un contrato vigente sin eliminarlo.
     */
    public function terminate(Contract $contrato): RedirectResponse
    {
        $this->authorize('terminate', $contrato);
        $this->service->terminate($contrato);

        return redirect()->route('rh.contratos.show', $contrato)
            ->with('exito', 'Contrato terminado correctamente.');
    }
}
