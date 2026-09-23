<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('obra_id')->nullable();
            $table->unsignedBigInteger('rol_id')->nullable();
            $table->string('curp_dni', 50)->nullable();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('puesto_cargo', 100);
            $table->enum('estatus', ['activo', 'inactivo'])->default('activo');
            $table->unsignedBigInteger('registrado_por_usuario_id');
            $table->string('registrado_por_cargo', 100);
            $table->boolean('es_titular_externo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};