<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class EmpleadoTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        return [
            'NOMBRE',
            'APELLIDO',
            'CURP_DNI',
            'PUESTO_CARGO',
            'EMPRESA_RFC',
            'OBRA_CODIGO',
            'ESTATUS',
        ];
    }

    public function array(): array
    {
        // Fila de ejemplo (en MAYÚSCULAS)
        return [
            [
                'JOSE JUAN',
                'BERNAL GARCIA',
                '',
                'OFICIAL CARPINTERO',
                'CAH240101BBB',
                'LC-08',
                'ACTIVO',
            ],
            [
                'ANGEL ALEXIS',
                'MELCHOR ARENAS',
                '',
                'OFICIAL ALBAÑIL',
                'CAH240101BBB',
                'LC-08',
                'ACTIVO',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // NOMBRE
            'B' => 30,  // APELLIDO
            'C' => 20,  // CURP_DNI
            'D' => 30,  // PUESTO_CARGO
            'E' => 20,  // EMPRESA_RFC
            'F' => 15,  // OBRA_CODIGO
            'G' => 12,  // ESTATUS
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E5180'], // Azul CaboSync
                ],
            ],
        ];
    }
}