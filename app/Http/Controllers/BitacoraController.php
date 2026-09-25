<?php

namespace App\Http\Controllers;

use App\Models\BitacoraAccion;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BitacoraController extends Controller
{
    protected const POR_PAGINA = 10;

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

        if (!$this->puedeVerBitacora($user)) {
            abort(403, 'No tienes permisos para ver la bitácora.');
        }

        // Query base (sin paginación) para KPIs
        $queryBase = BitacoraAccion::query();

        // Aislamiento: Contratista solo ve acciones de su empresa
        if ($user->esContratista()) {
            $queryBase->where('empresa_id', $user->empresa_id);
        }

        // Aplicar filtros a la query base
        $this->aplicarFiltros($queryBase, $request, $user);

        // KPIs calculados sobre la query filtrada
        $kpis = [
            'total'              => (clone $queryBase)->count(),
            'ips_distintas'      => (clone $queryBase)->whereNotNull('direccion_ip')
                                                      ->distinct('direccion_ip')
                                                      ->count('direccion_ip'),
            'usuarios_distintos' => (clone $queryBase)->whereNotNull('usuario_id')
                                                      ->distinct('usuario_id')
                                                      ->count('usuario_id'),
            'publicas'           => (clone $queryBase)->where('es_publico', true)->count(),
            'con_geo'            => (clone $queryBase)->whereNotNull('latitud')
                                                      ->whereNotNull('longitud')
                                                      ->count(),
            'hoy'                => (clone $queryBase)->whereDate('created_at', today())->count(),
        ];

        // Paginación
        $registros = (clone $queryBase)
            ->with(['usuario', 'empresa'])
            ->orderByDesc('created_at')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        // Datos para filtros
        $usuariosQuery = User::query()->orderBy('nombre');
        if ($user->esContratista()) {
            $usuariosQuery->where('empresa_id', $user->empresa_id);
        }
        $usuariosFiltro = $usuariosQuery->get(['id', 'nombre', 'email']);

        $empresasFiltro = collect();
        if ($user->esAdministrador()) {
            $empresasFiltro = Empresa::orderBy('nombre')->get(['id', 'nombre']);
        }

        return view('bitacora.index', compact(
            'registros',
            'usuariosFiltro',
            'empresasFiltro',
            'kpis'
        ));
    }

    // =========================================================
    // DETALLE (JSON para modal)
    // =========================================================
    public function mostrar($id)
    {
        $user = Auth::user();

        if (!$this->puedeVerBitacora($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $registro = BitacoraAccion::with(['usuario', 'empresa'])->findOrFail($id);

        if ($user->esContratista() && $registro->empresa_id !== $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        return response()->json([
            'success' => true,
            'registro' => [
                'id'              => $registro->id,
                'accion'          => $registro->accion,
                'descripcion'     => $registro->descripcion,
                'tipo_accion'     => $registro->tipo_accion,
                'modelo_afectado' => $registro->modelo_afectado,
                'modelo_id'       => $registro->modelo_id,
                'es_publico'      => (bool) $registro->es_publico,
                'datos_antes'     => $registro->datos_antes,
                'datos_despues'   => $registro->datos_despues,
                'fecha'           => optional($registro->created_at)->format('d/m/Y H:i:s'),
                'fecha_humana'    => optional($registro->created_at)->diffForHumans(),
                'usuario'         => $registro->usuario ? [
                    'id'     => $registro->usuario->id,
                    'nombre' => $registro->usuario->nombre,
                    'email'  => $registro->usuario->email,
                ] : null,
                'empresa'         => $registro->empresa ? [
                    'id'     => $registro->empresa->id,
                    'nombre' => $registro->empresa->nombre,
                    'rfc'    => $registro->empresa->rfc,
                ] : null,
                'direccion_ip'    => $registro->direccion_ip,
                'latitud'         => $registro->latitud,
                'longitud'        => $registro->longitud,
                'precision_geo'   => $registro->precision_geo,
                'device_id'       => $registro->device_id,
                'plataforma'      => $registro->plataforma,
                'navegador'       => $registro->navegador,
                'user_agent'      => $registro->user_agent,
            ],
        ]);
    }

    // =========================================================
    // APLICAR FILTROS
    // =========================================================
    protected function aplicarFiltros($query, Request $request, $user): void
    {
        if ($request->filled('busqueda')) {
            $busqueda = $request->input('busqueda');
            $query->where(function ($q) use ($busqueda) {
                $q->where('descripcion', 'like', "%{$busqueda}%")
                  ->orWhere('accion', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }

        if ($request->filled('tipo_accion')) {
            $query->where('tipo_accion', $request->input('tipo_accion'));
        }

        if ($request->filled('empresa_id') && $user->esAdministrador()) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }

        if ($request->filled('es_publico')) {
            $query->where('es_publico', (bool) $request->input('es_publico'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }
    }

    // =========================================================
    // HELPER
    // =========================================================
    protected function puedeVerBitacora($user): bool
    {
        return $user->esAdministrador() || $user->esContratista();
    }
}