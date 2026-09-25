<?php

namespace App\Exports;

use App\Models\Rol;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UsuarioTemplateExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $rolesDisponibles;

    public function __construct()
    {
        $this->rolesDisponibles = Rol::where('tipo', 'sistema')
            ->orderBy('nombre')
            ->get(['codigo', 'nombre']);
    }

    public function array(): array
    {
        // Columnas: solo NOMBRE, EMAIL, ROL_CODIGO, ESTATUS (sin EMPRESA_RFC)
        $filas = [
            ['NOMBRE', 'EMAIL', 'ROL_CODIGO', 'ESTATUS'],
        ];

        // Filas de ejemplo
        $filas[] = ['JUAN PEREZ GARCIA', 'juan@empresa.com', 'jefe_obra', 'activo'];
        $filas[] = ['MARIA LOPEZ RUIZ', 'maria@empresa.com', 'seguridad', 'activo'];
        $filas[] = ['CARLOS RUIZ HERNANDEZ', 'carlos@empresa.com', 'rh', 'activo'];

        // Espacio
        $filas[] = [];
        $filas[] = ['--- ROLES DISPONIBLES (usa el código en ROL_CODIGO) ---'];
        $filas[] = ['CÓDIGO', 'NOMBRE DEL ROL'];

        foreach ($this->rolesDisponibles as $rol) {
            $filas[] = [$rol->codigo, $rol->nombre];
        }

        return $filas;
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado principal (A1:D1)
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E5180']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Fila del título del catálogo (después de las filas de ejemplo + fila vacía)
        $filaCatalogo = 6; // 1 header + 3 ejemplos + 1 vacía + 1 título

        $sheet->getStyle('A' . $filaCatalogo)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E5180'], 'size' => 11],
        ]);

        // Encabezados del catálogo
        $sheet->getStyle('A' . ($filaCatalogo + 1) . ':B' . ($filaCatalogo + 1))->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F28C28']],
        ]);

        // Cuerpo del catálogo
        $finCatalogo = $filaCatalogo + $this->rolesDisponibles->count() + 1;
        $sheet->getStyle('A' . ($filaCatalogo + 2) . ':B' . $finCatalogo)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'DDDDDD']]],
        ]);

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 22,
            'D' => 12,
        ];
    }

    public function title(): string
    {
        return 'Usuarios';
    }
}