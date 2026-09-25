<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_acciones', function (Blueprint $table) {
            $table->id();

            // Usuario y empresa
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable();

            // Marca si la acción fue realizada sin autenticación (público general)
            $table->boolean('es_publico')->default(false);

            // Acción
            $table->string('accion', 100);
            $table->text('descripcion');
            $table->enum('tipo_accion', [
                'insert',
                'update',
                'delete',
                'login',
                'logout',
                'view',
                'otro',
            ])->default('otro');

            // Modelo afectado (para diff)
            $table->string('modelo_afectado', 100)->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();

            // Datos antes/después (JSON)
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();

            // Geolocalización y contexto
            $table->string('direccion_ip', 45)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('precision_geo', 50)->nullable();
            $table->string('device_id', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('plataforma', 50)->nullable();
            $table->text('navegador')->nullable();

            // Timestamp
            $table->timestamp('created_at')->nullable();

            // Índices
            $table->index(['empresa_id', 'created_at'], 'bitacora_empresa_fecha_idx');
            $table->index(['tipo_accion'], 'bitacora_tipo_accion_idx');
            $table->index(['modelo_afectado', 'modelo_id'], 'bitacora_modelo_idx');
            $table->index(['usuario_id', 'created_at'], 'bitacora_usuario_fecha_idx');
            $table->index(['es_publico'], 'bitacora_es_publico_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_acciones');
    }
};