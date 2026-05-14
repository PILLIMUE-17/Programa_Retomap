<?php

namespace App\Http\Controllers;

use App\Models\Publicacion;
use Illuminate\Http\Request;

class AdminPublicacionController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /api/admin/publicaciones
    // Lista todas las publicaciones con paginación
    // ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Publicacion::with([
            'usuario:id,nombre_usuario,username_usuario,avatar_url_usuario',
            'retoCompletado.reto:id,nombre_reto',
        ]);

        $publicaciones = $query->orderByDesc('created_at')->paginate(15);

        // Agregar campo descriptivo para el frontend
        $publicaciones->getCollection()->transform(function ($p) {
            $p->descripcion_publicacion = $p->caption_publicacion;
            $p->oculta_publicacion = !$p->visible_publicacion;
            return $p;
        });

        return response()->json($publicaciones);
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/admin/publicaciones/reportadas
    // Lista publicaciones ocultas / no visibles (reportadas)
    // ─────────────────────────────────────────────────────────
    public function reportadas(Request $request)
    {
        $query = Publicacion::with([
            'usuario:id,nombre_usuario,username_usuario,avatar_url_usuario',
            'retoCompletado.reto:id,nombre_reto',
        ])->where('visible_publicacion', false);

        $publicaciones = $query->orderByDesc('created_at')->paginate(15);

        $publicaciones->getCollection()->transform(function ($p) {
            $p->descripcion_publicacion = $p->caption_publicacion;
            $p->oculta_publicacion = !$p->visible_publicacion;
            return $p;
        });

        return response()->json($publicaciones);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/admin/publicaciones/{id}/ocultar
    // Ocultar una publicación (moderación)
    // ─────────────────────────────────────────────────────────
    public function ocultar(int $id)
    {
        $publicacion = Publicacion::findOrFail($id);
        $publicacion->visible_publicacion = false;
        $publicacion->save();

        return response()->json(['message' => 'Publicación ocultada correctamente.']);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/admin/publicaciones/{id}/restaurar
    // Restaurar una publicación oculta
    // ─────────────────────────────────────────────────────────
    public function restaurar(int $id)
    {
        $publicacion = Publicacion::findOrFail($id);
        $publicacion->visible_publicacion = true;
        $publicacion->save();

        return response()->json(['message' => 'Publicación restaurada correctamente.']);
    }

    // ─────────────────────────────────────────────────────────
    // DELETE /api/admin/publicaciones/{id}
    // Eliminar una publicación permanentemente (moderación)
    // ─────────────────────────────────────────────────────────
    public function destroy(int $id)
    {
        $publicacion = Publicacion::findOrFail($id);
        $publicacion->delete();

        return response()->json(['message' => 'Publicación eliminada correctamente.']);
    }
}
