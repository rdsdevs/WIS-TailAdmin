<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateCommittedValueRequest;
use App\Http\Requests\RH\UpdateCommittedValueRequest;
use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use App\Services\RH\CommittedValueService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommittedValueController extends Controller
{
    public function __construct(private readonly CommittedValueService $service) {}

    public function index(Contract $contrato): View
    {
        $this->authorize('viewAny', [CommittedValue::class, $contrato]);

        $contrato->load('collaborator');
        $valores = $this->service->getAllForContract($contrato);

        return view('pages.rh.contratos.valores-comprometidos.index', [
            'contrato' => $contrato,
            'valores' => $valores,
        ]);
    }

    public function store(CreateCommittedValueRequest $request, Contract $contrato): RedirectResponse
    {
        $this->service->create($contrato, $request->validated());

        return redirect()
            ->route('rh.contratos.valores-comprometidos.index', $contrato)
            ->with('exito', 'Valor comprometido registrado correctamente.');
    }

    public function update(
        UpdateCommittedValueRequest $request,
        Contract $contrato,
        CommittedValue $valor_comprometido,
    ): RedirectResponse {
        $this->ensureBelongsTo($contrato, $valor_comprometido);
        $this->service->update($valor_comprometido, $request->validated());

        return redirect()
            ->route('rh.contratos.valores-comprometidos.index', $contrato)
            ->with('exito', 'Valor comprometido actualizado correctamente.');
    }

    public function destroy(Contract $contrato, CommittedValue $valor_comprometido): RedirectResponse
    {
        $this->ensureBelongsTo($contrato, $valor_comprometido);
        $this->authorize('delete', $valor_comprometido);

        try {
            $this->service->delete($valor_comprometido);
        } catch (DomainException $e) {
            return redirect()
                ->route('rh.contratos.valores-comprometidos.index', $contrato)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('rh.contratos.valores-comprometidos.index', $contrato)
            ->with('exito', 'Valor comprometido eliminado correctamente.');
    }

    /**
     * Garantiza que el valor comprometido pertenezca al contrato de la URL
     * (sustituye a Route::scopeBindings, que requeriría una relación con
     * nombre en español en el modelo Contract).
     */
    private function ensureBelongsTo(Contract $contrato, CommittedValue $valorComprometido): void
    {
        if ($valorComprometido->contract_id !== $contrato->id) {
            throw new NotFoundHttpException('El valor comprometido no pertenece al contrato indicado.');
        }
    }
}
