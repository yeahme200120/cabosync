<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\HoraExtra;
use App\Models\Obra;
use App\Services\BitacoraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HoraExtraController extends Controller
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
        $user = Auth::user();

        if (!$this->puedeVerHorasExtras($user)) {
            abort(403, 'No tienes permisos para ver horas extras.');
        }

        // Empresas visibles
        $empresasQuery = Empresa::where('estatus', 'activo')->orderBy('nombre');
        if ($user->esContratista()) {
            $empresasQuery->where('id', $user->empresa_id);
        }
        $empresas = $empresasQuery->get(['id', 'nombre']);

        // Obras visibles
        $obrasQuery = Obra::where('estatus', 'activa')->orderBy('nombre');
        if ($user->esContratista()) {
            $obrasQuery->where('empresa_id', $user->empresa_id);
        }
        $obras = $obrasQuery->get(['id', 'nombre', 'empresa_id']);

        // Semana actual por defecto
        $weekInput = $request->input('week', now()->format('o-\WW'));
        $semana = $this->calcularSemana($weekInput);

        // KPIs
        $queryBase = HoraExtra::query()->visiblesPara($user)
            ->enRangoFecha($semana['inicio'], $semana['fin']);

        if ($request->filled('empresa_id') && $user->esAdministrador()) {
            $queryBase->deEmpresa((int) $request->input('empresa_id'));
        }

        if ($request->filled('obra_id')) {
            $queryBase->whereHas('empleado', fn($q) => $q->where('obra_id', $request->input('obra_id')));
        }

        $kpis = [
            'total'       => (clone $queryBase)->count(),
            'solicitadas' => (clone $queryBase)->sum('horas_solicitadas'),
            'aprobadas'   => (clone $queryBase)->where('estado', 'aprobado')->sum('horas_aprobadas'),
            'pendientes'  => (clone $queryBase)->where('estado', 'pendiente')->count(),
            'rechazadas'  => (clone $queryBase)->where('estado', 'rechazado')->count(),
        ];

        return view('horas-extras.index', compact(
            'empresas',
            'obras',
            'semana',
            'weekInput',
            'kpis'
        ));
    }

    // =========================================================
    // HISTORIAL (JSON agrupado por empleado)
    // =========================================================
    public function historial(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeVerHorasExtras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $weekInput = $request->input('week', now()->format('o-\WW'));
        $semana = $this->calcularSemana($weekInput);

        $query = HoraExtra::with(['empleado.empresa', 'empleado.obra', 'empleado.rol', 'aprobadoPor'])
            ->visiblesPara($user)
            ->enRangoFecha($semana['inicio'], $semana['fin'])
            ->orderBy('empleado_id')
            ->orderBy('fecha');

        if ($request->filled('empresa_id') && $user->esAdministrador()) {
            $query->deEmpresa((int) $request->input('empresa_id'));
        }

        if ($request->filled('obra_id')) {
            $query->whereHas('empleado', fn($q) => $q->where('obra_id', $request->input('obra_id')));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $registros = $query->get();

        // Agrupar por empleado
        $agrupados = [];
        foreach ($registros as $r) {
            $empId = $r->empleado_id;
            if (!isset($agrupados[$empId])) {
                $agrupados[$empId] = [
                    'empleado' => [
                        'id'      => $r->empleado->id,
                        'nombre'  => trim($r->empleado->nombre . ' ' . $r->empleado->apellido),
                        'puesto'  => $r->empleado->puesto_cargo,
                        'empresa' => $r->empleado->empresa?->nombre,
                        'obra'    => $r->empleado->obra?->nombre,
                    ],
                    'dias'    => [],
                    'totales' => ['solicitadas' => 0, 'aprobadas' => 0],
                ];
            }

            // 1=lunes ... 6=sábado
            $diaSemana = Carbon::parse($r->fecha)->dayOfWeekIso;

            $agrupados[$empId]['dias'][$diaSemana] = [
                'id'                => $r->id,
                'fecha'             => $r->fecha->format('Y-m-d'),
                'horas_solicitadas' => (int) $r->horas_solicitadas,
                'horas_aprobadas'   => (int) $r->horas_aprobadas,
                'estado'            => $r->estado,
                'badge_estado'      => $r->badgeEstado(),
                'texto_estado'      => $r->textoEstado(),
                'aprobado_por'      => $r->aprobadoPor?->nombre,
            ];

            $agrupados[$empId]['totales']['solicitadas'] += (int) $r->horas_solicitadas;
            $agrupados[$empId]['totales']['aprobadas']   += (int) $r->horas_aprobadas;
        }

        return response()->json([
            'success'   => true,
            'semana'    => $semana,
            'registros' => array_values($agrupados),
        ]);
    }

    // =========================================================
    // APROBAR INDIVIDUAL
    // =========================================================
    public function aprobar(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeAprobarHorasExtras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $horaExtra = HoraExtra::with('empleado')->findOrFail($id);

        if (!$user->esAdministrador() && $horaExtra->empleado->empresa_id !== $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        if (!$horaExtra->estaPendiente()) {
            return response()->json(['success' => false, 'error' => 'Solo se pueden aprobar solicitudes pendientes.'], 422);
        }

        DB::beginTransaction();
        try {
            $horas = (int) $request->input('horas_aprobadas', $horaExtra->horas_solicitadas);

            $horaExtra->update([
                'horas_aprobadas'         => $horas,
                'estado'                  => 'aprobado',
                'aprobado_por_usuario_id' => $user->id,
            ]);

            BitacoraService::actualizar(
                'hora_extra.aprobar',
                "Horas extras aprobadas para {$horaExtra->empleado->nombre} - {$horas}h",
                'App\Models\HoraExtra',
                $horaExtra->id,
                ['estado' => 'pendiente', 'horas_aprobadas' => 0],
                ['estado' => 'aprobado', 'horas_aprobadas' => $horas],
                [
                    'empresa_id' => $horaExtra->empleado->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            return response()->json(['success' => true, 'mensaje' => "Horas aprobadas: {$horas}h"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // RECHAZAR INDIVIDUAL
    // =========================================================
    public function rechazar(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeAprobarHorasExtras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $horaExtra = HoraExtra::with('empleado')->findOrFail($id);

        if (!$user->esAdministrador() && $horaExtra->empleado->empresa_id !== $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        if (!$horaExtra->estaPendiente()) {
            return response()->json(['success' => false, 'error' => 'Solo se pueden rechazar solicitudes pendientes.'], 422);
        }

        DB::beginTransaction();
        try {
            $horaExtra->update([
                'horas_aprobadas'         => 0,
                'estado'                  => 'rechazado',
                'aprobado_por_usuario_id' => $user->id,
            ]);

            BitacoraService::actualizar(
                'hora_extra.rechazar',
                "Horas extras rechazadas para {$horaExtra->empleado->nombre}",
                'App\Models\HoraExtra',
                $horaExtra->id,
                ['estado' => 'pendiente'],
                ['estado' => 'rechazado'],
                [
                    'empresa_id' => $horaExtra->empleado->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            return response()->json(['success' => true, 'mensaje' => 'Horas rechazadas']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // APROBAR MASIVO
    // =========================================================
    public function aprobarMasivo(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeAprobarHorasExtras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'error' => 'Sin selección'], 422);
        }

        DB::beginTransaction();
        try {
            $query = HoraExtra::whereIn('id', $ids)->pendientes();

            if (!$user->esAdministrador()) {
                $query->whereHas('empleado', fn($q) => $q->where('empresa_id', $user->empresa_id));
            }

            $registros = $query->get();
            $total = 0;

            foreach ($registros as $r) {
                $r->update([
                    'horas_aprobadas'         => $r->horas_solicitadas,
                    'estado'                  => 'aprobado',
                    'aprobado_por_usuario_id' => $user->id,
                ]);
                $total++;
            }

            BitacoraService::insertar(
                'hora_extra.aprobar_masivo',
                "Aprobación masiva: {$total} registros",
                'App\Models\HoraExtra',
                0,
                ['total' => $total],
                ['empresa_id' => $user->empresa_id]
            );

            DB::commit();
            return response()->json(['success' => true, 'mensaje' => "Se aprobaron {$total} registros"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // RECHAZAR MASIVO
    // =========================================================
    public function rechazarMasivo(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeAprobarHorasExtras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'error' => 'Sin selección'], 422);
        }

        DB::beginTransaction();
        try {
            $query = HoraExtra::whereIn('id', $ids)->pendientes();

            if (!$user->esAdministrador()) {
                $query->whereHas('empleado', fn($q) => $q->where('empresa_id', $user->empresa_id));
            }

            $registros = $query->get();
            $total = 0;

            foreach ($registros as $r) {
                $r->update([
                    'horas_aprobadas'         => 0,
                    'estado'                  => 'rechazado',
                    'aprobado_por_usuario_id' => $user->id,
                ]);
                $total++;
            }

            BitacoraService::insertar(
                'hora_extra.rechazar_masivo',
                "Rechazo masivo: {$total} registros",
                'App\Models\HoraExtra',
                0,
                ['total' => $total],
                ['empresa_id' => $user->empresa_id]
            );

            DB::commit();
            return response()->json(['success' => true, 'mensaje' => "Se rechazaron {$total} registros"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================
    protected function calcularSemana(string $weekInput): array
    {
        if (!preg_match('/^(\d{4})-W(\d{1,2})$/', $weekInput, $m)) {
            $weekInput = now()->format('o-\WW');
            preg_match('/^(\d{4})-W(\d{1,2})$/', $weekInput, $m);
        }

        $anio   = (int) $m[1];
        $semana = (int) $m[2];

        $lunes  = Carbon::now()->setISODate($anio, $semana)->startOfDay();
        $sabado = $lunes->copy()->addDays(5);

        return [
            'inicio' => $lunes->format('Y-m-d'),
            'fin'    => $sabado->format('Y-m-d'),
            'week'   => $weekInput,
            'texto'  => $lunes->format('d/m/Y') . ' al ' . $sabado->format('d/m/Y'),
        ];
    }

    protected function puedeVerHorasExtras($user): bool
    {
        return $user->esAdministrador() || $user->esContratista();
    }

    protected function puedeAprobarHorasExtras($user): bool
    {
        return $user->esAdministrador() || $user->esContratista();
    }
}