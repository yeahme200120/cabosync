<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoraExtra extends Model
{
    protected $table = 'horas_extras';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'horas_solicitadas',
        'horas_aprobadas',
        'estado',
        'aprobado_por_usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por_usuario_id');
    }
}