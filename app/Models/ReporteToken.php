<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteToken extends Model
{
    protected $table = 'reporte_tokens';

    protected $fillable = [
        'token',
        'empresa_id',
        'obra_id',
        'week',
        'pdf_path',
        'excel_path',
        'expira_en',
        'descargas',
    ];

    protected $casts = [
        'expira_en' => 'datetime',
    ];

    // ============================
    // SCOPES
    // ============================

    public function scopeVigentes($query)
    {
        return $query->where('expira_en', '>', now());
    }

    public function scopeExpirados($query)
    {
        return $query->where('expira_en', '<=', now());
    }

    // ============================
    // HELPERS
    // ============================

    public function estaVigente(): bool
    {
        return $this->expira_en->isFuture();
    }

    public function estaExpirado(): bool
    {
        return $this->expira_en->isPast();
    }

    public function registrarDescarga(): void
    {
        $this->increment('descargas');
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
}