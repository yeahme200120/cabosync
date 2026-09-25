<?php

namespace App\Services;

use App\Models\AsistenciaFinal;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Obra;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ReporteSemanalService
{
    /**
     * Genera los datos del reporte (empleados + totales).
     */
    public function obtenerDatos(int $empresaId, ?int $obraId, string $week): array
    {
        $semana = $this->calcularSemanaDesdeWeek($week);
        $fechasSemana = $semana['fechas'];

        // Obtener empleados
        $empleadosQuery = Empleado::with(['empresa', 'obra'])
            ->where('empresa_id', $empresaId)
            ->where('estatus', 'activo');

        if ($obraId) $empleadosQuery->where('obra_id', $obraId);

        $empleados = $empleadosQuery
            ->orderBy('puesto_cargo')
            ->orderBy('nombre')
            ->get();

        $empleadoIds = $empleados->pluck('id')->toArray();

        // Asistencias finales de la semana
        $finales = AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->get()
            ->keyBy(fn ($f) => $f->empleado_id . '_' . $f->fecha->format('Y-m-d'));

        // Armar filas
        $filas = [];
        $totales = [
            'presentes'      => 0,
            'faltas'         => 0,
            'justificadas'   => 0,
            'dias_descuento' => 0,
            'horas_extra'    => 0,
            'sin_registro'   => 0,
        ];

        foreach ($empleados as $emp) {
            $fila = [
                'empleado'     => $emp,
                'dias'         => [],
                'presentes'    => 0,
                'faltas'       => 0,
                'justificadas' => 0,
                'dias_descuento' => 0,
                'horas_extra'  => 0,
            ];

            foreach (array_keys($fechasSemana) as $fecha) {
                $key = $emp->id . '_' . $fecha;
                $final = $finales->get($key);

                $estado = $final?->estado_final;
                $horasExtra = $this->obtenerHorasExtraEmpleadoDia($emp->id, $fecha);
                $fila['horas_extra'] += $horasExtra;

                $fila['dias'][$fecha] = [
                    'estado'      => $estado,
                    'horas_extra' => $horasExtra,
                ];

                switch ($estado) {
                    case 'asistencia':
                        $fila['presentes']++;
                        $totales['presentes']++;
                        break;
                    case 'asistencia_justificada':
                        $fila['justificadas']++;
                        $totales['justificadas']++;
                        break;
                    case 'falta_injustificada':
                        $fila['faltas']++;
                        $fila['dias_descuento'] += 2;
                        $totales['faltas']++;
                        $totales['dias_descuento'] += 2;
                        break;
                    default:
                        $totales['sin_registro']++;
                        break;
                }
            }

            $totales['horas_extra'] += $fila['horas_extra'];
            $filas[] = $fila;
        }

        // Datos de la empresa y obra
        $empresa = Empresa::find($empresaId);
        $obra    = $obraId ? Obra::find($obraId) : null;

        return [
            'empresa'    => $empresa,
            'obra'       => $obra,
            'semana'     => $semana,
            'filas'      => $filas,
            'totales'    => $totales,
            'total_empleados' => count($filas),
        ];
    }

    /**
     * Genera y guarda el PDF. Devuelve la ruta relativa.
     */
    public function generarPdf(int $empresaId, ?int $obraId, string $week, ?string $responsable = null): string
    {
        $datos = $this->obtenerDatos($empresaId, $obraId, $week);

        $html = view('reportes.pdf.semanal', array_merge($datos, [
            'responsable' => $responsable,
            'generado_en' => now(),
        ]))->render();

        // Configurar mPDF
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L', // Horizontal
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 25,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'fontDir'       => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'      => $fontData,
            'default_font'  => 'dejavusans',
        ]);

        // Marca de agua
        $mpdf->SetWatermarkText('CABOSYNC');
        $mpdf->showWatermarkText = true;
        $mpdf->watermark_font = 'DejaVuSans';
        $mpdf->watermarkTextAlpha = 0.08;

        // Protección: sin impresión, sin copiar todo (solo copiar)
        $mpdf->SetProtection(['copy'], '', 'CaboSyncID2026');

        // Metadatos
        $mpdf->SetTitle('Reporte Semanal de Asistencia - CaboSync');
        $mpdf->SetAuthor('CaboSync - ID SOFTWARE HOUSE');
        $mpdf->SetCreator('CaboSync');

        // Escribir HTML
        $mpdf->WriteHTML($html);

        // Guardar
        $rutaRelativa = "reportes/{$empresaId}/semana-{$week}/reporte-{$week}.pdf";
        $rutaAbsoluta = storage_path('app/public/' . $rutaRelativa);

        // Asegurar directorio
        @mkdir(dirname($rutaAbsoluta), 0755, true);

        $mpdf->Output($rutaAbsoluta, \Mpdf\Output\Destination::FILE);

        return $rutaRelativa;
    }

    /**
     * Genera y guarda el Excel. Devuelve la ruta relativa.
     */
    public function generarExcel(int $empresaId, ?int $obraId, string $week, ?string $responsable = null): string
    {
        $rutaRelativa = "reportes/{$empresaId}/semana-{$week}/reporte-{$week}.xlsx";
        $rutaAbsoluta = storage_path('app/public/' . $rutaRelativa);

        // Asegurar directorio
        @mkdir(dirname($rutaAbsoluta), 0755, true);

        \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\ReporteSemanalExport($empresaId, $obraId, $week, $responsable),
            $rutaRelativa,
            'public'
        );

        return $rutaRelativa;
    }

    /**
     * Obtiene las horas extra de un empleado en una fecha.
     */
    protected function obtenerHorasExtraEmpleadoDia(int $empleadoId, string $fecha): float
    {
        $suma = \App\Models\Asistencia::where('empleado_id', $empleadoId)
            ->where('fecha', $fecha)
            ->sum('horas_extra');

        return (float) $suma;
    }

    /**
     * Calcula la semana (Lun-Sáb) desde un input type="week" (ej. "2026-W39").
     */
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
            'week'   => $weekInput,
            'fechas' => $fechas,
        ];
    }
}