<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'empleados';

    protected $fillable = [
        'empresa_id',
        'obra_id',
        'rol_id',
        'curp_dni',
        'foto',
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

    /**
     * URL pública de la foto del empleado.
     * Si no tiene foto, devuelve el avatar por defecto.
     */
    public function getFotoUrlAttribute(): string
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return asset('storage/' . $this->foto);
        }

        return asset('img/avatar-default.png');
    }

    /**
     * Indica si el empleado tiene foto personalizada.
     */
    public function getTieneFotoAttribute(): bool
    {
        return $this->foto && Storage::disk('public')->exists($this->foto);
    }

    /**
     * Iniciales del empleado (para avatares alternativos).
     */
    public function getInicialesAttribute(): string
    {
        $n = mb_substr($this->nombre, 0, 1);
        $a = mb_substr($this->apellido, 0, 1);
        return mb_strtoupper("{$n}{$a}");
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