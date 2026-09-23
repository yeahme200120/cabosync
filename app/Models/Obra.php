<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obra extends Model
{
    protected $table = 'obras';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'codigo',
        'ubicacion',
        'fecha_inicio',
        'fecha_fin',
        'estatus',
        'creado_por_usuario_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    // ============================
    // RELACIONES
    // ============================

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'obra_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_usuario_id');
    }
}