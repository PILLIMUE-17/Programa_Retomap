<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LikeComentario extends Model
{
    public $timestamps = false;
    protected $table = 'like_comentarios';
    public $incrementing = false;

    protected $fillable = ['usuario_id', 'comentario_id'];

    protected $casts = ['created_at' => 'datetime'];

    public function comentario()
    {
        return $this->belongsTo(Comentario::class, 'comentario_id');
    }
}
