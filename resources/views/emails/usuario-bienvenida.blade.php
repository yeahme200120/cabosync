<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $esReset ? 'Contraseña restablecida' : 'Bienvenido a CaboSync' }}</title>
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
            <p>Hola <strong>{{ $usuario->nombre }}</strong>,</p>

            @if($esReset)
                <p>Tu contraseña ha sido <strong>restablecida</strong> por <strong>{{ $creadoPor }}</strong>.</p>
            @else
                <p>Tu cuenta en <strong>CaboSync</strong> ha sido creada por <strong>{{ $creadoPor }}</strong>.</p>
            @endif

            <div style="background: #f8f9fa; border-left: 4px solid #F28C28; padding: 16px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0 0 8px 0; font-size: 13px;"><strong>Credenciales de acceso:</strong></p>
                <p style="margin: 4px 0; font-size: 13px;"><strong>Usuario:</strong> {{ $usuario->email }}</p>
                <p style="margin: 4px 0; font-size: 13px;"><strong>Contraseña temporal:</strong>
                    <code style="background: #fff; padding: 3px 8px; border-radius: 4px; font-family: 'Courier New', monospace; color: #1E5180; font-weight: bold;">
                        {{ $passwordTemporal }}
                    </code>
                </p>
            </div>

            <div style="background: #fff3cd; color: #856404; padding: 12px 16px; border-radius: 6px; margin: 20px 0; font-size: 13px;">
                <strong>⚠️ Importante:</strong> Por seguridad, cambia tu contraseña al iniciar sesión.
            </div>

            <div style="text-align: center; margin: 24px 0;">
                <a href="{{ $urlLogin }}"
                   style="background: #1E5180; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block;">
                    Iniciar Sesión
                </a>
            </div>

            <p style="font-size: 12px; color: #999; margin-top: 24px;">
                Este correo fue generado automáticamente por CaboSync. Si no esperabas este mensaje, contacta a ID SOFTWARE HOUSE.
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