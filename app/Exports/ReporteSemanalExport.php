<?php

namespace App\Exports;

use App\Models\Asistencia;
use App\Models\AsistenciaFinal;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Obra;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteSemanalExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected int $empresaId;
    protected ?int $obraId;
    protected string $week;
    protected ?string $responsable;
    protected array $semana;
    protected array $datos;

    public function __construct(int $empresaId, ?int $obraId, string $week, ?string $responsable = null)
    {
        $this->empresaId   = $empresaId;
        $this->obraId      = $obraId;
        $this->week        = $week;
        $this->responsable = $responsable;
        $this->semana      = $this->calcularSemana();
        $this->datos       = $this->obtenerDatos();
    }

    public function array(): array
    {
        $filas = [];

        // Título
        $filas[] = ['REPORTE SEMANAL DE ASISTENCIA - CABOSYNC'];
        $filas[] = ['Semana: ' . $this->semana['inicio'] . ' al ' . $this->semana['fin'] . ' (' . $this->week . ')'];
        $filas[] = ['Empresa: ' . ($this->datos['empresa']->nombre ?? 'N/D')];
        $filas[] = ['Obra: ' . ($this->datos['obra']->nombre ?? 'Todas')];
        $filas[] = []; // Fila vacía

        // Encabezados
        $encabezados = ['Empleado', 'Puesto'];
        foreach ($this->semana['fechas'] as $fecha => $letra) {
            $encabezados[] = $letra . ' ' . Carbon::parse($fecha)->format('d');
        }
        $encabezados = array_merge($encabezados, ['Presentes', 'Faltas', 'Justificadas', 'Días descuento', 'Horas extra']);
        $filas[] = $encabezados;

        // Filas de empleados
        foreach ($this->datos['filas'] as $fila) {
            $row = [
                $fila['empleado']->nombre_completo,
                $fila['empleado']->puesto_cargo,
            ];

            foreach ($this->semana['fechas'] as $fecha => $letra) {
                $estado = $fila['dias'][$fecha]['estado'];
                $simbolo = match ($estado) {
                    'asistencia'             => '✓',
                    'asistencia_justificada' => '⚠',
                    'falta_injustificada'    => '✗',
                    default                  => '—',
                };
                $row[] = $simbolo;
            }

            $row = array_merge($row, [
                $fila['presentes'],
                $fila['faltas'],
                $fila['justificadas'],
                $fila['dias_descuento'],
                number_format($fila['horas_extra'], 2, '.', ''),
            ]);

            $filas[] = $row;
        }

        // Fila de totales
        $filas[] = [];
        $filas[] = [
            'TOTALES',
            '',
            '', '', '', '', '', '', // días vacíos
            '',
            '',
            $this->datos['totales']['presentes'],
            $this->datos['totales']['faltas'],
            $this->datos['totales']['justificadas'],
            $this->datos['totales']['dias_descuento'],
            number_format($this->datos['totales']['horas_extra'], 2, '.', ''),
        ];

        return $filas;
    }

    public function styles(Worksheet $sheet)
    {
        $ultimaFila = count($this->datos['filas']) + 6;

        // Título principal
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E5180']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Subtítulos
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');
        $sheet->mergeCells('A4:J4');

        // Encabezados de la tabla
        $sheet->getStyle('A6:' . $sheet->getHighestColumn() . '6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E5180']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Bordes a toda la tabla
        $sheet->getStyle('A6:' . $sheet->getHighestColumn() . $ultimaFila)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);

        // Columna empleado
        $sheet->getStyle('A7:A' . $ultimaFila)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E5180']],
        ]);

        // Fila totales
        $sheet->getStyle('A' . ($ultimaFila + 2) . ':' . $sheet->getHighestColumn() . ($ultimaFila + 2))->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F28C28']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35, // Empleado
            'B' => 20, // Puesto
            'C' => 6, 'D' => 6, 'E' => 6, 'F' => 6, 'G' => 6, 'H' => 6, // Días
            'I' => 11, 'J' => 9, 'K' => 13, 'L' => 15, 'M' => 13, // Totales
        ];
    }

    public function title(): string
    {
        return 'Asistencia Semanal';
    }

    // =========================================================
    // HELPERS
    // =========================================================

    protected function obtenerDatos(): array
    {
        $fechasSemana = $this->semana['fechas'];

        $empleadosQuery = Empleado::with(['empresa', 'obra'])
            ->where('empresa_id', $this->empresaId)
            ->where('estatus', 'activo');

        if ($this->obraId) $empleadosQuery->where('obra_id', $this->obraId);

        $empleados = $empleadosQuery->orderBy('puesto_cargo')->orderBy('nombre')->get();
        $empleadoIds = $empleados->pluck('id')->toArray();

        $finales = AsistenciaFinal::whereIn('empleado_id', $empleadoIds)
            ->whereIn('fecha', array_keys($fechasSemana))
            ->get()
            ->keyBy(fn ($f) => $f->empleado_id . '_' . $f->fecha->format('Y-m-d'));

        $filas = [];
        $totales = ['presentes' => 0, 'faltas' => 0, 'justificadas' => 0, 'dias_descuento' => 0, 'horas_extra' => 0];

        foreach ($empleados as $emp) {
            $fila = [
                'empleado'       => $emp,
                'dias'           => [],
                'presentes'      => 0,
                'faltas'         => 0,
                'justificadas'   => 0,
                'dias_descuento' => 0,
                'horas_extra'    => 0,
            ];

            foreach (array_keys($fechasSemana) as $fecha) {
                $final = $finales->get($emp->id . '_' . $fecha);
                $estado = $final?->estado_final;

                $horasExtra = (float) Asistencia::where('empleado_id', $emp->id)
                    ->where('fecha', $fecha)
                    ->sum('horas_extra');

                $fila['dias'][$fecha] = ['estado' => $estado, 'horas_extra' => $horasExtra];
                $fila['horas_extra'] += $horasExtra;

                if ($estado === 'asistencia') {
                    $fila['presentes']++;
                    $totales['presentes']++;
                } elseif ($estado === 'asistencia_justificada') {
                    $fila['justificadas']++;
                    $totales['justificadas']++;
                } elseif ($estado === 'falta_injustificada') {
                    $fila['faltas']++;
                    $fila['dias_descuento'] += 2;
                    $totales['faltas']++;
                    $totales['dias_descuento'] += 2;
                }
            }

            $totales['horas_extra'] += $fila['horas_extra'];
            $filas[] = $fila;
        }

        return [
            'empresa' => Empresa::find($this->empresaId),
            'obra'    => $this->obraId ? Obra::find($this->obraId) : null,
            'filas'   => $filas,
            'totales' => $totales,
        ];
    }

    protected function calcularSemana(): array
    {
        if (!preg_match('/^(\d{4})-W(\d{1,2})$/', $this->week, $m)) {
            $this->week = now()->format('o-\WW');
            preg_match('/^(\d{4})-W(\d{1,2})$/', $this->week, $m);
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

        return [
            'inicio' => $lunes->format('Y-m-d'),
            'fin'    => $lunes->copy()->addDays(5)->format('Y-m-d'),
            'week'   => $this->week,
            'fechas' => $fechas,
        ];
    }
}