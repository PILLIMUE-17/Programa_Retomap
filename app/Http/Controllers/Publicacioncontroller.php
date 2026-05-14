<?php

namespace App\Http\Controllers;

use App\Models\Publicacion;
use App\Models\LikePublicacion;
use App\Models\LikeComentario;
use App\Models\Comentario;
use App\Models\RetoCompletado;
use App\Models\Amistad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicacionController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /api/feed
    // Feed global de publicaciones paginado
    // ─────────────────────────────────────────────────
    public function feed(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $publicaciones = Publicacion::with([
            'usuario:id,nombre_usuario,avatar_url_usuario,xp_total_usuario',
            'retoCompletado.reto.lugar',
        ])
        ->visibles()
        ->orderByDesc('created_at')
        ->paginate(15);

        // IDs de publicaciones a las que el usuario ya dio like
        $misLikes = LikePublicacion::where('usuario_id', $usuario->id)
            ->whereIn('publicacion_id', $publicaciones->pluck('id'))
            ->pluck('publicacion_id')
            ->toArray();

        return response()->json([
            'data' => $publicaciones->map(fn($p) => array_merge(
                $this->formatoPublicacion($p),
                ['yo_di_like' => in_array($p->id, $misLikes)]
            )),
            'total'      => $publicaciones->total(),
            'pagina'     => $publicaciones->currentPage(),
            'por_pagina' => $publicaciones->perPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/feed/trending
    // Posts ordenados por score: likes + comentarios*2 + compartidos*3
    // Solo últimos 30 días
    // ─────────────────────────────────────────────────
    public function trending(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $publicaciones = Publicacion::with([
            'usuario:id,nombre_usuario,avatar_url_usuario',
            'retoCompletado.reto.lugar',
        ])
        ->visibles()
        ->where('created_at', '>=', now()->subDays(30))
        ->orderByRaw(
            '(likes_cache_publicacion + (comentarios_cache_publicacion * 2) + (compartidos_cache_publicacion * 3)) DESC'
        )
        ->paginate(15);

        $misLikes = LikePublicacion::where('usuario_id', $usuario->id)
            ->whereIn('publicacion_id', $publicaciones->pluck('id'))
            ->pluck('publicacion_id')
            ->toArray();

        return response()->json([
            'data'   => $publicaciones->map(fn($p) => array_merge(
                $this->formatoPublicacion($p),
                ['yo_di_like' => in_array($p->id, $misLikes)]
            )),
            'total'  => $publicaciones->total(),
            'pagina' => $publicaciones->currentPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/publicaciones/autopublicar
    // Crea publicaciones para retos completados sin publicación
    // ─────────────────────────────────────────────────
    public function autopublicar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $sinPublicar = RetoCompletado::where('usuario_id', $usuario->id)
            ->whereDoesntHave('publicacion')
            ->get();

        foreach ($sinPublicar as $completado) {
            Publicacion::create([
                'usuario_id'             => $usuario->id,
                'reto_completado_id'     => $completado->id,
                'caption_publicacion'    => null,
                'imagen_url_publicacion' => $completado->evidencia_url_reto_completado,
                'visible_publicacion'    => true,
            ]);
        }

        return response()->json(['publicaciones_creadas' => $sinPublicar->count()]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/comentarios/{id}/like
    // Toggle like en un comentario
    // ─────────────────────────────────────────────────
    public function likeComentario(Request $request, int $id): JsonResponse
    {
        $comentario = Comentario::find($id);
        if (!$comentario) {
            return response()->json(['message' => 'Comentario no encontrado.'], 404);
        }

        $usuarioId = $request->user()->id;
        $like = LikeComentario::where('usuario_id', $usuarioId)
                    ->where('comentario_id', $id)
                    ->first();

        if ($like) {
            $like->delete();
            $comentario->decrement('likes_cache_comentario');
            $yoDiLike = false;
        } else {
            LikeComentario::create([
                'usuario_id'    => $usuarioId,
                'comentario_id' => $id,
            ]);
            $comentario->increment('likes_cache_comentario');
            $yoDiLike = true;
        }

        $comentario->refresh();
        return response()->json([
            'yo_di_like' => $yoDiLike,
            'likes'      => $comentario->likes_cache_comentario,
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/publicaciones/{id}/compartir
    // Incrementa el contador de compartidos
    // ─────────────────────────────────────────────────
    public function compartir(Request $request, int $id): JsonResponse
    {
        $publicacion = Publicacion::find($id);
        if (!$publicacion) {
            return response()->json(['message' => 'Publicación no encontrada.'], 404);
        }
        $publicacion->increment('compartidos_cache_publicacion');
        return response()->json(['compartidos' => $publicacion->compartidos_cache_publicacion]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/feed/amigos
    // Feed filtrado a amigos aceptados del usuario
    // ─────────────────────────────────────────────────
    public function feedAmigos(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $amigosIds = Amistad::where(function ($q) use ($usuario) {
                $q->where('solicitante_id', $usuario->id)
                  ->orWhere('receptor_id', $usuario->id);
            })
            ->where('estado_amistad', 'aceptada')
            ->get()
            ->map(fn($a) => $a->solicitante_id == $usuario->id
                ? $a->receptor_id
                : $a->solicitante_id)
            ->toArray();

        if (empty($amigosIds)) {
            return response()->json([
                'data'          => [],
                'total'         => 0,
                'pagina'        => 1,
                'tiene_amigos'  => false,
            ]);
        }

        $publicaciones = Publicacion::with([
            'usuario:id,nombre_usuario,avatar_url_usuario',
            'retoCompletado.reto.lugar',
        ])
        ->whereIn('usuario_id', $amigosIds)
        ->visibles()
        ->orderByDesc('created_at')
        ->paginate(15);

        $misLikes = LikePublicacion::where('usuario_id', $usuario->id)
            ->whereIn('publicacion_id', $publicaciones->pluck('id'))
            ->pluck('publicacion_id')
            ->toArray();

        return response()->json([
            'data'          => $publicaciones->map(fn($p) => array_merge(
                $this->formatoPublicacion($p),
                ['yo_di_like' => in_array($p->id, $misLikes)]
            )),
            'total'         => $publicaciones->total(),
            'pagina'        => $publicaciones->currentPage(),
            'tiene_amigos'  => true,
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/publicaciones
    // Crear una publicación (post del reto completado)
    // ─────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'reto_completado_id' => ['required', 'integer', 'exists:reto_completados,id'],
            'caption'            => ['nullable', 'string', 'max:500'],
            'imagen_url'         => ['nullable', 'url', 'max:500'],
        ]);

        // Verificar que el reto_completado pertenece al usuario logueado
        $retoCompletado = RetoCompletado::find($request->reto_completado_id);
        $usuarioActual = $request->user();

        if (!$retoCompletado) {
            return response()->json([
                'message' => 'Reto completado no encontrado.',
            ], 404);
        }

        if ((int) $retoCompletado->usuario_id !== (int) $usuarioActual->id) {
            return response()->json([
                'message' => 'No puedes publicar un reto que no es tuyo.',
            ], 403);
        }

        // Verificar que no haya publicado ya ese reto completado
        $yaPublicado = Publicacion::where('reto_completado_id', $request->reto_completado_id)->exists();
        if ($yaPublicado) {
            return response()->json([
                'message' => 'Ya publicaste este reto completado.',
            ], 409);
        }

        $publicacion = Publicacion::create([
            'usuario_id'             => $request->user()->id,
            'reto_completado_id'     => $request->reto_completado_id,
            'caption_publicacion'    => $request->caption,
            'imagen_url_publicacion' => $request->imagen_url,
            'visible_publicacion'    => true,
        ]);

        return response()->json([
            'message'      => '¡Publicación creada!',
            'publicacion'  => $this->formatoPublicacion($publicacion->load('usuario', 'retoCompletado.reto')),
        ], 201);
    }

    // ─────────────────────────────────────────────────
    // GET /api/publicaciones/{id}
    // Detalle de una publicación con comentarios
    // ─────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $publicacion = Publicacion::with([
            'usuario:id,nombre_usuario,avatar_url_usuario',
            'retoCompletado.reto.lugar',
            'comentarios' => fn($q) => $q->where('visible_comentario', true)
                                         ->whereNull('parent_id')
                                         ->with([
                                             'usuario:id,nombre_usuario',
                                             'replies.usuario:id,nombre_usuario',
                                         ])
                                         ->orderByDesc('created_at')
                                         ->limit(30),
        ])->find($id);

        if (!$publicacion || !$publicacion->visible_publicacion) {
            return response()->json(['message' => 'Publicación no encontrada.'], 404);
        }

        $usuario      = $request->user();
        $yoDiLike     = LikePublicacion::where('usuario_id', $usuario->id)
                            ->where('publicacion_id', $id)->exists();

        // IDs de todos los comentarios y sus respuestas para saber cuales tiene like
        $todosIds = $publicacion->comentarios
            ->pluck('id')
            ->merge($publicacion->comentarios->flatMap(fn($c) => $c->replies->pluck('id')))
            ->toArray();

        $misLikes = LikeComentario::where('usuario_id', $usuario->id)
            ->whereIn('comentario_id', $todosIds)
            ->pluck('comentario_id')
            ->toArray();

        $formatComentario = fn($c) => [
            'id'         => $c->id,
            'contenido'  => $c->contenido_comentario,
            'usuario'    => [
                'id'     => $c->usuario?->id,
                'nombre' => $c->usuario?->nombre_usuario ?? 'Usuario',
            ],
            'likes'      => $c->likes_cache_comentario ?? 0,
            'yo_di_like' => in_array($c->id, $misLikes),
            'fecha'      => $c->created_at->toDateTimeString(),
        ];

        return response()->json([
            'publicacion' => array_merge(
                $this->formatoPublicacion($publicacion),
                [
                    'yo_di_like'  => $yoDiLike,
                    'comentarios' => $publicacion->comentarios->map(fn($c) => array_merge(
                        $formatComentario($c),
                        ['respuestas' => $c->replies->map($formatComentario)->values()]
                    )),
                ]
            ),
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/publicaciones/{id}/like
    // Dar o quitar like (toggle)
    // ─────────────────────────────────────────────────
    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $publicacion = Publicacion::find($id);

        if (!$publicacion) {
            return response()->json(['message' => 'Publicación no encontrada.'], 404);
        }

        $usuarioId = $request->user()->id;

        $like = LikePublicacion::where('usuario_id', $usuarioId)
            ->where('publicacion_id', $id)
            ->first();

        if ($like) {
            // Ya tenía like → quitarlo
            $like->delete(); // Observer decrementa likes_cache
            $accion = 'quitado';
        } else {
            // No tenía like → darlo
            LikePublicacion::create([
                'usuario_id'     => $usuarioId,
                'publicacion_id' => $id,
            ]); // Observer incrementa likes_cache y notifica
            $accion = 'dado';
        }

        $publicacion->refresh();

        return response()->json([
            'message'    => 'Like ' . $accion . '.',
            'yo_di_like' => $accion === 'dado',
            'total_likes' => $publicacion->likes_cache_publicacion,
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/publicaciones/{id}/comentar
    // Agregar un comentario
    // ─────────────────────────────────────────────────
    public function comentar(Request $request, int $id): JsonResponse
    {
        $publicacion = Publicacion::find($id);

        if (!$publicacion || !$publicacion->visible_publicacion) {
            return response()->json(['message' => 'Publicación no encontrada.'], 404);
        }

        $request->validate([
            'contenido'  => ['required', 'string', 'min:1', 'max:300'],
            'parent_id'  => ['nullable', 'integer', 'exists:comentarios,id'],
        ]);

        $comentario = Comentario::create([
            'publicacion_id'       => $id,
            'parent_id'            => $request->parent_id,
            'usuario_id'           => $request->user()->id,
            'contenido_comentario' => $request->contenido,
            'visible_comentario'   => true,
        ]);

        // Solo contar en cache los comentarios raíz
        if (!$request->parent_id) {
            $publicacion->increment('comentarios_cache_publicacion');
        }

        return response()->json([
            'message'    => 'Comentario agregado.',
            'comentario' => [
                'id'        => $comentario->id,
                'contenido' => $comentario->contenido_comentario,
                'usuario'   => $request->user()->nombre_usuario,
                'fecha'     => $comentario->created_at->toDateTimeString(),
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────
    // GET /api/publicaciones/usuario/{id}
    // Publicaciones de un usuario específico
    // ─────────────────────────────────────────────────
    public function porUsuario(Request $request, int $usuarioId): JsonResponse
    {
        $publicaciones = Publicacion::with([
            'usuario:id,nombre_usuario,avatar_url_usuario',
            'retoCompletado.reto',
        ])
        ->where('usuario_id', $usuarioId)
        ->visibles()
        ->orderByDesc('created_at')
        ->paginate(15);

        return response()->json([
            'data'       => $publicaciones->map(fn($p) => $this->formatoPublicacion($p)),
            'total'      => $publicaciones->total(),
            'pagina'     => $publicaciones->currentPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // DELETE /api/publicaciones/{id}
    // Eliminar propia publicación (soft delete)
    // ─────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $publicacion = Publicacion::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->first();

        if (!$publicacion) {
            return response()->json([
                'message' => 'Publicación no encontrada o no tienes permiso.',
            ], 404);
        }

        $publicacion->delete(); // soft delete

        return response()->json(['message' => 'Publicación eliminada.']);
    }

    // ─────────────────────────────────────────────────
    // Formato reutilizable
    // ─────────────────────────────────────────────────
    // 
    private function formatoPublicacion(Publicacion $p): array
    {
        return [
            'id'          => $p->id,
            'caption'     => $p->caption_publicacion,
            'imagen_url'  => $p->imagen_url_publicacion,
            'likes'       => $p->likes_cache_publicacion ?? 0,
            'comentarios' => $p->comentarios_cache_publicacion ?? 0,
            'compartidos' => $p->compartidos_cache_publicacion ?? 0,
            'fecha'       => $p->created_at?->toDateTimeString(),
            'usuario'     => [
                'id'     => $p->usuario?->id,
                'nombre' => $p->usuario?->nombre_usuario,
                'avatar' => $p->usuario?->avatar_url_usuario,
            ],
            'reto'        => $p->retoCompletado?->reto ? [
                'nombre' => $p->retoCompletado->reto->nombre_reto,
                'lugar'  => $p->retoCompletado->reto->lugar?->nombre_lugar,
                'xp'     => $p->retoCompletado->xp_ganado_reto_completado,
            ] : null,
        ];
    }
}