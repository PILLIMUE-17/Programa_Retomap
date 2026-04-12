<?php

namespace App\Http\Controllers;

use App\Models\Publicacion;
use App\Models\LikePublicacion;
use App\Models\Comentario;
use App\Models\RetoCompletado;
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
                                         ->with('usuario:id,nombre_usuario,avatar_url_usuario')
                                         ->orderByDesc('created_at')
                                         ->limit(20),
        ])->find($id);

        if (!$publicacion || !$publicacion->visible_publicacion) {
            return response()->json(['message' => 'Publicación no encontrada.'], 404);
        }

        $yoDiLike = LikePublicacion::where('usuario_id', $request->user()->id)
            ->where('publicacion_id', $id)
            ->exists();

        return response()->json([
            'publicacion' => array_merge(
                $this->formatoPublicacion($publicacion),
                [
                    'yo_di_like'  => $yoDiLike,
                    'comentarios' => $publicacion->comentarios->map(fn($c) => [
                        'id'         => $c->id,
                        'contenido'  => $c->contenido_comentario,
                        'usuario'    => $c->usuario->nombre_usuario,
                        'avatar'     => $c->usuario->avatar_url_usuario,
                        'fecha'      => $c->created_at->toDateTimeString(),
                    ]),
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
            'contenido' => ['required', 'string', 'min:1', 'max:300'],
        ]);

        $comentario = Comentario::create([
            'publicacion_id'      => $id,
            'usuario_id'          => $request->user()->id,
            'contenido_comentario' => $request->contenido,
            'visible_comentario'  => true,
        ]);

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
            'likes'       => $p->likes_cache_publicacion,
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