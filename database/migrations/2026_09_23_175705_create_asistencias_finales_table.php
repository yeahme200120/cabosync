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
            $table->date('fecha');
            $table->enum('estado_final', ['asistencia', 'asistencia_justificada', 'falta_injustificada']);
            $table->integer('dias_falta')->default(0);
            $table->integer('dias_penalizacion_extra')->default(0);
            $table->integer('total_dias_descuento')->default(0);
            $table->unsignedBigInteger('conciliado_por_usuario_id');
            $table->boolean('enviado_rh')->default(false);
            $table->timestamp('fecha_envio_rh')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_finales');
    }
};