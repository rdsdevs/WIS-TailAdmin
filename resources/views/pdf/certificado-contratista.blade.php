<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Contratación - {{ $collaborator_snapshot['full_name'] }}</title>
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
            font-size: 12pt;
            line-height: 1.5;
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
        .contract-header {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .section-label {
            font-weight: bold;
            text-transform: uppercase;
        }
        .section-content {
            margin-bottom: 15px;
            text-align: justify;
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
        .justify { text-align: justify; }
    </style>
</head>
<body>
    @php
        $nombre = $collaborator_snapshot['full_name'];
        $tipoDoc = strtolower($collaborator_snapshot['document_type'] ?? 'cc');
        $numDoc = number_format((float)$collaborator_snapshot['document_number'], 0, ',', '.');
        $genero = $collaborator_snapshot['gender'] ?? 'M';
        $identificada = ($genero === 'F') ? 'identificada' : 'identificado';
        
        $cargoFirmante = strtolower($signature->signer_position ?? '');
        $laSuscrita = (str_contains($cargoFirmante, 'director') && !str_contains($cargoFirmante, 'directora')) 
            ? 'EL SUSCRITO' 
            : 'LA SUSCRITA';

        if (!function_exists('formatMoneyLocal')) {
            function formatMoneyLocal($val) { return '$' . number_format((float)$val, 0, ',', '.'); }
        }
        
        if (!function_exists('numeroALetrasLocal')) {
            function numeroALetrasLocal($valor) {
                $valor = intval($valor);
                if ($valor == 0) return 'CERO';
                $control = array(0 => "", 1 => "UNO", 2 => "DOS", 3 => "TRES", 4 => "CUATRO", 5 => "CINCO", 6 => "SEIS", 7 => "SIETE", 8 => "OCHO", 9 => "NUEVE", 10 => "DIEZ", 11 => "ONCE", 12 => "DOCE", 13 => "TRECE", 14 => "CATORCE", 15 => "QUINCE", 16 => "DIECISÉIS", 17 => "DIECISIETE", 18 => "DIECIOCHO", 19 => "DIECINUEVE", 20 => "VEINTE", 21 => "VEINTIUNO", 22 => "VEINTIDÓS", 23 => "VEINTITRÉS", 24 => "VEINTICUATRO", 25 => "VEINTICINCO", 26 => "VEINTISÉIS", 27 => "VEINTISIETE", 28 => "VEINTIOCHO", 29 => "VEINTINUEVE");
                $decenas = array(30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA", 70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA");
                $centenas = array(100 => "CIEN", 200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS", 500 => "QUINIENTOS", 600 => "SEISCIENTOS", 700 => "SETECIENTOS", 800 => "OCHOCIENTOS", 900 => "NOVECIENTOS");
                if ($valor <= 29) return $control[$valor];
                if ($valor < 100) { $d = intval($valor / 10) * 10; $u = $valor % 10; return $decenas[$d] . ($u > 0 ? " Y " . $control[$u] : ""); }
                if ($valor == 100) return "CIEN";
                if ($valor < 1000) { $c = intval($valor / 100) * 100; $r = $valor % 100; if ($c == 100) return "CIENTO " . numeroALetrasLocal($r); return $centenas[$c] . ($r > 0 ? " " . numeroALetrasLocal($r) : ""); }
                if ($valor < 1000000) { $m = intval($valor / 1000); $r = $valor % 1000; $str = ($m == 1 ? "MIL" : numeroALetrasLocal($m) . " MIL"); return $str . ($r > 0 ? " " . numeroALetrasLocal($r) : ""); }
                if ($valor < 1000000000000) { $m = intval($valor / 1000000); $r = $valor % 1000000; $str = ($m == 1 ? "UN MILLÓN" : numeroALetrasLocal($m) . " MILLONES"); return $str . ($r > 0 ? " " . numeroALetrasLocal($r) : ""); }
                return "VALOR DEMASIADO ALTO";
            }
        }
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
                <img src="{{ $footer_base64 }}" width="100%" style="margin-bottom: 10px;">
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
        identificada con {{ $tipoDoc }} N° {{ $numDoc }}, 
        cuenta con la siguiente información relacionada a {{ count($contracts_snapshot) > 1 ? 'sus contratos' : 'su contrato' }} de acuerdo con la base de datos de contratación.
    </div>

    @foreach($contracts_snapshot as $contrato)
        @php
            $valor = (float)($contrato['fees'] ?? $contrato['salary'] ?? 0);
        @endphp
        
        <div class="contract-header">
            CONTRATO DE {{ strtoupper($contrato['contract_type']) }} No. {{ $contrato['contract_code'] }}
        </div>

        @if(!empty($contrato['object']) && !empty($options_snapshot['show_object']))
            <div class="section-content">
                <span class="section-label">OBJETO:</span> 
                {!! strip_tags($contrato['object']) !!}
            </div>
        @endif

        <div class="section-content">
            <span class="section-label">PLAZO DE EJECUCIÓN:</span> 
            Por el periodo comprendido entre el {{ $contrato['start_date'] }} 
            y el {{ !empty($contrato['end_date']) ? $contrato['end_date'] : 'fecha indeterminada' }}.
        </div>

        @if($valor > 0 && !empty($options_snapshot['show_value']))
            <div class="section-content">
                <span class="section-label">VALOR DEL CONTRATO:</span> 
                {{ numeroALetrasLocal($valor) }} PESOS 
                ({{ formatMoneyLocal($valor) }}) M/cte.
            </div>
        @endif

        @if(!empty($contrato['obligations']) && !empty($options_snapshot['show_obligations']))
            <div class="section-content">
                <span class="section-label">OBLIGACIONES:</span> 
                {!! strip_tags($contrato['obligations']) !!}
            </div>
        @endif

        @if(!empty($contrato['extensions']) && !empty($options_snapshot['show_prorrogas']))
            <div class="section-content">
                <span class="section-label">PRÓRROGAS Y ADICIONES:</span>
                <ul style="padding-left: 20px; margin-top: 5px;">
                    @foreach($contrato['extensions'] as $ext)
                        <li>
                            Adición aprobada el {{ $ext['approval_date'] }}.
                            @if(!empty($ext['extension_months']) || !empty($ext['extension_days']))
                                Duración: {{ $ext['extension_months'] ?? 0 }} meses y {{ $ext['extension_days'] ?? 0 }} días.
                            @endif
                            @if(!empty($ext['extension_value']) && $ext['extension_value'] > 0)
                                Valor adicionado: {{ formatMoneyLocal($ext['extension_value']) }}.
                            @endif
                            @if(!empty($ext['new_end_date']))
                                Nueva fecha de finalización: {{ $ext['new_end_date'] }}.
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($contrato['early_termination_date']) && !empty($options_snapshot['show_early_termination']))
            <div class="section-content">
                <span class="section-label">TERMINACIÓN ANTICIPADA:</span> 
                El contrato fue terminado el {{ $contrato['early_termination_date'] }}
                @if(!empty($contrato['early_termination_reason']))
                    por el siguiente motivo: {{ $contrato['early_termination_reason'] }}.
                @endif
            </div>
        @endif

        <div class="section-content">
            <span class="section-label">ESTADO DEL CONTRATO:</span> {{ $contrato['status'] }}
        </div>
    @endforeach

    <div style="page-break-inside: avoid;">
        <div class="content-paragraph" style="margin-top: 25px;">
            De acuerdo con lo dispuesto por el numeral 3° del artículo 32 de la Ley 80 de 1993, en ningún caso estos contratos generaron relación laboral, ni prestaciones sociales por las obligaciones contratadas y se celebraron por el término estrictamente indispensable.
        </div>

        <div class="content-paragraph">
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
