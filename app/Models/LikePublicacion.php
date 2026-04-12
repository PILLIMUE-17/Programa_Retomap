<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class LikePublicacion extends Model
{
    protected $table    = 'like_publicaciones';
    public $timestamps  = false; // solo tiene created_at, no updated_at
    public $incrementing = false; // PK compuesta, no autoincrement
 
    protected $fillable = [
        'usuario_id',
        'publicacion_id',
    ];
 
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
 
    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }
}
