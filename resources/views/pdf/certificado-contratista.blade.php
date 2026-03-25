@php
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $diasSemana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $fechaLarga = $diasSemana[$issued_at->dayOfWeek] . ' ' . $issued_at->day . ' de ' . $meses[$issued_at->month - 1] . ' de ' . $issued_at->year;

    $nombre         = $collaborator_snapshot['is_company']
        ? ($collaborator_snapshot['company_name'] ?? $collaborator_snapshot['full_name'] ?? '')
        : ($collaborator_snapshot['full_name'] ?? '');
    $tipoDoc        = $collaborator_snapshot['document_type_name'] ?? 'Cédula de Ciudadanía';
    $numDoc         = $collaborator_snapshot['document_number'] ?? '';
    $genero         = $collaborator_snapshot['gender'] ?? 'M';
    $isCompany      = ! empty($collaborator_snapshot['is_company']);
    $identificado   = $isCompany ? 'identificada' : ($genero === 'F' ? 'identificada' : 'identificado');
    $numContratos   = count($contracts_snapshot);

    $showObject          = ! empty($options_snapshot['show_object']);
    $showObligations     = ! empty($options_snapshot['show_obligations']);
    $showValue           = ! empty($options_snapshot['show_value']);
    $showProrrogas       = ! empty($options_snapshot['show_prorrogas']);
    $showEarlyTermination = ! empty($options_snapshot['show_early_termination']);

    $signerGender = strtolower($signature->signer_position ?? '');
    $laSuscrita   = str_contains($signerGender, 'director') ? 'EL SUSCRITO' : 'LA SUSCRITA';

    // Helper para convertir número a texto
    function montoATexto(float $monto): string {
        $formatter = new \NumberFormatter('es_CO', \NumberFormatter::SPELLOUT);
        return ucfirst($formatter->format((int) $monto));
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Contratación</title>
    <style>
        @page {
            margin-top: 110px;
            margin-bottom: 3.5cm;
            margin-left: 2cm;
            margin-right: 1.5cm;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11pt;
            color: #000;
        }
        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 65px;
        }
        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ccc;
        }
        .bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .contract-section { margin-bottom: 8px; }
        .section-label { font-weight: bold; }
        .page-break { page-break-before: always; margin-bottom: 30px; }
    </style>
</head>
<body>

{{-- Header fijo --}}
<header>
    <table style="border-collapse: collapse; border: none; width: 100%;">
        <tr>
            <td style="border: none; text-align: left; vertical-align: top; width: 60%;">
                <strong style="font-size: 10pt;">{{ strtoupper($institution_name) }}</strong><br>
                @if ($logo_base64)
                    <img src="{{ $logo_base64 }}" style="height: 45px; margin-top: 4px;">
                @endif
            </td>
            <td style="border: none; text-align: right; vertical-align: top; width: 40%;">
                <img src="data:image/png;base64,{{ $qr_base64 }}" style="height: 55px;">
                <br><span style="font-size: 7pt;">Escanea para validar</span>
            </td>
        </tr>
    </table>
</header>

{{-- Footer fijo --}}
<footer>
    Certificado de contratación — {{ strtoupper($institution_name) }} — Código: {{ $certificate->verification_code }}
</footer>

