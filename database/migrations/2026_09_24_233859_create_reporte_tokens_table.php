<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('obra_id')->nullable();
            $table->string('week', 10);
            $table->string('pdf_path', 255);
            $table->string('excel_path', 255);
            $table->timestamp('expira_en');
            $table->unsignedInteger('descargas')->default(0);
            $table->timestamps();

            $table->index('token');
            $table->index('expira_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_tokens');
    }
};