<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\AsistenciaFinal;
use App\Models\BitacoraAccion;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\HoraExtra;
use App\Models\Justificacion;
use App\Models\Obra;
use App\Models\User;
use App\Services\BitacoraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConciliacionController extends Controller
{
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

        if (!$this->puedeAccederConciliacion($user)) {
            abort(403, 'No tienes permisos para conciliar asistencia.');
        }

        $empresasQuery = Empresa::query();
        $obrasQuery   = Obra::query();

        if ($user->esContratista() || $user->esJefeObra()) {
            $empresasQuery->where('id', $user->empresa_id);
            $obrasQuery->where('empresa_id', $user->empresa_id);
        }

        $empresas = $empresasQuery->where('estatus', 'activo')->orderBy('nombre')->get();
        $obras    = $obrasQuery->where('estatus', 'activa')->orderBy('nombre')->get();

        $weekInput    = $request->input('week', now()->format('o-\WW'));
        $semanaActual = $this->calcularSemanaDesdeWeek($weekInput);

        $semanaBloqueada = $this->semanaEstaBloqueada($semanaActual, $user);

        return view('conciliacion.index', compact(
            'empresas',
            'obras',
            'weekInput',
            'semanaActual',
            'semanaBloqueada'
        ));
    }

    // =========================================================
    // OBTENER MATRIZ
    // =========================================================
    public function obtenerMatriz(Request $request)
    {
        $user = $request->user();

        if (!$this->puedeAccederConciliacion($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $empresaId = $request->input('empresa_id');
        $obraId    = $request->input('obra_id');
        $weekInput = $request->input('week', now()->format('o-\WW'));
        $pagina    = max(1, (int) $request->input('page', 1));

        if ($user->esContratista() || $user->esJefeObra()) {
            $empresaId = $user->empresa_id;
        }

        $semana       = $this->calcularSemanaDesdeWeek($weekInput);
        $fechasSemana = $semana['fechas'];

        $empleadosBaseQuery = Empleado::query()
            ->with(['empresa', 'obra'])
            ->where('estatus', 'activo');

        if ($empresaId) $empleadosBaseQuery->where('empresa_id', $empresaId);
        if ($obraId)    $empleadosBaseQuery->where('obra_id', $obraId);

        $empleadosBaseQuery->orderBy('puesto_cargo')->orderBy('nombre');

        $todosLosEmpleadosIds = (clone $empleadosBaseQuery)->pluck('id')->toArray();
        $totalEsperado = count($todosLosEmpleadosIds) * count($fechasSemana);

        $totalBloqueadosGlobal = 0;
        if (!empty($todosLosEmpleadosIds)) {
            $totalBloqueadosGlobal = AsistenciaFinal::whereIn('empleado_id', $todosLosEmpleadosIds)
                ->whereIn('fecha', array_keys($fechasSemana))
                ->where('bloqueado_edicion', true)
                ->count();
        }

        $modo = ($totalEsperado > 0 && $totalBloqueadosGlobal >= $totalEsperado)
            ? 'lectura'
            : 'edicion';

        $empleadosPaginados = $empleadosBaseQuery->paginate(self::POR_PAGINA, ['*'], 'page', $pagina);
        $empleadoIds = $empleadosPaginados->pluck('id')->toArray();

        $asistencias = Asistencia::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->get()
            ->keyBy(fn($a) => $a->empleado_id . '_' . $a->fecha->format('Y-m-d') . '_' . $a->origen_registro);

        $finales = AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->get()
            ->keyBy(fn($f) => $f->empleado_id . '_' . $f->fecha->format('Y-m-d'));

        $empleadosData = [];

        foreach ($empleadosPaginados as $emp) {
            $matriz = [];
            $discrepancias = 0;

            foreach (array_keys($fechasSemana) as $fecha) {
                $keyJefe = $emp->id . '_' . $fecha . '_jefe_obra';
                $keySeg  = $emp->id . '_' . $fecha . '_seguridad';
                $keyFin  = $emp->id . '_' . $fecha;

                $asistJefe = $asistencias->get($keyJefe);
                $asistSeg  = $asistencias->get($keySeg);
                $final     = $finales->get($keyFin);

                $hayDiscrepancia = false;

                if ($asistJefe && $asistSeg) {
                    if ($asistJefe->estado !== $asistSeg->estado) {
                        $hayDiscrepancia = true;
                    } elseif (
                        $asistJefe->estado === 'falta'
                        && $asistJefe->es_justificada !== $asistSeg->es_justificada
                    ) {
                        $hayDiscrepancia = true;
                    }
                }

                if ($hayDiscrepancia && !$final) {
                    $discrepancias++;
                }

                $matriz[$fecha] = [
                    'jefe' => $asistJefe ? [
                        'id'             => $asistJefe->id,
                        'estado'         => $asistJefe->estado,
                        'es_justificada' => $asistJefe->es_justificada,
                        'horas_extra'    => (float) $asistJefe->horas_extra,
                        'evidencia_url'  => $asistJefe->evidencia_ruta
                            ? asset('storage/' . $asistJefe->evidencia_ruta)
                            : null,
                        'usuario'        => $asistJefe->usuario?->nombre,
                        'updated_at'     => $asistJefe->updated_at?->format('d/m/Y H:i'),
                    ] : null,
                    'seguridad' => $asistSeg ? [
                        'id'             => $asistSeg->id,
                        'estado'         => $asistSeg->estado,
                        'es_justificada' => $asistSeg->es_justificada,
                        'horas_extra'    => (float) $asistSeg->horas_extra,
                        'evidencia_url'  => $asistSeg->evidencia_ruta
                            ? asset('storage/' . $asistSeg->evidencia_ruta)
                            : null,
                        'usuario'        => $asistSeg->usuario?->nombre,
                        'updated_at'     => $asistSeg->updated_at?->format('d/m/Y H:i'),
                    ] : null,
                    'final' => $final ? [
                        'id'              => $final->id,
                        'estado_final'    => $final->estado_final,
                        'origen_adoptado' => $final->origen_adoptado,
                        'conciliado_en'   => $final->conciliado_en?->format('d/m/Y H:i'),
                        'bloqueado'       => $final->bloqueado_edicion,
                    ] : null,
                    'discrepancia' => $hayDiscrepancia && !$final,
                    'sin_registro' => !$asistJefe && !$asistSeg,
                ];
            }

            $empleadosData[] = [
                'id'              => $emp->id,
                'nombre_completo' => $emp->nombre_completo,
                'puesto_cargo'    => $emp->puesto_cargo,
                'foto_url'        => $emp->foto_url,
                'empresa'         => $emp->empresa?->nombre,
                'empresa_id'      => $emp->empresa_id,
                'obra'            => $emp->obra?->nombre,
                'matriz'          => $matriz,
                'discrepancias'   => $discrepancias,
            ];
        }

        return response()->json([
            'success' => true,
            'modo'    => $modo,
            'semana'  => [
                'inicio' => $semana['inicio'],
                'fin'    => $semana['fin'],
                'week'   => $weekInput,
                'fechas' => $fechasSemana,
            ],
            'empleados'        => $empleadosData,
            'paginacion'       => [
                'current_page' => $empleadosPaginados->currentPage(),
                'last_page'    => $empleadosPaginados->lastPage(),
                'per_page'     => $empleadosPaginados->perPage(),
                'total'        => $empleadosPaginados->total(),
                'from'         => $empleadosPaginados->firstItem(),
                'to'           => $empleadosPaginados->lastItem(),
            ],
            'total_empleados'  => $empleadosPaginados->total(),
            'hoy'              => now()->format('Y-m-d'),
            'puede_conciliar'  => $this->puedeConciliarSemana($user, $semana),
            'semana_bloqueada' => $this->semanaEstaBloqueada($semana, $user),
            'es_admin'         => $user->esAdministrador(),
        ]);
    }

    // =========================================================
    // DETALLE DE UNA CELDA
    // =========================================================
    public function detalleCelda(Request $request)
    {
        $user = $request->user();

        if (!$this->puedeAccederConciliacion($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha'       => 'required|date',
        ]);

        $empleado = Empleado::with(['empresa', 'obra'])->findOrFail($data['empleado_id']);

        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $jefe = Asistencia::where('empleado_id', $data['empleado_id'])
            ->where('fecha', $data['fecha'])
            ->where('origen_registro', 'jefe_obra')
            ->with('usuario')
            ->first();

        $seguridad = Asistencia::where('empleado_id', $data['empleado_id'])
            ->where('fecha', $data['fecha'])
            ->where('origen_registro', 'seguridad')
            ->with('usuario')
            ->first();

        $final = AsistenciaFinal::where('empleado_id', $data['empleado_id'])
            ->where('fecha', $data['fecha'])
            ->first();

        return response()->json([
            'success'   => true,
            'empleado'  => [
                'id'              => $empleado->id,
                'nombre_completo' => $empleado->nombre_completo,
                'puesto_cargo'    => $empleado->puesto_cargo,
                'foto_url'        => $empleado->foto_url,
            ],
            'fecha'     => $data['fecha'],
            'jefe'      => $jefe ? [
                'id'             => $jefe->id,
                'estado'         => $jefe->estado,
                'es_justificada' => $jefe->es_justificada,
                'horas_extra'    => (float) $jefe->horas_extra,
                'evidencia_url'  => $jefe->evidencia_ruta
                    ? asset('storage/' . $jefe->evidencia_ruta)
                    : null,
                'usuario'        => $jefe->usuario?->nombre,
                'updated_at'     => $jefe->updated_at?->format('d/m/Y H:i'),
            ] : null,
            'seguridad' => $seguridad ? [
                'id'             => $seguridad->id,
                'estado'         => $seguridad->estado,
                'es_justificada' => $seguridad->es_justificada,
                'horas_extra'    => (float) $seguridad->horas_extra,
                'evidencia_url'  => $seguridad->evidencia_ruta
                    ? asset('storage/' . $seguridad->evidencia_ruta)
                    : null,
                'usuario'        => $seguridad->usuario?->nombre,
                'updated_at'     => $seguridad->updated_at?->format('d/m/Y H:i'),
            ] : null,
            'final'     => $final ? [
                'estado_final'    => $final->estado_final,
                'origen_adoptado' => $final->origen_adoptado,
                'conciliado_en'   => $final->conciliado_en?->format('d/m/Y H:i'),
                'bloqueado'       => $final->bloqueado_edicion,
            ] : null,
            'puede_conciliar' => $this->puedeConciliarFecha($user, $data['fecha']),
        ]);
    }

    // =========================================================
    // CONCILIAR UN DÍA
    // =========================================================
    public function conciliarDia(Request $request)
    {
        $user = $request->user();

        if (!$this->puedeAccederConciliacion($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'empleado_id'   => 'required|exists:empleados,id',
            'fecha'         => 'required|date',
            'adoptar'       => 'required|in:jefe_obra,seguridad,justificado',
            'motivo'        => 'nullable|in:permiso,enfermedad,otro',
            'descripcion'   => 'nullable|string|max:500',
            'evidencia'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if (!$this->puedeConciliarFecha($user, $data['fecha'])) {
            return response()->json(['success' => false, 'error' => 'No puedes conciliar esta fecha'], 403);
        }

        $empleado = Empleado::findOrFail($data['empleado_id']);
        if ($user->esContratista() && $empleado->empresa_id != $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $finalExistente = AsistenciaFinal::where('empleado_id', $data['empleado_id'])
            ->where('fecha', $data['fecha'])
            ->first();

        if ($finalExistente && $finalExistente->bloqueado_edicion && !$user->esAdministrador()) {
            return response()->json([
                'success' => false,
                'error'   => 'Este registro ya está conciliado y bloqueado. Solo el Admin puede desbloquearlo.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $jefe      = Asistencia::where('empleado_id', $data['empleado_id'])
                ->where('fecha', $data['fecha'])
                ->where('origen_registro', 'jefe_obra')
                ->first();
            $seguridad = Asistencia::where('empleado_id', $data['empleado_id'])
                ->where('fecha', $data['fecha'])
                ->where('origen_registro', 'seguridad')
                ->first();

            $estadoFinal = null;

            if ($data['adoptar'] === 'jefe_obra') {
                if (!$jefe) {
                    return response()->json(['success' => false, 'error' => 'No hay registro del Jefe de Obra para esta fecha'], 422);
                }
                $estadoFinal = $jefe->estado === 'presente' ? 'asistencia' : 'falta_injustificada';
            } elseif ($data['adoptar'] === 'seguridad') {
                if (!$seguridad) {
                    return response()->json(['success' => false, 'error' => 'No hay registro de Seguridad para esta fecha'], 422);
                }
                $estadoFinal = $seguridad->estado === 'presente' ? 'asistencia' : 'falta_injustificada';
            } elseif ($data['adoptar'] === 'justificado') {
                $estadoFinal = 'asistencia_justificada';
            }

            $diasFalta        = $estadoFinal === 'falta_injustificada' ? 1 : 0;
            $diasPenalizacion = $estadoFinal === 'falta_injustificada' ? 1 : 0;
            $totalDescuento   = $diasFalta + $diasPenalizacion;

            $justificacionCreada = null;
            if ($data['adoptar'] === 'justificado' && $data['motivo']) {
                $rutaEvidencia = null;
                if ($request->hasFile('evidencia')) {
                    $rutaEvidencia = $request->file('evidencia')->store('justificaciones', 'public');
                }

                $justificacionCreada = Justificacion::create([
                    'empleado_id'           => $data['empleado_id'],
                    'fecha'                 => $data['fecha'],
                    'motivo'                => $data['motivo'],
                    'descripcion'           => $data['descripcion'] ?? null,
                    'ruta_archivo'          => $rutaEvidencia,
                    'subido_por_usuario_id' => $user->id,
                ]);
            }

            $datosAntes = $finalExistente ? [
                'estado_final'    => $finalExistente->estado_final,
                'origen_adoptado' => $finalExistente->origen_adoptado,
                'dias_descuento'  => $finalExistente->total_dias_descuento,
            ] : null;

            $final = AsistenciaFinal::updateOrCreate(
                [
                    'empleado_id' => $data['empleado_id'],
                    'fecha'       => $data['fecha'],
                ],
                [
                    'empresa_id'                => $empleado->empresa_id,
                    'estado_final'              => $estadoFinal,
                    'origen_adoptado'           => $data['adoptar'],
                    'dias_falta'                => $diasFalta,
                    'dias_penalizacion_extra'   => $diasPenalizacion,
                    'total_dias_descuento'      => $totalDescuento,
                    'conciliado_por_usuario_id' => $user->id,
                    'conciliado_en'             => now(),
                    'bloqueado_edicion'         => true,
                ]
            );

            // =========================================================
            // CONSOLIDAR HORAS EXTRAS DEL DÍA
            // =========================================================
            $horasJefe = (float) ($jefe?->horas_extra ?? 0);
            $horasSeg  = (float) ($seguridad?->horas_extra ?? 0);

            $horasFinales = 0;
            switch ($data['adoptar']) {
                case 'jefe_obra':
                    $horasFinales = $horasJefe;
                    break;
                case 'seguridad':
                    $horasFinales = $horasSeg;
                    break;
                case 'justificado':
                default:
                    $horasFinales = max($horasJefe, $horasSeg);
                    break;
            }

            if ($horasFinales > 0) {
                HoraExtra::updateOrCreate(
                    [
                        'empleado_id' => $data['empleado_id'],
                        'fecha'       => $data['fecha'],
                    ],
                    [
                        'horas_solicitadas'       => (int) round($horasFinales),
                        'horas_aprobadas'         => 0,
                        'estado'                  => 'pendiente',
                        'aprobado_por_usuario_id' => null,
                    ]
                );
            }

            // Bitácora con datos antes/después
            $datosDespues = [
                'estado_final'    => $final->estado_final,
                'origen_adoptado' => $final->origen_adoptado,
                'dias_descuento'  => $final->total_dias_descuento,
            ];

            $opcionesBitacora = [
                'empresa_id' => $empleado->empresa_id,
                'geo'        => [
                    'lat'       => $request->header('X-Geo-Lat'),
                    'lng'       => $request->header('X-Geo-Lng'),
                    'precision' => $request->header('X-Geo-Precision'),
                ],
                'device_id'  => $request->header('X-Device-Id'),
            ];

            if ($finalExistente) {
                BitacoraService::actualizar(
                    'conciliacion.conciliar_dia',
                    "Día conciliado ({$data['adoptar']}) para {$empleado->nombre_completo} el {$data['fecha']}",
                    'App\Models\AsistenciaFinal',
                    $final->id,
                    $datosAntes,
                    $datosDespues,
                    $opcionesBitacora
                );
            } else {
                BitacoraService::insertar(
                    'conciliacion.conciliar_dia',
                    "Día conciliado ({$data['adoptar']}) para {$empleado->nombre_completo} el {$data['fecha']}",
                    'App\Models\AsistenciaFinal',
                    $final->id,
                    $datosDespues,
                    $opcionesBitacora
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => 'Día conciliado correctamente',
                'final'   => $final->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // CONCILIAR MASIVO (Todo Jefe / Todo Seguridad)
    // =========================================================
    public function conciliarMasivo(Request $request)
    {
        $user = $request->user();

        if (!$this->puedeAccederConciliacion($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'week'              => 'required|string',
            'adoptar'           => 'required|in:jefe_obra,seguridad',
            'empresa_id'        => 'nullable|exists:empresas,id',
            'obra_id'           => 'nullable|exists:obras,id',
            'solo_coincidentes' => 'nullable|boolean',
        ]);

        $semana = $this->calcularSemanaDesdeWeek($data['week']);

        if (!$this->puedeConciliarSemana($user, $semana)) {
            return response()->json(['success' => false, 'error' => 'No puedes conciliar esta semana'], 403);
        }

        $empresaId = $data['empresa_id'];
        if ($user->esContratista() || $user->esJefeObra()) {
            $empresaId = $user->empresa_id;
        }

        $empleadosQuery = Empleado::where('estatus', 'activo');
        if ($empresaId) $empleadosQuery->where('empresa_id', $empresaId);
        if (!empty($data['obra_id'])) $empleadosQuery->where('obra_id', $data['obra_id']);

        $empleados = $empleadosQuery->get();

        DB::beginTransaction();
        try {
            $conciliados = 0;
            $omitidos    = 0;

            foreach ($empleados as $emp) {
                foreach (array_keys($semana['fechas']) as $fecha) {
                    // Obtener jefe y seguridad SIEMPRE
                    $jefe = Asistencia::where('empleado_id', $emp->id)
                        ->where('fecha', $fecha)
                        ->where('origen_registro', 'jefe_obra')
                        ->first();

                    $seguridad = Asistencia::where('empleado_id', $emp->id)
                        ->where('fecha', $fecha)
                        ->where('origen_registro', 'seguridad')
                        ->first();

                    // =========================================================
                    // CONSOLIDAR HORAS EXTRAS (ANTES del continue)
                    // =========================================================
                    $horasJefe = (float) ($jefe?->horas_extra ?? 0);
                    $horasSeg  = (float) ($seguridad?->horas_extra ?? 0);

                    $horasFinales = 0;
                    if ($data['adoptar'] === 'jefe_obra') {
                        $horasFinales = $horasJefe;
                    } elseif ($data['adoptar'] === 'seguridad') {
                        $horasFinales = $horasSeg;
                    } else {
                        $horasFinales = max($horasJefe, $horasSeg);
                    }

                    if ($horasFinales > 0) {
                        HoraExtra::updateOrCreate(
                            [
                                'empleado_id' => $emp->id,
                                'fecha'       => $fecha,
                            ],
                            [
                                'horas_solicitadas'       => (int) round($horasFinales),
                                'horas_aprobadas'         => 0,
                                'estado'                  => 'pendiente',
                                'aprobado_por_usuario_id' => null,
                            ]
                        );
                    }

                    // Verificar si ya está bloqueado
                    $finalExistente = AsistenciaFinal::where('empleado_id', $emp->id)
                        ->where('fecha', $fecha)
                        ->where('bloqueado_edicion', true)
                        ->first();

                    if ($finalExistente && !$user->esAdministrador()) {
                        $omitidos++;
                        continue;
                    }

                    if (!empty($data['solo_coincidentes'])) {
                        if (!$jefe || !$seguridad) {
                            $omitidos++;
                            continue;
                        }
                        $sonIguales = $jefe->estado === $seguridad->estado
                            && $jefe->es_justificada === $seguridad->es_justificada;
                        if (!$sonIguales) {
                            $omitidos++;
                            continue;
                        }
                    }

                    $asistencia = $data['adoptar'] === 'jefe_obra' ? $jefe : $seguridad;

                    if (!$asistencia) {
                        $omitidos++;
                        continue;
                    }

                    $estadoFinal = $asistencia->estado === 'presente'
                        ? 'asistencia'
                        : ($asistencia->es_justificada ? 'asistencia_justificada' : 'falta_injustificada');

                    $diasFalta        = $estadoFinal === 'falta_injustificada' ? 1 : 0;
                    $diasPenalizacion = $estadoFinal === 'falta_injustificada' ? 1 : 0;
                    $totalDescuento   = $diasFalta + $diasPenalizacion;

                    AsistenciaFinal::updateOrCreate(
                        [
                            'empleado_id' => $emp->id,
                            'fecha'       => $fecha,
                        ],
                        [
                            'empresa_id'                => $emp->empresa_id,
                            'estado_final'              => $estadoFinal,
                            'origen_adoptado'           => $data['adoptar'],
                            'dias_falta'                => $diasFalta,
                            'dias_penalizacion_extra'   => $diasPenalizacion,
                            'total_dias_descuento'      => $totalDescuento,
                            'conciliado_por_usuario_id' => $user->id,
                            'conciliado_en'             => now(),
                            'bloqueado_edicion'         => true,
                        ]
                    );

                    $conciliados++;
                }
            }

            BitacoraService::insertar(
                'conciliacion.masiva',
                "Conciliación masiva ({$data['adoptar']}) — {$conciliados} días",
                'App\Models\AsistenciaFinal',
                0,
                [
                    'week'        => $data['week'],
                    'adoptar'     => $data['adoptar'],
                    'conciliados' => $conciliados,
                    'omitidos'    => $omitidos,
                ],
                [
                    'empresa_id' => $empresaId,
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
                'success'     => true,
                'conciliados' => $conciliados,
                'omitidos'    => $omitidos,
                'mensaje'     => "Se conciliaron {$conciliados} días. Omitidos: {$omitidos}.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // CONCILIAR SEMANA COMPLETA
    // =========================================================
    public function conciliarSemanaCompleta(Request $request)
    {
        $user = $request->user();

        if (!$this->puedeAccederConciliacion($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'week'       => 'required|string',
            'empresa_id' => 'nullable|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        $semana = $this->calcularSemanaDesdeWeek($data['week']);

        if (!$this->puedeConciliarSemana($user, $semana)) {
            return response()->json(['success' => false, 'error' => 'No puedes conciliar esta semana'], 403);
        }

        $empresaId = $data['empresa_id'];
        if ($user->esContratista() || $user->esJefeObra()) {
            $empresaId = $user->empresa_id;
        }

        $empleadosQuery = Empleado::where('estatus', 'activo');
        if ($empresaId) $empleadosQuery->where('empresa_id', $empresaId);
        if (!empty($data['obra_id'])) $empleadosQuery->where('obra_id', $data['obra_id']);

        $empleados = $empleadosQuery->get();
        $fechas = array_keys($semana['fechas']);

        // PASO 1: verificar discrepancias
        $discrepanciasPendientes = 0;

        foreach ($empleados as $emp) {
            foreach ($fechas as $fecha) {
                $final = AsistenciaFinal::where('empleado_id', $emp->id)
                    ->where('fecha', $fecha)
                    ->where('bloqueado_edicion', true)
                    ->exists();

                if ($final) continue;

                $jefe = Asistencia::where('empleado_id', $emp->id)
                    ->where('fecha', $fecha)
                    ->where('origen_registro', 'jefe_obra')
                    ->first();

                $seguridad = Asistencia::where('empleado_id', $emp->id)
                    ->where('fecha', $fecha)
                    ->where('origen_registro', 'seguridad')
                    ->first();

                $hayDiscrepancia = false;

                if ($jefe && $seguridad) {
                    if ($jefe->estado !== $seguridad->estado) {
                        $hayDiscrepancia = true;
                    } elseif (
                        $jefe->estado === 'falta'
                        && $jefe->es_justificada !== $seguridad->es_justificada
                    ) {
                        $hayDiscrepancia = true;
                    }
                }

                if ($hayDiscrepancia) $discrepanciasPendientes++;
            }
        }

        if ($discrepanciasPendientes > 0) {
            return response()->json([
                'success' => false,
                'error'   => "Hay {$discrepanciasPendientes} discrepancias pendientes.",
                'discrepancias_pendientes' => $discrepanciasPendientes,
            ], 422);
        }

        // PASO 2: generar TODO
        DB::beginTransaction();
        try {
            $creados          = 0;
            $nulos            = 0;
            $bloqueados       = 0;
            $horasExtrasCreadas = 0;
            $total            = 0;

            foreach ($empleados as $emp) {
                foreach ($fechas as $fecha) {
                    $total++;

                    // =========================================================
                    // OBTENER jefe y seguridad SIEMPRE (para horas extras)
                    // =========================================================
                    $jefe = Asistencia::where('empleado_id', $emp->id)
                        ->where('fecha', $fecha)
                        ->where('origen_registro', 'jefe_obra')
                        ->first();

                    $seguridad = Asistencia::where('empleado_id', $emp->id)
                        ->where('fecha', $fecha)
                        ->where('origen_registro', 'seguridad')
                        ->first();

                    // =========================================================
                    // CONSOLIDAR HORAS EXTRAS (ANTES del continue)
                    // =========================================================
                    $horasJefe = (float) ($jefe?->horas_extra ?? 0);
                    $horasSeg  = (float) ($seguridad?->horas_extra ?? 0);

                    if ($horasJefe == $horasSeg) {
                        $horasFinales = $horasJefe;
                    } elseif ($horasJefe > 0 && $horasSeg == 0) {
                        $horasFinales = $horasJefe;
                    } elseif ($horasSeg > 0 && $horasJefe == 0) {
                        $horasFinales = $horasSeg;
                    } else {
                        $horasFinales = max($horasJefe, $horasSeg);
                    }

                    if ($horasFinales > 0) {
                        $horaExtra = HoraExtra::updateOrCreate(
                            [
                                'empleado_id' => $emp->id,
                                'fecha'       => $fecha,
                            ],
                            [
                                'horas_solicitadas'       => (int) round($horasFinales),
                                'horas_aprobadas'         => 0,
                                'estado'                  => 'pendiente',
                                'aprobado_por_usuario_id' => null,
                            ]
                        );

                        if ($horaExtra->wasRecentlyCreated) {
                            $horasExtrasCreadas++;
                        }
                    }

                    // =========================================================
                    // Ahora verificar si ya está bloqueado
                    // =========================================================
                    $finalExistente = AsistenciaFinal::where('empleado_id', $emp->id)
                        ->where('fecha', $fecha)
                        ->first();

                    if ($finalExistente && $finalExistente->bloqueado_edicion) {
                        $bloqueados++;
                        continue;
                    }

                    $estadoFinal      = null;
                    $origenAdoptado   = null;
                    $diasFalta        = 0;
                    $diasPenalizacion = 0;
                    $totalDescuento   = 0;

                    $ganador = $jefe ?? $seguridad;
                    $origenGanador = $jefe && $seguridad ? 'automatico' : ($jefe ? 'jefe_obra' : ($seguridad ? 'seguridad' : null));

                    if ($ganador) {
                        if ($ganador->estado === 'presente') {
                            $estadoFinal = 'asistencia';
                        } elseif ($ganador->es_justificada) {
                            $estadoFinal = 'asistencia_justificada';
                        } else {
                            $estadoFinal      = 'falta_injustificada';
                            $diasFalta        = 1;
                            $diasPenalizacion = 1;
                            $totalDescuento   = 2;
                        }
                        $origenAdoptado = $origenGanador;
                        $creados++;
                    } else {
                        $nulos++;
                    }

                    AsistenciaFinal::updateOrCreate(
                        [
                            'empleado_id' => $emp->id,
                            'fecha'       => $fecha,
                        ],
                        [
                            'empresa_id'                => $emp->empresa_id,
                            'estado_final'              => $estadoFinal,
                            'origen_adoptado'           => $origenAdoptado,
                            'dias_falta'                => $diasFalta,
                            'dias_penalizacion_extra'   => $diasPenalizacion,
                            'total_dias_descuento'      => $totalDescuento,
                            'conciliado_por_usuario_id' => $user->id,
                            'conciliado_en'             => now(),
                            'bloqueado_edicion'         => true,
                        ]
                    );
                }
            }

            BitacoraService::insertar(
                'conciliacion.semana_completa',
                "Semana {$data['week']} conciliada. Procesados: {$total}, con estado: {$creados}, sin registro: {$nulos}, ya bloqueados: {$bloqueados}, horas extras: {$horasExtrasCreadas}.",
                'App\Models\AsistenciaFinal',
                0,
                [
                    'week'               => $data['week'],
                    'empresa_id'         => $empresaId,
                    'obra_id'            => $data['obra_id'] ?? null,
                    'creados'            => $creados,
                    'nulos'              => $nulos,
                    'bloqueados'         => $bloqueados,
                    'horas_extras_creadas' => $horasExtrasCreadas,
                    'total'              => $total,
                ],
                [
                    'empresa_id' => $empresaId,
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
                'generados'  => $creados,
                'nulos'      => $nulos,
                'bloqueados' => $bloqueados,
                'horas_extras_creadas' => $horasExtrasCreadas,
                'total'      => $total,
                'mensaje'    => "Semana conciliada. Total: {$total}. Horas extras generadas: {$horasExtrasCreadas}.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error conciliando semana', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // DESBLOQUEAR (solo Admin)
    // =========================================================
    public function desbloquear(Request $request)
    {
        $user = $request->user();

        if (!$user->esAdministrador()) {
            return response()->json(['success' => false, 'error' => 'Solo el Admin puede desbloquear'], 403);
        }

        $data = $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha'       => 'required|date',
        ]);

        $final = AsistenciaFinal::where('empleado_id', $data['empleado_id'])
            ->where('fecha', $data['fecha'])
            ->first();

        if (!$final) {
            return response()->json(['success' => false, 'error' => 'No hay conciliación para esa fecha'], 404);
        }

        DB::beginTransaction();
        try {
            $final->update(['bloqueado_edicion' => false]);

            BitacoraService::actualizar(
                'conciliacion.desbloquear',
                "Desbloqueada conciliación del empleado ID {$data['empleado_id']} para el {$data['fecha']}",
                'App\Models\AsistenciaFinal',
                $final->id,
                ['bloqueado_edicion' => true],
                ['bloqueado_edicion' => false],
                [
                    'empresa_id' => $final->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            return response()->json(['success' => true, 'mensaje' => 'Registro desbloqueado']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // DESBLOQUEAR SEMANA
    // =========================================================
    public function desbloquearSemana(Request $request)
    {
        $user = $request->user();

        if (!$user->esAdministrador()) {
            return response()->json(['success' => false, 'error' => 'Solo el Admin puede desbloquear'], 403);
        }

        $data = $request->validate([
            'week'       => 'required|string',
            'empresa_id' => 'nullable|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        $semana = $this->calcularSemanaDesdeWeek($data['week']);

        $empresaId = $data['empresa_id'];
        $empleadosQuery = Empleado::where('estatus', 'activo');
        if ($empresaId) $empleadosQuery->where('empresa_id', $empresaId);
        if (!empty($data['obra_id'])) $empleadosQuery->where('obra_id', $data['obra_id']);

        $empleados = $empleadosQuery->get();
        $empleadoIds = $empleados->pluck('id')->toArray();

        DB::beginTransaction();
        try {
            $datosAntes = AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
                ->whereIn('fecha', array_keys($semana['fechas']))
                ->where('bloqueado_edicion', true)
                ->count();

            $desbloqueados = AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
                ->whereIn('fecha', array_keys($semana['fechas']))
                ->update(['bloqueado_edicion' => false]);

            BitacoraService::actualizar(
                'conciliacion.desbloquear_semana',
                "Semana {$data['week']} desbloqueada por Admin.",
                'App\Models\AsistenciaFinal',
                0,
                ['bloqueados' => $datosAntes],
                ['desbloqueados' => $desbloqueados],
                [
                    'empresa_id' => $empresaId,
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
                'desbloqueados' => $desbloqueados,
                'mensaje'       => "Semana desbloqueada. {$desbloqueados} registros editables.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // LISTAR JEFES
    // =========================================================
    public function listarJefes(Request $request)
    {
        $user = $request->user();

        if (!$user->esContratista() && !$user->esAdministrador()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $query = User::whereHas('rol', function ($q) {
            $q->where('codigo', 'jefe_obra');
        })->where('estatus', 'activo');

        if ($user->esContratista()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $jefes = $query->orderBy('nombre')->get(['id', 'nombre', 'email', 'puede_conciliar', 'empresa_id']);

        return response()->json([
            'success' => true,
            'jefes'   => $jefes,
        ]);
    }

    public function togglePermisoJefe(Request $request)
    {
        $user = $request->user();

        if (!$user->esContratista() && !$user->esAdministrador()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'activo'  => 'required|boolean',
        ]);

        $jefe = User::findOrFail($data['user_id']);

        if ($user->esContratista() && $jefe->empresa_id != $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        if ($jefe->rol?->codigo !== 'jefe_obra') {
            return response()->json(['success' => false, 'error' => 'El usuario no es Jefe de Obra'], 422);
        }

        DB::beginTransaction();
        try {
            $antes = $jefe->puede_conciliar;

            $jefe->update(['puede_conciliar' => $data['activo']]);

            BitacoraService::actualizar(
                'conciliacion.toggle_permiso',
                "Permiso de conciliación " . ($data['activo'] ? 'activado' : 'desactivado') . " para {$jefe->nombre}",
                'App\Models\User',
                $jefe->id,
                ['puede_conciliar' => $antes],
                ['puede_conciliar' => (bool) $data['activo']],
                [
                    'empresa_id' => $jefe->empresa_id,
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
                'success' => true,
                'mensaje' => 'Permiso actualizado',
                'jefe'    => $jefe->fresh(['rol']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // HELPERS PROTEGIDOS
    // =========================================================

    protected function puedeAccederConciliacion($user): bool
    {
        if ($user->esAdministrador() || $user->esContratista()) {
            return true;
        }
        if ($user->esJefeObra() && $user->puede_conciliar) {
            return true;
        }
        return false;
    }

    protected function puedeConciliarFecha($user, string $fecha): bool
    {
        $fechaObj = Carbon::parse($fecha)->startOfDay();
        $hoy = now()->startOfDay();

        if ($user->esAdministrador()) return true;

        $semanaActual = $this->calcularSemanaDesdeWeek(now()->format('o-\WW'));
        return in_array($fecha, array_keys($semanaActual['fechas']), true);
    }

    protected function puedeConciliarSemana($user, array $semana): bool
    {
        if ($user->esAdministrador()) return true;

        $semanaActual = $this->calcularSemanaDesdeWeek(now()->format('o-\WW'));
        return $semana['inicio'] === $semanaActual['inicio'];
    }

    protected function semanaEstaBloqueada(array $semana, $user): bool
    {
        if ($user->esAdministrador()) return false;

        $semanaActual = $this->calcularSemanaDesdeWeek(now()->format('o-\WW'));
        return $semana['inicio'] !== $semanaActual['inicio'];
    }

    protected function calcularSemanaDesdeWeek(string $weekInput): array
    {
        if (!preg_match('/^(\d{4})-W(\d{1,2})$/', $weekInput, $m)) {
            $weekInput = now()->format('o-\WW');
            preg_match('/^(\d{4})-W(\d{1,2})$/', $weekInput, $m);
        }

        $anio   = (int) $m[1];
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
}