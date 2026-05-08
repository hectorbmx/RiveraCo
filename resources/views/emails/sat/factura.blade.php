<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura CFDI</title>
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">

    <div style="max-width:680px; margin:0 auto; padding:32px 16px;">

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden;">

            <div style="background:#0f172a; padding:24px 28px;">
                <h1 style="margin:0; color:#ffffff; font-size:22px;">
                    Factura CFDI
                </h1>
                <p style="margin:6px 0 0; color:#cbd5e1; font-size:14px;">
                    Rivera Construcciones
                </p>
            </div>

            <div style="padding:28px;">

                <p style="font-size:15px; margin:0 0 16px;">
                    Estimado cliente,
                </p>

                <p style="font-size:15px; line-height:1.6; margin:0 0 24px; color:#334155;">
                    Adjuntamos los archivos XML y PDF correspondientes a su factura CFDI.
                </p>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px; margin-bottom:24px;">

                    <table style="width:100%; border-collapse:collapse; font-size:14px;">
                        <tr>
                            <td style="padding:8px 0; color:#64748b;">Folio</td>
                            <td style="padding:8px 0; text-align:right; font-weight:bold;">
                                {{ $factura->serie }}-{{ $factura->folio }}
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:8px 0; color:#64748b;">UUID</td>
                            <td style="padding:8px 0; text-align:right; font-weight:bold; font-size:12px;">
                                {{ $factura->uuid }}
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:8px 0; color:#64748b;">Total</td>
                            <td style="padding:8px 0; text-align:right; font-weight:bold; color:#059669;">
                                ${{ number_format($factura->total, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:8px 0; color:#64748b;">Fecha</td>
                            <td style="padding:8px 0; text-align:right; font-weight:bold;">
                                {{ $factura->fecha_emision?->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    </table>

                </div>

                <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:14px; padding:14px 16px; color:#047857; font-size:14px;">
                    Los archivos fiscales se encuentran adjuntos a este correo.
                </div>

                <p style="margin:24px 0 0; font-size:14px; color:#334155;">
                    Gracias.
                </p>

            </div>

            <div style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:18px 28px; font-size:12px; color:#64748b;">
                Este correo fue generado automáticamente desde el sistema SIRICO.
            </div>

        </div>

    </div>

</body>
</html>