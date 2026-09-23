<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Obra;
use App\Models\Empresa;

class ObraSeeder extends Seeder
{
    public function run(): void
    {
        $caboAndHome = Empresa::where('nombre', 'Cabo And Home')->first();

        $obras = [
            [
                'empresa_id'            => $caboAndHome?->id,
                'nombre'                => 'Lote Cielo 08',
                'codigo'                => 'LC-08',
                'ubicacion'             => 'Los Cabos, BCS',
                'fecha_inicio'          => '2026-09-21',
                'fecha_fin'             => null,
                'estatus'               => 'activa',
                'creado_por_usuario_id' => 1, // Admin por defecto
            ],
        ];

        foreach ($obras as $obra) {
            Obra::updateOrCreate(['codigo' => $obra['codigo']], $obra);
        }
    }
}