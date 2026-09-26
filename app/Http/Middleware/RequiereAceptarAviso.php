<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracionSistema;
use App\Models\Consentimiento;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequiereAceptarAviso
{
    public function handle(Request $request, Closure $next): Response
    {
        // ⚠️ EXCEPCIONES: permitir estas rutas para que el usuario pueda aceptar
        if ($request->is('legal/aceptar') || $request->is('legal-app/aceptar') || $request->is('logout')) {
            return $next($request);
        }

        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Admin puede saltar el bloqueo (para que pueda editar los textos)
        if ($user->esAdministrador()) {
            return $next($request);
        }

        // Versiones actuales
        $versionTerminos = ConfiguracionSistema::obtener('legal.terminos.version', '1.0');
        $versionAviso    = ConfiguracionSistema::obtener('legal.aviso_privacidad.version', '1.0');

        // ¿Ya aceptó ambas versiones?
        $aceptoTerminos = Consentimiento::where('usuario_id', $user->id)
            ->where('tipo', 'terminos')
            ->where('version', $versionTerminos)
            ->where('aceptado', true)
            ->exists();

        $aceptoAviso = Consentimiento::where('usuario_id', $user->id)
            ->where('tipo', 'privacidad')
            ->where('version', $versionAviso)
            ->where('aceptado', true)
            ->exists();

        if ($aceptoTerminos && $aceptoAviso) {
            return $next($request);
        }

        // Si es una petición AJAX/JSON → responder 409 para que JS muestre el modal
        if ($request->expectsJson()) {
            return response()->json([
                'success'              => false,
                'requiere_consentimiento' => true,
                'error'                => 'Debes aceptar los Términos y el Aviso de Privacidad.',
            ], 409);
        }

        // Si es HTML → compartir variable para que el layout muestre el modal
        view()->share('requiereConsentimiento', true);
        view()->share('versionTerminosActual', $versionTerminos);
        view()->share('versionAvisoActual', $versionAviso);

        return $next($request);
    }
}
