@php
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $diasSemana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $fechaLarga = $diasSemana[$issued_at->dayOfWeek] . ' ' . $issued_at->day . ' de ' . $meses[$issued_at->month - 1] . ' de ' . $issued_at->year;

    $nombre         = $collaborator_snapshot['full_name'] ?? '';
    $tipoDoc        = $collaborator_snapshot['document_type_name'] ?? 'Cédula de Ciudadanía';
    $numDoc         = $collaborator_snapshot['document_number'] ?? '';
    $genero         = $collaborator_snapshot['gender'] ?? 'M';
    $identificado   = $genero === 'F' ? 'identificada' : 'identificado';
    $numContratos   = count($contracts_snapshot);

    $showSalary          = ! empty($options_snapshot['show_salary']);
    $showPositionHistory = ! empty($options_snapshot['show_position_history']);

    // Cargo del firmante
    $signerGender   = strtolower($signature->signer_position ?? '');
    $laSuscrita     = str_contains($signerGender, 'director') ? 'EL SUSCRITO' : 'LA SUSCRITA';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado Laboral</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
            font-size: 10pt;
        }
        th {
            background-color: #e8e8e8;
            border: 1px solid #999;
            padding: 4px 6px;
            text-align: center;
            font-weight: bold;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            text-align: center;
        }
        .bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .section-title {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 8px;
            margin-bottom: 4px;
        }
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
    Certificado laboral — {{ strtoupper($institution_name) }} — Código: {{ $certificate->verification_code }}
</footer>

{{-- Contenido principal --}}
<main>
    <p class="bold text-center" style="margin-top: 10px; line-height: 1.6;">
        {{ $laSuscrita }} {{ strtoupper($signature->signer_position ?? '') }}<br>
        DE {{ strtoupper($institution_name) }}<br>
        CERTIFICA:
    </p>

    <p class="text-justify">
        Que <strong>{{ strtoupper($nombre) }}</strong>, {{ $identificado }}(a) con {{ $tipoDoc }}
        N° {{ number_format((float) $numDoc, 0, ',', '.') }}, cuenta con la siguiente información
        relacionada a {{ $numContratos > 1 ? 'sus contratos laborales' : 'su contrato laboral' }}
        de acuerdo a la base de datos de su historia laboral.
    </p>

    @foreach ($contracts_snapshot as $i => $contrato)
        {{-- Tabla de contrato --}}
        <table>
            <thead>
                <tr>
                    <th>Tipo de Contrato</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Terminación</th>
                    <th>Estado</th>
                    @if ($showSalary)
                        <th>Cargo</th>
                        <th>Último salario mensual</th>
                    @else
                        <th style="width: 40%;">Cargo</th>
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
                            <td>{{ $cambio['observations'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($i < $numContratos - 1)
            <div class="page-break"></div>
        @endif
    @endforeach

    <p class="text-justify" style="margin-top: 20px;">
        La presente certificación está dirigida a <strong>{{ $addressed_to }}</strong>, y se expide
        de forma automática a través del aplicativo <strong>WIS</strong> — Web Information System,
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
