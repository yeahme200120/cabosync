<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = [
            [
                'nombre'   => 'ID SOFTWARE HOUSE',
                'rfc'      => 'ISH240101AAA',
                'tipo'     => 'matriz',
                'estatus'  => 'activo',
            ],
            [
                'nombre'   => 'Cabo And Home',
                'rfc'      => 'CAH240101BBB',
                'tipo'     => 'externa',
                'estatus'  => 'activo',
            ],
        ];

        foreach ($empresas as $empresa) {
            Empresa::updateOrCreate(['rfc' => $empresa['rfc']], $empresa);
        }
    }
}