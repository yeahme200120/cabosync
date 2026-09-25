<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\AsistenciaFinal;
use App\Models\BitacoraAccion;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Justificacion;
use App\Models\Obra;
use App\Models\Rol;
use App\Services\BitacoraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AsistenciaController extends Controller
{
    /**
     * Cantidad de registros por página en el pase de lista.
     */
    protected const POR_PAGINA = 25;

    public function __construct()
    {
        $this->middleware('auth');
    }

    // =========================================================
    // VISTA PRINCIPAL
    // =========================================================

    public function index(Request $request)
    {
        $user = Auth::user();

        $empresasQuery = Empresa::query();
        $obrasQuery   = Obra::query();

        if ($user->esContratista()) {
            $empresasQuery->where('id', $user->empresa_id);
            $obrasQuery->where('empresa_id', $user->empresa_id);
        }

        $empresas        = $empresasQuery->where('estatus', 'activo')->orderBy('nombre')->get();
        $obras           = $obrasQuery->where('estatus', 'activa')->orderBy('nombre')->get();
        $rolesOperativos = Rol::operativo()->orderBy('nombre')->get();

        // Semana actual desde el input type="week" (formato: 2026-W39)
        $weekInput    = $request->input('week', now()->format('o-\WW'));
        $semanaActual = $this->calcularSemanaDesdeWeek($weekInput);

        // Permiso para capturar horas extras (para deshabilitar la columna en masa)
        $puedeCapturarHorasExtra = $this->usuarioPuedeCapturarHorasExtra($user);

        return view('asistencia.pase-lista', compact(
            'empresas',
            'obras',
            'rolesOperativos',
            'semanaActual',
            'weekInput',
            'puedeCapturarHorasExtra'
        ));
    }

    // =========================================================
    // OBTENER DATOS (JSON) — con paginación server-side
    // =========================================================

    public function obtenerDatos(Request $request)
    {
        $user = $request->user();

        $empresaId      = $request->input('empresa_id');
        $obraId         = $request->input('obra_id');
        $rolId          = $request->input('rol_id');
        $origenRegistro = $request->input('origen_registro', 'jefe_obra');
        $weekInput      = $request->input('week', now()->format('o-\WW'));
        $pagina         = max(1, (int) $request->input('page', 1));

        if ($user->esContratista()) {
            $empresaId = $user->empresa_id;
        }

        $semana       = $this->calcularSemanaDesdeWeek($weekInput);
        $fechasSemana = $semana['fechas'];
        $hoy          = now()->format('Y-m-d');

        // ¿La semana visible contiene el día de HOY?
        $hoyEnEstaSemana = in_array($hoy, array_keys($fechasSemana), true);

        // ¿Puede el usuario editar HOY? (regla por rol)
        $puedeEditarHoy = $this->validarPermisoEdicionFecha($user, $hoy)['ok'];

        $empleadosQuery = Empleado::query()
            ->with(['empresa', 'obra', 'rol'])
            ->where('estatus', 'activo');

        if ($empresaId) $empleadosQuery->where('empresa_id', $empresaId);
        if ($obraId)    $empleadosQuery->where('obra_id', $obraId);
        if ($rolId)     $empleadosQuery->where('rol_id', $rolId);

        $empleadosQuery->orderBy('puesto_cargo', 'asc')->orderBy('nombre', 'asc');

        $empleadosPaginados = $empleadosQuery->paginate(self::POR_PAGINA, ['*'], 'page', $pagina);

        $empleadoIds = $empleadosPaginados->pluck('id')->toArray();

        $asistencias = Asistencia::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->where('origen_registro', $origenRegistro)
            ->get()
            ->keyBy(fn($item) => $item->empleado_id . '_' . $item->fecha->format('Y-m-d'));

        // Faltas INJUSTIFICADAS por empleado (toda la semana visible)
        $faltasInjustificadasPorEmpleado = Asistencia::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->where('origen_registro', $origenRegistro)
            ->where('estado', 'falta')
            ->where('es_justificada', false)
            ->select('empleado_id', DB::raw('COUNT(*) as total'))
            ->groupBy('empleado_id')
            ->pluck('total', 'empleado_id')
            ->toArray();

        // Asistencias finales bloqueadas (para saber qué días están conciliados)
        $finalesBloqueados = AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->where('bloqueado_edicion', true)
            ->get()
            ->keyBy(fn($f) => $f->empleado_id . '_' . $f->fecha->format('Y-m-d'));

        $empleadosData = [];

        foreach ($empleadosPaginados as $emp) {
            $asistenciasEmpleado = [];
            foreach (array_keys($fechasSemana) as $fecha) {
                $key        = $emp->id . '_' . $fecha;
                $asistencia = $asistencias->get($key);
                $finalKey   = $emp->id . '_' . $fecha;
                $bloqueadoDia = $finalesBloqueados->has($finalKey);

                $asistenciasEmpleado[$fecha] = [
                    'id'               => $asistencia?->id,
                    'estado'           => $asistencia?->estado,
                    'es_justificada'   => (bool) $asistencia?->es_justificada,
                    'justificacion_id' => $asistencia?->justificacion_id,
                    'evidencia_ruta'   => $asistencia?->evidencia_ruta,
                    'evidencia_url'    => $asistencia?->evidencia_ruta
                        ? asset('storage/' . $asistencia->evidencia_ruta)
                        : null,
                    'horas_extra'      => (float) ($asistencia?->horas_extra ?? 0),
                    'bloqueado'        => $bloqueadoDia,  // ⬅️ NUEVO: día bloqueado por conciliación
                ];
            }

            $faltasInjustificadas = (int) ($faltasInjustificadasPorEmpleado[$emp->id] ?? 0);

            // ¿Tiene falta injustificada HOY específicamente?
            $asistenciaHoy       = $asistenciasEmpleado[$hoy] ?? null;
            $faltaInjustificadaHoy = $asistenciaHoy
                && $asistenciaHoy['estado'] === 'falta'
                && !$asistenciaHoy['es_justificada'];

            $empleadosData[] = [
                'id'                       => $emp->id,
                'nombre_completo'          => $emp->nombre_completo,
                'puesto_cargo'             => $emp->puesto_cargo,
                'foto_url'                 => $emp->foto_url,
                'empresa'                  => $emp->empresa?->nombre,
                'obra'                     => $emp->obra?->nombre,
                'asistencias'              => $asistenciasEmpleado,
                'faltas_injustificadas'    => $faltasInjustificadas,
                'dias_penalizacion'        => $faltasInjustificadas * 2,
                'bloqueado_horas_extras'   => $faltasInjustificadas > 0,
                'falta_injustificada_hoy'  => $faltaInjustificadaHoy,
                'horas_extra_hoy'          => $asistenciaHoy['horas_extra'] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'semana'  => [
                'inicio' => $semana['inicio'],
                'fin'    => $semana['fin'],
                'week'   => $weekInput,
                'fechas' => $fechasSemana,
            ],
            'origen_registro'   => $origenRegistro,
            'empleados'         => $empleadosData,
            'paginacion'        => [
                'current_page' => $empleadosPaginados->currentPage(),
                'last_page'    => $empleadosPaginados->lastPage(),
                'per_page'     => $empleadosPaginados->perPage(),
                'total'        => $empleadosPaginados->total(),
                'from'         => $empleadosPaginados->firstItem(),
                'to'           => $empleadosPaginados->lastItem(),
            ],
            'total_empleados'   => $empleadosPaginados->total(),
            'hoy'               => $hoy,
            'hoy_en_semana'     => $hoyEnEstaSemana,
            'puede_editar_hoy'  => $puedeEditarHoy,
            'es_admin'          => $user->esAdministrador(),  // ⬅️ NUEVO
            'dia_actual_letra'  => $this->letraDiaSemana(now()),
        ]);
    }

    /**
     * Devuelve el estado completo de un empleado para una semana/origen dados.
     */
    public function estadoEmpleado(Request $request, Empleado $empleado)
    {
        $user = $request->user();

        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json([
                'success' => false,
                'error'   => 'No autorizado',
            ], 403);
        }

        $origenRegistro = $request->input('origen_registro', 'jefe_obra');
        $weekInput      = $request->input('week', now()->format('o-\WW'));

        $semana       = $this->calcularSemanaDesdeWeek($weekInput);
        $fechasSemana = $semana['fechas'];
        $hoy          = now()->format('Y-m-d');

        // Asistencias del empleado en la semana
        $asistencias = Asistencia::where('empleado_id', $empleado->id)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->where('origen_registro', $origenRegistro)
            ->get()
            ->keyBy(fn($item) => $item->fecha->format('Y-m-d'));

        // Asistencias finales bloqueadas
        $finalesBloqueados = AsistenciaFinal::where('empleado_id', $empleado->id)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->where('bloqueado_edicion', true)
            ->get()
            ->keyBy(fn($f) => $f->fecha->format('Y-m-d'));

        $faltasInjustificadas = $asistencias
            ->filter(fn($a) => $a->estado === 'falta' && !$a->es_justificada)
            ->count();

        $asistenciasEmpleado = [];
        foreach (array_keys($fechasSemana) as $fecha) {
            $asistencia  = $asistencias->get($fecha);
            $bloqueadoDia = $finalesBloqueados->has($fecha);

            $asistenciasEmpleado[$fecha] = [
                'id'               => $asistencia?->id,
                'estado'           => $asistencia?->estado,
                'es_justificada'   => (bool) $asistencia?->es_justificada,
                'justificacion_id' => $asistencia?->justificacion_id,
                'evidencia_ruta'   => $asistencia?->evidencia_ruta,
                'evidencia_url'    => $asistencia?->evidencia_ruta
                    ? asset('storage/' . $asistencia->evidencia_ruta)
                    : null,
                'horas_extra'      => (float) ($asistencia?->horas_extra ?? 0),
                'bloqueado'        => $bloqueadoDia,
            ];
        }

        $asistenciaHoy         = $asistenciasEmpleado[$hoy] ?? null;
        $faltaInjustificadaHoy = $asistenciaHoy
            && $asistenciaHoy['estado'] === 'falta'
            && !$asistenciaHoy['es_justificada'];

        $hoyEnEstaSemana = in_array($hoy, array_keys($fechasSemana), true);
        $puedeEditarHoy  = $this->validarPermisoEdicionFecha($user, $hoy)['ok'];

        $empleado->load(['empresa', 'obra', 'rol']);

        return response()->json([
            'success' => true,
            'empleado' => [
                'id'                       => $empleado->id,
                'nombre_completo'          => $empleado->nombre_completo,
                'puesto_cargo'             => $empleado->puesto_cargo,
                'foto_url'                 => $empleado->foto_url,
                'empresa'                  => $empleado->empresa?->nombre,
                'obra'                     => $empleado->obra?->nombre,
                'asistencias'              => $asistenciasEmpleado,
                'faltas_injustificadas'    => $faltasInjustificadas,
                'dias_penalizacion'        => $faltasInjustificadas * 2,
                'bloqueado_horas_extras'   => $faltasInjustificadas > 0,
                'falta_injustificada_hoy'  => $faltaInjustificadaHoy,
                'horas_extra_hoy'          => $asistenciaHoy['horas_extra'] ?? 0,
            ],
            'semana' => [
                'inicio' => $semana['inicio'],
                'fin'    => $semana['fin'],
                'week'   => $weekInput,
                'fechas' => $fechasSemana,
            ],
            'hoy'              => $hoy,
            'hoy_en_semana'    => $hoyEnEstaSemana,
            'puede_editar_hoy' => $puedeEditarHoy,
            'es_admin'         => $user->esAdministrador(),
        ]);
    }

    // =========================================================
    // GUARDAR ASISTENCIA (1-Click Presente/Falta)
    // =========================================================

    public function guardarPase(Request $request)
    {
        $user = $request->user();

        // =========================================================
        // 1. VALIDACIÓN (PRIMERO, para tener $data disponible)
        // =========================================================
        $data = $request->validate([
            'empleado_id'     => 'required|exists:empleados,id',
            'fecha'           => 'required|date',
            'estado'          => 'required|in:presente,falta',
            'origen_registro' => 'required|in:jefe_obra,seguridad',
        ]);

        $fechaObj = Carbon::parse($data['fecha']);

        // 2. No domingos
        if ($fechaObj->dayOfWeek === Carbon::SUNDAY) {
            return response()->json([
                'success' => false,
                'error'   => 'No se puede registrar asistencia en domingo',
            ], 422);
        }

        // 3. Aislamiento por contratista
        $empleado = Empleado::findOrFail($data['empleado_id']);
        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        // =========================================================
        // 4. BLOQUEO POR CONCILIACIÓN (solo el Admin puede editar días bloqueados)
        // =========================================================
        if (!$user->esAdministrador()) {
            $bloqueadoDia = AsistenciaFinal::where('empleado_id', $data['empleado_id'])
                ->where('fecha', $data['fecha'])
                ->where('bloqueado_edicion', true)
                ->exists();

            if ($bloqueadoDia) {
                return response()->json([
                    'success' => false,
                    'error'   => '🔒 Este día ya fue conciliado. Solo el Admin puede modificarlo.',
                ], 403);
            }
        }

        // 5. Reglas por rol
        $validacion = $this->validarPermisoEdicionFecha($user, $data['fecha']);
        if (!$validacion['ok']) {
            return response()->json([
                'success' => false,
                'error'   => $validacion['error'],
            ], 403);
        }

        // 6. Bloqueo por evidencia
        $asistenciaExistente = Asistencia::where('empleado_id', $data['empleado_id'])
            ->where('fecha', $data['fecha'])
            ->where('origen_registro', $data['origen_registro'])
            ->first();

        if ($asistenciaExistente && $asistenciaExistente->evidencia_ruta && !$user->esAdministrador()) {
            return response()->json([
                'success' => false,
                'error'   => '🔒 Esta falta tiene evidencia adjunta. Contacta al administrador para modificarla.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            // 7. Eliminar justificación previa si aplica
            $justificacionPreviaId = $asistenciaExistente?->justificacion_id;
            $evidenciaPrevia       = $asistenciaExistente?->evidencia_ruta;

            $debeEliminarJustificacion = $asistenciaExistente
                && ($asistenciaExistente->es_justificada
                    || $justificacionPreviaId
                    || $evidenciaPrevia)
                && $data['estado'] === 'presente';

            if ($debeEliminarJustificacion) {
                if ($evidenciaPrevia && Storage::disk('public')->exists($evidenciaPrevia)) {
                    Storage::disk('public')->delete($evidenciaPrevia);
                }
                if ($justificacionPreviaId) {
                    Justificacion::where('id', $justificacionPreviaId)->delete();
                }
            }

            // 8. Guardar/actualizar asistencia
            $asistencia = Asistencia::updateOrCreate(
                [
                    'empleado_id'     => $data['empleado_id'],
                    'fecha'           => $data['fecha'],
                    'origen_registro' => $data['origen_registro'],
                ],
                [
                    'estado'           => $data['estado'],
                    'usuario_id'       => $user->id,
                    'es_justificada'   => false,
                    'justificacion_id' => null,
                    'evidencia_ruta'   => null,
                    'horas_extra'      => $data['estado'] === 'falta'
                        ? 0
                        : ($asistenciaExistente?->horas_extra ?? 0),
                ]
            );

            // 9. Recalcular penalización semanal
            $semanaActual = $this->calcularSemanaDesdeWeek(now()->format('o-\WW'));
            $fechasSemana = array_keys($semanaActual['fechas']);

            $faltasInjustificadas = Asistencia::where('empleado_id', $data['empleado_id'])
                ->whereIn('fecha', $fechasSemana)
                ->where('origen_registro', $data['origen_registro'])
                ->where('estado', 'falta')
                ->where('es_justificada', false)
                ->count();

            // 10. Bitácora con servicio nuevo (incluye geo/device/datos)
            $datosAntes = $asistenciaExistente ? [
                'estado'         => $asistenciaExistente->estado,
                'es_justificada' => $asistenciaExistente->es_justificada,
                'horas_extra'    => $asistenciaExistente->horas_extra,
            ] : null;

            $datosDespues = [
                'estado'         => $asistencia->estado,
                'es_justificada' => $asistencia->es_justificada,
                'horas_extra'    => (float) $asistencia->horas_extra,
            ];

            $opciones = [
                'empresa_id' => $empleado->empresa_id,
                'geo'        => [
                    'lat'       => $request->header('X-Geo-Lat'),
                    'lng'       => $request->header('X-Geo-Lng'),
                    'precision' => $request->header('X-Geo-Precision'),
                ],
                'device_id'  => $request->header('X-Device-Id'),
            ];

            if ($asistenciaExistente) {
                BitacoraService::actualizar(
                    'asistencia.marcar_' . $data['estado'],
                    "Asistencia marcada como {$data['estado']} para {$empleado->nombre_completo} el {$data['fecha']}"
                        . ($debeEliminarJustificacion ? ' (justificación eliminada)' : ''),
                    'App\Models\Asistencia',
                    $asistencia->id,
                    $datosAntes,
                    $datosDespues,
                    $opciones
                );
            } else {
                BitacoraService::insertar(
                    'asistencia.marcar_' . $data['estado'],
                    "Asistencia marcada como {$data['estado']} para {$empleado->nombre_completo} el {$data['fecha']}",
                    'App\Models\Asistencia',
                    $asistencia->id,
                    $datosDespues,
                    $opciones
                );
            }

            DB::commit();

            return response()->json([
                'success'                 => true,
                'mensaje'                 => 'Asistencia guardada',
                'asistencia'              => $asistencia->fresh(),
                'justificacion_eliminada' => $debeEliminarJustificacion,
                'faltas_injustificadas'   => $faltasInjustificadas,
                'dias_penalizacion'       => $faltasInjustificadas * 2,
                'bloqueado_horas_extras'  => $faltasInjustificadas > 0,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================
    // GUARDAR HORAS EXTRA (solo día actual)
    // =========================================================

    public function guardarHorasExtra(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'empleado_id'     => 'required|exists:empleados,id',
            'horas'           => 'required|numeric|min:0|max:12',
            'origen_registro' => 'required|in:jefe_obra,seguridad',
        ]);

        $hoy = now()->format('Y-m-d');

        // No domingos
        if (now()->dayOfWeek === Carbon::SUNDAY) {
            return response()->json([
                'success' => false,
                'error'   => 'Hoy es domingo, no se pueden capturar horas extras.',
            ], 422);
        }

        // Aislamiento
        $empleado = Empleado::findOrFail($data['empleado_id']);
        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        // BLOQUEO por conciliación (solo Admin puede modificar)
        if (!$user->esAdministrador()) {
            $bloqueadoDia = AsistenciaFinal::where('empleado_id', $data['empleado_id'])
                ->where('fecha', $hoy)
                ->where('bloqueado_edicion', true)
                ->exists();

            if ($bloqueadoDia) {
                return response()->json([
                    'success' => false,
                    'error'   => '🔒 Este día ya fue conciliado. Solo el Admin puede modificar horas extra.',
                ], 403);
            }
        }

        // Regla por rol
        $validacion = $this->validarPermisoEdicionFecha($user, $hoy);
        if (!$validacion['ok']) {
            return response()->json([
                'success' => false,
                'error'   => $validacion['error'],
            ], 403);
        }

        // Bloqueo SOLO por falta INJUSTIFICADA en la semana
        $semanaActual = $this->calcularSemanaDesdeWeek(now()->format('o-\WW'));
        $fechasSemana = array_keys($semanaActual['fechas']);

        $faltasInjustificadas = Asistencia::where('empleado_id', $data['empleado_id'])
            ->whereIn('fecha', $fechasSemana)
            ->where('origen_registro', $data['origen_registro'])
            ->where('estado', 'falta')
            ->where('es_justificada', false)
            ->count();

        if ($faltasInjustificadas > 0) {
            return response()->json([
                'success' => false,
                'error'   => '🔒 Empleado bloqueado: tiene falta injustificada esta semana (1+1 = 2 días de penalización).',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $asistencia = Asistencia::firstOrNew([
                'empleado_id'     => $data['empleado_id'],
                'fecha'           => $hoy,
                'origen_registro' => $data['origen_registro'],
            ]);

            $horasAntes = $asistencia->exists ? (float) $asistencia->horas_extra : 0;

            if (!$asistencia->exists) {
                $asistencia->estado     = 'presente';
                $asistencia->usuario_id = $user->id;
            }

            if ($asistencia->estado === 'falta' && !$asistencia->es_justificada) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No se pueden capturar horas extra en un día con Falta Injustificada.',
                ], 422);
            }

            $asistencia->horas_extra = $data['horas'];
            $asistencia->usuario_id  = $user->id;
            $asistencia->save();

            BitacoraService::actualizar(
                'asistencia.horas_extra',
                "Horas extra ({$data['horas']}h) registradas para {$empleado->nombre_completo} el {$hoy}",
                'App\Models\Asistencia',
                $asistencia->id,
                ['horas_extra' => $horasAntes],
                ['horas_extra' => (float) $data['horas']],
                [
                    'empresa_id' => $empleado->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            return response()->json([
                'success'    => true,
                'mensaje'    => 'Horas extra guardadas',
                'horas'      => (float) $asistencia->horas_extra,
                'asistencia' => $asistencia,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => 'Error al guardar horas extra: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================
    // JUSTIFICAR FALTA
    // =========================================================

    public function justificarFalta(Request $request)
    {
        $user = $request->user();

        // =========================================================
        // VALIDACIÓN: Solo Admin, Contratista y Jefe de Obra pueden justificar
        // =========================================================
        if (!$user->esAdministrador() && !$user->esContratista() && !$user->esJefeObra()) {
            return response()->json([
                'success' => false,
                'error'   => 'No tienes permiso para justificar faltas.',
            ], 403);
        }

        $data = $request->validate([
            'empleado_id'     => 'required|exists:empleados,id',
            'fecha'           => 'required|date',
            'origen_registro' => 'required|in:jefe_obra,seguridad',
            'motivo'          => 'required|in:permiso,enfermedad,otro',
            'descripcion'     => 'nullable|string|max:500',
            'evidencia'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $empleado = Empleado::findOrFail($data['empleado_id']);
        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        // BLOQUEO por conciliación
        if (!$user->esAdministrador()) {
            $bloqueadoDia = AsistenciaFinal::where('empleado_id', $data['empleado_id'])
                ->where('fecha', $data['fecha'])
                ->where('bloqueado_edicion', true)
                ->exists();

            if ($bloqueadoDia) {
                return response()->json([
                    'success' => false,
                    'error'   => '🔒 Este día ya fue conciliado. Solo el Admin puede modificar justificaciones.',
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            $rutaEvidencia = null;
            if ($request->hasFile('evidencia')) {
                $rutaEvidencia = $request->file('evidencia')->store('justificaciones', 'public');
            }

            $justificacion = Justificacion::create([
                'empleado_id'            => $data['empleado_id'],
                'fecha'                  => $data['fecha'],
                'motivo'                 => $data['motivo'],
                'descripcion'            => $data['descripcion'] ?? null,
                'ruta_archivo'           => $rutaEvidencia,
                'subido_por_usuario_id'  => $user->id,
            ]);

            $asistencia = Asistencia::updateOrCreate(
                [
                    'empleado_id'     => $data['empleado_id'],
                    'fecha'           => $data['fecha'],
                    'origen_registro' => $data['origen_registro'],
                ],
                [
                    'estado'           => 'falta',
                    'es_justificada'   => true,
                    'justificacion_id' => $justificacion->id,
                    'evidencia_ruta'   => $rutaEvidencia,
                    'usuario_id'       => $user->id,
                ]
            );

            BitacoraService::insertar(
                'asistencia.justificar_falta',
                "Falta justificada ({$data['motivo']}) para {$empleado->nombre_completo} el {$data['fecha']}",
                'App\Models\Justificacion',
                $justificacion->id,
                [
                    'motivo'      => $data['motivo'],
                    'fecha'       => $data['fecha'],
                    'empleado_id' => $empleado->id,
                ],
                [
                    'empresa_id' => $empleado->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            return response()->json([
                'success'       => true,
                'mensaje'       => 'Falta justificada correctamente',
                'asistencia'    => $asistencia,
                'justificacion' => $justificacion,
                'evidencia_url' => $rutaEvidencia ? asset('storage/' . $rutaEvidencia) : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => 'Error al justificar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================
    // QUITAR JUSTIFICACIÓN
    // =========================================================

    public function quitarJustificacion(Request $request)
    {
        $user = $request->user();

        // Validación: solo Admin, Contratista y Jefe de Obra pueden quitar justificaciones
        if (!$user->esAdministrador() && !$user->esContratista() && !$user->esJefeObra()) {
            return response()->json([
                'success' => false,
                'error'   => 'No tienes permiso para eliminar justificaciones.',
            ], 403);
        }

        $data = $request->validate([
            'empleado_id'     => 'required|exists:empleados,id',
            'fecha'           => 'required|date',
            'origen_registro' => 'required|in:jefe_obra,seguridad',
        ]);

        $asistencia = Asistencia::where('empleado_id', $data['empleado_id'])
            ->where('fecha', $data['fecha'])
            ->where('origen_registro', $data['origen_registro'])
            ->first();

        if (!$asistencia) {
            return response()->json(['success' => false, 'error' => 'Asistencia no encontrada'], 404);
        }

        $empleado = $asistencia->empleado;
        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        // BLOQUEO por conciliación
        if (!$user->esAdministrador()) {
            $bloqueadoDia = AsistenciaFinal::where('empleado_id', $data['empleado_id'])
                ->where('fecha', $data['fecha'])
                ->where('bloqueado_edicion', true)
                ->exists();

            if ($bloqueadoDia) {
                return response()->json([
                    'success' => false,
                    'error'   => '🔒 Este día ya fue conciliado. Solo el Admin puede modificarlo.',
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            if ($asistencia->evidencia_ruta && Storage::disk('public')->exists($asistencia->evidencia_ruta)) {
                Storage::disk('public')->delete($asistencia->evidencia_ruta);
            }

            if ($asistencia->justificacion_id) {
                Justificacion::where('id', $asistencia->justificacion_id)->delete();
            }

            $asistencia->update([
                'es_justificada'   => false,
                'justificacion_id' => null,
                'evidencia_ruta'   => null,
            ]);

            BitacoraService::actualizar(
                'asistencia.quitar_justificacion',
                "Justificación eliminada para {$empleado->nombre_completo} el {$data['fecha']}",
                'App\Models\Asistencia',
                $asistencia->id,
                ['es_justificada' => true],
                ['es_justificada' => false],
                [
                    'empresa_id' => $empleado->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            return response()->json(['success' => true, 'mensaje' => 'Justificación eliminada']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================
    // HELPERS PROTEGIDOS
    // =========================================================

    /**
     * Reglas por rol para edición de fecha:
     * - Admin:       cualquier fecha, cualquier semana
     * - Contratista: cualquier día de la semana actual
     * - Jefe/Seg:    solo el día de HOY
     */
    protected function validarPermisoEdicionFecha($user, string $fecha): array
    {
        $fechaObj = Carbon::parse($fecha)->startOfDay();
        $hoy      = now()->startOfDay();

        if ($user->esAdministrador()) {
            return ['ok' => true];
        }

        if ($user->esContratista()) {
            $semanaActual = $this->calcularSemanaDesdeWeek(now()->format('o-\WW'));
            $fechasPermitidas = array_keys($semanaActual['fechas']);

            if (in_array($fecha, $fechasPermitidas, true)) {
                return ['ok' => true];
            }

            return ['ok' => false, 'error' => 'Solo puedes editar la semana actual.'];
        }

        if ($user->esJefeObra() || $user->esSeguridad() || $user->esMaestroObra() || $user->esTopografo()) {
            if ($fechaObj->equalTo($hoy)) {
                return ['ok' => true];
            }

            return ['ok' => false, 'error' => 'Solo puedes editar el día de HOY.'];
        }

        return ['ok' => false, 'error' => 'No tienes permisos para registrar asistencia.'];
    }

    protected function usuarioPuedeCapturarHorasExtra($user): bool
    {
        return $user->esAdministrador()
            || $user->esContratista()
            || $user->esJefeObra()
            || $user->esSeguridad()
            || $user->esMaestroObra()
            || $user->esTopografo();
    }

    protected function calcularSemanaDesdeWeek(string $weekInput): array
    {
        if (!preg_match('/^(\d{4})-W(\d{1,2})$/', $weekInput, $m)) {
            $weekInput = now()->format('o-\WW');
            preg_match('/^(\d{4})-W(\d{1,2})$/', $weekInput, $m);
        }

        $anio = (int) $m[1];
        $semana = (int) $m[2];

        $lunes = Carbon::now()->setISODate($anio, $semana)->startOfDay();

        $letras = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
        $fechas = [];

        $fechaActual = $lunes->copy();
        for ($i = 0; $i < 6; $i++) {
            $fechas[$fechaActual->format('Y-m-d')] = $letras[$fechaActual->dayOfWeek];
            $fechaActual->addDay();
        }

        $sabado = $lunes->copy()->addDays(5);

        return [
            'inicio' => $lunes->format('Y-m-d'),
            'fin'    => $sabado->format('Y-m-d'),
            'fechas' => $fechas,
        ];
    }

    protected function letraDiaSemana(Carbon $fecha): string
    {
        $letras = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
        return $letras[$fecha->dayOfWeek];
    }
}
