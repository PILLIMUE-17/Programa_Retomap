<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class Publicacion extends Model
{
    use SoftDeletes;
 
    protected $table = 'publicaciones';
 
    protected $fillable = [
        'usuario_id',
        'reto_completado_id',
        'caption_publicacion',
        'imagen_url_publicacion',
        'likes_cache_publicacion',
        'visible_publicacion',
    ];
 
    protected $casts = [
        'visible_publicacion'      => 'boolean',
        'likes_cache_publicacion'  => 'integer',
    ];
 
    // Pertenece a un usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
 
    // Pertenece a un reto completado
    public function retoCompletado()
    {
        return $this->belongsTo(RetoCompletado::class, 'reto_completado_id');
    }
 
    // Tiene muchos likes
    public function likes()
    {
        return $this->hasMany(LikePublicacion::class, 'publicacion_id');
    }
 
    // Tiene muchos comentarios
    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'publicacion_id');
    }
 
    // ─── HELPER: ¿El usuario ya le dio like? ──────────────────────
    // Uso: $publicacion->yaLeDioLike($usuarioId)
    public function yaLeDioLike(int $usuarioId): bool
    {
        return $this->likes()->where('usuario_id', $usuarioId)->exists();
    }
 
    // ─── SCOPE: solo visibles ──────────────────────────────────────
    public function scopeVisibles($query)
    {
        return $query->where('visible_publicacion', true);
    }
}
