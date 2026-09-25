<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'origen_registro',
        'estado',
        'es_justificada',
        'justificacion_id',
        'evidencia_ruta',
        'horas_extra',
        'usuario_id',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'es_justificada'  => 'boolean',
        'horas_extra'     => 'decimal:2',
    ];

    // ============================
    // SCOPES
    // ============================

    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopePorOrigen($query, $origen)
    {
        return $query->where('origen_registro', $origen);
    }

    public function scopePresentes($query)
    {
        return $query->where('estado', 'presente');
    }

    public function scopeFaltas($query)
    {
        return $query->where('estado', 'falta');
    }

    public function scopeJustificadas($query)
    {
        return $query->where('es_justificada', true);
    }

    public function scopeConHorasExtra($query)
    {
        return $query->where('horas_extra', '>', 0);
    }

    // ============================
    // HELPERS
    // ============================

    public function esPresente(): bool
    {
        return $this->estado === 'presente';
    }

    public function esFalta(): bool
    {
        return $this->estado === 'falta';
    }

    public function estaJustificada(): bool
    {
        return $this->esFalta() && $this->es_justificada;
    }

    public function tieneEvidencia(): bool
    {
        return !empty($this->evidencia_ruta);
    }

    public function tieneHorasExtra(): bool
    {
        return (float) $this->horas_extra > 0;
    }

    // ============================
    // RELACIONES
    // ============================

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function justificacion()
    {
        return $this->belongsTo(Justificacion::class, 'justificacion_id');
    }
}