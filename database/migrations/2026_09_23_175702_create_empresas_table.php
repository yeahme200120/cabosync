<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('rfc', 50)->nullable();
            $table->enum('tipo', ['matriz', 'externa'])->default('externa');
            $table->enum('estatus', ['activo', 'inactivo'])->default('activo');
            $table->unsignedBigInteger('creado_por_usuario_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};