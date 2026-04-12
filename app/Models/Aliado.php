<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aliado extends Model
{
    use SoftDeletes;

    protected $table = 'aliados';

    protected $fillable = [
        'nombre_negocio_aliado',
        'tipo_negocio_aliado',
        'descripcion_aliado',
        'contacto_email_aliado',
        'contacto_telefono_aliado',
        'logo_url_aliado',
        'sitio_web_aliado',
        'municipio_aliado',
        'activo_aliado',
    ];

    protected $casts = [
        'activo_aliado' => 'boolean',
    ];

    // Un aliado tiene muchos lugares
    public function lugares()
    {
        return $this->hasMany(Lugar::class, 'aliado_id');
    }

    // Un aliado tiene muchos beneficios
    public function beneficios()
    {
        return $this->hasMany(Beneficio::class, 'aliado_id');
    }
}