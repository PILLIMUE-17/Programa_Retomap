<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /api/notificaciones
    // Listar mis notificaciones paginadas
    // ─────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Notificacion::where('usuario_id', $request->user()->id)
            ->orderByDesc('created_at');

        // Filtro: solo no leídas /api/notificaciones?no_leidas=1
        if ($request->boolean('no_leidas')) {
            $query->where('leida_notificacion', false);
        }

        $notificaciones = $query->paginate(20);

        return response()->json([
            'data' => $notificaciones->map(fn($n) => $this->formato($n)),
            'total'      => $notificaciones->total(),
            'pagina'     => $notificaciones->currentPage(),
            'no_leidas'  => Notificacion::where('usuario_id', $request->user()->id)
                                ->where('leida_notificacion', false)
                                ->count(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/notificaciones/contador
    // Solo el número de no leídas — para el ícono de campana
    // ─────────────────────────────────────────────────
    public function contador(Request $request): JsonResponse
    {
        $noLeidas = Notificacion::where('usuario_id', $request->user()->id)
            ->where('leida_notificacion', false)
            ->count();

        return response()->json([
            'no_leidas' => $noLeidas,
        ]);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/notificaciones/{id}/leer
    // Marcar una notificación como leída
    // ─────────────────────────────────────────────────
    public function marcarLeida(Request $request, int $id): JsonResponse
    {
        $notificacion = Notificacion::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->first();

        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada.'], 404);
        }

        if ($notificacion->leida_notificacion) {
            return response()->json(['message' => 'Ya estaba marcada como leída.']);
        }

        $notificacion->marcarLeida(); // método del modelo

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/notificaciones/leer-todas
    // Marcar TODAS mis notificaciones como leídas
    // ─────────────────────────────────────────────────
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $cantidad = Notificacion::where('usuario_id', $request->user()->id)
            ->where('leida_notificacion', false)
            ->update([
                'leida_notificacion'    => true,
                'leida_en_notificacion' => now(),
            ]);

        return response()->json([
            'message'   => 'Todas las notificaciones marcadas como leídas.',
            'marcadas'  => $cantidad,
        ]);
    }

    // ─────────────────────────────────────────────────
    // DELETE /api/notificaciones/{id}
    // Eliminar una notificación
    // ─────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notificacion = Notificacion::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->first();

        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada.'], 404);
        }

        $notificacion->delete();

        return response()->json(['message' => 'Notificación eliminada.']);
    }

    // ─────────────────────────────────────────────────
    // DELETE /api/notificaciones/limpiar/leidas
    // Eliminar todas las notificaciones leídas
    // ─────────────────────────────────────────────────
    public function limpiarLeidas(Request $request): JsonResponse
    {
        $cantidad = Notificacion::where('usuario_id', $request->user()->id)
            ->where('leida_notificacion', true)
            ->delete();

        return response()->json([
            'message'    => 'Notificaciones leídas eliminadas.',
            'eliminadas' => $cantidad,
        ]);
    }

    // ─────────────────────────────────────────────────
    // Formato reutilizable
    // ─────────────────────────────────────────────────
    private function formato(Notificacion $n): array
    {
        return [
            'id'       => $n->id,
            'tipo'     => $n->tipo_notificacion,
            'titulo'   => $n->titulo_notificacion,
            'cuerpo'   => $n->cuerpo_notificacion,
            'leida'    => $n->leida_notificacion,
            'leida_en' => $n->leida_en_notificacion?->toDateTimeString(),
            'fecha'    => $n->created_at->toDateTimeString(),
            'entidad'  => $n->entidad_tipo_notificacion ? [
                'tipo' => $n->entidad_tipo_notificacion,
                'id'   => $n->entidad_id_notificacion,
            ] : null,
        ];
    }
}