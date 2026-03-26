<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado Laboral - {{ $collaborator_snapshot['full_name'] }}</title>
    <style>
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 60mm;
            margin-bottom: 45mm;
            margin-left: 10mm;
            margin-right: 15mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }
        
        .main-cert-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 30px;
            text-transform: uppercase;
            font-size: 12pt;
        }

        .content-paragraph {
            text-align: justify;
            margin-bottom: 15px;
        }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5em;
            font-size: 9.5pt;
        }
        table.data-table th {
            background-color: #f3f4f6;
            border: 1px solid #999;
            padding: 6px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.data-table td {
            border: 1px solid #999;
            padding: 6px;
            text-align: center;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 15px;
            font-size: 10pt;
        }

        .signature-section {
            margin-top: 40px;
            text-align: center;
        }
        .signature-image {
            width: 175px;
            margin-bottom: 5px;
        }
        .signer-name {
            font-weight: bold;
            text-transform: uppercase;
            display: block;
            font-size: 11pt;
            margin: 0;
        }
        .signer-title {
            text-transform: uppercase;
            display: block;
            font-size: 10.5pt;
            margin: 0;
        }

        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        table.header-table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
    @php
        $nombre = $collaborator_snapshot['full_name'] ?? '';
        $tipoDoc = strtolower($collaborator_snapshot['document_type'] ?? 'cc');
        $numDoc = number_format((float)($collaborator_snapshot['document_number'] ?? 0), 0, ',', '.');
        $genero = $collaborator_snapshot['gender'] ?? 'M';
        $identificado = ($genero === 'F') ? 'identificada' : 'identificado';
        
        $cargoFirmante = strtolower($signature->signer_position ?? '');
        $laSuscrita = (str_contains($cargoFirmante, 'director') && !str_contains($cargoFirmante, 'directora')) 
            ? 'EL SUSCRITO' 
            : 'LA SUSCRITA';

        $showSalary = !empty($options_snapshot['show_salary']);
        $showPositionHistory = !empty($options_snapshot['show_position_history']);
    @endphp

    <htmlpageheader name="page-header">
        <div style="font-family: arial; font-size: 12pt; margin-bottom: 10px;">
            <table width="100%" style="border-collapse: collapse; vertical-align: top;">
                <tr>
                    <td width="60%" style="text-align: left; vertical-align: top;">
                        <div style="margin-bottom: 15px;">
                            <strong>ASOCIACIÓN COLOMBIANA DE UNIVERSIDADES</strong>
                        </div>
                        <img src="{{ $qr_base64 }}" style="height: 137px; margin-top: 10px;">
                    </td>
                    <td width="40%" style="text-align: right; vertical-align: top;">
                        @if($logo_base64)
                            <img src="{{ $logo_base64 }}" style="height: 90px; margin-bottom: 10px;"><br>
                        @endif
                        <span style="font-size: 10pt;">Página {PAGENO} de {nbpg}</span>
                    </td>
                </tr>
            </table>
        </div>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <div style="text-align: center;">
            @if($footer_base64)
                <img src="{{ $footer_base64 }}" style="width: 100%; margin-bottom: 10px;">
            @endif
        </div>
    </htmlpagefooter>

    <div class="main-cert-title">
        {{ $laSuscrita }} {{ strtoupper($signature->signer_position ?? '') }}<br>
        DE LA ASOCIACIÓN COLOMBIANA DE UNIVERSIDADES -ASCÚN-<br>
        NIT. 860.025.721-0<br>
        CERTIFICA:
    </div>

    <div class="content-paragraph">
        Que <span class="bold uppercase">{{ $nombre }}</span>, 
        {{ $identificado }} con {{ $tipoDoc }} N° {{ $numDoc }}, 
        cuenta con la siguiente información relacionada a {{ count($contracts_snapshot) > 1 ? 'sus contratos laborales' : 'su contrato laboral' }} de acuerdo con la base de datos de su historia laboral.
    </div>

    @foreach ($contracts_snapshot as $contrato)
        <table class="data-table">
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

        @if ($showPositionHistory && ! empty($contrato['position_changes']))
            <div class="section-title">HISTORIAL DE CARGOS:</div>
            <table class="data-table">
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

        @if(!$loop->last)
            <pagebreak />
        @endif
    @endforeach

    <div style="page-break-inside: avoid;">
        <div class="content-paragraph" style="margin-top: 25px;">
            Se expide la presente certificación a petición de <strong>{{ $addressed_to }}</strong> el {{ $date }}.
        </div>

        <div class="signature-section">
            @if($signature_base64)
                <img src="{{ $signature_base64 }}" class="signature-image"><br>
            @else
                <br><br><br>
            @endif
            <div class="signer-name">{{ strtoupper($signature->signer_name ?? '') }}</div>
            <div class="signer-title">{{ strtoupper($signature->signer_position ?? '') }}</div>
        </div>
    </div>

    <pagebreak />

    <div class="content-paragraph">
        <div class="bold uppercase" style="margin-bottom: 15px;">Validación de Documento</div>
        <p>Este certificado requiere para su plena validez y confiabilidad que la información aquí consignada sea verificada y convalidada.</p>
        <ol type="a" style="padding-left: 20px;">
            <li>Por medio del código QR presente en este certificado.</li>
            <li>Por el link: <a href="{{ route('certificados.verificar', $certificate->verification_code) }}">{{ route('certificados.verificar', $certificate->verification_code) }}</a></li>
            <li>Con Gestión documental a través de la <strong>línea 6231580 Ext.:603</strong> o a través del correo electrónico <strong>gestiondocumental@ascun.org.co</strong>; Si la certificación anterior no es refrendada a través de alguna de las formas mencionadas, la misma solo tendrá el valor probatorio que las partes le den, y será equiparada para todos los efectos legales a una prueba sumaria. La Asociación Colombiana de Universidades no asume ningún tipo de responsabilidad por el contenido y/o por la firma consignada en este tipo de documentos, hasta tanto no sea validado o confirmado por alguno de los medios ya mencionados.</li>
        </ol>

        <div style="text-align: justify; margin-top: 10px;">
            <strong>APLICACIÓN DE LAS NORMAS VIGENTES SOBRE COMERCIO ELECTRÓNICO:</strong> Todas las comunicaciones que se expresen vía Mensaje de Datos (Internet, Correo Electrónico, Teléfono), tendrán el mismo alcance, efecto y valor probatorio que las normas vigentes y aplicables sobre Comercio Electrónico consignadas en la Ley 527 de 1999, Ley 588 de 2000, Decreto Reglamentario 1747 de 2000, y la Resolución 26930 de 2000 y demás que las remplacen o modifiquen. Este documento se rige bajo La política de tratamiento de datos, Ley 1581 de 2012 y el Decreto 377 de 2013, entendiendo que, por solicitud propia, se tiene el consentimiento de la persona de presentar la información descrita es este documento.
        </div>
    </div>
    
    <div style="text-align: center; color: red; font-size: 9pt; margin-top: 50px;">
        Documento generado electrónicamente por el sistema de certificaciones WIS-ASCUN<br>
        Código de validación: {{ $certificate->verification_code }}
    </div>
</body>
</html>
