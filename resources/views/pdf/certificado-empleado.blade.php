@php
    $meses      = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $diasSemana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $fechaLarga = $diasSemana[$issued_at->dayOfWeek] . ', ' . $issued_at->day . ' de ' . $meses[$issued_at->month - 1] . ' de ' . $issued_at->year;

    $nombre       = $collaborator_snapshot['full_name'] ?? '';
    $tipoDoc      = $collaborator_snapshot['document_type_name'] ?? 'Cédula de Ciudadanía';
    $numDoc       = $collaborator_snapshot['document_number'] ?? '';
    $genero       = $collaborator_snapshot['gender'] ?? 'M';
    $identificado = $genero === 'F' ? 'identificada' : 'identificado';
    $numContratos = count($contracts_snapshot);

    $showSalary          = ! empty($options_snapshot['show_salary']);
    $showPositionHistory = ! empty($options_snapshot['show_position_history']);

    $cargoFirmante = strtolower($signature->signer_position ?? '');
    $laSuscrita    = (str_contains($cargoFirmante, 'director') && ! str_contains($cargoFirmante, 'directora'))
        ? 'EL SUSCRITO'
        : 'LA SUSCRITA';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado Laboral</title>
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
        .bold         { font-weight: bold; }
        .text-center  { text-align: center; }
        .text-justify { text-align: justify; }
        .section-title {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 10px;
            margin-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
            font-size: 10pt;
        }
        th {
            background-color: #dde3ef;
            border: 1px solid #777;
            padding: 5px 6px;
            text-align: center;
            font-weight: bold;
        }
        td {
            border: 1px solid #777;
            padding: 5px 6px;
            text-align: center;
        }
        .certifica-header {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            line-height: 1.7;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .intro-paragraph {
            font-size: 12pt;
            text-align: justify;
            margin-bottom: 18px;
        }
        .firma-block {
            text-align: center;
            margin-top: 50px;
        }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

{{-- Numeración de página --}}
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
            <td style="border: none; vertical-align: top; text-align: left; width: 18%;">
                <img src="{{ $qr_base64 }}" style="height: 68px; width: 68px;">
                <br><span style="font-size: 6pt; color: #555;">Escanea para validar el certificado</span>
            </td>
            <td style="border: none; vertical-align: middle; text-align: left; width: 42%; padding-left: 6px;">
                <strong style="font-size: 9.5pt; color: #1a1a1a;">{{ strtoupper($institution_name) }}</strong>
            </td>
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

    <div class="certifica-header">
        {{ $laSuscrita }} {{ strtoupper($signature->signer_position ?? '') }}<br>
        DE {{ strtoupper($institution_name) }}<br>
        CERTIFICA:
    </div>

    <p class="intro-paragraph">
        Que <strong>{{ strtoupper($nombre) }}</strong>,
        {{ $identificado }} con {{ strtolower($tipoDoc) }}
        N° {{ number_format((float) $numDoc, 0, ',', '.') }},
        cuenta con la siguiente información relacionada a
        {{ $numContratos > 1 ? 'sus contratos laborales' : 'su contrato laboral' }}
        de acuerdo con la base de datos de su historia laboral.
    </p>

    @foreach ($contracts_snapshot as $i => $contrato)

        {{-- Tabla principal del contrato --}}
        <table>
            <thead>
                <tr>
                    <th>Tipo de Contrato</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Terminación</th>
                    <th>Estado</th>
                    <th>Cargo</th>
                    @if ($showSalary)
                        <th>Último Salario</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $contrato['contract_type'] ?? '—' }}</td>
                    <td>{{ $contrato['start_date'] ?? '—' }}</td>
                    <td>{{ $contrato['end_date'] ?? 'Indefinido' }}</td>
                    <td>{{ $contrato['status'] ?? '—' }}</td>
                    <td>{{ $contrato['position'] ?? '—' }}</td>
                    @if ($showSalary)
                        <td>
                            @if (! empty($contrato['salary']) && (float) $contrato['salary'] > 0)
                                $ {{ number_format((float) $contrato['salary'], 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                    @endif
                </tr>
            </tbody>
        </table>

        {{-- Historial de cargos --}}
        @if ($showPositionHistory && ! empty($contrato['position_changes']))
            <p class="section-title">HISTORIAL DE CARGOS:</p>
            <table>
                <thead>
                    <tr>
                        <th>Cargo anterior</th>
                        <th>Nuevo cargo</th>
                        <th>Fecha de cambio</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contrato['position_changes'] as $cambio)
                        <tr>
                            <td>{{ $cambio['from_position'] ?? '—' }}</td>
                            <td>{{ $cambio['to_position'] ?? '—' }}</td>
                            <td>{{ $cambio['change_date'] ?? '—' }}</td>
                            <td style="text-align: left;">{{ $cambio['observations'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($i < $numContratos - 1)
            <div class="page-break"></div>
        @endif
    @endforeach

    <p class="text-justify" style="font-size: 11pt; margin-top: 20px;">
        Se expide la presente certificación a petición de
        <strong>{{ $addressed_to }}</strong>
        el {{ $fechaLarga }}.
    </p>

    {{-- Firma centrada --}}
    <div class="firma-block">
        @if (! empty($signature->signature_image))
            <img src="{{ $signature->signature_image }}" style="width: 160px; margin-bottom: -10px;">
        @else
            <br><br><br>
        @endif
        <p style="margin: 0; font-weight: bold; font-size: 11pt;">
            {{ strtoupper($signature->signer_name ?? '') }}
        </p>
        <p style="margin: 2px 0 0 0; font-size: 11pt;">
            {{ strtoupper($signature->signer_position ?? '') }}
        </p>
    </div>

</main>

{{-- Página final: texto legal --}}
<div class="page-break"></div>
@include('pdf.partials.certificado-legal')

</body>
</html>
