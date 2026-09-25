<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Semanal</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">

        {{-- HEADER --}}
        <div style="background: #1E5180; color: #fff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 22px; letter-spacing: 1px;">CABOSYNC</h1>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: #F28C28;">Sistema de Gestión de Asistencia</p>
        </div>

        {{-- BODY --}}
        <div style="padding: 24px; color: #333; font-size: 14px; line-height: 1.6;">
            <p>Hola,</p>

            <p>{{ $mensajePersonalizado }}</p>

            <div style="background: #f8f9fa; border-left: 4px solid #F28C28; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0; font-size: 13px;">
                    <strong>Empresa:</strong> {{ $empresaNombre }}<br>
                    <strong>Semana:</strong> {{ $semanaTexto }}
                </p>
            </div>

            <p style="font-size: 13px; color: #666;">
                <i>Encontrarás adjuntos:</i>
            </p>
            <ul style="font-size: 13px; color: #666;">
                <li>📄 Reporte en PDF (formato formal)</li>
                <li>📊 Reporte en Excel (con fórmulas)</li>
            </ul>

            <p style="font-size: 13px; color: #999; margin-top: 24px;">
                Este correo fue generado automáticamente por CaboSync. Por favor, no responder.
            </p>
        </div>

        {{-- FOOTER --}}
        <div style="background: #f4f6f9; padding: 16px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0;">CaboSync — ID SOFTWARE HOUSE</p>
            <p style="margin: 4px 0 0 0;">Cuautla, Morelos, México</p>
        </div>
    </div>
</body>
</html>