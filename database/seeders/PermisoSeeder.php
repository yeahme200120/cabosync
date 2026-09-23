<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // ============ ASISTENCIA ============
            ['nombre' => 'Tomar asistencia',              'clave' => 'asistencia.tomar'],
            ['nombre' => 'Conciliar asistencia',          'clave' => 'asistencia.conciliar'],
            ['nombre' => 'Ver asistencia',                'clave' => 'asistencia.ver'],
            ['nombre' => 'Justificar falta',              'clave' => 'asistencia.justificar'],

            // ============ HORAS EXTRAS ============
            ['nombre' => 'Solicitar horas extras',        'clave' => 'horas_extras.solicitar'],
            ['nombre' => 'Aprobar horas extras',          'clave' => 'horas_extras.aprobar'],
            ['nombre' => 'Ver horas extras',              'clave' => 'horas_extras.ver'],

            // ============ JUSTIFICACIONES ============
            ['nombre' => 'Subir justificación',           'clave' => 'justificacion.subir'],
            ['nombre' => 'Aprobar justificación',         'clave' => 'justificacion.aprobar'],

            // ============ USUARIOS ============
            ['nombre' => 'Crear usuarios',                'clave' => 'usuarios.crear'],
            ['nombre' => 'Editar usuarios',               'clave' => 'usuarios.editar'],
            ['nombre' => 'Eliminar usuarios',             'clave' => 'usuarios.eliminar'],
            ['nombre' => 'Ver usuarios',                  'clave' => 'usuarios.ver'],

            // ============ EMPLEADOS ============
            ['nombre' => 'Crear empleados',               'clave' => 'empleados.crear'],
            ['nombre' => 'Editar empleados',              'clave' => 'empleados.editar'],
            ['nombre' => 'Eliminar empleados',            'clave' => 'empleados.eliminar'],
            ['nombre' => 'Ver expediente',                'clave' => 'empleados.expediente'],

            // ============ EMPRESAS ============
            ['nombre' => 'Crear empresas',                'clave' => 'empresas.crear'],
            ['nombre' => 'Editar empresas',               'clave' => 'empresas.editar'],
            ['nombre' => 'Ver empresas',                  'clave' => 'empresas.ver'],

            // ============ REPORTES ============
            ['nombre' => 'Exportar reportes',             'clave' => 'reportes.exportar'],
            ['nombre' => 'Enviar reportes a RH',          'clave' => 'reportes.enviar_rh'],
            ['nombre' => 'Ver reportes',                  'clave' => 'reportes.ver'],

            // ============ SISTEMA ============
            ['nombre' => 'Configurar sistema',            'clave' => 'config.editar'],
            ['nombre' => 'Ver bitácora',                  'clave' => 'bitacora.ver'],
            ['nombre' => 'Gestionar roles y permisos',    'clave' => 'roles.gestionar'],
        ];

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(['clave' => $permiso['clave']], $permiso);
        }
    }
}