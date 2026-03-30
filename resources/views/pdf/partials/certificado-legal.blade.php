{{-- Página de Validación: Texto legal y código de verificación --}}
<div style="font-family: Arial, sans-serif; font-size: 11.5pt; text-align: justify; line-height: 1.4; color: #000;">
    <p>
        Este certificado requiere para su plena validez y confiabilidad que la información aquí consignada sea
        verificada y convalidada:
    </p>
    <p style="margin-left: 10px;">
        a). Por medio del código QR presente en este certificado.<br>
        b). Por el enlace:
        <span style="color: #d32f2f;">{{ route('certificados.verificar', $certificate->verification_code) }}</span><br>
        c). Con Gestión documental a través de la línea 6231580 Ext.:603 o a través del correo electrónico
        <span style="color: #d32f2f;">gestiondocumental@ascun.org.co</span>.
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

    <div style="margin-top: 60px; text-align: center;">
        <p style="font-size: 10.5pt; color: #d32f2f; line-height: 1.5;">
            Documento generado electrónicamente por el sistema de certificaciones <strong>WIS-ASCUN</strong><br>
            Código de validación: <strong>{{ $certificate->verification_code }}</strong>
        </p>
    </div>
</div>
