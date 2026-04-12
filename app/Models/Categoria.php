<?php
// ═══════════════════════════════════════════════════
// CATEGORIA
// ═══════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'nombre_categoria',
        'icono_categoria',
        'color_hex_categoria',
    ];

    // Una categoría tiene muchos lugares
    public function lugares()
    {
        return $this->hasMany(Lugar::class, 'categoria_id');
    }
}