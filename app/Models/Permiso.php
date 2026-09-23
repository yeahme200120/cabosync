<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'clave',
    ];

    // ============================
    // RELACIONES
    // ============================

    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_permisos',
            'permiso_id',
            'rol_id'
        )->withTimestamps();
    }
}