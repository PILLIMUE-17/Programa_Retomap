<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
 
    protected $table = 'comentarios';
 
    protected $fillable = [
        'publicacion_id',
        'usuario_id',
        'contenido_comentario',
        'visible_comentario',
    ];
 
    protected $casts = [
        'visible_comentario' => 'boolean',
    ];
 
    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }
 
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}