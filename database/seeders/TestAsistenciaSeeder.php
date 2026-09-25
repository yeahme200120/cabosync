<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestAsistenciaSeeder extends Seeder
{
    public function run(): void
    {
        $jefe      = User::where('email', 'jefeobra@caboandhome.com')->first();
        $seguridad = User::where('email', 'seguridad@caboandhome.com')->first();

        if (!$jefe || !$seguridad) {
            $this->command->error('Usuarios jefe/seguridad no encontrados');
            return;
        }

        $empleados = Empleado::limit(10)->get();

        // Semana del 21 al 26 de septiembre 2026
        $fechas = [
            '2026-09-21',
            '2026-09-22',
            '2026-09-23',
            '2026-09-24',
            '2026-09-25',
            '2026-09-26',
        ];

        $registrados = 0;

        foreach ($empleados as $i => $emp) {
            foreach ($fechas as $j => $fecha) {
                // Variar los patrones para tener casos distintos
                $patron = ($i + $j) % 5;

                // Jefe de Obra
                $estadoJefe = null;
                if ($patron !== 4) {
                    $estadoJefe = $patron === 0 ? 'falta' : 'presente';

                    Asistencia::updateOrCreate(
                        [
                            'empleado_id'     => $emp->id,
                            'fecha'           => $fecha,
                            'origen_registro' => 'jefe_obra',
                        ],
                        [
                            'estado'         => $estadoJefe,
                            'es_justificada' => false,
                            'horas_extra'    => 0,
                            'usuario_id'     => $jefe->id,
                        ]
                    );
                    $registrados++;
                }

                // Seguridad
                $estadoSeg = null;
                if ($patron !== 3 && $patron !== 4) {
                    // En algunos casos, Seguridad registra DIFERENTE al Jefe
                    $estadoSeg = $patron === 2 ? 'falta' : 'presente';

                    Asistencia::updateOrCreate(
                        [
                            'empleado_id'     => $emp->id,
                            'fecha'           => $fecha,
                            'origen_registro' => 'seguridad',
                        ],
                        [
                            'estado'         => $estadoSeg,
                            'es_justificada' => false,
                            'horas_extra'    => 0,
                            'usuario_id'     => $seguridad->id,
                        ]
                    );
                    $registrados++;
                }
            }
        }

        $this->command->info("✅ {$registrados} registros de asistencia creados para probar.");
        $this->command->info("   • Algunos son coincidentes (J=S)");
        $this->command->info("   • Algunos son discrepancias (J≠S)");
        $this->command->info("   • Algunos son parciales (solo J o solo S)");
    }
}