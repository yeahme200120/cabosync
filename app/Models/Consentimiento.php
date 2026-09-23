<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consentimiento extends Model
{
    protected $table = 'consentimientos';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'version',
        'aceptado',
        'ip',
        'user_agent',
        'aceptado_en',
        'revocado_en',
    ];

    protected $casts = [
        'aceptado' => 'boolean',
        'aceptado_en' => 'datetime',
        'revocado_en' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}