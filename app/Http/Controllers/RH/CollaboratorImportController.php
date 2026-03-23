<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Exports\RH\CollaboratorTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\RH\StoreCollaboratorImportRequest;
use App\Jobs\RH\ImportCollaboratorsJob;
use App\Models\RH\Collaborator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CollaboratorImportController extends Controller
{
    public function create(): View
    {
        $this->authorize('import', Collaborator::class);

        $resultadoPrevio = cache()->pull('import_result_'.auth()->id());

        return view('pages.rh.colaboradores.importar', compact('resultadoPrevio'));
    }

    public function store(StoreCollaboratorImportRequest $request): RedirectResponse
    {
        $path = $request->file('archivo')->store('imports/colaboradores/'.auth()->id(), 'local');

        ImportCollaboratorsJob::dispatch(
            filePath: $path,
            institutionId: auth()->user()->institution_id,
            type: $request->validated('tipo'),
            overwrite: $request->boolean('sobrescribir'),
            userId: auth()->id(),
        );

        return redirect()
            ->route('rh.colaboradores.importar')
            ->with('exito', 'Importación iniciada. Esta página se actualizará con los resultados en breve.');
    }

    public function template(string $tipo): BinaryFileResponse
    {
        $map = [
            'empleados'    => 'empleados',
            'contratistas' => 'contratistas',
            'todos'        => 'todos',
        ];

        $type = $map[$tipo] ?? 'todos';
        $filename = "plantilla_colaboradores_{$tipo}_".now()->format('Ymd').'.xlsx';

        return Excel::download(new CollaboratorTemplateExport($type), $filename);
    }
}
