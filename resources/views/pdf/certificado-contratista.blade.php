@php
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $diasSemana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $fechaLarga = $diasSemana[$issued_at->dayOfWeek] . ', ' . $issued_at->day . ' de ' . $meses[$issued_at->month - 1] . ' de ' . $issued_at->year;

    $nombre       = $collaborator_snapshot['is_company']
        ? ($collaborator_snapshot['company_name'] ?? $collaborator_snapshot['full_name'] ?? '')
        : ($collaborator_snapshot['full_name'] ?? '');
    $tipoDoc      = $collaborator_snapshot['document_type_name'] ?? 'Cédula de Ciudadanía';
    $tipoDocCorto = $collaborator_snapshot['document_type'] ?? 'cc';
    $numDoc       = $collaborator_snapshot['document_number'] ?? '';
    $genero       = $collaborator_snapshot['gender'] ?? 'M';
    $isCompany    = ! empty($collaborator_snapshot['is_company']);
    $identificado = $isCompany ? 'identificada' : ($genero === 'F' ? 'identificada' : 'identificado');
    $numContratos = count($contracts_snapshot);

    $showObject           = ! empty($options_snapshot['show_object']);
    $showObligations      = ! empty($options_snapshot['show_obligations']);
    $showValue            = ! empty($options_snapshot['show_value']);
    $showProrrogas        = ! empty($options_snapshot['show_prorrogas']);
    $showEarlyTermination = ! empty($options_snapshot['show_early_termination']);

    // Determina el género del cargo firmante para "EL SUSCRITO" / "LA SUSCRITA"
    $cargoFirmante = strtolower($signature->signer_position ?? '');
    $laSuscrita    = (str_contains($cargoFirmante, 'director') && ! str_contains($cargoFirmante, 'directora'))
        ? 'EL SUSCRITO'
        : 'LA SUSCRITA';

    // Helper: fecha larga desde string d/m/Y
    function fechaLargaCont(string $fecha): string {
        $mesesL = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        try {
            $d = \Carbon\Carbon::createFromFormat('d/m/Y', $fecha);
            return $d->day . ' de ' . $mesesL[$d->month - 1] . ' de ' . $d->year;
        } catch (\Throwable) {
            return $fecha;
        }
    }

    // Helper: monto a texto
    function montoATextoCont(float $monto): string {
        $fmt = new \NumberFormatter('es_CO', \NumberFormatter::SPELLOUT);
        return strtoupper($fmt->format((int) $monto));
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Contratación</title>
    <style>
        @page {
            margin-top: 120px;
            margin-bottom: 55px;
            margin-left: 2cm;
            margin-right: 1.8cm;
        }
        body {
            font-family: "Arial", sans-serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.45;
        }
        header {
            position: fixed;
            top: -105px;
            left: 0;
            right: 0;
            height: 90px;
        }
        footer {
            position: fixed;
            bottom: -45px;
            left: 0;
            right: 0;
            height: 44px;
        }
        .bold        { font-weight: bold; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .section-label { font-weight: bold; }
        .page-break { page-break-before: always; }
        .certifica-header {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            line-height: 1.5;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .intro-paragraph {
            font-size: 12pt;
            text-align: justify;
            margin-bottom: 12px;
        }
        .contract-title {
            font-weight: bold;
            font-size: 11.5pt;
            margin-top: 12px;
            margin-bottom: 4px;
        }
        .contract-section {
            text-align: justify;
            margin-bottom: 4px;
            font-size: 11pt;
        }
        .firma-block {
            text-align: center;
            margin-top: 50px;
        }
        .firma-block img {
            display: block;
            margin: 0 auto 2px auto;
            width: 160px;
        }
    </style>
</head>
<body>

{{-- Script para numeración de página --}}
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("Arial", "normal");
        $w    = $pdf->get_width();
        $pdf->page_text($w - 95, 17, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 7.5, [0.1, 0.1, 0.1]);
    }
</script>

{{-- ── Header fijo ─────────────────────────────────────────────────────── --}}
<header>
    <table style="border-collapse: collapse; border: none; width: 100%; padding: 0; margin: 0;">
        <tr>
            {{-- Izquierda: QR --}}
            <td style="border: none; vertical-align: top; text-align: left; width: 18%;">
                <img src="{{ $qr_base64 }}" style="height: 68px; width: 68px;">
                <br><span style="font-size: 6pt; color: #555;">Escanea para validar el certificado</span>
            </td>
            {{-- Centro: Nombre institución --}}
            <td style="border: none; vertical-align: middle; text-align: left; width: 42%; padding-left: 6px;">
                <strong style="font-size: 9.5pt; color: #1a1a1a;">{{ strtoupper($institution_name) }}</strong>
            </td>
            {{-- Derecha: Logo --}}
            <td style="border: none; vertical-align: top; text-align: right; width: 40%;">
                @if ($logo_base64)
                    <img src="{{ $logo_base64 }}" style="max-height: 72px; max-width: 180px;">
                @endif
            </td>
        </tr>
    </table>
    <hr style="border: 0; border-top: 1.5px solid #1a3a6b; margin: 4px 0 0 0;">
</header>

{{-- ── Footer fijo ─────────────────────────────────────────────────────── --}}
<footer>
    @if ($footer_base64)
        <img src="{{ $footer_base64 }}" style="width: 100%; height: 44px;">
    @else
        <div style="background-color: #1a3a6b; padding: 5px 10px; text-align: center; color: #fff; font-size: 7.5pt;">
            <span style="margin: 0 6px;">&#9679; Calle 93 No. 16 - 43</span>
            <span style="margin: 0 6px;">&#9679; (57 - 1) 623 15 80</span>
            <span style="margin: 0 6px;">&#9679; ascun@ascun.org.co</span>
            <span style="margin: 0 6px;">&#9679; www.ascun.org.co</span>
            <span style="margin: 0 6px;">&#9679; Bogotá - Colombia</span>
        </div>
    @endif
</footer>

{{-- ── Contenido principal ──────────────────────────────────────────────── --}}
<main>

    {{-- Bloque CERTIFICA --}}
    <div class="certifica-header">
        LA SUSCRITA {{ strtoupper($signature->signer_position ?? '') }}<br>
        DE LA ASOCIACIÓN COLOMBIANA DE UNIVERSIDADES -ASCÚN-<br>
        NIT. 860.025.721-0<br>
        CERTIFICA:
    </div>

    {{-- Párrafo introductorio --}}
    <p class="intro-paragraph">
        Que <strong>{{ strtoupper($nombre) }}</strong>,
        {{ $identificado }} con {{ strtolower($tipoDocCorto) }} N°
        {{ number_format((float) $numDoc, 0, ',', '.') }},
        cuenta con la siguiente información relacionada a
        {{ $numContratos > 1 ? 'sus contratos' : 'su contrato' }}
        de acuerdo con la base de datos de contratación.
    </p>

    {{-- Contratos --}}
    @foreach ($contracts_snapshot as $idx => $contrato)
        @php
            $fees   = (float) ($contrato['fees']   ?? 0);
            $salary = (float) ($contrato['salary'] ?? 0);
            $valor  = $fees > 0 ? $fees : $salary;

            $startLargo = ! empty($contrato['start_date']) ? fechaLargaCont($contrato['start_date']) : '—';
            $endLargo   = ! empty($contrato['end_date'])   ? fechaLargaCont($contrato['end_date'])   : 'fecha indeterminada';
        @endphp

        <p class="contract-title">
            CONTRATO DE {{ strtoupper($contrato['contract_type'] ?? '') }}
            No. {{ $contrato['contract_code'] ?? '' }}
        </p>

        @if ($showObject && ! empty($contrato['object']))
            <p class="contract-section">
                <span class="section-label">OBJETO</span>:
                {!! $contrato['object'] !!}
            </p>
        @endif

        <p class="contract-section">
            <span class="section-label">PLAZO DE EJECUCIÓN</span>:
            Por el periodo comprendido entre el {{ $startLargo }} y el {{ $endLargo }}.
        </p>

        @if ($showValue && $valor > 0)
            <p class="contract-section">
                <span class="section-label">VALOR DEL CONTRATO</span>:
                {{ montoATextoCont($valor) }} PESOS
                (${{ number_format($valor, 0, ',', '.') }}) M/cte.
            </p>
        @endif

        @if ($showObligations && ! empty($contrato['obligations']))
            <p class="contract-section">
                <span class="section-label">OBLIGACIONES</span>:
                {!! $contrato['obligations'] !!}
            </p>
        @endif

        @if ($showProrrogas && ! empty($contrato['extensions']))
            @foreach ($contrato['extensions'] as $ext)
                <p class="contract-section" style="margin-left: 0;">
                    @if (! empty($ext['approval_date']))
                        El presente contrato suscribió una prórroga
                        @if (! empty($ext['extension_months']))
                            de {{ $ext['extension_months'] }} mes(es)
                        @elseif (! empty($ext['extension_days']))
                            de {{ $ext['extension_days'] }} día(s)
                        @endif
                        @if (! empty($ext['new_end_date']))
                            hasta el <strong>{{ fechaLargaCont($ext['new_end_date']) }}</strong>
                        @endif
                        , aprobada el {{ fechaLargaCont($ext['approval_date']) }}.
                        @if (! empty($ext['extension_value']))
                            Se adicionó un valor de
                            <strong>$ {{ number_format((float) $ext['extension_value'], 0, ',', '.') }}</strong> M/cte.
                        @endif
                    @endif
                </p>
            @endforeach
        @endif

        @if ($showEarlyTermination && ! empty($contrato['early_termination_date']))
            <p class="contract-section">
                <span class="section-label">TERMINACIÓN ANTICIPADA</span>:
                El contrato fue terminado anticipadamente el
                <strong>{{ fechaLargaCont($contrato['early_termination_date']) }}</strong>.
                @if (! empty($contrato['early_termination_reason']))
                    Motivo: {{ $contrato['early_termination_reason'] }}.
                @endif
            </p>
        @endif

        <p class="contract-section">
            <span class="section-label">ESTADO DEL CONTRATO</span>:
            {{ $contrato['status'] ?? '—' }}
        </p>

        <p class="contract-section" style="font-size: 10.5pt;">
            De acuerdo con lo dispuesto por el numeral 3° del artículo 32 de la Ley 80 de 1993,
            en ningún caso estos contratos generaron relación laboral, ni prestaciones sociales
            por las obligaciones contratadas y se celebraron por el término estrictamente
            indispensable.
        </p>

        @if ($idx < $numContratos - 1)
            <div class="page-break"></div>
        @endif
    @endforeach

    {{-- Cierre --}}
    <p class="text-justify" style="font-size: 11pt; margin-top: 25px;">
        Se expide la presente certificación a petición de
        <strong>{{ $addressed_to }}</strong>
        el {{ $fechaLarga }}.
    </p>

    {{-- Firma centrada --}}
    <div class="firma-block" style="margin-top: 30px;">
        @if (! empty($signature->signature_path))
            <img src="{{ $signature->signature_path }}" style="width: 200px; margin-bottom: 2px;">
        @else
            <br><br><br>
        @endif
        <p style="margin: 0; font-weight: bold; font-size: 11.5pt;">
            {{ strtoupper($signature->signer_name ?? '') }}
        </p>
        <p style="margin: 0; font-size: 11pt;">
            {{ strtoupper($signature->signer_position ?? '') }}
        </p>
    </div>

</main>

{{-- Página final: texto legal --}}
<div class="page-break"></div>
@include('pdf.partials.certificado-legal')

</body>
</html>
