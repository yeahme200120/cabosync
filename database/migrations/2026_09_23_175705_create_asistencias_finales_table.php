<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias_finales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->date('fecha');
            $table->enum('estado_final', [
                'asistencia',
                'asistencia_justificada',
                'falta_injustificada',
            ])->nullable();
            $table->enum('origen_adoptado', [
                'jefe_obra',
                'seguridad',
                'justificado',
                'automatico',
            ])->nullable();
            $table->integer('dias_falta')->default(0);
            $table->integer('dias_penalizacion_extra')->default(0);
            $table->integer('total_dias_descuento')->default(0);
            $table->unsignedBigInteger('conciliado_por_usuario_id');
            $table->timestamp('conciliado_en')->nullable();
            $table->boolean('bloqueado_edicion')->default(false);
            $table->boolean('enviado_rh')->default(false);
            $table->timestamp('fecha_envio_rh')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['fecha', 'bloqueado_edicion'], 'asistencias_finales_fecha_bloqueo_idx');
            $table->index(['empresa_id', 'fecha'], 'asistencias_finales_empresa_fecha_idx');
            $table->index(['empleado_id', 'fecha'], 'asistencias_finales_empleado_fecha_idx');
            $table->unique(['empleado_id', 'fecha'], 'asistencias_finales_empleado_fecha_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_finales');
    }
};