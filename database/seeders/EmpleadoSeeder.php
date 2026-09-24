<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\Rol;
use App\Models\User;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::where('nombre', 'Cabo And Home')->first();
        $obra = Obra::where('codigo', 'LC-08')->first();
        $contratista = User::where('email', 'contratista@caboandhome.com')->first();

        if (!$empresa || !$obra || !$contratista) {
            $this->command->error('Faltan datos base. Ejecuta primero EmpresaSeeder, ObraSeeder y UserSeeder.');
            return;
        }

        $roles = Rol::operativo()->pluck('id', 'codigo')->toArray();

        // ============================
        // OFICIALES CARPINTEROS (11)
        // ============================
        $carpinteros = [
            ['Jose', 'Juan Perez'],
            ['Melchor', 'Peralta Uriel Alexis'],
            ['Cesar', 'Maciel Miguel Angel'],
            ['Bruno', 'Gutierrez Rene'],
            ['Vazquez', 'Martinez Rosendo'],
            ['Castro', 'Cazares Paulino'],
            ['M. Martinez', 'Reyes Luis Angel'],
            ['Hernandez', 'Yonel Bladimir'],
            ['Castro', 'Cazares Cecilio'],
            ['Gonzalez', 'Bautista Genaro'],
            ['Daniel', 'Castro Dimas'],
        ];

        // ============================
        // OFICIALES ALBAÑILES (13)
        // ============================
        $albaniles = [
            ['Dominguez', 'De La Cruz Pancho'],
            ['Oliva', 'Soriano Rodrigo'],
            ['Rivera', 'Angel Astudillo'],
            ['Del Campo', 'Santiago Carmelito'],
            ['Dominguez', 'Soriano Edgar'],
            ['Jose Luis', 'Miguel Vaquero'],
            ['Garcia', 'Meza Carlos Daniel'],
            ['Oliver', 'Lopez Rojas'],
            ['Jose Luis', 'Villalva'],
            ['Contreras', 'Vidal Adrian'],
            ['Montez', 'Garcia Vicente'],
            ['Sanchez', 'Ojeda Jovany'],
            ['Barrera', 'Sanchez Humberto'],
        ];

        // ============================
        // FIERREROS (6)
        // ============================
        $fierreros = [
            ['Jose Cruz', 'Sanchez Reyes'],
            ['Moreno', 'Gamez Adelido'],
            ['Jose', 'Bedolla Garcia'],
            ['Morales', 'De Cristobal Andy'],
            ['Luis Fernando', 'Diaz Garcia'],
            ['Juan Carlos', 'Rodriguez Lito'],
        ];

        // ============================
        // LIMPIEZA (4)
        // ============================
        $limpieza = [
            ['Zepeda', 'Perez Michaela'],
            ['Garcia', 'Mecino Magali'],
            ['Zayas', 'Pincheco Yajaira Marisol'],
            ['Chavez', 'Cruz Lizeth'],
        ];

        // ============================
        // AYUDANTES GENERALES (12)
        // ============================
        $ayudantes = [
            ['Cano', 'Cruz Mario'],
            ['Bulmaro', 'Chavez Garcia'],
            ['Bulmaro', 'Chavez Cruz'],
            ['Apashendi', 'Lopez Renato'],
            ['David', 'Cano Cruz'],
            ['Jose Antonio', 'Organista Marin'],
            ['Luis', 'Castro Benitez'],
            ['Osvaldo', 'Otoniel Hernandez'],
            ['Martinez', 'Aquino Hujinio'],
            ['Marcos', 'Garcia Martinez'],
            ['Benito', 'Cevada Lopez'],
            ['Cesar', 'Morales Ortega'],
        ];

        $todos = [
            'oficial_carpintero' => $carpinteros,
            'oficial_albanil'    => $albaniles,
            'fierrero'           => $fierreros,
            'limpieza'           => $limpieza,
            'ayudante_general'   => $ayudantes,
        ];

        $totalCreados = 0;

        foreach ($todos as $codigoRol => $empleados) {
            $rolId = $roles[$codigoRol] ?? null;
            if (!$rolId) {
                $this->command->warn("⚠️  Rol operativo no encontrado: {$codigoRol}");
                continue;
            }

            $nombreRol = Rol::find($rolId)->nombre;

            foreach ($empleados as $emp) {
                Empleado::updateOrCreate(
                    [
                        'nombre'     => $emp[0],
                        'apellido'   => $emp[1],
                        'empresa_id' => $empresa->id,
                    ],
                    [
                        'obra_id'                     => $obra->id,
                        'rol_id'                      => $rolId,
                        'curp_dni'                    => null,
                        'foto'                        => null,
                        'puesto_cargo'                => $nombreRol,
                        'estatus'                     => 'activo',
                        'registrado_por_usuario_id'   => $contratista->id,
                        'registrado_por_cargo'        => 'Contratista',
                        'es_titular_externo'          => true,
                    ]
                );
                $totalCreados++;
            }

            $this->command->info("✅ {$codigoRol}: " . count($empleados) . " empleados registrados");
        }

        $this->command->info("🎉 Total: {$totalCreados} empleados registrados en Lote Cielo 08");
    }
}