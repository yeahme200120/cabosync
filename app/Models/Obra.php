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
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ============================
    // SCOPES
    // ============================

    public function scopeActivas($query)
    {
        return $query->where('estatus', 'activa');
    }

    public function scopePausadas($query)
    {
        return $query->where('estatus', 'pausada');
    }

    public function scopeTerminadas($query)
    {
        return $query->where('estatus', 'terminada');
    }

    /**
     * Filtra obras según el rol del usuario.
     * - Admin: ve TODAS las obras.
     * - Contratista: solo obras de su empresa.
     * - Otros roles: solo obras de su empresa.
     */
    public function scopeVisiblesPara($query, User $user)
    {
        if ($user->esAdministrador()) {
            return $query;
        }

        return $query->where('empresa_id', $user->empresa_id);
    }

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

    // ============================
    // HELPERS
    // ============================

    public function estaActiva(): bool
    {
        return $this->estatus === 'activa';
    }

    public function estaPausada(): bool
    {
        return $this->estatus === 'pausada';
    }

    public function estaTerminada(): bool
    {
        return $this->estatus === 'terminada';
    }

    /**
     * Devuelve un color de badge según el estatus.
     */
    public function badgeEstatus(): string
    {
        return match ($this->estatus) {
            'activa'    => 'success',
            'pausada'   => 'warning',
            'terminada' => 'secondary',
            default     => 'secondary',
        };
    }

    /**
     * Devuelve el texto legible del estatus.
     */
    public function textoEstatus(): string
    {
        return match ($this->estatus) {
            'activa'    => 'Activa',
            'pausada'   => 'Pausada',
            'terminada' => 'Terminada',
            default     => 'Desconocido',
        };
    }
}