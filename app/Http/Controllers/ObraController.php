<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Obra;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ObraController extends Controller
{
    protected const POR_PAGINA = 12;

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

        if (!$this->puedeGestionarObras($user)) {
            abort(403, 'No tienes permisos para gestionar obras.');
        }

        $query = Obra::query()
            ->with(['empresa', 'creadoPor'])
            ->visiblesPara($user)
            ->orderByDesc('created_at');

        // Filtros
        if ($request->filled('busqueda')) {
            $busqueda = $request->input('busqueda');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('codigo', 'like', "%{$busqueda}%")
                  ->orWhere('ubicacion', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('empresa_id') && $user->esAdministrador()) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        $obras = $query->paginate(self::POR_PAGINA)->withQueryString();

        // Datos para los filtros
        $empresasQuery = Empresa::query();
        if ($user->esContratista()) {
            $empresasQuery->where('id', $user->empresa_id);
        } elseif (!$user->esAdministrador()) {
            $empresasQuery->where('id', $user->empresa_id);
        }
        $empresas = $empresasQuery->where('estatus', 'activo')->orderBy('nombre')->get();

        return view('obras.index', compact('obras', 'empresas'));
    }

    // =========================================================
    // CREAR OBRA
    // =========================================================
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarObras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'empresa_id'   => 'required|exists:empresas,id',
            'nombre'       => 'required|string|max:255',
            'codigo'       => 'nullable|string|max:50|unique:obras,codigo',
            'ubicacion'    => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'estatus'      => 'required|in:activa,pausada,terminada',
        ]);

        // Contratista: forzar su empresa
        if ($user->esContratista()) {
            $data['empresa_id'] = $user->empresa_id;
        }

        DB::beginTransaction();
        try {
            $obra = Obra::create([
                'empresa_id'            => $data['empresa_id'],
                'nombre'                => $data['nombre'],
                'codigo'                => $data['codigo'] ?? null,
                'ubicacion'             => $data['ubicacion'] ?? null,
                'fecha_inicio'          => $data['fecha_inicio'] ?? null,
                'fecha_fin'             => $data['fecha_fin'] ?? null,
                'estatus'               => $data['estatus'],
                'creado_por_usuario_id' => $user->id,
            ]);

            BitacoraService::insertar(
                'obra.crear',
                "Obra creada: {$obra->nombre}",
                'App\Models\Obra',
                $obra->id,
                [
                    'nombre'    => $obra->nombre,
                    'codigo'    => $obra->codigo,
                    'empresa'   => $obra->empresa_id,
                    'estatus'   => $obra->estatus,
                ],
                [
                    'empresa_id' => $obra->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $obra->load(['empresa', 'creadoPor']);
            $cardHtml = view('obras._card', ['obra' => $obra])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => 'Obra creada correctamente',
                'obra'      => $obra,
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creando obra', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // MOSTRAR OBRA (JSON)
    // =========================================================
    public function mostrar($id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarObras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $obra = Obra::with(['empresa', 'creadoPor'])->findOrFail($id);

        // Aislamiento
        if (!$user->esAdministrador() && $obra->empresa_id !== $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        return response()->json([
            'success' => true,
            'obra'    => $obra,
        ]);
    }

    // =========================================================
    // ACTUALIZAR OBRA
    // =========================================================
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarObras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $obra = Obra::findOrFail($id);

        // Aislamiento
        if (!$user->esAdministrador() && $obra->empresa_id !== $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'empresa_id'   => 'required|exists:empresas,id',
            'nombre'       => 'required|string|max:255',
            'codigo'       => 'nullable|string|max:50|unique:obras,codigo,' . $id,
            'ubicacion'    => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'estatus'      => 'required|in:activa,pausada,terminada',
        ]);

        // Contratista: no puede mover de empresa
        if ($user->esContratista()) {
            $data['empresa_id'] = $user->empresa_id;
        }

        DB::beginTransaction();
        try {
            $antes = [
                'nombre'       => $obra->nombre,
                'codigo'       => $obra->codigo,
                'ubicacion'    => $obra->ubicacion,
                'fecha_inicio' => optional($obra->fecha_inicio)->format('Y-m-d'),
                'fecha_fin'    => optional($obra->fecha_fin)->format('Y-m-d'),
                'estatus'      => $obra->estatus,
            ];

            $obra->update($data);

            BitacoraService::actualizar(
                'obra.editar',
                "Obra actualizada: {$obra->nombre}",
                'App\Models\Obra',
                $obra->id,
                $antes,
                [
                    'nombre'       => $obra->nombre,
                    'codigo'       => $obra->codigo,
                    'ubicacion'    => $obra->ubicacion,
                    'fecha_inicio' => optional($obra->fecha_inicio)->format('Y-m-d'),
                    'fecha_fin'    => optional($obra->fecha_fin)->format('Y-m-d'),
                    'estatus'      => $obra->estatus,
                ],
                [
                    'empresa_id' => $obra->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $obra->load(['empresa', 'creadoPor']);
            $cardHtml = view('obras._card', ['obra' => $obra])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => 'Obra actualizada correctamente',
                'obra'      => $obra,
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // CAMBIAR ESTATUS: PAUSAR
    // =========================================================
    public function pausar(Request $request, $id)
    {
        return $this->cambiarEstatus($request, $id, 'pausada', 'obra.pausar', 'Obra pausada');
    }

    // =========================================================
    // CAMBIAR ESTATUS: ACTIVAR
    // =========================================================
    public function activar(Request $request, $id)
    {
        return $this->cambiarEstatus($request, $id, 'activa', 'obra.activar', 'Obra activada');
    }

    // =========================================================
    // CAMBIAR ESTATUS: TERMINAR
    // =========================================================
    public function terminar(Request $request, $id)
    {
        return $this->cambiarEstatus($request, $id, 'terminada', 'obra.terminar', 'Obra terminada');
    }

    // =========================================================
    // HELPER: CAMBIAR ESTATUS
    // =========================================================
    protected function cambiarEstatus(Request $request, $id, string $nuevoEstatus, string $accion, string $mensaje)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarObras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $obra = Obra::findOrFail($id);

        // Aislamiento
        if (!$user->esAdministrador() && $obra->empresa_id !== $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $estatusAnterior = $obra->estatus;

        DB::beginTransaction();
        try {
            $obra->update(['estatus' => $nuevoEstatus]);

            BitacoraService::actualizar(
                $accion,
                "{$mensaje}: {$obra->nombre}",
                'App\Models\Obra',
                $obra->id,
                ['estatus' => $estatusAnterior],
                ['estatus' => $nuevoEstatus],
                [
                    'empresa_id' => $obra->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $obra->load(['empresa', 'creadoPor']);
            $cardHtml = view('obras._card', ['obra' => $obra])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => $mensaje,
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ELIMINAR OBRA
    // =========================================================
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarObras($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $obra = Obra::findOrFail($id);

        // Aislamiento
        if (!$user->esAdministrador() && $obra->empresa_id !== $user->empresa_id) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        // No se puede eliminar si tiene empleados
        if ($obra->empleados()->count() > 0) {
            return response()->json([
                'success' => false,
                'error'   => 'No puedes eliminar esta obra porque tiene empleados asociados.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $datosAntes = [
                'nombre'    => $obra->nombre,
                'codigo'    => $obra->codigo,
                'empresa'   => $obra->empresa_id,
                'estatus'   => $obra->estatus,
            ];

            BitacoraService::eliminar(
                'obra.eliminar',
                "Obra eliminada: {$obra->nombre}",
                'App\Models\Obra',
                $obra->id,
                $datosAntes,
                [
                    'empresa_id' => $obra->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            $obra->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => 'Obra eliminada permanentemente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // CARD HTML
    // =========================================================
    public function card($id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarObras($user)) {
            abort(403);
        }

        $obra = Obra::with(['empresa', 'creadoPor'])->findOrFail($id);

        if (!$user->esAdministrador() && $obra->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        return view('obras._card', ['obra' => $obra]);
    }

    // =========================================================
    // HELPER PROTEGIDO
    // =========================================================
    protected function puedeGestionarObras($user): bool
    {
        return $user->esAdministrador() || $user->esContratista();
    }
}