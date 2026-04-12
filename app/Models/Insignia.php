<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insignia extends Model
{
    protected $table = 'insignias';
 
    protected $fillable = [
        'nombre_insignia',
        'descripcion_insignia',
        'icono_insignia',
        'condicion_insignia',
        'categoria_insignia',
    ];
 
    // Muchos usuarios tienen esta insignia
    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            'usuario_insignias',
            'insignia_id',
            'usuario_id'
        )->withPivot('obtenida_en_usuario_insignia');
    }
}
