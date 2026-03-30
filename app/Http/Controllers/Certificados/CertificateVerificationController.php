<?php

declare(strict_types=1);

namespace App\Http\Controllers\Certificados;

use App\Http\Controllers\Controller;
use App\Services\Certificados\CertificateService;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function __construct(private readonly CertificateService $service) {}

    public function show(string $codigo): View
    {
        $certificate = $this->service->findByVerificationCode($codigo);

        if ($certificate === null) {
            return view('pages.certificados.verificar', [
                'certificate' => null,
                'notFound' => true,
            ]);
        }

        // La verificación pública NO muestra salario/honorarios
        return view('pages.certificados.verificar', [
            'certificate' => $certificate,
            'notFound' => false,
        ]);
    }
}
