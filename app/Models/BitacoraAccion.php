<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraAccion extends Model
{
    protected $table = 'bitacora_acciones';

    public $timestamps = false; // Solo usa created_at

    protected $fillable = [
        'usuario_id',
        'accion',
        'descripcion',
        'direccion_ip',
        'navegador',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}