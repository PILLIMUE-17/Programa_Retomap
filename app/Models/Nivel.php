<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    protected $table = 'niveles';

    protected $fillable = [
        'nombre_nivel',
        'xp_requerido_nivel',
        'insignia_url_nivel',
        'descripcion_nivel',
    ];

    // Un nivel tiene muchos usuarios
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'nivel_id');
    }
}