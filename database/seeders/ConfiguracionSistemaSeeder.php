<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfiguracionSistema;

class ConfiguracionSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $valores = [
            // ============ TEMA VISUAL ============
            'tema.color_primario'     => '#1E5180', // Azul oscuro CaboSync
            'tema.color_secundario'   => '#F28C28', // Naranja CaboSync
            'tema.color_acento'       => '#4a90e2',
            'tema.color_exito'        => '#28a745',
            'tema.color_peligro'      => '#dc3545',
            'tema.color_advertencia'  => '#ffc107',

            // ============ SISTEMA ============
            'sistema.nombre'          => 'CaboSync',
            'sistema.logo'            => '/img/logo-cabosync.png',
            'sistema.empresa_default' => '1',

            // ============ PENALIZACIONES ============
            'penalizacion.dias_falta' => '1',
            'penalizacion.dias_extra' => '1',
            'penalizacion.total'      => '2',

            // ============ FLUJO DE TRABAJO ============
            'flujo.dias_registro_empleados' => '1,2', // Lunes y martes (ISO: 1=lunes)
            'flujo.requiere_conciliacion'   => '1',
            'flujo.roles_conciliadores'     => 'contratista,jefe_obra',
        ];

        foreach ($valores as $clave => $valor) {
            ConfiguracionSistema::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor]
            );
        }
    }
}