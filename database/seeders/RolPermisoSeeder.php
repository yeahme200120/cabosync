<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Permiso;

class RolPermisoSeeder extends Seeder
{
    public function run(): void
    {
        // Mapa de permisos por código de rol (solo roles tipo "sistema")
        $mapa = [

            // ============================================
            // ADMINISTRADOR: todos los permisos
            // ============================================
            'admin' => '*',

            // ============================================
            // CONTRATISTA: pieza clave del sistema
            // - Administra empresas y empleados
            // - Concilia asistencia (su función principal)
            // - Ve y exporta reportes
            // - Puede tomar asistencia si es necesario
            // ============================================
            'contratista' => [
                // Asistencia
                'asistencia.tomar',
                'asistencia.conciliar',
                'asistencia.ver',
                'asistencia.justificar',
                // Horas extras
                'horas_extras.solicitar',
                'horas_extras.aprobar',
                'horas_extras.ver',
                // Justificaciones
                'justificacion.subir',
                'justificacion.aprobar',
                // Empleados
                'empleados.crear',
                'empleados.editar',
                'empleados.eliminar',
                'empleados.expediente',
                // Empresas (las que tiene asignadas)
                'empresas.ver',
                // Reportes
                'reportes.exportar',
                'reportes.enviar_rh',
                'reportes.ver',
                // Bitácora
                'bitacora.ver',
            ],

            // ============================================
            // RECURSOS HUMANOS
            // ============================================
            'rh' => [
                'asistencia.ver',
                'asistencia.justificar',
                'justificacion.aprobar',
                'empleados.crear',
                'empleados.editar',
                'empleados.expediente',
                'horas_extras.ver',
                'reportes.exportar',
                'reportes.ver',
            ],

            // ============================================
            // CONTABILIDAD
            // ============================================
            'contabilidad' => [
                'asistencia.ver',
                'horas_extras.ver',
                'horas_extras.aprobar',
                'reportes.exportar',
                'reportes.ver',
            ],

            // ============================================
            // JEFE DE OBRA
            // ============================================
            'jefe_obra' => [
                'asistencia.tomar',
                'asistencia.ver',
                'asistencia.justificar',
                'justificacion.subir',
                'horas_extras.solicitar',
                'horas_extras.ver',
                'empleados.expediente',
            ],

            // ============================================
            // MAESTRO DE OBRA
            // ============================================
            'maestro_obra' => [
                'asistencia.tomar',
                'asistencia.ver',
                'justificacion.subir',
                'horas_extras.solicitar',
                'empleados.expediente',
            ],

            // ============================================
            // SEGURIDAD
            // ============================================
            'seguridad' => [
                'asistencia.tomar',
                'asistencia.ver',
                'justificacion.subir',
            ],

            // ============================================
            // TOPÓGRAFO
            // ============================================
            'topografo' => [
                'asistencia.tomar',
                'asistencia.ver',
            ],
        ];

        $todosPermisos = Permiso::all();

        foreach ($mapa as $codigoRol => $permisos) {
            $rol = Rol::where('codigo', $codigoRol)->first();
            if (!$rol) {
                $this->command->warn("⚠️  Rol no encontrado: {$codigoRol}");
                continue;
            }

            if ($permisos === '*') {
                $ids = $todosPermisos->pluck('id')->toArray();
            } else {
                $ids = Permiso::whereIn('clave', $permisos)->pluck('id')->toArray();
            }

            $rol->permisos()->sync($ids);
            $this->command->info("✅ {$rol->nombre}: " . count($ids) . " permisos asignados");
        }
    }
}