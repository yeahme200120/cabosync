<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\Consentimiento;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalController extends Controller
{
    // =========================================================
    // VISTAS PÚBLICAS
    // =========================================================
    public function terminos()
    {
        $version = ConfiguracionSistema::obtener('legal.terminos.version', '1.0');
        $titulo  = ConfiguracionSistema::obtener('legal.terminos.titulo', 'Términos y Condiciones');
        $texto   = ConfiguracionSistema::obtener('legal.terminos.texto', '<p>Sin contenido.</p>');

        return view('legal.publico', compact('version', 'titulo', 'texto'))
            ->with('tipo', 'terminos');
    }

    public function aviso()
    {
        $version = ConfiguracionSistema::obtener('legal.aviso_privacidad.version', '1.0');
        $titulo  = ConfiguracionSistema::obtener('legal.aviso_privacidad.titulo', 'Aviso de Privacidad');
        $texto   = ConfiguracionSistema::obtener('legal.aviso_privacidad.texto', '<p>Sin contenido.</p>');

        return view('legal.publico', compact('version', 'titulo', 'texto'))
            ->with('tipo', 'aviso');
    }

    // =========================================================
    // ACEPTAR CONSENTIMIENTO
    // =========================================================
    public function aceptar(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'No autenticado'], 401);
        }

        $versionTerminos = ConfiguracionSistema::obtener('legal.terminos.version', '1.0');
        $versionAviso    = ConfiguracionSistema::obtener('legal.aviso_privacidad.version', '1.0');

        $ip = $request->ip();
        $ua = $request->userAgent();

        // Registrar consentimiento de términos
        Consentimiento::create([
            'usuario_id'  => $user->id,
            'tipo'        => 'terminos',
            'version'     => $versionTerminos,
            'aceptado'    => true,
            'ip'          => $ip,
            'user_agent'  => $ua,
            'aceptado_en' => now(),
        ]);

        // Registrar consentimiento de privacidad
        Consentimiento::create([
            'usuario_id'  => $user->id,
            'tipo'        => 'privacidad',
            'version'     => $versionAviso,
            'aceptado'    => true,
            'ip'          => $ip,
            'user_agent'  => $ua,
            'aceptado_en' => now(),
        ]);

        // Registrar consentimiento de tratamiento externo (migración)
        Consentimiento::create([
            'usuario_id'  => $user->id,
            'tipo'        => 'tratamiento_externo',
            'version'     => $versionTerminos,
            'aceptado'    => true,
            'ip'          => $ip,
            'user_agent'  => $ua,
            'aceptado_en' => now(),
        ]);

        // Bitácora
        BitacoraService::insertar(
            'legal.aceptar',
            "Usuario {$user->nombre} aceptó términos y aviso de privacidad v{$versionTerminos}",
            'App\Models\User',
            $user->id,
            [
                'terminos_version' => $versionTerminos,
                'aviso_version'    => $versionAviso,
            ],
            [
                'empresa_id' => $user->empresa_id,
                'geo'        => [
                    'lat'       => $request->header('X-Geo-Lat'),
                    'lng'       => $request->header('X-Geo-Lng'),
                    'precision' => $request->header('X-Geo-Precision'),
                ],
                'device_id'  => $request->header('X-Device-Id'),
            ]
        );

        return response()->json(['success' => true]);
    }

    // =========================================================
    // MIS CONSENTIMIENTOS
    // =========================================================
    public function misConsentimientos()
    {
        $user = Auth::user();

        $consentimientos = Consentimiento::where('usuario_id', $user->id)
            ->orderByDesc('aceptado_en')
            ->get();

        $versionActualTerminos = ConfiguracionSistema::obtener('legal.terminos.version', '1.0');
        $versionActualAviso    = ConfiguracionSistema::obtener('legal.aviso_privacidad.version', '1.0');

        return view('legal.mis_consentimientos', compact(
            'consentimientos',
            'versionActualTerminos',
            'versionActualAviso'
        ));
    }

    // =========================================================
    // ADMIN — Gestión de textos
    // =========================================================
    public function admin()
    {
        $user = Auth::user();
        if (!$user->esAdministrador()) {
            abort(403, 'Solo el Administrador puede gestionar textos legales.');
        }

        $datos = [
            'terminos_version' => ConfiguracionSistema::obtener('legal.terminos.version', '1.0'),
            'terminos_titulo'  => ConfiguracionSistema::obtener('legal.terminos.titulo', 'Términos y Condiciones'),
            'terminos_texto'   => ConfiguracionSistema::obtener('legal.terminos.texto', ''),
            'aviso_version'    => ConfiguracionSistema::obtener('legal.aviso_privacidad.version', '1.0'),
            'aviso_titulo'     => ConfiguracionSistema::obtener('legal.aviso_privacidad.titulo', 'Aviso de Privacidad'),
            'aviso_texto'      => ConfiguracionSistema::obtener('legal.aviso_privacidad.texto', ''),
        ];

        return view('legal.admin', compact('datos'));
    }

    public function guardar(Request $request)
    {
        $user = Auth::user();
        if (!$user->esAdministrador()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'terminos_version' => 'required|string|max:20',
            'terminos_titulo'  => 'required|string|max:255',
            'terminos_texto'   => 'required|string',
            'aviso_version'    => 'required|string|max:20',
            'aviso_titulo'     => 'required|string|max:255',
            'aviso_texto'      => 'required|string',
        ]);

        ConfiguracionSistema::establecer('legal.terminos.version', $data['terminos_version']);
        ConfiguracionSistema::establecer('legal.terminos.titulo', $data['terminos_titulo']);
        ConfiguracionSistema::establecer('legal.terminos.texto', $data['terminos_texto']);
        ConfiguracionSistema::establecer('legal.aviso_privacidad.version', $data['aviso_version']);
        ConfiguracionSistema::establecer('legal.aviso_privacidad.titulo', $data['aviso_titulo']);
        ConfiguracionSistema::establecer('legal.aviso_privacidad.texto', $data['aviso_texto']);

        BitacoraService::insertar(
            'legal.actualizar_textos',
            "Admin {$user->nombre} actualizó textos legales a v{$data['terminos_version']}",
            'App\Models\ConfiguracionSistema',
            0,
            [],
            ['empresa_id' => $user->empresa_id]
        );

        return response()->json(['success' => true, 'mensaje' => 'Textos actualizados']);
    }
}