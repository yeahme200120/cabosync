<?php

namespace App\Http\Controllers;

use App\Exports\UsuarioTemplateExport;
use App\Imports\UsuarioImport;
use App\Mail\UsuarioBienvenidaMail;
use App\Models\BitacoraAccion;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UsuarioController extends Controller
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

        if (!$this->puedeGestionarUsuarios($user)) {
            abort(403, 'No tienes permisos para gestionar usuarios.');
        }

        // Filtros
        $query = User::query()
            ->with(['empresa', 'rol'])
            ->orderBy('nombre');

        // Aislamiento por rol
        if ($user->esContratista()) {
            $query->where('empresa_id', $user->empresa_id)
                ->ocultarAdmin();
        }

        // Filtros opcionales
        if ($request->filled('busqueda')) {
            $busqueda = $request->input('busqueda');
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('empresa_id') && $user->esAdministrador()) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->input('rol_id'));
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        $usuarios = $query->paginate(self::POR_PAGINA)->withQueryString();

        // Datos para los filtros
        $empresasQuery = Empresa::query();
        if ($user->esContratista()) {
            $empresasQuery->where('id', $user->empresa_id);
        }
        $empresas = $empresasQuery->where('estatus', 'activo')->orderBy('nombre')->get();

        // Roles permitidos según el usuario
        $rolesPermitidos = $this->rolesDisponibles($user);

        // Usuarios con sesión activa en los últimos 5 minutos
        $timestampLimite = now()->subMinutes(5)->timestamp;
        $usuariosEnLinea = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $timestampLimite)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        return view('usuarios.index', compact(
            'usuarios',
            'empresas',
            'rolesPermitidos',
            'usuariosEnLinea'   // <-- NUEVO
        ));
    }

    // =========================================================
    // CREAR USUARIO
    // =========================================================
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'nombre'     => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'rol_id'     => 'required|exists:roles,id',
            'empresa_id' => 'nullable|exists:empresas,id',
            'estatus'    => 'required|in:activo,inactivo',
        ]);

        // Validar que el rol sea permitido
        $rolesPermitidos = $this->rolesDisponibles($user)->pluck('id')->toArray();
        if (!in_array((int) $data['rol_id'], $rolesPermitidos, true)) {
            return response()->json(['success' => false, 'error' => 'Rol no permitido'], 403);
        }

        // Contratista: forzar su empresa
        if ($user->esContratista()) {
            $data['empresa_id'] = $user->empresa_id;
        }

        // Generar contraseña temporal
        $passwordTemporal = $this->generarPasswordTemporal();

        DB::beginTransaction();
        try {
            $nuevoUsuario = User::create([
                'nombre'     => $data['nombre'],
                'email'      => $data['email'],
                'password'   => Hash::make($passwordTemporal),
                'rol_id'     => $data['rol_id'],
                'empresa_id' => $data['empresa_id'] ?? null,
                'estatus'    => $data['estatus'],
            ]);

            // Enviar correo con credenciales
            try {
                Mail::to($nuevoUsuario->email)->send(new UsuarioBienvenidaMail(
                    $nuevoUsuario,
                    $passwordTemporal,
                    $user->nombre
                ));
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar correo de bienvenida', [
                    'user_id' => $nuevoUsuario->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            // Bitácora
            BitacoraService::insertar(
                'usuario.crear',
                "Usuario creado: {$nuevoUsuario->nombre} ({$nuevoUsuario->email})",
                'App\Models\User',
                $nuevoUsuario->id,
                [
                    'nombre'  => $nuevoUsuario->nombre,
                    'email'   => $nuevoUsuario->email,
                    'rol_id'  => $nuevoUsuario->rol_id,
                    'empresa' => $nuevoUsuario->empresa_id,
                ],
                [
                    'empresa_id' => $nuevoUsuario->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            // Devolver la card HTML del nuevo usuario + contraseña temporal
            $nuevoUsuario->load(['empresa', 'rol']);
            $cardHtml = view('usuarios._card', ['usuario' => $nuevoUsuario])->render();

            return response()->json([
                'success'            => true,
                'mensaje'            => 'Usuario creado correctamente',
                'usuario'            => $nuevoUsuario,
                'card_html'          => $cardHtml,
                'password_temporal'  => $passwordTemporal,
                'correo_enviado'     => !empty(config('mail.mailers.smtp.host')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando usuario', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // MOSTRAR USUARIO (JSON)
    // =========================================================
    public function mostrar($id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $usuario = User::with(['empresa', 'rol'])->findOrFail($id);

        // Aislamiento
        if ($user->esContratista()) {
            if ($usuario->empresa_id !== $user->empresa_id || $usuario->esAdministrador()) {
                return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'usuario' => $usuario,
        ]);
    }

    // =========================================================
    // ACTUALIZAR USUARIO
    // =========================================================
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $usuario = User::findOrFail($id);

        // Aislamiento
        if ($user->esContratista()) {
            if ($usuario->empresa_id !== $user->empresa_id || $usuario->esAdministrador()) {
                return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
            }
        }

        $data = $request->validate([
            'nombre'     => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $id,
            'rol_id'     => 'required|exists:roles,id',
            'empresa_id' => 'nullable|exists:empresas,id',
            'estatus'    => 'required|in:activo,inactivo',
        ]);

        // Validar rol permitido
        $rolesPermitidos = $this->rolesDisponibles($user)->pluck('id')->toArray();
        if (!in_array((int) $data['rol_id'], $rolesPermitidos, true)) {
            return response()->json(['success' => false, 'error' => 'Rol no permitido'], 403);
        }

        // Contratista: forzar su empresa
        if ($user->esContratista()) {
            $data['empresa_id'] = $user->empresa_id;
        }

        DB::beginTransaction();
        try {
            $antes = [
                'nombre'     => $usuario->nombre,
                'email'      => $usuario->email,
                'rol_id'     => $usuario->rol_id,
                'empresa_id' => $usuario->empresa_id,
                'estatus'    => $usuario->estatus,
            ];

            $usuario->update($data);

            $despues = [
                'nombre'     => $usuario->nombre,
                'email'      => $usuario->email,
                'rol_id'     => $usuario->rol_id,
                'empresa_id' => $usuario->empresa_id,
                'estatus'    => $usuario->estatus,
            ];

            BitacoraService::actualizar(
                'usuario.editar',
                "Usuario actualizado: {$usuario->nombre} ({$usuario->email})",
                'App\Models\User',
                $usuario->id,
                $antes,
                $despues,
                [
                    'empresa_id' => $usuario->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $usuario->load(['empresa', 'rol']);
            $cardHtml = view('usuarios._card', ['usuario' => $usuario])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => 'Usuario actualizado correctamente',
                'usuario'   => $usuario,
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // DESACTIVAR USUARIO (soft)
    // =========================================================
    public function desactivar(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $usuario = User::findOrFail($id);

        // Aislamiento
        if ($user->esContratista()) {
            if ($usuario->empresa_id !== $user->empresa_id || $usuario->esAdministrador()) {
                return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
            }
        }

        // No puede desactivarse a sí mismo
        if ($usuario->id === $user->id) {
            return response()->json(['success' => false, 'error' => 'No puedes desactivar tu propio usuario'], 422);
        }

        DB::beginTransaction();
        try {
            $usuario->update(['estatus' => 'inactivo']);

            BitacoraService::actualizar(
                'usuario.desactivar',
                "Usuario desactivado: {$usuario->nombre} ({$usuario->email})",
                'App\Models\User',
                $usuario->id,
                ['estatus' => 'activo'],
                ['estatus' => 'inactivo'],
                [
                    'empresa_id' => $usuario->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            DB::commit();

            $usuario->load(['empresa', 'rol']);
            $cardHtml = view('usuarios._card', ['usuario' => $usuario])->render();

            return response()->json([
                'success'   => true,
                'mensaje'   => 'Usuario desactivado',
                'card_html' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ELIMINAR USUARIO (hard delete)
    // =========================================================
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $usuario = User::findOrFail($id);

        // Aislamiento
        if ($user->esContratista()) {
            if ($usuario->empresa_id !== $user->empresa_id || $usuario->esAdministrador()) {
                return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
            }
        }

        // No puede eliminarse a sí mismo
        if ($usuario->id === $user->id) {
            return response()->json(['success' => false, 'error' => 'No puedes eliminar tu propio usuario'], 422);
        }

        DB::beginTransaction();
        try {
            $datosAntes = [
                'nombre'     => $usuario->nombre,
                'email'      => $usuario->email,
                'rol_id'     => $usuario->rol_id,
                'empresa_id' => $usuario->empresa_id,
            ];

            BitacoraService::eliminar(
                'usuario.eliminar',
                "Usuario eliminado: {$usuario->nombre} ({$usuario->email})",
                'App\Models\User',
                $usuario->id,
                $datosAntes,
                [
                    'empresa_id' => $usuario->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );

            $usuario->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => 'Usuario eliminado permanentemente',
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

        if (!$this->puedeGestionarUsuarios($user)) {
            abort(403);
        }

        $usuario = User::with(['empresa', 'rol'])->findOrFail($id);

        return view('usuarios._card', ['usuario' => $usuario]);
    }

    // =========================================================
    // RESET PASSWORD (envío por correo)
    // =========================================================
    public function resetPassword(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $usuario = User::findOrFail($id);

        // Aislamiento
        if ($user->esContratista()) {
            if ($usuario->empresa_id !== $user->empresa_id || $usuario->esAdministrador()) {
                return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
            }
        }

        // Generar nueva contraseña temporal
        $passwordTemporal = $this->generarPasswordTemporal();

        DB::beginTransaction();
        try {
            $usuario->update(['password' => Hash::make($passwordTemporal)]);

            // Enviar correo
            try {
                Mail::to($usuario->email)->send(new UsuarioBienvenidaMail(
                    $usuario,
                    $passwordTemporal,
                    $user->nombre,
                    true // esReset
                ));
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar correo de reset', ['user_id' => $usuario->id]);
            }

            BitacoraService::actualizar(
                'usuario.reset_password',
                "Contraseña reseteada para: {$usuario->nombre} ({$usuario->email})",
                'App\Models\User',
                $usuario->id,
                [],
                [],
                [
                    'empresa_id' => $usuario->empresa_id,
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
                'success'           => true,
                'mensaje'           => 'Contraseña reseteada y enviada por correo',
                'password_temporal' => $passwordTemporal,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // IMPERSONAR (login como otro usuario)
    // =========================================================
    public function impersonar(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->esAdministrador()) {
            return response()->json(['success' => false, 'error' => 'Solo Admin puede impersonar'], 403);
        }

        $usuario = User::findOrFail($id);

        if ($usuario->id === $user->id) {
            return response()->json(['success' => false, 'error' => 'No puedes impersonar tu propio usuario'], 422);
        }

        // Guardar el ID del admin original en sesión
        session(['impersonate_admin_id' => $user->id]);
        session()->forget('impersonate_user_id');

        // Login como el otro usuario
        Auth::login($usuario);

        BitacoraService::insertar(
            'usuario.impersonar',
            "Admin {$user->nombre} está impersonando a {$usuario->nombre}",
            'App\Models\User',
            $usuario->id,
            [],
            [
                'empresa_id' => $usuario->empresa_id,
                'geo'        => [
                    'lat'       => $request->header('X-Geo-Lat'),
                    'lng'       => $request->header('X-Geo-Lng'),
                    'precision' => $request->header('X-Geo-Precision'),
                ],
                'device_id'  => $request->header('X-Device-Id'),
            ]
        );

        return response()->json([
            'success'    => true,
            'redirect_to' => route('dashboard'),
        ]);
    }

    // =========================================================
    // DEJAR DE IMPERSONAR
    // =========================================================
    public function dejarImpersonar(Request $request)
    {
        $adminId = session('impersonate_admin_id');

        if (!$adminId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($adminId);

        if ($admin) {
            Auth::login($admin);

            BitacoraService::insertar(
                'usuario.dejar_impersonar',
                "Admin {$admin->nombre} dejó de impersonar",
                'App\Models\User',
                $admin->id,
                [],
                [
                    'empresa_id' => $admin->empresa_id,
                    'geo'        => [
                        'lat'       => $request->header('X-Geo-Lat'),
                        'lng'       => $request->header('X-Geo-Lng'),
                        'precision' => $request->header('X-Geo-Precision'),
                    ],
                    'device_id'  => $request->header('X-Device-Id'),
                ]
            );
        }

        session()->forget('impersonate_admin_id');

        return redirect()->route('usuarios.index');
    }

    // =========================================================
    // IMPORTAR USUARIOS (CSV/Excel)
    // =========================================================
    public function importar(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            $import = new UsuarioImport($user);
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('archivo'));

            $resultado = $import->getResultado();

            BitacoraService::insertar(
                'usuario.importar',
                "Importación de usuarios: {$resultado['resumen']['cargados']} cargados, {$resultado['resumen']['actualizados']} actualizados",
                'App\Models\User',
                0,
                $resultado['resumen'],
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

            return response()->json([
                'success' => true,
                'resumen' => $resultado['resumen'],
                'cargados' => $resultado['cargados'],
                'actualizados' => $resultado['actualizados'],
                'omitidos' => $resultado['omitidos'],
                'errores' => $resultado['errores'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error importando usuarios', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // IMPORTAR USUARIOS SQL
    // =========================================================
    public function importarSQL(Request $request)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $request->validate([
            'archivo_sql' => 'required|file|max:10240',
        ]);

        try {
            $contenido = file_get_contents($request->file('archivo_sql')->getRealPath());

            $import = new UsuarioImport($user, true);
            $import->procesarSQL($contenido);

            $resultado = $import->getResultado();

            BitacoraService::insertar(
                'usuario.importar_sql',
                "Importación SQL de usuarios: {$resultado['resumen']['cargados']} cargados",
                'App\Models\User',
                0,
                $resultado['resumen'],
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

            return response()->json([
                'success' => true,
                'resumen' => $resultado['resumen'],
                'cargados' => $resultado['cargados'],
                'actualizados' => $resultado['actualizados'],
                'omitidos' => $resultado['omitidos'],
                'errores' => $resultado['errores'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error importando usuarios SQL', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // PLANTILLAS
    // =========================================================
    public function descargarPlantilla()
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            abort(403);
        }

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-usuarios.csv"',
        ];

        $columnas = ['NOMBRE', 'EMAIL', 'ROL_CODIGO', 'ESTATUS'];

        $callback = function () use ($columnas) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($file, $columnas);
            fputcsv($file, ['JUAN PEREZ', 'juan@empresa.com', 'jefe_obra', 'activo']);
            fputcsv($file, ['MARIA LOPEZ', 'maria@empresa.com', 'seguridad', 'activo']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function descargarPlantillaExcel()
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            abort(403);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new UsuarioTemplateExport(),
            'plantilla-usuarios.xlsx'
        );
    }
    // =========================================================
    // HISTORIAL DE ACCIONES DEL USUARIO
    // =========================================================
    public function historial(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->puedeGestionarUsuarios($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $usuario = User::findOrFail($id);

        // Aislamiento: Contratista solo ve usuarios de su empresa
        if ($user->esContratista()) {
            if ($usuario->empresa_id !== $user->empresa_id || $usuario->esAdministrador()) {
                return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
            }
        }

        // Obtener bitácora relacionada con ESTE usuario (como actor o como afectado)
        $registros = \App\Models\BitacoraAccion::where(function ($q) use ($usuario) {
            $q->where('usuario_id', $usuario->id)
                ->orWhere(function ($q2) use ($usuario) {
                    $q2->where('modelo_afectado', 'App\Models\User')
                        ->where('modelo_id', $usuario->id);
                });
        })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $registrosMapeados = $registros->map(function ($r) {
            return [
                'id'            => $r->id,
                'accion'        => $r->accion,
                'descripcion'   => $r->descripcion,
                'tipo_accion'   => $r->tipo_accion,
                'fecha'         => optional($r->created_at)->format('d/m/Y H:i'),
                'fecha_humana'  => optional($r->created_at)->diffForHumans(),
                'datos_antes'   => $r->datos_antes,
                'datos_despues' => $r->datos_despues,
                'actor_nombre'  => optional($r->usuario)->nombre ?? 'Sistema',
                'es_publico'    => (bool) $r->es_publico,
                'direccion_ip'  => $r->direccion_ip,
                'latitud'       => $r->latitud,
                'longitud'      => $r->longitud,
            ];
        });

        return response()->json([
            'success'   => true,
            'usuario'   => [
                'id'     => $usuario->id,
                'nombre' => $usuario->nombre,
                'email'  => $usuario->email,
            ],
            'registros' => $registrosMapeados,
        ]);
    }
    // =========================================================
    // HELPERS PROTEGIDOS
    // =========================================================

    protected function puedeGestionarUsuarios($user): bool
    {
        return $user->esAdministrador() || $user->esContratista();
    }

    protected function rolesDisponibles($user)
    {
        $query = Rol::where('tipo', 'sistema')->orderBy('nombre');

        if ($user->esContratista()) {
            // Contratista NO puede asignar 'admin'
            $query->where('codigo', '!=', 'admin');
        }

        return $query->get();
    }

    protected function generarPasswordTemporal(): string
    {
        return Str::random(10) . rand(10, 99);
    }
}
