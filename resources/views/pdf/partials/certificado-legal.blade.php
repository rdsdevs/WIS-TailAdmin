{{-- Página 2: Texto legal y código de verificación --}}
<div style="font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; text-align: justify; line-height: 1.5;">
    <p>
        Este certificado requiere para su plena validez y confiabilidad que la información aquí consignada sea
        verificada y convalidada:
    </p>
    <p>
        a). Por medio del código QR presente en este certificado.<br>
        b). Por el enlace:
        <strong>{{ route('certificados.verificar', $certificate->verification_code) }}</strong><br>
        c). Con Gestión documental a través de la línea 6231580 Ext.:603 o a través del correo electrónico
        <strong>gestiondocumental@ascun.org.co</strong>.
    </p>
    <p>
        Si la certificación anterior no es refrendada a través de alguna de las formas mencionadas, la misma
        solo tendrá el valor probatorio que las partes le den, y será equiparada para todos los efectos legales
        a una prueba sumaria. La Asociación Colombiana de Universidades no asume ningún tipo de responsabilidad
        por el contenido y/o por la firma consignada en este tipo de documentos, hasta tanto no sea validado o
        confirmado por alguno de los medios ya mencionados.
    </p>
    <p>
        <strong>APLICACIÓN DE LAS NORMAS VIGENTES SOBRE COMERCIO ELECTRÓNICO:</strong>
        Todas las comunicaciones que se expresen vía Mensaje de Datos (Internet, Correo Electrónico, Teléfono),
        tendrán el mismo alcance, efecto y valor probatorio que las normas vigentes y aplicables sobre Comercio
        Electrónico consignadas en la Ley 527 de 1999, Ley 588 de 2000, Decreto Reglamentario 1747 de 2000, y
        la Resolución 26930 de 2000 y demás que las remplacen o modifiquen.
    </p>
    <p>
        Este documento se rige bajo la política de tratamiento de datos, Ley 1581 de 2012 y el Decreto 377 de
        2013, entendiendo que, por solicitud propia, se tiene el consentimiento de la persona de presentar la
        información descrita en este documento.
    </p>
    <div style="margin-top: 40px; text-align: center; color: #666;">
        <p style="font-size: 9pt;">
            Documento generado electrónicamente por el sistema de certificaciones <strong>WIS-ASCUN</strong><br>
            Código de validación: <strong>{{ $certificate->verification_code }}</strong>
        </p>
    </div>
</div>
