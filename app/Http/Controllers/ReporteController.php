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
     * Devuelve null si el Admin NO selecciona empresa (para que el reporte
     * incluya TODAS las empresas).
     */
    protected function resolverEmpresaId($user, $empresaIdSolicitada): ?int
    {
        // Contratista/Jefe: siempre su empresa
        if ($user->esContratista() || $user->esJefeObra()) {
            return $user->empresa_id;
        }

        // Admin: si selecciona empresa, respetarla
        if (!empty($empresaIdSolicitada)) {
            return (int) $empresaIdSolicitada;
        }

        // Admin sin filtro: null = todas las empresas
        return null;
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
    // DESCARGAR PDF / EXCEL
    // =========================================================
    public function descargar(Request $request, string $tipo)
    {
        $user = $request->user();

        $data = $request->validate([
            'week'       => 'required|string',
            'empresa_id' => 'nullable|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        if (!in_array($tipo, ['pdf', 'excel'])) {
            abort(400, 'Tipo de archivo inválido');
        }

        $empresaId = $this->resolverEmpresaId($user, $data['empresa_id'] ?? null);

        try {
            $path = $tipo === 'pdf'
                ? $this->reporteService->generarPdf($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre)
                : $this->reporteService->generarExcel($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);

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

            $nombreArchivo = "Reporte-{$data['week']}." . ($tipo === 'pdf' ? 'pdf' : 'xlsx');
            $mime = $tipo === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            $rutaAbsoluta = storage_path('app/public/' . $path);

            if (!file_exists($rutaAbsoluta)) {
                abort(404, 'Archivo no encontrado');
            }

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
            'empresa_id' => 'nullable|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        if (!in_array($tipo, ['pdf', 'excel'])) {
            abort(400, 'Tipo inválido');
        }

        $empresaId = $this->resolverEmpresaId($user, $data['empresa_id'] ?? null);

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
            'empresa_id' => 'nullable|exists:empresas,id',
            'obra_id'    => 'nullable|exists:obras,id',
        ]);

        $empresaId = $this->resolverEmpresaId($user, $data['empresa_id'] ?? null);

        try {
            $pdfPath   = $this->reporteService->generarPdf($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);
            $excelPath = $this->reporteService->generarExcel($empresaId, $data['obra_id'] ?? null, $data['week'], $user->nombre);

            // Si empresaId es null, usar 0 para el registro del token (o la primera empresa real)
            $empresaParaToken = $empresaId ?? Empresa::whereHas('empleados', function ($q) {
                $q->where('estatus', 'activo');
            })->value('id') ?? Empresa::value('id');

            $token = ReporteToken::create([
                'token'      => Str::random(48),
                'empresa_id' => $empresaParaToken,
                'obra_id'    => $data['obra_id'] ?? null,
                'week'       => $data['week'],
                'pdf_path'   => $pdfPath,
                'excel_path' => $excelPath,
                'expira_en'  => now()->addHours(24),
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
            'empresa_id'    => 'nullable|exists:empresas,id',
            'obra_id'       => 'nullable|exists:obras,id',
            'destinatarios' => 'required|string',
            'cc'            => 'nullable|string',
            'mensaje'       => 'nullable|string|max:1000',
        ]);

        $empresaId = $this->resolverEmpresaId($user, $data['empresa_id'] ?? null);

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
                    empresaNombre: $empresa?->nombre ?? 'Todas las empresas',
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

        if ($reporteToken->estaExpirado()) {
            abort(410, 'El enlace ha expirado');
        }

        if (!in_array($tipo, ['pdf', 'excel'])) {
            abort(400, 'Tipo inválido');
        }

        $path = $tipo === 'pdf' ? $reporteToken->pdf_path : $reporteToken->excel_path;
        $rutaAbsoluta = storage_path('app/public/' . $path);

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