<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'origen_registro',
        'estado',
        'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // ============================
    // RELACIONES
    // ============================

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}