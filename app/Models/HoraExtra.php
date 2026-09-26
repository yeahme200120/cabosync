<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HoraExtra extends Model
{
    protected $table = 'horas_extras';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'horas_solicitadas',
        'horas_aprobadas',
        'estado',
        'aprobado_por_usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // ============================
    // RELACIONES
    // ============================

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por_usuario_id');
    }

    // ============================
    // SCOPES
    // ============================

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobadas(Builder $query): Builder
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeRechazadas(Builder $query): Builder
    {
        return $query->where('estado', 'rechazado');
    }

    public function scopeEnRangoFecha(Builder $query, string $inicio, string $fin): Builder
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }

    public function scopeDeEmpresa(Builder $query, int $empresaId): Builder
    {
        return $query->whereHas('empleado', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        });
    }

    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        if ($user->esAdministrador()) {
            return $query;
        }

        return $query->deEmpresa($user->empresa_id);
    }

    // ============================
    // HELPERS
    // ============================

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaAprobada(): bool
    {
        return $this->estado === 'aprobado';
    }

    public function estaRechazada(): bool
    {
        return $this->estado === 'rechazado';
    }

    public function badgeEstado(): string
    {
        return match ($this->estado) {
            'aprobado'  => 'success',
            'rechazado' => 'danger',
            'pendiente' => 'warning',
            default     => 'secondary',
        };
    }

    public function textoEstado(): string
    {
        return match ($this->estado) {
            'aprobado'  => 'Aprobado',
            'rechazado' => 'Rechazado',
            'pendiente' => 'Pendiente',
            default     => 'Desconocido',
        };
    }
}