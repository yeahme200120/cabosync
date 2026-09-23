<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Justificacion extends Model
{
    protected $table = 'justificaciones';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'motivo',
        'descripcion',
        'ruta_archivo',
        'subido_por_usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por_usuario_id');
    }
}