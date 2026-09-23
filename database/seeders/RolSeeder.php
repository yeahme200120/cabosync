<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // ============================================
            // ROLES DEL SISTEMA (pueden iniciar sesión)
            // ============================================
            [
                'nombre'      => 'Administrador',
                'codigo'      => 'admin',
                'descripcion' => 'Acceso total al sistema, gestión de catálogos, configuraciones y usuarios globales.',
                'tipo'        => 'sistema',
            ],
            [
                'nombre'      => 'Contratista',
                'codigo'      => 'contratista',
                'descripcion' => 'Pieza clave: administra empresas externas, subcontratos, asignación de empleados y concilia reportes de asistencia.',
                'tipo'        => 'sistema',
            ],
            [
                'nombre'      => 'Recursos Humanos',
                'codigo'      => 'rh',
                'descripcion' => 'Gestiona expedientes de trabajadores, altas, bajas, contratos e incidencias del personal.',
                'tipo'        => 'sistema',
            ],
            [
                'nombre'      => 'Contabilidad',
                'codigo'      => 'contabilidad',
                'descripcion' => 'Recibe reportes de RH, concilia incidencias de asistencia y aplica descuentos o bonos en nómina.',
                'tipo'        => 'sistema',
            ],
            [
                'nombre'      => 'Jefe de Obra',
                'codigo'      => 'jefe_obra',
                'descripcion' => 'Registra y supervisa el pase de lista diario y avances técnicos directamente en el frente de trabajo.',
                'tipo'        => 'sistema',
            ],
            [
                'nombre'      => 'Maestro de Obra',
                'codigo'      => 'maestro_obra',
                'descripcion' => 'Coordina las cuadrillas en campo, valida la asistencia operativa y reporta ausentismos al Jefe de Obra.',
                'tipo'        => 'sistema',
            ],
            [
                'nombre'      => 'Seguridad',
                'codigo'      => 'seguridad',
                'descripcion' => 'Registra pase de lista diario en caseta de acceso (entradas/salidas) y vigila equipo de protección.',
                'tipo'        => 'sistema',
            ],
            [
                'nombre'      => 'Topógrafo',
                'codigo'      => 'topografo',
                'descripcion' => 'Personal técnico encargado de mediciones de terreno, trazo, niveles y linderos geométricos.',
                'tipo'        => 'sistema',
            ],

            // ============================================
            // ROLES OPERATIVOS (solo catálogo de puestos)
            // ============================================
            [
                'nombre'      => 'Oficial Albañil',
                'codigo'      => 'oficial_albanil',
                'descripcion' => 'Personal calificado para levantar muros, colados, acabados y lectura básica de planos.',
                'tipo'        => 'operativo',
            ],
            [
                'nombre'      => 'Oficial Carpintero',
                'codigo'      => 'oficial_carpintero',
                'descripcion' => 'Especialista en habilitado de cimbra, moldes de madera para concreto y estructuras de madera.',
                'tipo'        => 'operativo',
            ],
            [
                'nombre'      => 'Fierrero',
                'codigo'      => 'fierrero',
                'descripcion' => 'Encargado del corte, doblado, armado y amarre del acero de refuerzo para cimentaciones y losas.',
                'tipo'        => 'operativo',
            ],
            [
                'nombre'      => 'Técnico de Instalaciones',
                'codigo'      => 'tecnico_instalaciones',
                'descripcion' => 'Especialistas encargados de redes hidrosanitarias, plomería, electricidad y canalizaciones.',
                'tipo'        => 'operativo',
            ],
            [
                'nombre'      => 'Operador de Maquinaria',
                'codigo'      => 'operador_maquinaria',
                'descripcion' => 'Conductor certificado de equipo pesado (excavadoras, grúas, revolvedoras, plataformas).',
                'tipo'        => 'operativo',
            ],
            [
                'nombre'      => 'Ayudante General',
                'codigo'      => 'ayudante_general',
                'descripcion' => 'Soporte en carga de materiales, preparación de mezclas y asistencia directa a los oficiales.',
                'tipo'        => 'operativo',
            ],
            [
                'nombre'      => 'Limpieza',
                'codigo'      => 'limpieza',
                'descripcion' => 'Encargado del orden, retiro de escombro, mantenimiento de áreas comunes y despeje de frentes de trabajo.',
                'tipo'        => 'operativo',
            ],
            [
                'nombre'      => 'Almacenista',
                'codigo'      => 'almacenista',
                'descripcion' => 'Controla las entradas, salidas, resguardo y firmas de entrega de herramientas y consumibles.',
                'tipo'        => 'operativo',
            ],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(['codigo' => $rol['codigo']], $rol);
        }
    }
}