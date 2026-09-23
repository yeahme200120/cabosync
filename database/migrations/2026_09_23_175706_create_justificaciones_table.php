<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id');
            $table->date('fecha');
            $table->enum('motivo', ['permiso', 'enfermedad', 'otro']);
            $table->text('descripcion')->nullable();
            $table->string('ruta_archivo')->nullable();
            $table->unsignedBigInteger('subido_por_usuario_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificaciones');
    }
};