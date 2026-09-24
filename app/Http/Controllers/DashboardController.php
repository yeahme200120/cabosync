<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\Asistencia;
use App\Models\AsistenciaFinal;
use App\Models\HoraExtra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $esContratista = $user->esContratista();
        $esAdmin = $user->esAdministrador();

        // ============================
        // FILTROS
        // ============================
        $empresasQuery = Empresa::query();
        if ($esContratista) {
            $empresasQuery->where('id', $user->empresa_id);
        } elseif (!$esAdmin) {
            $empresasQuery->where('id', $user->empresa_id);
        }
        $empresas = $empresasQuery->where('estatus', 'activo')->get();

        $obrasQuery = Obra::query();
        if ($esContratista) {
            $obrasQuery->where('empresa_id', $user->empresa_id);
        }
        $obras = $obrasQuery->where('estatus', 'activa')->get();

        $empresaId = $request->input('empresa_id');
        $obraId = $request->input('obra_id');

        if ($esContratista && !$empresaId) {
            $empresaId = $user->empresa_id;
        }

        // ============================
        // KPIs
        // ============================
        $empleadosQuery = Empleado::query()->where('estatus', 'activo');
        if ($empresaId) $empleadosQuery->where('empresa_id', $empresaId);
        if ($obraId) $empleadosQuery->where('obra_id', $obraId);
        $totalEmpleados = $empleadosQuery->count();

        $asistenciasHoy = Asistencia::whereDate('fecha', today())
            ->where('estado', 'presente')
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereHas('empleado', fn($sub) => $sub->where('empresa_id', $empresaId));
            })
            ->count();

        $porcentajeAsistencia = $totalEmpleados > 0
            ? round(($asistenciasHoy / $totalEmpleados) * 100, 1)
            : 0;

        $faltasInjustificadas = AsistenciaFinal::whereBetween('fecha', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('estado_final', 'falta_injustificada')
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereHas('empleado', fn($sub) => $sub->where('empresa_id', $empresaId));
            })
            ->count();

        $faltasJustificadas = AsistenciaFinal::whereBetween('fecha', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('estado_final', 'asistencia_justificada')
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereHas('empleado', fn($sub) => $sub->where('empresa_id', $empresaId));
            })
            ->count();

        $diasDescontados = AsistenciaFinal::whereBetween('fecha', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('estado_final', 'falta_injustificada')
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereHas('empleado', fn($sub) => $sub->where('empresa_id', $empresaId));
            })
            ->sum('total_dias_descuento');

        $horasExtrasSemana = HoraExtra::whereBetween('fecha', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('estado', 'aprobado')
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->whereHas('empleado', fn($sub) => $sub->where('empresa_id', $empresaId));
            })
            ->sum('horas_aprobadas');

        // ============================
        // DISTRIBUCIÓN POR PUESTO
        // ============================
        $distribucionPuestos = Empleado::select('puesto_cargo', DB::raw('COUNT(*) as total'))
            ->where('estatus', 'activo')
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->when($obraId, fn($q) => $q->where('obra_id', $obraId))
            ->groupBy('puesto_cargo')
            ->orderBy('total', 'desc')
            ->get();

        // Datos demo si no hay empleados aún
        if ($distribucionPuestos->isEmpty()) {
            $distribucionPuestos = collect([
                (object) ['puesto_cargo' => 'Oficial Albañil', 'total' => 13],
                (object) ['puesto_cargo' => 'Oficial Carpintero', 'total' => 11],
                (object) ['puesto_cargo' => 'Fierrero', 'total' => 6],
                (object) ['puesto_cargo' => 'Limpieza', 'total' => 4],
                (object) ['puesto_cargo' => 'Ayudante General', 'total' => 12],
            ]);
        }

        $labelsPuestos = $distribucionPuestos->pluck('puesto_cargo')->toArray();
        $dataPuestos = $distribucionPuestos->pluck('total')->toArray();
        $coloresPuestos = [
            '#1E5180', '#F28C28', '#4a90e2', '#28a745', '#ffc107',
            '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d',
        ];

        // ============================
        // TOP EMPLEADOS
        // ============================
        $topAsistencia = Empleado::withCount([
            'asistencias as asistencias_presentes' => function ($q) {
                $q->where('estado', 'presente')
                    ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()]);
            }
        ])
        ->where('estatus', 'activo')
        ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
        ->orderBy('asistencias_presentes', 'desc')
        ->limit(3)
        ->get();

        $topFaltas = Empleado::withCount([
            'asistenciasFinales as faltas_count' => function ($q) {
                $q->where('estado_final', 'falta_injustificada')
                    ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()]);
            }
        ])
        ->where('estatus', 'activo')
        ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
        ->orderBy('faltas_count', 'desc')
        ->limit(3)
        ->get();

        return view('dashboard.index', compact(
            'empresas', 'obras',
            'empresaId', 'obraId',
            'totalEmpleados', 'porcentajeAsistencia', 'asistenciasHoy',
            'faltasInjustificadas', 'faltasJustificadas',
            'diasDescontados', 'horasExtrasSemana',
            'labelsPuestos', 'dataPuestos', 'coloresPuestos',
            'topAsistencia', 'topFaltas'
        ));
    }
}