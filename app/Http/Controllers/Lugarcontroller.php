<?php

namespace App\Http\Controllers;

use App\Models\Lugar;
use App\Models\Categoria;
use App\Http\Requests\LugarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LugarController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /api/lugares
    // Lista todos los lugares con filtros opcionales
    // ─────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Lugar::with('categoria', 'aliado')
            ->whereNull('deleted_at');

        // Filtro por municipio: /api/lugares?municipio=Neiva
        if ($request->filled('municipio')) {
            $query->where('municipio_lugar', 'like', '%' . $request->municipio . '%');
        }

        // Filtro por categoría: /api/lugares?categoria_id=1
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtro por nombre: /api/lugares?buscar=parque
        if ($request->filled('buscar')) {
            $query->where('nombre_lugar', 'like', '%' . $request->buscar . '%');
        }

        // Solo verificados: /api/lugares?verificados=1
        if ($request->filled('verificados')) {
            $query->where('verificado_lugar', true);
        }

        // Ordenar por nombre o calificación: /api/lugares?orden=calificacion
        $orden = $request->get('orden', 'nombre');
        match ($orden) {
            'calificacion' => $query->orderByDesc('calificacion_promedio_lugar'),
            default        => $query->orderBy('nombre_lugar'),
        };

        // Paginación: 15 lugares por página
        $lugares = $query->paginate(15);

        return response()->json([
            'data'       => $lugares->map(fn($l) => $this->formatoLista($l)),
            'total'      => $lugares->total(),
            'pagina'     => $lugares->currentPage(),
            'por_pagina' => $lugares->perPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/lugares/{id}
    // Detalle completo de un lugar con sus retos
    // ─────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $lugar = Lugar::with([
            'categoria',
            'aliado',
            'retos' => fn($q) => $q->activos()->with('tipoReto'), // solo retos activos
        ])->find($id);

        if (!$lugar) {
            return response()->json([
                'message' => 'Lugar no encontrado.',
            ], 404);
        }

        return response()->json([
            'lugar' => [
                'id'                  => $lugar->id,
                'nombre'              => $lugar->nombre_lugar,
                'descripcion'         => $lugar->descripcion_lugar,
                'latitud'             => $lugar->latitud_lugar,
                'longitud'            => $lugar->longitud_lugar,
                'direccion'           => $lugar->direccion_lugar,
                'municipio'           => $lugar->municipio_lugar,
                'departamento'        => $lugar->departamento_lugar,
                'verificado'          => $lugar->verificado_lugar,
                'imagen_url'          => $lugar->imagen_url_lugar,
                'horario'             => $lugar->horario_lugar,
                'calificacion'        => $lugar->calificacion_promedio_lugar,
                'categoria'           => [
                    'id'     => $lugar->categoria?->id,
                    'nombre' => $lugar->categoria?->nombre_categoria,
                    'icono'  => $lugar->categoria?->icono_categoria,
                    'color'  => $lugar->categoria?->color_hex_categoria,
                ],
                'aliado'              => $lugar->aliado ? [
                    'id'     => $lugar->aliado->id,
                    'nombre' => $lugar->aliado->nombre_negocio_aliado,
                    'logo'   => $lugar->aliado->logo_url_aliado,
                ] : null,
                'retos'               => $lugar->retos->map(fn($r) => [
                    'id'           => $r->id,
                    'nombre'       => $r->nombre_reto,
                    'descripcion'  => $r->descripcion_reto,
                    'xp'           => $r->xp_recompensa_reto,
                    'dificultad'   => $r->dificultad_reto,
                    'tipo'         => $r->tipoReto?->nombre_tipo_reto,
                    'icono_tipo'   => $r->tipoReto?->icono_tipo_reto,
                    'completados'  => $r->veces_completado_reto,
                    'expira_en'    => $r->expira_en_reto?->toDateTimeString(),
                ]),
                'total_retos'         => $lugar->retos->count(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/lugares/cercanos
    // Lugares cercanos a unas coordenadas dadas
    // /api/lugares/cercanos?lat=2.9262&lng=-75.2892&radio=2
    // ─────────────────────────────────────────────────
    public function cercanos(Request $request): JsonResponse
    {
        $request->validate([
            'lat'   => ['required', 'numeric'],
            'lng'   => ['required', 'numeric'],
            'radio' => ['nullable', 'numeric', 'min:0.1', 'max:50'], // km
        ]);

        $lat   = $request->lat;
        $lng   = $request->lng;
        $radio = $request->get('radio', 5); // 5km por defecto

        // Fórmula Haversine en MySQL para calcular distancia
        $lugares = Lugar::with('categoria')
            ->selectRaw("*, (
                6371 * ACOS(
                    COS(RADIANS(?)) * COS(RADIANS(latitud_lugar)) *
                    COS(RADIANS(longitud_lugar) - RADIANS(?)) +
                    SIN(RADIANS(?)) * SIN(RADIANS(latitud_lugar))
                )
            ) AS distancia_km", [$lat, $lng, $lat])
            ->having('distancia_km', '<=', $radio)
            ->orderBy('distancia_km')
            ->whereNull('deleted_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $lugares->map(fn($l) => array_merge(
                $this->formatoLista($l),
                ['distancia_km' => round($l->distancia_km, 2)]
            )),
            'total' => $lugares->count(),
            'radio_km' => $radio,
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/lugares/municipios
    // Lista los municipios disponibles para filtrar
    // ─────────────────────────────────────────────────
    public function municipios(): JsonResponse
    {
        $municipios = Lugar::select('municipio_lugar')
            ->distinct()
            ->orderBy('municipio_lugar')
            ->pluck('municipio_lugar');

        return response()->json(['municipios' => $municipios]);
    }

    // ─────────────────────────────────────────────────
    // Formato compacto para listados
    // ─────────────────────────────────────────────────
    private function formatoLista(Lugar $lugar): array
    {
        return [
            'id'           => $lugar->id,
            'nombre'       => $lugar->nombre_lugar,
            'municipio'    => $lugar->municipio_lugar,
            'latitud'      => $lugar->latitud_lugar,
            'longitud'     => $lugar->longitud_lugar,
            'imagen_url'   => $lugar->imagen_url_lugar,
            'verificado'   => $lugar->verificado_lugar,
            'calificacion' => $lugar->calificacion_promedio_lugar,
            'categoria'    => [
                'nombre' => $lugar->categoria?->nombre_categoria,
                'icono'  => $lugar->categoria?->icono_categoria,
                'color'  => $lugar->categoria?->color_hex_categoria,
            ],
        ];
    }
}