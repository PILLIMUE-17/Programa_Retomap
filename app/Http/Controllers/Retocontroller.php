<?php

namespace App\Http\Controllers;

use App\Models\Reto;
use App\Models\RetoCompletado;
use App\Http\Requests\RetoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RetoController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /api/retos
    // Lista todos los retos activos con filtros
    // ─────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Reto::with('lugar.categoria', 'tipoReto')
            ->activos(); // scope que definimos en el modelo

        // Filtro por lugar: /api/retos?lugar_id=1
        if ($request->filled('lugar_id')) {
            $query->where('lugar_id', $request->lugar_id);
        }

        // Filtro por dificultad: /api/retos?dificultad=1
        if ($request->filled('dificultad')) {
            $query->where('dificultad_reto', $request->dificultad);
        }

        // Filtro por tipo: /api/retos?tipo_reto_id=1
        if ($request->filled('tipo_reto_id')) {
            $query->where('tipo_reto_id', $request->tipo_reto_id);
        }

        // Ordenar por XP o nombre
        $orden = $request->get('orden', 'xp');
        match ($orden) {
            'xp'     => $query->orderByDesc('xp_recompensa_reto'),
            'nombre' => $query->orderBy('nombre_reto'),
            default  => $query->orderByDesc('xp_recompensa_reto'),
        };

        $retos = $query->paginate(15);

        // IDs de retos que el usuario YA completó
        $retosCompletados = RetoCompletado::where('usuario_id', $usuario->id)
            ->where('estado_reto_completado', 'aprobado')
            ->pluck('reto_id')
            ->toArray();

        return response()->json([
            'data' => $retos->map(fn($r) => array_merge(
                $this->formatoLista($r),
                ['ya_completado' => in_array($r->id, $retosCompletados)]
            )),
            'total'      => $retos->total(),
            'pagina'     => $retos->currentPage(),
            'por_pagina' => $retos->perPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/retos/{id}
    // Detalle completo de un reto
    // ─────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $reto = Reto::with('lugar.categoria', 'tipoReto')->find($id);

        if (!$reto) {
            return response()->json(['message' => 'Reto no encontrado.'], 404);
        }

        if (!$reto->estaVigente()) {
            return response()->json(['message' => 'Este reto ya no está disponible.'], 410);
        }

        // Verificar si el usuario ya lo completó
        $yaCompletado = RetoCompletado::where('usuario_id', $usuario->id)
            ->where('reto_id', $id)
            ->exists();

        return response()->json([
            'reto' => [
                'id'                  => $reto->id,
                'nombre'              => $reto->nombre_reto,
                'descripcion'         => $reto->descripcion_reto,
                'xp'                  => $reto->xp_recompensa_reto,
                'dificultad'          => $reto->dificultad_reto,
                'instruccion'         => $reto->instruccion_evidencia_reto,
                'expira_en'           => $reto->expira_en_reto?->toDateTimeString(),
                'veces_completado'    => $reto->veces_completado_reto,
                'ya_completado'       => $yaCompletado,
                'tipo'                => $reto->tipoReto?->nombre_tipo_reto,
                'icono_tipo'          => $reto->tipoReto?->icono_tipo_reto,
                'lugar'               => [
                    'id'        => $reto->lugar->id,
                    'nombre'    => $reto->lugar->nombre_lugar,
                    'latitud'   => $reto->lugar->latitud_lugar,
                    'longitud'  => $reto->lugar->longitud_lugar,
                    'municipio' => $reto->lugar->municipio_lugar,
                    'categoria' => $reto->lugar->categoria?->nombre_categoria,
                    'icono'     => $reto->lugar->categoria?->icono_categoria,
                ],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/retos/{id}/completar
    // El usuario completa un reto y sube evidencia
    // ─────────────────────────────────────────────────
    public function completar(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        // 1. Verificar que el reto existe y está activo
        $reto = Reto::find($id);

        if (!$reto) {
            return response()->json(['message' => 'Reto no encontrado.'], 404);
        }

        if (!$reto->estaVigente()) {
            return response()->json(['message' => 'Este reto ya no está disponible.'], 410);
        }

        // 2. Verificar que no lo haya completado antes
        $yaExiste = RetoCompletado::where('usuario_id', $usuario->id)
            ->where('reto_id', $id)
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'message' => 'Ya completaste este reto anteriormente.',
            ], 409); // 409 = Conflict
        }

        // 3. Validar la evidencia
        $request->validate([
            'evidencia_url' => ['nullable', 'url', 'max:500'],
        ]);

        // 4. Crear el registro — el Observer se encarga del XP automáticamente
        $completado = RetoCompletado::create([
            'usuario_id'                    => $usuario->id,
            'reto_id'                       => $id,
            'evidencia_url_reto_completado' => $request->evidencia_url,
            'xp_ganado_reto_completado'     => $reto->xp_recompensa_reto,
            'estado_reto_completado'        => 'aprobado',
        ]);

        // 5. Recargar el usuario para mostrar el XP actualizado
        $usuario->refresh();

        return response()->json([
            'message'     => '¡Reto completado! +' . $reto->xp_recompensa_reto . ' XP',
            'xp_ganado'   => $reto->xp_recompensa_reto,
            'xp_total'    => $usuario->xp_total_usuario,
            'nivel_actual' => $usuario->nivel?->nombre_nivel,
        ], 201);
    }

    // ─────────────────────────────────────────────────
    // GET /api/retos/completados
    // Retos completados por el usuario logueado
    // ─────────────────────────────────────────────────
    public function misCompletados(Request $request): JsonResponse
    {
        $completados = RetoCompletado::with('reto.lugar')
            ->where('usuario_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $completados->map(fn($c) => [
                'id'           => $c->id,
                'reto'         => $c->reto->nombre_reto,
                'lugar'        => $c->reto->lugar->nombre_lugar,
                'xp_ganado'    => $c->xp_ganado_reto_completado,
                'estado'       => $c->estado_reto_completado,
                'completado_en'=> $c->created_at->toDateTimeString(),
            ]),
            'total'  => $completados->total(),
            'pagina' => $completados->currentPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // Formato compacto para listados
    // ─────────────────────────────────────────────────
    private function formatoLista(Reto $reto): array
    {
        return [
            'id'          => $reto->id,
            'nombre'      => $reto->nombre_reto,
            'xp'          => $reto->xp_recompensa_reto,
            'dificultad'  => $reto->dificultad_reto,
            'tipo'        => $reto->tipoReto?->nombre_tipo_reto,
            'icono_tipo'  => $reto->tipoReto?->icono_tipo_reto,
            'completados' => $reto->veces_completado_reto,
            'lugar'       => [
                'id'       => $reto->lugar->id,
                'nombre'   => $reto->lugar->nombre_lugar,
                'municipio'=> $reto->lugar->municipio_lugar,
                'categoria'=> $reto->lugar->categoria?->nombre_categoria,
                'icono'    => $reto->lugar->categoria?->icono_categoria,
            ],
        ];
    }
}