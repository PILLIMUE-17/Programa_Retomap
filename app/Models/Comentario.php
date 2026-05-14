<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
 
    protected $table = 'comentarios';
 
    protected $fillable = [
        'publicacion_id',
        'parent_id',
        'usuario_id',
        'contenido_comentario',
        'likes_cache_comentario',
        'visible_comentario',
    ];

    protected $casts = [
        'visible_comentario'     => 'boolean',
        'likes_cache_comentario' => 'integer',
    ];

    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function replies()
    {
        return $this->hasMany(Comentario::class, 'parent_id')
                    ->where('visible_comentario', true)
                    ->with('usuario:id,nombre_usuario')
                    ->orderBy('created_at');
    }

    public function likeComentarios()
    {
        return $this->hasMany(LikeComentario::class, 'comentario_id');
    }
}