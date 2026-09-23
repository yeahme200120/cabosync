<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $empresaMatriz  = Empresa::where('tipo', 'matriz')->first();
        $empresaExterna = Empresa::where('tipo', 'externa')->first();

        $rolAdmin       = Rol::where('codigo', 'admin')->first();
        $rolContratista = Rol::where('codigo', 'contratista')->first();
        $rolJefeObra    = Rol::where('codigo', 'jefe_obra')->first();
        $rolSeguridad   = Rol::where('codigo', 'seguridad')->first();

        // 1. ADMIN — ID SOFTWARE HOUSE
        User::updateOrCreate(
            ['email' => 'admin@idsoftwarehouse.com'],
            [
                'empresa_id' => $empresaMatriz?->id,
                'rol_id'     => $rolAdmin->id,
                'nombre'     => 'Administrador ID Software House',
                'password'   => Hash::make('admin123'),
                'estatus'    => 'activo',
            ]
        );

        // 2. CONTRATISTA — Cabo And Home
        User::updateOrCreate(
            ['email' => 'contratista@caboandhome.com'],
            [
                'empresa_id' => $empresaExterna?->id,
                'rol_id'     => $rolContratista->id,
                'nombre'     => 'Juan Contratista',
                'password'   => Hash::make('contratista123'),
                'estatus'    => 'activo',
            ]
        );

        // 3. JEFE DE OBRA — Cabo And Home
        User::updateOrCreate(
            ['email' => 'jefeobra@caboandhome.com'],
            [
                'empresa_id' => $empresaExterna?->id,
                'rol_id'     => $rolJefeObra->id,
                'nombre'     => 'Pedro Jefe de Obra',
                'password'   => Hash::make('jefe123'),
                'estatus'    => 'activo',
            ]
        );

        // 4. SEGURIDAD — Cabo And Home
        User::updateOrCreate(
            ['email' => 'seguridad@caboandhome.com'],
            [
                'empresa_id' => $empresaExterna?->id,
                'rol_id'     => $rolSeguridad->id,
                'nombre'     => 'Luis Guardia',
                'password'   => Hash::make('seguridad123'),
                'estatus'    => 'activo',
            ]
        );
    }
}