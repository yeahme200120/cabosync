<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
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

        if (!$this->puedeGestionarEmpresas($user)) {
            abort(403, 'No tienes permisos para gestionar empresas.');
        }

        $query = Empresa::query()
            ->with('creadoPor')
            ->visiblesPara($user)
            ->orderBy('nombre');

        // Filtros
        if ($request->filled('busqueda')) {
            $busqueda = $request->input('busqueda');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('rfc', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('tipo') && $user->esAdministrador()) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        $empresas = $query->paginate(self::POR_PAGINA)->withQueryString();

        return view('empresas.index', compact('empresas'));
    }

    // =========================================================
    // CREAR EMPRESA
    // =========================================================
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarEmpresas($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'nombre'  => 'required|string|max:255',
            'rfc'     => 'nullable|string|max:50|unique:empresas,rfc',
            'tipo'    => 'required|in:matriz,externa',
            'estatus' => 'required|in:activo,inactivo',
        ]);

        // Contratista: solo puede crear empresas externas
        if ($user->esContratista() && $data['tipo'] !== 'externa') {
            return response()->json([
                'success' => false,
                'error'   => 'Solo puedes crear empresas de tipo externa.',
            ], 403);
        }

        // Contratista: no puede crear la matriz
        if ($user->esContratista()) {
            $data['tipo'] = 'externa';
        }

        // Validación: solo puede existir UNA empresa matriz
        if ($data['tipo'] === 'matriz' && Empresa::where('tipo', 'matriz')->exists()) {
            return response()->json([
                'success' => false,
                'error'   => 'Ya existe una empresa matriz registrada.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $empresa = Empresa::create([
                'nombre'                 => $data['nombre'],
                'rfc'                    => $data['rfc'] ?? null,
                'tipo'                   => $data['tipo'],
                'estatus'                => $data['estatus'],
                'creado_por_usuario_id'  => $user->id,
            ]);

            BitacoraService::insertar(
                'empresa.crear',
                "Empresa creada: {$empresa->nombre}",
                'App\Models\Empresa',
                $empresa->id,
                [
                    'nombre' => $empresa->nombre,
                    'rfc'    => $empresa->rfc,
                    'tipo'   => $empresa->tipo,
                ],
                [
                    'empresa_id' => $empresa->id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $empresa->load('creadoPor');
            $cardHtml = view('empresas._card', ['empresa' => $empresa])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => 'Empresa creada correctamente',
                'empresa'   => $empresa,
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creando empresa', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // MOSTRAR EMPRESA (JSON)
    // =========================================================
    public function mostrar($id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarEmpresas($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $empresa = Empresa::findOrFail($id);

        // Aislamiento
        if ($user->esContratista() && $empresa->esMatriz()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        return response()->json([
            'success' => true,
            'empresa' => $empresa,
        ]);
    }

    // =========================================================
    // ACTUALIZAR EMPRESA
    // =========================================================
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarEmpresas($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $empresa = Empresa::findOrFail($id);

        // Aislamiento
        if ($user->esContratista() && $empresa->esMatriz()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'nombre'  => 'required|string|max:255',
            'rfc'     => 'nullable|string|max:50|unique:empresas,rfc,' . $id,
            'tipo'    => 'required|in:matriz,externa',
            'estatus' => 'required|in:activo,inactivo',
        ]);

        // Contratista: no puede cambiar el tipo de una empresa
        if ($user->esContratista()) {
            $data['tipo'] = 'externa';
        }

        // Validación: solo puede existir UNA empresa matriz
        if ($data['tipo'] === 'matriz' && $empresa->tipo !== 'matriz') {
            if (Empresa::where('tipo', 'matriz')->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Ya existe otra empresa matriz registrada.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $antes = [
                'nombre'  => $empresa->nombre,
                'rfc'     => $empresa->rfc,
                'tipo'    => $empresa->tipo,
                'estatus' => $empresa->estatus,
            ];

            $empresa->update($data);

            BitacoraService::actualizar(
                'empresa.editar',
                "Empresa actualizada: {$empresa->nombre}",
                'App\Models\Empresa',
                $empresa->id,
                $antes,
                [
                    'nombre'  => $empresa->nombre,
                    'rfc'     => $empresa->rfc,
                    'tipo'    => $empresa->tipo,
                    'estatus' => $empresa->estatus,
                ],
                [
                    'empresa_id' => $empresa->id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $empresa->load('creadoPor');
            $cardHtml = view('empresas._card', ['empresa' => $empresa])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => 'Empresa actualizada correctamente',
                'empresa'   => $empresa,
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // DESACTIVAR EMPRESA (soft)
    // =========================================================
    public function desactivar(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarEmpresas($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $empresa = Empresa::findOrFail($id);

        // Aislamiento
        if ($user->esContratista() && $empresa->esMatriz()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        // No se puede desactivar la empresa matriz
        if ($empresa->esMatriz()) {
            return response()->json([
                'success' => false,
                'error'   => 'No se puede desactivar la empresa matriz.',
            ], 422);
        }

        // No se puede desactivar si tiene usuarios activos
        $usuariosActivos = $empresa->usuarios()->where('estatus', 'activo')->count();
        if ($usuariosActivos > 0) {
            return response()->json([
                'success' => false,
                'error'   => "No puedes desactivar esta empresa porque tiene {$usuariosActivos} usuario(s) activo(s).",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $empresa->update(['estatus' => 'inactivo']);

            BitacoraService::actualizar(
                'empresa.desactivar',
                "Empresa desactivada: {$empresa->nombre}",
                'App\Models\Empresa',
                $empresa->id,
                ['estatus' => 'activo'],
                ['estatus' => 'inactivo'],
                [
                    'empresa_id' => $empresa->id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $empresa->load('creadoPor');
            $cardHtml = view('empresas._card', ['empresa' => $empresa])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => 'Empresa desactivada',
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ELIMINAR EMPRESA (hard delete)
    // =========================================================
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarEmpresas($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $empresa = Empresa::findOrFail($id);

        // Aislamiento
        if ($user->esContratista() && $empresa->esMatriz()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        // No se puede eliminar la empresa matriz
        if ($empresa->esMatriz()) {
            return response()->json([
                'success' => false,
                'error'   => 'No se puede eliminar la empresa matriz.',
            ], 422);
        }

        // No se puede eliminar si tiene usuarios
        if ($empresa->usuarios()->count() > 0) {
            return response()->json([
                'success' => false,
                'error'   => 'No puedes eliminar esta empresa porque tiene usuarios asociados.',
            ], 422);
        }

        // No se puede eliminar si tiene obras
        if ($empresa->obras()->count() > 0) {
            return response()->json([
                'success' => false,
                'error'   => 'No puedes eliminar esta empresa porque tiene obras asociadas.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $datosAntes = [
                'nombre' => $empresa->nombre,
                'rfc'    => $empresa->rfc,
                'tipo'   => $empresa->tipo,
            ];

            BitacoraService::eliminar(
                'empresa.eliminar',
                "Empresa eliminada: {$empresa->nombre}",
                'App\Models\Empresa',
                $empresa->id,
                $datosAntes,
                [
                    'empresa_id' => $empresa->id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            $empresa->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => 'Empresa eliminada permanentemente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // CARD HTML (para actualización en vivo)
    // =========================================================
    public function card($id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarEmpresas($user)) {
            abort(403);
        }

        $empresa = Empresa::with('creadoPor')->findOrFail($id);

        if ($user->esContratista() && $empresa->esMatriz()) {
            abort(403);
        }

        return view('empresas._card', ['empresa' => $empresa]);
    }

    // =========================================================
    // HELPERS PROTEGIDOS
    // =========================================================

    protected function puedeGestionarEmpresas($user): bool
    {
        return $user->esAdministrador() || $user->esContratista();
    }
}