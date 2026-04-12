<?php

namespace App\Observers;

use App\Models\LikePublicacion;

class LikePublicacionObserver
{
    // Se dispara cuando alguien da like
    public function created(LikePublicacion $like): void
    {
        // Incrementar el caché de likes en la publicación
        $like->publicacion()->increment('likes_cache_publicacion');

        // Notificar al dueño de la publicación
        $publicacion = $like->publicacion;

        // No notificar si el usuario se da like a sí mismo
        if ($publicacion->usuario_id === $like->usuario_id) return;

        $publicacion->usuario->notificaciones()->create([
            'tipo_notificacion'         => 'like',
            'titulo_notificacion'       => '¡A alguien le gustó tu publicación!',
            'cuerpo_notificacion'       => $like->usuario->nombre_usuario . ' le dio like a tu publicación.',
            'entidad_tipo_notificacion' => 'publicacion',
            'entidad_id_notificacion'   => $publicacion->id,
        ]);
    }

    // Se dispara cuando alguien quita el like
    public function deleted(LikePublicacion $like): void
    {
        // Decrementar pero nunca bajar de 0
        $like->publicacion()->decrement('likes_cache_publicacion');
    }
}