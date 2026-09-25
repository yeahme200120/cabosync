<?php

namespace App\Services;

use App\Models\BitacoraAccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class BitacoraService
{
    /**
     * Registra una acción con contexto completo.
     */
    public static function registrar(
        string $accion,
        string $descripcion,
        array $opciones = []
    ): BitacoraAccion {
        $user = Auth::user();

        // ¿Es acción pública? (sin usuario autenticado)
        $esPublico = $user === null;

        // Detectar plataforma desde user agent
        $userAgent  = Request::userAgent() ?? '';
        $plataforma = self::detectarPlataforma($userAgent);

        // Geolocalización
        $latitud   = $opciones['geo']['lat'] ?? null;
        $longitud  = $opciones['geo']['lng'] ?? null;
        $precision = $opciones['geo']['precision'] ?? null;

        // Marcar si es imprecisa
        $precisionNum = is_numeric($precision) ? (float) $precision : null;
        $esImprecisa  = $precisionNum !== null && $precisionNum > 500;

        if ($esImprecisa) {
            $descripcion .= ' [Ubicación aproximada ±' . round($precisionNum) . 'm]';
        }

        // Empresa
        $empresaId = $opciones['empresa_id']
            ?? $user?->empresa_id
            ?? null;

        // Si es público y no se pasó descripción específica, agregar marca
        if ($esPublico && !isset($opciones['descripcion_prefijo'])) {
            $descripcion = '[PÚBLICO GENERAL] ' . $descripcion;
        }

        return BitacoraAccion::create([
            'usuario_id'      => $opciones['usuario_id'] ?? $user?->id ?? null,
            'empresa_id'      => $empresaId,
            'es_publico'      => $esPublico,
            'accion'          => $accion,
            'descripcion'     => $descripcion,
            'datos_antes'     => $opciones['datos_antes'] ?? null,
            'datos_despues'   => $opciones['datos_despues'] ?? null,
            'tipo_accion'     => $opciones['tipo_accion'] ?? 'otro',
            'modelo_afectado' => $opciones['modelo_afectado'] ?? null,
            'modelo_id'       => $opciones['modelo_id'] ?? null,
            'direccion_ip'    => Request::ip(),
            'latitud'         => $latitud,
            'longitud'        => $longitud,
            'precision_geo'   => $precision,
            'device_id'       => $opciones['device_id'] ?? null,
            'user_agent'      => substr($userAgent, 0, 1000),
            'plataforma'      => $plataforma,
            'navegador'       => substr($userAgent, 0, 1000),
            'created_at'      => now(),
        ]);
    }

    /**
     * Registra un INSERT.
     */
    public static function insertar(
        string $accion,
        string $descripcion,
        string $modeloAfectado,
        int $modeloId,
        array $datosDespues,
        array $opciones = []
    ): BitacoraAccion {
        return self::registrar($accion, $descripcion, array_merge([
            'tipo_accion'     => 'insert',
            'modelo_afectado' => $modeloAfectado,
            'modelo_id'       => $modeloId,
            'datos_despues'   => $datosDespues,
        ], $opciones));
    }

    /**
     * Registra un UPDATE (con diff). Devuelve null si no hay cambios.
     */
    public static function actualizar(
        string $accion,
        string $descripcion,
        string $modeloAfectado,
        int $modeloId,
        array $datosAntes,
        array $datosDespues,
        array $opciones = []
    ): ?BitacoraAccion {
        // Filtrar solo los campos que cambiaron
        $antes   = [];
        $despues = [];

        foreach ($datosDespues as $campo => $valorNuevo) {
            $valorViejo = $datosAntes[$campo] ?? null;

            if ($valorViejo != $valorNuevo) {
                $antes[$campo]   = $valorViejo;
                $despues[$campo] = $valorNuevo;
            }
        }

        // Si no hay cambios, no registrar
        if (empty($despues)) {
            return null;
        }

        return self::registrar($accion, $descripcion, array_merge([
            'tipo_accion'     => 'update',
            'modelo_afectado' => $modeloAfectado,
            'modelo_id'       => $modeloId,
            'datos_antes'     => $antes,
            'datos_despues'   => $despues,
        ], $opciones));
    }

    /**
     * Registra un DELETE.
     */
    public static function eliminar(
        string $accion,
        string $descripcion,
        string $modeloAfectado,
        int $modeloId,
        array $datosAntes,
        array $opciones = []
    ): BitacoraAccion {
        return self::registrar($accion, $descripcion, array_merge([
            'tipo_accion'     => 'delete',
            'modelo_afectado' => $modeloAfectado,
            'modelo_id'       => $modeloId,
            'datos_antes'     => $datosAntes,
        ], $opciones));
    }

    /**
     * Registra login/logout.
     */
    public static function login(string $descripcion, array $opciones = []): BitacoraAccion
    {
        return self::registrar('auth.login', $descripcion, array_merge([
            'tipo_accion' => 'login',
        ], $opciones));
    }

    public static function logout(string $descripcion, array $opciones = []): BitacoraAccion
    {
        return self::registrar('auth.logout', $descripcion, array_merge([
            'tipo_accion' => 'logout',
        ], $opciones));
    }

    /**
     * Detecta la plataforma desde el user agent.
     */
    protected static function detectarPlataforma(string $userAgent): ?string
    {
        if (empty($userAgent)) return null;

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'windows')) return 'Windows';
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) return 'macOS';
        if (str_contains($ua, 'linux')) return 'Linux';
        if (str_contains($ua, 'android')) return 'Android';
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) return 'iOS';

        return 'Desconocida';
    }
}
