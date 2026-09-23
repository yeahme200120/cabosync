<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaFinal extends Model
{
    protected $table = 'asistencias_finales';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'estado_final',
        'dias_falta',
        'dias_penalizacion_extra',
        'total_dias_descuento',
        'conciliado_por_usuario_id',
        'enviado_rh',
        'fecha_envio_rh',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_envio_rh' => 'datetime',
        'enviado_rh' => 'boolean',
    ];

    // ============================
    // RELACIONES
    // ============================

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function conciliadoPor()
    {
        return $this->belongsTo(User::class, 'conciliado_por_usuario_id');
    }
}