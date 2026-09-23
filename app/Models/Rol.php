<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'tipo',
    ];

    protected $casts = [
        'tipo' => 'string',
    ];

    // ============================
    // SCOPES
    // ============================

    public function scopeSistema($query)
    {
        return $query->where('tipo', 'sistema');
    }

    public function scopeOperativo($query)
    {
        return $query->where('tipo', 'operativo');
    }

    // ============================
    // HELPERS
    // ============================

    public function puedeIniciarSesion(): bool
    {
        return $this->tipo === 'sistema';
    }

    // ============================
    // RELACIONES
    // ============================

    public function permisos()
    {
        return $this->belongsToMany(
            Permiso::class,
            'rol_permisos',
            'rol_id',
            'permiso_id'
        )->withTimestamps();
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'rol_id');
    }
}