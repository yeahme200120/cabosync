<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'empleados';

    protected $fillable = [
        'empresa_id',
        'obra_id',
        'rol_id',
        'curp_dni',
        'nombre',
        'apellido',
        'puesto_cargo',
        'estatus',
        'registrado_por_usuario_id',
        'registrado_por_cargo',
        'es_titular_externo',
    ];

    protected $casts = [
        'es_titular_externo' => 'boolean',
    ];

    // ============================
    // ACCESORES
    // ============================

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeActivos($query)
    {
        return $query->where('estatus', 'activo');
    }

    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    // ============================
    // RELACIONES
    // ============================

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_usuario_id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'empleado_id');
    }

    public function asistenciasFinales()
    {
        return $this->hasMany(AsistenciaFinal::class, 'empleado_id');
    }

    public function justificaciones()
    {
        return $this->hasMany(Justificacion::class, 'empleado_id');
    }

    public function horasExtras()
    {
        return $this->hasMany(HoraExtra::class, 'empleado_id');
    }
}