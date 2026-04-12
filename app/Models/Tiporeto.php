<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoReto extends Model
{
    protected $table = 'tipo_retos';

    protected $fillable = [
        'nombre_tipo_reto',
        'icono_tipo_reto',
        'descripcion_tipo_reto',
    ];

    // Un tipo de reto tiene muchos retos
    public function retos()
    {
        return $this->hasMany(Reto::class, 'tipo_reto_id');
    }
}