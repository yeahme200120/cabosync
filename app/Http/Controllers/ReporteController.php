<?php

namespace App\Http\Controllers;

use App\Mail\ReporteSemanalMail;
use App\Models\AsistenciaFinal;
use App\Models\BitacoraAccion;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\ReporteToken;
use App\Services\BitacoraService;
use App\Services\ReporteSemanalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReporteController extends Controller
{
    protected const HORAS_VIGENCIA_TOKEN = 48;

    protected ReporteSemanalService $reporteService;

    public function __construct(ReporteSemanalService $reporteService)
    {
        $this->middleware('auth')->except(['descargaPublica', 'descargarArchivo']);
        $this->reporteService = $reporteService;
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Resuelve el empresa_id efectivo.
     * - Contratista/Jefe: SIEMPRE su propia empresa.
     * - Admin: DEBE venir empresa en el request.
     */
    protected function resolverEmpresaId($user, $empresaIdSolicitada): int
    {
        if ($user->esContratista() || $user->esJefeObra()) {
            return (int) $user->empresa_id;
        }

        if (empty($empresaIdSolicitada)) {
            throw new \Exception('Debes seleccionar una empresa para generar el reporte.');
        }

        return (int) $empresaIdSolicitada;
    }

    protected function opcionesBitacora(Request $request, ?int $empresaId = null): array
    {
        return [
            'empresa_id' => $empresaId,
            'geo'        => [
                'lat'       => $request->header('X-Geo-Lat'),
                'lng'       => $request->header('X-Geo-Lng'),
                'precision' => $request->header('X-Geo-Precision'),
            ],
            'device_id'  => $request->header('X-Device-Id'),
        ];
    }

    // =========================================================
    // VISTA PRINCIPAL DE REPORTES
    // =========================================================
    public function index(Request $request)
    {
        $user = $request->user();

        // Empresas visibles según rol
        $empresasQuery = Empresa::query()->where('estatus', 'activo')->orderBy('nombre');
        if ($user->esContratista() || $user->esJefeObra()) {
            $empresasQuery->where('id', $user->empresa_id);
        }
        $empresas = $empresasQuery->get(['id', 'nombre']);

        // Obras visibles según rol
        $obrasQuery = \App\Models\Obra::query()->where('estatus', 'activa')->orderBy('nombre');
        if ($user->esContratista() || $user->esJefeObra()) {
            $obrasQuery->where('empresa_id', $user->empresa_id);
        }
        $obras = $obrasQuery->get(['id', 'nombre', 'empresa_id']);

        return view('reportes.index', compact('empresas', 'obras'));
    }

    // =========================================================
    // HISTORIAL AJAX
    // =========================================================
    public function historial(Request $request)
    {
        $user = $request->user();

        $query = ReporteToken::with(['empresa', 'obra'])
            ->orderByDesc('created_at');

        if ($user->esContratista() || $user->esJefeObra()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        if ($request->filled('empresa_id') && $user->esAdministrador()) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }

        if ($request->filled('obra_id')) {
            $query->where('obra_id', $request->input('obra_id'));
        }

        if ($request->filled('week')) {
            $query->where('week', $request->input('week'));
        }

        if ($request->filled('estado')) {
            if ($request->input('estado') === 'vigentes') {
                $query->where('expira_en', '>', now());
            } elseif ($request->input('estado') === 'expirados') {
                $query->where('expira_en', '<=', now());
            }
        }

        $registros = $query->limit(100)->get();

        $registrosMapeados = $registros->map(function ($r) {
            return [
                'id'            => $r->id,
                'empresa'       => $r->empresa?->nombre ?? '—',
                'obra'          => $r->obra?->nombre ?? 'Todas',
                'week'          => $r->week,
                'creado'        => $r->created_at->format('d/m/Y H:i'),
                'creado_humano' => $r->created_at->diffForHumans(),
                'expira'        => $r->expira_en ? $r->expira_en->format('d/m/Y H:i') : '—',
                'esta_vigente'  => $r->estaVigente(),
                'descargas'     => $r->descargas,
                'link_publico'  => route('reportes.publico.descarga', $r->token),
                'link_pdf'      => route('reportes.publico.descargar', [$r->token, 'pdf']),
                'link_excel'    => route('reportes.publico.descargar', [$r->token, 'excel']),
            ];
        });

        return response()->json([
            'success'   => true,
            'registros' => $registrosMapeados,
        ]);
    }

    // =========================================================
    // DESCARGAR PDF / EXCEL
    // =========================================================
    public function descargar(Request $request, string $tipo)
    {
        $user = $request->user();

        $data = $request->validate([
            'week'       => 'required|string',
            'empresa_id' => 'required|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        if (!in_array($tipo, ['pdf', 'excel'])) {
            abort(400, 'Tipo de archivo inválido');
        }

        $empresaId = $this->resolverEmpresaId($user, $data['empresa_id']);

        try {
            // Generar AMBOS archivos
            $pdfPath   = $this->reporteService->generarPdf($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);
            $excelPath = $this->reporteService->generarExcel($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);

            // Crear token con AMBOS paths
            ReporteToken::create([
                'token'      => Str::random(48),
                'empresa_id' => $empresaId,
                'obra_id'    => $data['obra_id'] ?? null,
                'week'       => $data['week'],
                'pdf_path'   => $pdfPath,
                'excel_path' => $excelPath,
                'expira_en'  => now()->addHours(self::HORAS_VIGENCIA_TOKEN),
                'descargas'  => 0,
            ]);

            BitacoraService::insertar(
                'reporte.descargar_' . $tipo,
                "Descarga de reporte {$tipo} - Semana {$data['week']}",
                'App\Models\AsistenciaFinal',
                0,
                [
                    'tipo'       => $tipo,
                    'week'       => $data['week'],
                    'empresa_id' => $empresaId,
                    'obra_id'    => $data['obra_id'] ?? null,
                ],
                $this->opcionesBitacora($request, $empresaId)
            );

            $path = $tipo === 'pdf' ? $pdfPath : $excelPath;
            $rutaAbsoluta = storage_path('app/public/' . $path);

            if (!file_exists($rutaAbsoluta)) {
                abort(404, 'Archivo no encontrado');
            }

            $nombreArchivo = "Reporte-{$data['week']}." . ($tipo === 'pdf' ? 'pdf' : 'xlsx');
            $mime = $tipo === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            return response()->download($rutaAbsoluta, $nombreArchivo, ['Content-Type' => $mime]);

        } catch (\Exception $e) {
            \Log::error('Error ReporteController::descargar', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error al generar: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // GENERAR BLOB (para Web Share API)
    // =========================================================
    public function obtenerBlob(Request $request, string $tipo)
    {
        $user = $request->user();

        $data = $request->validate([
            'week'       => 'required|string',
            'empresa_id' => 'required|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        if (!in_array($tipo, ['pdf', 'excel'])) {
            abort(400, 'Tipo inválido');
        }

        $empresaId = $this->resolverEmpresaId($user, $data['empresa_id']);

        try {
            $path = $tipo === 'pdf'
                ? $this->reporteService->generarPdf($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre)
                : $this->reporteService->generarExcel($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);

            $rutaAbsoluta = storage_path('app/public/' . $path);

            if (!file_exists($rutaAbsoluta)) {
                abort(404, 'Archivo no encontrado');
            }

            $contenido = file_get_contents($rutaAbsoluta);
            $mime = $tipo === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            return response($contenido)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'attachment; filename="Reporte-' . $data['week'] . '.' . ($tipo === 'pdf' ? 'pdf' : 'xlsx') . '"');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // GENERAR LINK TEMPORAL
    // =========================================================
    public function generarLink(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'week'       => 'required|string',
            'empresa_id' => 'required|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        try {
            $empresaId = $this->resolverEmpresaId($user, $data['empresa_id']);

            $pdfPath   = $this->reporteService->generarPdf($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);
            $excelPath = $this->reporteService->generarExcel($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);

            $token = ReporteToken::create([
                'token'      => Str::random(48),
                'empresa_id' => $empresaId,
                'obra_id'    => $data['obra_id'] ?? null,
                'week'       => $data['week'],
                'pdf_path'   => $pdfPath,
                'excel_path' => $excelPath,
                'expira_en'  => now()->addHours(self::HORAS_VIGENCIA_TOKEN),
                'descargas'  => 0,
            ]);

            BitacoraService::insertar(
                'reporte.generar_link',
                "Link temporal generado - Semana {$data['week']}",
                'App\Models\ReporteToken',
                $token->id,
                [
                    'week'       => $data['week'],
                    'empresa_id' => $empresaId,
                    'obra_id'    => $data['obra_id'] ?? null,
                    'expira_en'  => $token->expira_en->toDateTimeString(),
                ],
                $this->opcionesBitacora($request, $empresaId)
            );

            return response()->json([
                'success'   => true,
                'link'      => route('reportes.publico.descarga', $token->token),
                'expira_en' => $token->expira_en->format('d/m/Y H:i'),
                'week'      => $data['week'],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error ReporteController::generarLink', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ENVIAR POR CORREO
    // =========================================================
    public function enviarCorreo(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'week'          => 'required|string',
            'empresa_id'    => 'required|exists:empresas,id',
            'obra_id'       => 'nullable|exists:obras,id',
            'destinatarios' => 'required|string',
            'cc'            => 'nullable|string',
            'mensaje'       => 'nullable|string|max:1000',
        ]);

        $empresaId = $this->resolverEmpresaId($user, $data['empresa_id']);

        $destinatarios = array_filter(array_map('trim', preg_split('/[,;]/', $data['destinatarios'])));
        $cc = !empty($data['cc']) ? array_filter(array_map('trim', preg_split('/[,;]/', $data['cc']))) : [];

        if (empty($destinatarios)) {
            return response()->json(['success' => false, 'error' => 'Debes indicar al menos un destinatario'], 422);
        }

        foreach (array_merge($destinatarios, $cc) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['success' => false, 'error' => "Email inválido: {$email}"], 422);
            }
        }

        try {
            $pdfPath   = $this->reporteService->generarPdf($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);
            $excelPath = $this->reporteService->generarExcel($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);

            $empresa      = $empresaId ? Empresa::find($empresaId) : null;
            $semana       = $this->reporteService->obtenerDatos($empresaId, $data['obra_id'] ?? null, $data['week'])['semana'];
            $semanaTexto  = Carbon::parse($semana['inicio'])->format('d/m/Y') . ' al ' . Carbon::parse($semana['fin'])->format('d/m/Y');

            $mensaje = $data['mensaje'] ?: "Lista de asistencia semana del {$semanaTexto}.";

            Mail::to($destinatarios)
                ->cc($cc)
                ->send(new ReporteSemanalMail(
                    empresaNombre: $empresa?->nombre ?? 'Empresa',
                    semanaTexto: $semanaTexto,
                    mensajePersonalizado: $mensaje,
                    pdfPath: $pdfPath,
                    excelPath: $excelPath,
                    week: $data['week'],
                ));

            // Marcar enviado a RH
            $empleadoIdsQuery = Empleado::query();
            if ($empresaId) {
                $empleadoIdsQuery->where('empresa_id', $empresaId);
            }
            $empleadoIds = $empleadoIdsQuery->pluck('id');

            AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
                ->whereBetween('fecha', [$semana['inicio'], $semana['fin']])
                ->update([
                    'enviado_rh'     => true,
                    'fecha_envio_rh' => now(),
                ]);

            BitacoraService::insertar(
                'reporte.enviar_correo',
                "Reporte enviado a: " . implode(', ', $destinatarios) . " - Semana {$data['week']}",
                'App\Models\AsistenciaFinal',
                0,
                [
                    'destinatarios' => $destinatarios,
                    'cc'            => $cc,
                    'semana'        => $data['week'],
                ],
                $this->opcionesBitacora($request, $empresaId)
            );

            return response()->json([
                'success' => true,
                'mensaje' => 'Correo enviado correctamente a: ' . implode(', ', $destinatarios),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error enviando correo', ['exception' => $e]);
            return response()->json(['success' => false, 'error' => 'Error al enviar: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // DESCARGA PÚBLICA
    // =========================================================
    public function descargaPublica(string $token)
    {
        $reporteToken = ReporteToken::where('token', $token)->firstOrFail();
        return view('reportes.publico.descarga', ['token' => $reporteToken]);
    }

    public function descargarArchivo(Request $request, string $token, string $tipo)
    {
        $reporteToken = ReporteToken::where('token', $token)->firstOrFail();

        /* if ($reporteToken->estaExpirado()) {
            abort(410, 'El enlace ha expirado');
        } */

        if (!in_array($tipo, ['pdf', 'excel'])) {
            abort(400, 'Tipo inválido');
        }

        $path = $tipo === 'pdf' ? $reporteToken->pdf_path : $reporteToken->excel_path;
        $rutaAbsoluta = $path ? storage_path('app/public/' . $path) : null;

        // Si el archivo no existe, regenerarlo
        if (!$rutaAbsoluta || !file_exists($rutaAbsoluta)) {
            $service = app(ReporteSemanalService::class);
            $nuevoPath = $tipo === 'pdf'
                ? $service->generarPdf($reporteToken->empresa_id, $reporteToken->obra_id, $reporteToken->week, 'Sistema')
                : $service->generarExcel($reporteToken->empresa_id, $reporteToken->obra_id, $reporteToken->week, 'Sistema');

            $reporteToken->update([
                $tipo === 'pdf' ? 'pdf_path' : 'excel_path' => $nuevoPath,
            ]);

            $rutaAbsoluta = storage_path('app/public/' . $nuevoPath);
        }

        if (!file_exists($rutaAbsoluta)) {
            abort(404, 'Archivo no encontrado');
        }

        $reporteToken->registrarDescarga();

        BitacoraService::registrar(
            'reporte.descarga_publica',
            "Descarga pública del reporte {$tipo} - Semana {$reporteToken->week}",
            [
                'tipo_accion'     => 'view',
                'modelo_afectado' => 'App\Models\ReporteToken',
                'modelo_id'       => $reporteToken->id,
                'empresa_id'      => $reporteToken->empresa_id,
                'datos_despues'   => [
                    'tipo'      => $tipo,
                    'week'      => $reporteToken->week,
                    'descargas' => $reporteToken->descargas + 1,
                ],
                'geo'             => [
                    'lat'       => $request->header('X-Geo-Lat'),
                    'lng'       => $request->header('X-Geo-Lng'),
                    'precision' => $request->header('X-Geo-Precision'),
                ],
                'device_id'       => $request->header('X-Device-Id'),
            ]
        );

        $nombreArchivo = "Reporte-{$reporteToken->week}." . ($tipo === 'pdf' ? 'pdf' : 'xlsx');
        $mime = $tipo === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()->download($rutaAbsoluta, $nombreArchivo, ['Content-Type' => $mime]);
    }
}