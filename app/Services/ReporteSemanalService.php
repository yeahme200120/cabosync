<?php

namespace App\Services;

use App\Models\Asistencia;
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
     * Obtiene los datos del reporte.
     * Acepta empresaId nullable: si es null, incluye TODAS las empresas.
     */
    public function obtenerDatos(?int $empresaId, ?int $obraId, string $week): array
    {
        $semana       = $this->calcularSemanaDesdeWeek($week);
        $fechasSemana = $semana['fechas'];

        // =========================================================
        // Query de empleados (SIN filtrar por empresa si es Admin)
        // =========================================================
        $empleadosQuery = Empleado::with(['empresa', 'obra'])
            ->where('estatus', 'activo');

        if ($empresaId) {
            $empleadosQuery->where('empresa_id', $empresaId);
        }

        if ($obraId) {
            $empleadosQuery->where('obra_id', $obraId);
        }

        $empleados = $empleadosQuery
            ->orderBy('empresa_id')
            ->orderBy('puesto_cargo')
            ->orderBy('nombre')
            ->get();

        $empleadoIds = $empleados->pluck('id')->toArray();

        // Asistencias finales de la semana
        $finales = AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->get()
            ->keyBy(fn ($f) => $f->empleado_id . '_' . $f->fecha->format('Y-m-d'));

        // Horas extra por empleado/día (agrupadas para eficiencia)
        $horasExtra = Asistencia::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->where('horas_extra', '>', 0)
            ->get()
            ->groupBy('empleado_id');

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
                'empleado'       => $emp,
                'empresa'        => $emp->empresa?->nombre ?? 'N/D',
                'dias'           => [],
                'presentes'      => 0,
                'faltas'         => 0,
                'justificadas'   => 0,
                'dias_descuento' => 0,
                'horas_extra'    => 0,
            ];

            $horasExtraEmpleado = $horasExtra->get($emp->id, collect())->keyBy(
                fn ($h) => $h->fecha->format('Y-m-d')
            );

            foreach (array_keys($fechasSemana) as $fecha) {
                $final = $finales->get($emp->id . '_' . $fecha);
                $estado = $final?->estado_final;
                $horas = (float) ($horasExtraEmpleado->get($fecha)?->horas_extra ?? 0);

                $fila['horas_extra'] += $horas;

                $fila['dias'][$fecha] = [
                    'estado'      => $estado,
                    'horas_extra' => $horas,
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
        $empresa = $empresaId ? Empresa::find($empresaId) : null;
        $obra    = $obraId ? Obra::find($obraId) : null;

        return [
            'empresa'          => $empresa,
            'obra'             => $obra,
            'semana'           => $semana,
            'filas'            => $filas,
            'totales'          => $totales,
            'total_empleados'  => count($filas),
            'multi_empresa'    => $empresaId === null, // flag para el PDF
        ];
    }

    /**
     * Genera y guarda el PDF. Acepta empresaId nullable.
     */
    public function generarPdf(?int $empresaId, ?int $obraId, string $week, ?string $responsable = null): string
    {
        $datos = $this->obtenerDatos($empresaId, $obraId, $week);

        $html = view('reportes.pdf.semanal', array_merge($datos, [
            'responsable' => $responsable,
            'generado_en' => now(),
        ]))->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData          = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
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

        $mpdf->SetWatermarkText('CABOSYNC');
        $mpdf->showWatermarkText    = true;
        $mpdf->watermark_font       = 'DejaVuSans';
        $mpdf->watermarkTextAlpha   = 0.08;

        $mpdf->SetProtection(['copy'], '', 'CaboSyncID2026');

        $mpdf->SetTitle('Reporte Semanal de Asistencia - CaboSync');
        $mpdf->SetAuthor('CaboSync - ID SOFTWARE HOUSE');
        $mpdf->SetCreator('CaboSync');

        $mpdf->WriteHTML($html);

        // Ruta: si no hay empresa, usar "todas"
        $carpetaEmpresa = $empresaId ?? 'todas';
        $rutaRelativa = "reportes/{$carpetaEmpresa}/semana-{$week}/reporte-{$week}.pdf";
        $rutaAbsoluta = storage_path('app/public/' . $rutaRelativa);

        @mkdir(dirname($rutaAbsoluta), 0755, true);

        $mpdf->Output($rutaAbsoluta, \Mpdf\Output\Destination::FILE);

        return $rutaRelativa;
    }

    /**
     * Genera y guarda el Excel. Acepta empresaId nullable.
     */
    public function generarExcel(?int $empresaId, ?int $obraId, string $week, ?string $responsable = null): string
    {
        $carpetaEmpresa = $empresaId ?? 'todas';
        $rutaRelativa = "reportes/{$carpetaEmpresa}/semana-{$week}/reporte-{$week}.xlsx";
        $rutaAbsoluta = storage_path('app/public/' . $rutaRelativa);

        @mkdir(dirname($rutaAbsoluta), 0755, true);

        \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\ReporteSemanalExport($empresaId, $obraId, $week, $responsable),
            $rutaRelativa,
            'public'
        );

        return $rutaRelativa;
    }

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