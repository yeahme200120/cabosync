<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'empresa_id',
        'rol_id',
        'nombre',
        'email',
        'password',
        'estatus',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ============================
    // RELACIONES
    // ============================

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function consentimientos()
    {
        return $this->hasMany(Consentimiento::class, 'usuario_id');
    }

    // ============================
    // SCOPES DE AISLAMIENTO
    // ============================

    public function scopeActivos($query)
    {
        return $query->where('estatus', 'activo');
    }

    public function scopeOcultarAdmin($query)
    {
        return $query->whereHas('rol', function ($q) {
            $q->where('codigo', '!=', 'admin');
        });
    }

    // ============================
    // HELPERS
    // ============================

    public function esAdminRaiz(): bool
    {
        return $this->rol?->codigo === 'admin';
    }

    public function esContratista(): bool
    {
        return $this->rol?->codigo === 'contratista';
    }

    public function esAdministrador(): bool
    {
        return $this->rol?->codigo === 'admin';
    }

    public function tienePermiso(string $clave): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }

        return $this->rol
            ?->permisos()
            ->where('clave', $clave)
            ->exists() ?? false;
    }

    public function empresaFiltroId(): ?int
    {
        return $this->empresa_id;
    }
}