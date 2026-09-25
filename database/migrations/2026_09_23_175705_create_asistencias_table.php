<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id');
            $table->date('fecha');
            $table->enum('origen_registro', ['jefe_obra', 'seguridad']);
            $table->enum('estado', ['presente', 'falta']);
            $table->boolean('es_justificada')->default(false);
            $table->unsignedBigInteger('justificacion_id')->nullable();
            $table->string('evidencia_ruta', 255)->nullable();
            $table->decimal('horas_extra', 4, 2)->default(0);
            $table->unsignedBigInteger('usuario_id');
            $table->timestamps();

            // Índice único: no puede haber dos registros del mismo empleado, fecha y origen
            $table->unique(
                ['empleado_id', 'fecha', 'origen_registro'],
                'asistencias_empleado_fecha_origen_unique'
            );

            // Índice para consultas frecuentes por fecha y origen
            $table->index(['fecha', 'origen_registro'], 'asistencias_fecha_origen_idx');

            // Índice para KPIs de horas extra
            $table->index(['fecha', 'horas_extra'], 'asistencias_fecha_horas_extra_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};