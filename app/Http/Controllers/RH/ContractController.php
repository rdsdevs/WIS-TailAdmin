<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateContractRequest;
use App\Http\Requests\RH\UpdateContractRequest;
use App\Models\RH\Contract;
use App\Models\RH\Contractor;
use App\Models\RH\Employee;
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

        return view('pages.rh.contratos.index', compact('contratos', 'porVencer'));
    }

    public function create(): View
    {
        $this->authorize('create', Contract::class);

        $institutionId = auth()->user()->institution_id;
        $empleados = Employee::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'document_number']);
        $contratistas = Contractor::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'company_name', 'document_number']);

        return view('pages.rh.contratos.create', compact('empleados', 'contratistas'));
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

        $contrato->load('contractable');

        return view('pages.rh.contratos.show', compact('contrato'));
    }

    public function edit(Contract $contrato): View
    {
        $this->authorize('update', $contrato);

        return view('pages.rh.contratos.edit', compact('contrato'));
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
     * Termina un contrato activo sin eliminarlo.
     */
    public function terminate(Contract $contrato): RedirectResponse
    {
        $this->authorize('terminate', $contrato);
        $this->service->terminate($contrato);

        return redirect()->route('rh.contratos.show', $contrato)
            ->with('exito', 'Contrato terminado correctamente.');
    }
}
