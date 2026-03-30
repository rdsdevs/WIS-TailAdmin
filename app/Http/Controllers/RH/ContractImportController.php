<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Exports\RH\ContractTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\RH\StoreContractImportRequest;
use App\Jobs\RH\ImportContractsJob;
use App\Models\RH\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractImportController extends Controller
{
    public function create(): View
    {
        $this->authorize('import', Contract::class);

        $resultadoPrevio = cache()->pull('import_contracts_result_'.auth()->id());

        return view('pages.rh.contratos.importar', compact('resultadoPrevio'));
    }

    public function store(StoreContractImportRequest $request): RedirectResponse
    {
        $path = $request->file('archivo')
            ->store('imports/contratos/'.auth()->id(), 'local');

        ImportContractsJob::dispatch(
            filePath: $path,
            institutionId: auth()->user()->institution_id,
            overwrite: $request->boolean('sobrescribir'),
            userId: auth()->id(),
        );

        return redirect()
            ->route('rh.contratos.importar')
            ->with('exito', 'Importación iniciada. Esta página se actualizará con los resultados en breve.');
    }

    public function template(): BinaryFileResponse
    {
        $institutionId = auth()->user()->institution_id;
        $filename = 'plantilla_contratos_'.now()->format('Ymd').'.xlsx';

        return Excel::download(new ContractTemplateExport($institutionId), $filename);
    }
}
