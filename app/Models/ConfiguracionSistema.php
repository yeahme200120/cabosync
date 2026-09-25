<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    protected $table = 'configuracion_sistema';

    protected $fillable = ['clave', 'valor'];

    /**
     * Obtiene el valor de una clave o un default.
     */
    public static function obtener(string $clave, $default = null)
    {
        return static::where('clave', $clave)->value('valor') ?? $default;
    }

    /**
     * Establece el valor de una clave.
     */
    public static function establecer(string $clave, $valor): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor]
        );
    }
}