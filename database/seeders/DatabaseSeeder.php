<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            PermisoSeeder::class,
            RolPermisoSeeder::class,
            EmpresaSeeder::class,
            ObraSeeder::class,
            UserSeeder::class,
            EmpleadoSeeder::class,
            ConfiguracionSistemaSeeder::class,
            TestAsistenciaSeeder::class,
        ]);
    }
}