<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Exports\RH\PositionTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\RH\StorePositionImportRequest;
use App\Jobs\RH\ImportPositionEmailsJob;
use App\Jobs\RH\ImportPositionFunctionsJob;
use App\Jobs\RH\ImportPositionsJob;
use App\Models\RH\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PositionImportController extends Controller
{
    public function create(): View
    {
        $this->authorize('import', Position::class);

        $resultadoPrevio = cache()->pull('import_positions_result_'.auth()->id());

        return view('pages.rh.cargos.importar', compact('resultadoPrevio'));
    }

    public function store(StorePositionImportRequest $request): RedirectResponse
    {
        $tipo = $request->validated('tipo');
        $path = $request->file('archivo')
            ->store('imports/cargos/'.auth()->id(), 'local');

        $institutionId = (string) auth()->user()->institution_id;
        $userId = (string) auth()->id();

        match ($tipo) {
            'cargos' => ImportPositionsJob::dispatch($path, $institutionId, $userId),
            'funciones' => ImportPositionFunctionsJob::dispatch($path, $institutionId, $userId),
            'correos' => ImportPositionEmailsJob::dispatch($path, $institutionId, $userId),
        };

        return redirect()
            ->route('rh.cargos.importar')
            ->with('exito', 'Importación iniciada. Los resultados aparecerán en breve.');
    }

    public function template(string $tipo): BinaryFileResponse
    {
        $this->authorize('import', Position::class);

        $tipo = in_array($tipo, ['cargos', 'funciones', 'correos'], true) ? $tipo : 'cargos';
        $filename = "plantilla_{$tipo}_".now()->format('Ymd').'.xlsx';

        return Excel::download(new PositionTemplateExport($tipo), $filename);
    }
}
