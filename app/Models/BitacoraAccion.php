<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraAccion extends Model
{
    protected $table = 'bitacora_acciones';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'empresa_id',
        'accion',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'tipo_accion',
        'modelo_afectado',
        'modelo_id',
        'direccion_ip',
        'latitud',
        'longitud',
        'precision_geo',
        'device_id',
        'user_agent',
        'plataforma',
        'navegador',
        'created_at',
    ];

    protected $casts = [
        'datos_antes'   => 'array',
        'datos_despues' => 'array',
        'created_at'    => 'datetime',
        'latitud'       => 'decimal:8',
        'longitud'      => 'decimal:8',
    ];

    // ============================
    // SCOPES
    // ============================

    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeDelUsuario($query, int $userId)
    {
        return $query->where('usuario_id', $userId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_accion', $tipo);
    }

    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('created_at', [$desde, $hasta]);
    }

    public function scopeConGeolocalizacion($query)
    {
        return $query->whereNotNull('latitud')->whereNotNull('longitud');
    }

    // ============================
    // HELPERS
    // ============================

    public function tieneGeolocalizacion(): bool
    {
        return !is_null($this->latitud) && !is_null($this->longitud);
    }

    public function tieneDiff(): bool
    {
        return !is_null($this->datos_antes) || !is_null($this->datos_despues);
    }

    public function tieneCambios(): bool
    {
        if (!$this->datos_antes || !$this->datos_despues) {
            return false;
        }
        return $this->datos_antes !== $this->datos_despues;
    }

    // ============================
    // RELACIONES
    // ============================

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}