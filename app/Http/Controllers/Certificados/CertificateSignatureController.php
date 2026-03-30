<?php

declare(strict_types=1);

namespace App\Http\Controllers\Certificados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Certificados\CreateCertificateSignatureRequest;
use App\Http\Requests\Certificados\UpdateCertificateSignatureRequest;
use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Services\Certificados\CertificateSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CertificateSignatureController extends Controller
{
    public function __construct(private readonly CertificateSignatureService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', CertificateSignature::class);

        $firmas = $this->service->getAll(auth()->user()->institution_id);

        return view('pages.certificados.firmas.index', compact('firmas'));
    }

    public function create(): View
    {
        $this->authorize('create', CertificateSignature::class);

        $empleados = Collaborator::query()
            ->empleados()
            ->byInstitution(auth()->user()->institution_id)
            ->with(['activeContract.position'])
            ->orderBy('first_surname')
            ->get()
            ->map(fn ($c) => [
                'nombre' => $c->full_name,
                'cargo'  => $c->activeContract?->position?->name ?? '',
            ])
            ->values();

        return view('pages.certificados.firmas.create', compact('empleados'));
    }

    public function store(CreateCertificateSignatureRequest $request): RedirectResponse
    {
        $this->authorize('create', CertificateSignature::class);

        $this->service->create(
            $request->validated(),
            auth()->user()->institution_id,
        );

        return redirect()->route('certificados.firmas.index')
            ->with('exito', 'Firma digital registrada correctamente.');
    }

    public function edit(CertificateSignature $signature): View
    {
        $this->authorize('update', $signature);

        $empleados = Collaborator::query()
            ->empleados()
            ->byInstitution(auth()->user()->institution_id)
            ->with(['activeContract.position'])
            ->orderBy('first_surname')
            ->get()
            ->map(fn ($c) => [
                'nombre' => $c->full_name,
                'cargo'  => $c->activeContract?->position?->name ?? '',
            ])
            ->values();

        return view('pages.certificados.firmas.edit', compact('signature', 'empleados'));
    }

    public function update(UpdateCertificateSignatureRequest $request, CertificateSignature $signature): RedirectResponse
    {
        $this->authorize('update', $signature);

        $this->service->update($signature, $request->validated());

        return redirect()->route('certificados.firmas.index')
            ->with('exito', 'Firma digital actualizada correctamente.');
    }

    public function destroy(CertificateSignature $signature): RedirectResponse
    {
        $this->authorize('delete', $signature);

        try {
            $this->service->delete($signature);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('certificados.firmas.index')
            ->with('exito', 'Firma digital eliminada correctamente.');
    }
}