{{-- Contenido principal --}}
<main>
    <p class="bold text-center" style="margin-top: 10px; line-height: 1.6;">
        {{ $laSuscrita }} {{ strtoupper($signature->signer_position ?? '') }}<br>
        DE {{ strtoupper($institution_name) }}<br>
        CERTIFICA:
    </p>

    <p class="text-justify">
        Que <strong>{{ strtoupper($nombre) }}</strong>, {{ $identificado }} con
        {{ $isCompany ? strtolower($tipoDoc) : $tipoDoc }} N° {{ number_format((float) $numDoc, 0, ',', '.') }},
        cuenta con la siguiente información relacionada a
        {{ $numContratos > 1 ? 'sus contratos' : 'su contrato' }}
        de acuerdo con la base de datos de contratación.
    </p>

    @foreach ($contracts_snapshot as $idx => $contrato)
        @php
            $fees   = (float) ($contrato['fees'] ?? 0);
            $salary = (float) ($contrato['salary'] ?? 0);
            $valor  = $fees > 0 ? $fees : $salary;
        @endphp

        <p class="bold" style="margin-top: 14px;">
            CONTRATO DE {{ strtoupper($contrato['contract_type'] ?? '') }} No. {{ $contrato['contract_code'] ?? '' }}
        </p>

        @if ($showObject && ! empty($contrato['object']))
            <p class="text-justify contract-section">
                <span class="section-label">OBJETO:</span> {!! $contrato['object'] !!}
            </p>
        @endif

        <p class="text-justify contract-section">
            <span class="section-label">PLAZO DE EJECUCIÓN:</span>
            Por el periodo comprendido entre el {{ $contrato['start_date'] ?? '—' }}
            y el {{ $contrato['end_date'] ?? 'fecha indeterminada' }}.
        </p>

        @if ($showValue && $valor > 0)
            <p class="text-justify contract-section">
                <span class="section-label">VALOR DEL CONTRATO:</span>
                {{ montoATexto($valor) }} pesos ($ {{ number_format($valor, 0, ',', '.') }}) M/cte.
            </p>
        @endif

        @if ($showObligations && ! empty($contrato['obligations']))
            <p class="text-justify contract-section">
                <span class="section-label">OBLIGACIONES:</span> {!! $contrato['obligations'] !!}
            </p>
        @endif

        @if ($showProrrogas && ! empty($contrato['extensions']))
            <p class="bold" style="margin-top: 10px;">PRÓRROGA(S) AL CONTRATO:</p>
            @foreach ($contrato['extensions'] as $ext)
                <p class="text-justify" style="margin-left: 10px;">
                    @if (str_contains($ext['extension_type'] ?? '', 'tiempo'))
                        El contrato fue prorrogado hasta el <strong>{{ $ext['new_end_date'] ?? '—' }}</strong>.
                        @if (! empty($ext['extension_months']))
                            Extensión de {{ $ext['extension_months'] }} mes(es).
                        @endif
                        @if (! empty($ext['extension_days']))
                            Extensión de {{ $ext['extension_days'] }} día(s).
                        @endif
                    @endif
                    @if (str_contains($ext['extension_type'] ?? '', 'valor') && ! empty($ext['extension_value']))
                        Se adicionó al contrato un valor de <strong>$ {{ number_format((float) $ext['extension_value'], 0, ',', '.') }}</strong>.
                    @endif
                    @if (! empty($ext['approval_date']))
                        Fecha de aprobación: {{ $ext['approval_date'] }}.
                    @endif
                    @if (! empty($ext['reason']))
                        Motivo: {{ $ext['reason'] }}.
                    @endif
                </p>
            @endforeach
        @endif

        @if ($showEarlyTermination && ! empty($contrato['early_termination_date']))
            <p class="bold" style="margin-top: 10px;">TERMINACIÓN ANTICIPADA:</p>
            <p class="text-justify" style="margin-left: 10px;">
                Fecha de terminación anticipada: <strong>{{ $contrato['early_termination_date'] }}</strong>.
                @if (! empty($contrato['early_termination_reason']))
                    Motivo: {{ $contrato['early_termination_reason'] }}.
                @endif
            </p>
        @endif

        <p class="text-justify contract-section">
            <span class="section-label">ESTADO DEL CONTRATO:</span> {{ $contrato['status'] ?? '—' }}
        </p>

        <p class="text-justify" style="font-size: 10pt; margin-top: 8px;">
            De acuerdo con lo dispuesto por el numeral 3° del artículo 32 de la Ley 80 de 1993, en ningún caso
            estos contratos generaron relación laboral, ni prestaciones sociales por las obligaciones
            contratadas y se celebraron por el término estrictamente indispensable.
        </p>

        @if ($idx < $numContratos - 1)
            <div class="page-break"></div>
        @endif
    @endforeach

    <p class="text-justify" style="margin-top: 20px;">
        Se expide la presente certificación a petición de <strong>{{ $addressed_to }}</strong>
        el {{ $fechaLarga }}.
    </p>

    <br><br>
    <p>Atentamente,</p>
    <br><br><br>

    @if ($signature->signature_image)
        <img src="{{ $signature->signature_image }}" style="width: 22%; margin-bottom: -28px;"><br>
    @endif
    <strong>{{ strtoupper($signature->signer_name ?? '') }}</strong><br>
    {{ $signature->signer_position ?? '' }}
</main>

{{-- Página 2: texto legal --}}
<div class="page-break"></div>
@include('pdf.partials.certificado-legal')

</body>
</html>
