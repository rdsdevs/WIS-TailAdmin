<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateCollaboratorRequest;
use App\Http\Requests\RH\UpdateCollaboratorRequest;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;
use App\Services\RH\CollaboratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CollaboratorController extends Controller
{
    public function __construct(private readonly CollaboratorService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Collaborator::class);

        $institutionId = auth()->user()->institution_id;
        $filters = $request->only(['buscar', 'tipo', 'status_id']);
        $colaboradores = $this->service->getAll($institutionId, $filters);

        $estados = CollaboratorStatus::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Collaborator::where('institution_id', $institutionId)->count(),
            'empleados' => Collaborator::where('institution_id', $institutionId)->where('type', 'Empleado')->count(),
            'contratistas' => Collaborator::where('institution_id', $institutionId)->where('type', 'Contratista')->count(),
            'porVencer' => \App\Models\RH\Contract::where('status', 'Vigente')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>=', now())
                ->whereHas('collaborator', fn ($q) => $q->where('institution_id', $institutionId))
                ->count(),
        ];

        return view('pages.rh.colaboradores.index', compact('colaboradores', 'filters', 'estados', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', Collaborator::class);

        $institutionId = auth()->user()->institution_id;

        $tiposDocumento = DocumentType::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get();

        $estados = CollaboratorStatus::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get();

        return view('pages.rh.colaboradores.create', compact('tiposDocumento', 'estados'));
    }

    public function store(CreateCollaboratorRequest $request): RedirectResponse
    {
        $colaborador = $this->service->create($request);

        return redirect()->route('rh.colaboradores.show', $colaborador)
            ->with('exito', 'Colaborador registrado correctamente.');
    }

    public function show(Collaborator $collaborator): View
    {
        $this->authorize('view', $collaborator);

        $collaborator->load([
            'documentType',
            'status',
        ]);

        $contracts = $collaborator->contracts()
            ->with(['contractType', 'position.department', 'extensions'])
            ->orderByDesc('start_date')
            ->get();

        $activeContract = $contracts->first(fn ($c) => $c->status === 'Vigente');

        return view('pages.rh.colaboradores.show', compact('collaborator', 'contracts', 'activeContract'));
    }

    public function edit(Collaborator $collaborator): View
    {
        $this->authorize('update', $collaborator);

        $institutionId = auth()->user()->institution_id;

        $tiposDocumento = DocumentType::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get();

        $estados = CollaboratorStatus::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get();

        return view('pages.rh.colaboradores.edit', compact('collaborator', 'tiposDocumento', 'estados'));
    }

    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator): RedirectResponse
    {
        $this->service->update($collaborator, $request);

        return redirect()->route('rh.colaboradores.show', $collaborator)
            ->with('exito', 'Colaborador actualizado correctamente.');
    }

    public function destroy(Collaborator $collaborator): RedirectResponse
    {
        $this->authorize('delete', $collaborator);
        $this->service->delete($collaborator);

        return redirect()->route('rh.colaboradores.index')
            ->with('exito', 'Colaborador eliminado correctamente.');
    }

    public function export(string $tipo = 'todos'): BinaryFileResponse
    {
        $this->authorize('export', Collaborator::class);

        $institutionId = auth()->user()->institution_id;
        $nombreArchivo = "colaboradores_{$tipo}_".now()->format('Ymd_His').'.xlsx';

        return Excel::download($this->service->export($institutionId, $tipo), $nombreArchivo);
    }

    /**
     * Cambia el tipo del colaborador entre Empleado y Contratista.
     */
    public function changeType(Collaborator $colaborador): RedirectResponse
    {
        $this->authorize('changeType', $colaborador);

        $this->service->changeType($colaborador);

        return redirect()
            ->route('rh.colaboradores.show', $colaborador)
            ->with('exito', 'Tipo de colaborador actualizado exitosamente.');
    }
}
