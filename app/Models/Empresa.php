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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function scopeActivas($query)
    {
        return $query->where('estatus', 'activo');
    }

    /**
     * Filtra empresas según el rol del usuario.
     * - Admin: ve TODAS (matriz + externas).
     * - Contratista: solo empresas externas.
     * - Otros roles: solo su propia empresa.
     */
    public function scopeVisiblesPara($query, User $user)
    {
        if ($user->esAdministrador()) {
            return $query;
        }

        if ($user->esContratista()) {
            return $query->where('tipo', 'externa');
        }

        return $query->where('id', $user->empresa_id);
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

    // ============================
    // HELPERS
    // ============================

    public function esMatriz(): bool
    {
        return $this->tipo === 'matriz';
    }

    public function esExterna(): bool
    {
        return $this->tipo === 'externa';
    }

    public function estaActiva(): bool
    {
        return $this->estatus === 'activo';
    }
}