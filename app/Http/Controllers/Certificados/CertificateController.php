<?php

declare(strict_types=1);

namespace App\Http\Controllers\Certificados;

use App\Http\Controllers\Controller;
use App\Http\Requests\Certificados\GenerateContractorCertificateRequest;
use App\Http\Requests\Certificados\GenerateEmployeeCertificateRequest;
use App\Models\Certificados\Certificate;
use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Services\Certificados\CertificateService;
use App\Services\Certificados\CertificateSignatureService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $service,
        private readonly CertificateSignatureService $signatureService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certificate::class);

        $institutionId = auth()->user()->institution_id;
        $filters = $request->only(['tipo', 'buscar', 'fecha_desde', 'fecha_hasta']);

        $certificates = $this->service->getAll($institutionId, $filters);
        $firmas = $this->signatureService->getActive($institutionId);

        return view('pages.certificados.index', compact('certificates', 'firmas', 'filters'));
    }

    public function generateEmployee(GenerateEmployeeCertificateRequest $request): Response
    {
        $this->authorize('generateEmployee', Certificate::class);

        $validated  = $request->validated();
        $collaborator = Collaborator::findOrFail($validated['collaborator_id']);
        $signature    = CertificateSignature::findOrFail($validated['certificate_signature_id']);

        $certificate = $this->service->generateEmployee(
            collaborator: $collaborator,
            signature:    $signature,
            contractIds:  $validated['contract_ids'],
            options:      $validated['options'] ?? [],
            addressedTo:  $validated['addressed_to'] ?? null,
            issuedBy:     auth()->user(),
        );

        $pdf = $this->service->buildPdf($certificate);

        $filename = 'certificado_laboral_' . $collaborator->document_number . '_' . Carbon::now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function generateContractor(GenerateContractorCertificateRequest $request): Response
    {
        $this->authorize('generateContractor', Certificate::class);

        $validated    = $request->validated();
        $collaborator = Collaborator::findOrFail($validated['collaborator_id']);
        $signature    = CertificateSignature::findOrFail($validated['certificate_signature_id']);

        $certificate = $this->service->generateContractor(
            collaborator: $collaborator,
            signature:    $signature,
            contractIds:  $validated['contract_ids'],
            options:      $validated['options'] ?? [],
            addressedTo:  $validated['addressed_to'] ?? null,
            issuedBy:     auth()->user(),
        );

        $pdf = $this->service->buildPdf($certificate);

        $filename = 'certificado_contratacion_' . $collaborator->document_number . '_' . Carbon::now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $this->authorize('delete', $certificate);

        if (auth()->user()->institution_id !== $certificate->institution_id) {
            abort(403);
        }

        $certificate->delete();

        return redirect()->route('certificados.index')
            ->with('exito', 'Certificado eliminado del historial.');
    }
}
