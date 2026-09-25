<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaFinal extends Model
{
    protected $table = 'asistencias_finales';

    protected $fillable = [
        'empleado_id',
        'empresa_id',  
        'fecha',
        'estado_final',
        'origen_adoptado',
        'dias_falta',
        'dias_penalizacion_extra',
        'total_dias_descuento',
        'conciliado_por_usuario_id',
        'conciliado_en',
        'bloqueado_edicion',
        'enviado_rh',
        'fecha_envio_rh',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'conciliado_en'     => 'datetime',
        'fecha_envio_rh'    => 'datetime',
        'bloqueado_edicion' => 'boolean',
        'enviado_rh'        => 'boolean',
    ];

    // ============================
    // SCOPES
    // ============================

    public function scopeConciliadas($query)
    {
        return $query->whereNotNull('estado_final');
    }

    public function scopeBloqueadas($query)
    {
        return $query->where('bloqueado_edicion', true);
    }

    public function scopeSinBloqueo($query)
    {
        return $query->where('bloqueado_edicion', false);
    }

    public function scopeEnviadasRH($query)
    {
        return $query->where('enviado_rh', true);
    }

    public function scopePorSemana($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    // ============================
    // HELPERS
    // ============================

    public function estaConciliada(): bool
    {
        return !is_null($this->estado_final);
    }

    public function estaBloqueada(): bool
    {
        return (bool) $this->bloqueado_edicion;
    }

    public function esAsistencia(): bool
    {
        return $this->estado_final === 'asistencia';
    }

    public function esAsistenciaJustificada(): bool
    {
        return $this->estado_final === 'asistencia_justificada';
    }

    public function esFaltaInjustificada(): bool
    {
        return $this->estado_final === 'falta_injustificada';
    }

    // ============================
    // RELACIONES
    // ============================

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function conciliadoPor()
    {
        return $this->belongsTo(User::class, 'conciliado_por_usuario_id');
    }
}