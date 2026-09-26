<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\AsistenciaFinal;
use App\Models\BitacoraAccion;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\HoraExtra;
use App\Models\Obra;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // =========================================================
    // VISTA PRINCIPAL
    // =========================================================
    public function index(Request $request)
    {
        $user = $request->user();
        $esAdmin = $user->esAdministrador();
        $esContratista = $user->esContratista();
        $esJefe = $user->esJefeObra();

        // =========================================================
        // AISLAMIENTO POR ROL
        // =========================================================
        // Admin: ve todas las empresas
        // Contratista: solo la suya (obligatorio)
        // Jefe de Obra / otros: solo la suya (obligatorio)
        $empresaFiltroId = null;

        if ($esAdmin) {
            $empresaFiltroId = $request->input('empresa_id'); // puede ser null = todas
        } else {
            $empresaFiltroId = $user->empresa_id; // forzado
        }

        // =========================================================
        // EMPRESAS VISIBLES PARA EL SELECTOR
        // =========================================================
        if ($esAdmin) {
            $empresas = Empresa::where('estatus', 'activo')
                ->where('tipo', 'externa') // Admin no ve la matriz aquí
                ->orderBy('nombre')
                ->get(['id', 'nombre']);
        } elseif ($esContratista || $esJefe) {
            // Contratista/Jefe solo ven SU empresa en el selector
            $empresas = Empresa::where('id', $user->empresa_id)
                ->where('estatus', 'activo')
                ->get(['id', 'nombre']);
        } else {
            $empresas = collect();
        }

        // =========================================================
        // OBRAS VISIBLES
        // =========================================================
        $obrasQuery = Obra::where('estatus', 'activa');
        if ($empresaFiltroId) {
            $obrasQuery->where('empresa_id', $empresaFiltroId);
        }
        $obras = $obrasQuery->orderBy('nombre')->get(['id', 'nombre', 'empresa_id']);

        $obraId = $request->input('obra_id');

        // =========================================================
        // RANGO DE FECHAS
        // =========================================================
        $rango = $request->input('rango', 'mes'); // mes | semana | 7dias | 30dias

        switch ($rango) {
            case 'semana':
                $inicio = now()->startOfWeek();
                $fin = now()->endOfWeek();
                break;
            case '7dias':
                $inicio = now()->subDays(6)->startOfDay();
                $fin = now()->endOfDay();
                break;
            case '30dias':
                $inicio = now()->subDays(29)->startOfDay();
                $fin = now()->endOfDay();
                break;
            case 'mes':
            default:
                $inicio = now()->startOfMonth();
                $fin = now()->endOfMonth();
                break;
        }

        // =========================================================
        // HELPERS DE QUERY
        // =========================================================
        $filtrarEmpleados = function ($query) use ($empresaFiltroId, $obraId) {
            if ($empresaFiltroId) {
                $query->where('empresa_id', $empresaFiltroId);
            }
            if ($obraId) {
                $query->where('obra_id', $obraId);
            }
            return $query;
        };

        $filtrarAsistencias = function ($query) use ($empresaFiltroId, $obraId) {
            if ($empresaFiltroId || $obraId) {
                $query->whereHas('empleado', function ($q) use ($empresaFiltroId, $obraId) {
                    if ($empresaFiltroId) $q->where('empresa_id', $empresaFiltroId);
                    if ($obraId) $q->where('obra_id', $obraId);
                });
            }
            return $query;
        };

        // =========================================================
        // KPI 1: Total empleados activos
        // =========================================================
        $totalEmpleados = $filtrarEmpleados(
            Empleado::where('estatus', 'activo')
        )->count();

        // =========================================================
        // KPI 2: % Asistencia global (asistencias_finales / total esperado)
        // =========================================================
        $totalDiasEsperados = 0;
        $totalAsistencias = 0;

        if ($totalEmpleados > 0) {
            // Días laborales en el rango (L a S)
            $diasHabiles = 0;
            $cursor = $inicio->copy();
            while ($cursor <= $fin) {
                if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                    $diasHabiles++;
                }
                $cursor->addDay();
            }
            $totalDiasEsperados = $totalEmpleados * $diasHabiles;

            $totalAsistencias = $filtrarAsistencias(
                AsistenciaFinal::whereBetween('fecha', [$inicio, $fin])
                    ->where('estado_final', 'asistencia')
            )->count();
        }

        $porcentajeAsistencia = $totalDiasEsperados > 0
            ? round(($totalAsistencias / $totalDiasEsperados) * 100, 1)
            : 0;

        // =========================================================
        // KPI 3: Faltas injustificadas vs justificadas
        // =========================================================
        $faltasInjustificadas = $filtrarAsistencias(
            AsistenciaFinal::whereBetween('fecha', [$inicio, $fin])
                ->where('estado_final', 'falta_injustificada')
        )->count();

        $faltasJustificadas = $filtrarAsistencias(
            AsistenciaFinal::whereBetween('fecha', [$inicio, $fin])
                ->where('estado_final', 'asistencia_justificada')
        )->count();

        // =========================================================
        // KPI 4: Días descontados (penalizaciones)
        // =========================================================
        $diasDescontados = $filtrarAsistencias(
            AsistenciaFinal::whereBetween('fecha', [$inicio, $fin])
                ->where('estado_final', 'falta_injustificada')
        )->sum('total_dias_descuento');

        // =========================================================
        // KPI 5: Horas extras (aprobadas + pendientes)
        // =========================================================
        $horasExtrasQuery = HoraExtra::whereBetween('fecha', [$inicio, $fin]);
        if ($empresaFiltroId || $obraId) {
            $horasExtrasQuery->whereHas('empleado', function ($q) use ($empresaFiltroId, $obraId) {
                if ($empresaFiltroId) $q->where('empresa_id', $empresaFiltroId);
                if ($obraId) $q->where('obra_id', $obraId);
            });
        }

        $horasExtrasAprobadas = (clone $horasExtrasQuery)
            ->where('estado', 'aprobado')
            ->sum('horas_aprobadas');

        $horasExtrasPendientes = (clone $horasExtrasQuery)
            ->where('estado', 'pendiente')
            ->sum('horas_solicitadas');

        // =========================================================
        // GRÁFICO 1: Asistencia últimos 7 días (línea)
        // =========================================================
        $dias7 = [];
        $asistencias7 = [];
        $faltas7 = [];

        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            $dias7[] = now()->subDays($i)->format('d/m');

            $asistencias7[] = $filtrarAsistencias(
                AsistenciaFinal::where('fecha', $fecha)
                    ->where('estado_final', 'asistencia')
            )->count();

            $faltas7[] = $filtrarAsistencias(
                AsistenciaFinal::where('fecha', $fecha)
                    ->where('estado_final', 'falta_injustificada')
            )->count();
        }

        // =========================================================
        // GRÁFICO 2: Distribución por puesto (dona)
        // =========================================================
        $distribucionPuestos = Empleado::select('puesto_cargo', DB::raw('COUNT(*) as total'))
            ->where('estatus', 'activo')
            ->when($empresaFiltroId, fn($q) => $q->where('empresa_id', $empresaFiltroId))
            ->when($obraId, fn($q) => $q->where('obra_id', $obraId))
            ->groupBy('puesto_cargo')
            ->orderBy('total', 'desc')
            ->limit(8)
            ->get();

        $labelsPuestos = $distribucionPuestos->pluck('puesto_cargo')->toArray();
        $dataPuestos = $distribucionPuestos->pluck('total')->toArray();

        // =========================================================
        // GRÁFICO 3: Faltas justificadas vs injustificadas (dona)
        // =========================================================
        $labelsFaltas = ['Justificadas', 'Injustificadas'];
        $dataFaltas = [$faltasJustificadas, $faltasInjustificadas];

        // =========================================================
        // GRÁFICO 4: Empleados por empresa (barra) — solo Admin
        // =========================================================
        $labelsEmpresas = [];
        $dataEmpleadosEmpresa = [];

        if ($esAdmin) {
            $empresasConEmpleados = Empresa::where('estatus', 'activo')
                ->where('tipo', 'externa')
                ->withCount(['empleados' => function ($q) {
                    $q->where('estatus', 'activo');
                }])
                ->orderBy('empleados_count', 'desc')
                ->limit(8)
                ->get();

            $labelsEmpresas = $empresasConEmpleados->pluck('nombre')->toArray();
            $dataEmpleadosEmpresa = $empresasConEmpleados->pluck('empleados_count')->toArray();
        }

        // =========================================================
        // TOP 5 EMPLEADOS CON MÁS ASISTENCIAS
        // =========================================================
        $topAsistencia = Empleado::withCount([
                'asistenciasFinales as asistencias_count' => function ($q) use ($inicio, $fin) {
                    $q->whereBetween('fecha', [$inicio, $fin])
                        ->where('estado_final', 'asistencia');
                }
            ])
            ->where('estatus', 'activo')
            ->when($empresaFiltroId, fn($q) => $q->where('empresa_id', $empresaFiltroId))
            ->when($obraId, fn($q) => $q->where('obra_id', $obraId))
            ->orderBy('asistencias_count', 'desc')
            ->limit(5)
            ->get(['id', 'nombre', 'apellido', 'puesto_cargo', 'foto']);

        // =========================================================
        // TOP 5 EMPLEADOS CON MÁS FALTAS
        // =========================================================
        $topFaltas = Empleado::withCount([
                'asistenciasFinales as faltas_count' => function ($q) use ($inicio, $fin) {
                    $q->whereBetween('fecha', [$inicio, $fin])
                        ->where('estado_final', 'falta_injustificada');
                }
            ])
            ->where('estatus', 'activo')
            ->when($empresaFiltroId, fn($q) => $q->where('empresa_id', $empresaFiltroId))
            ->when($obraId, fn($q) => $q->where('obra_id', $obraId))
            ->orderBy('faltas_count', 'desc')
            ->limit(5)
            ->get(['id', 'nombre', 'apellido', 'puesto_cargo', 'foto']);

        // =========================================================
        // OBRAS ACTIVAS POR EMPRESA (tabla) — solo Admin
        // =========================================================
        $obrasActivas = collect();
        if ($esAdmin || $esContratista) {
            $obrasQuery2 = Obra::where('estatus', 'activa')
                ->with('empresa:id,nombre');

            if ($empresaFiltroId) {
                $obrasQuery2->where('empresa_id', $empresaFiltroId);
            } elseif ($esContratista) {
                $obrasQuery2->where('empresa_id', $user->empresa_id);
            }

            $obrasActivas = $obrasQuery2->orderBy('nombre')->limit(10)->get();
        }

        // =========================================================
        // ÚLTIMOS MOVIMIENTOS DE BITÁCORA
        // =========================================================
        $bitacoraQuery = BitacoraAccion::with('usuario:id,nombre')
            ->orderByDesc('created_at');

        if (!$esAdmin) {
            $bitacoraQuery->where('empresa_id', $user->empresa_id);
        } elseif ($empresaFiltroId) {
            $bitacoraQuery->where('empresa_id', $empresaFiltroId);
        }

        $ultimosMovimientos = $bitacoraQuery->limit(8)->get();

        // =========================================================
        // RENDER
        // =========================================================
        return view('dashboard.index', compact(
            'empresas', 'obras',
            'empresaFiltroId', 'obraId',
            'rango', 'inicio', 'fin',
            'totalEmpleados', 'porcentajeAsistencia', 'totalAsistencias', 'totalDiasEsperados',
            'faltasInjustificadas', 'faltasJustificadas',
            'diasDescontados',
            'horasExtrasAprobadas', 'horasExtrasPendientes',
            'dias7', 'asistencias7', 'faltas7',
            'labelsPuestos', 'dataPuestos',
            'labelsFaltas', 'dataFaltas',
            'labelsEmpresas', 'dataEmpleadosEmpresa',
            'topAsistencia', 'topFaltas',
            'obrasActivas',
            'ultimosMovimientos',
            'esAdmin', 'esContratista', 'esJefe'
        ));
    }
}