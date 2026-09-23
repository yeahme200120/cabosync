<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'rfc',
        'tipo',
        'estatus',
        'creado_por_usuario_id',
    ];

    // ============================
    // SCOPES
    // ============================

    public function scopeMatriz($query)
    {
        return $query->where('tipo', 'matriz');
    }

    public function scopeExternas($query)
    {
        return $query->where('tipo', 'externa');
    }

    // ============================
    // RELACIONES
    // ============================

    public function usuarios()
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'empresa_id');
    }

    public function obras()
    {
        return $this->hasMany(Obra::class, 'empresa_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_usuario_id');
    }
}