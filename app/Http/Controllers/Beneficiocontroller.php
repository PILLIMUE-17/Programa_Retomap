<?php

namespace App\Http\Controllers;

use App\Models\Beneficio;
use App\Models\Canje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BeneficioController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /api/beneficios
    // Lista beneficios activos y vigentes
    // ─────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Beneficio::with('aliado:id,nombre_negocio_aliado,logo_url_aliado,municipio_aliado')
            ->where('activo_beneficio', true)
            ->where(function ($q) {
                $q->whereNull('valido_hasta_beneficio')
                  ->orWhere('valido_hasta_beneficio', '>=', now()->toDateString());
            });

        // Filtro por aliado: /api/beneficios?aliado_id=1
        if ($request->filled('aliado_id')) {
            $query->where('aliado_id', $request->aliado_id);
        }

        // Filtro por XP máximo: /api/beneficios?max_xp=200
        if ($request->filled('max_xp')) {
            $query->where('costo_xp_beneficio', '<=', $request->max_xp);
        }

        // Ordenar por costo XP
        $query->orderBy('costo_xp_beneficio');

        $beneficios = $query->paginate(15);
        $xpUsuario  = $request->user()->xp_total_usuario;

        return response()->json([
            'data' => $beneficios->map(fn($b) => array_merge(
                $this->formatoBeneficio($b),
                [
                    'puedo_canjear' => $xpUsuario >= $b->costo_xp_beneficio && $b->tieneStock(),
                    'xp_faltante'   => max(0, $b->costo_xp_beneficio - $xpUsuario),
                ]
            )),
            'total'      => $beneficios->total(),
            'pagina'     => $beneficios->currentPage(),
            'mi_xp'      => $xpUsuario,
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/beneficios/{id}
    // Detalle de un beneficio
    // ─────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $beneficio = Beneficio::with('aliado')->find($id);

        if (!$beneficio || !$beneficio->activo_beneficio) {
            return response()->json(['message' => 'Beneficio no encontrado.'], 404);
        }

        $xpUsuario  = $request->user()->xp_total_usuario;
        $yaCanjeo   = Canje::where('usuario_id', $request->user()->id)
                        ->where('beneficio_id', $id)
                        ->exists();

        return response()->json([
            'beneficio' => array_merge(
                $this->formatoBeneficio($beneficio),
                [
                    'puedo_canjear' => $xpUsuario >= $beneficio->costo_xp_beneficio
                                        && $beneficio->tieneStock()
                                        && $beneficio->estaVigente(),
                    'xp_faltante'   => max(0, $beneficio->costo_xp_beneficio - $xpUsuario),
                    'ya_canjee'     => $yaCanjeo,
                ]
            ),
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/beneficios/{id}/canjear
    // Canjear un beneficio con XP
    // ─────────────────────────────────────────────────
    public function canjear(Request $request, int $id): JsonResponse
    {
        $usuario   = $request->user();
        $beneficio = Beneficio::with('aliado')->find($id);

        // 1. Verificar que existe
        if (!$beneficio) {
            return response()->json(['message' => 'Beneficio no encontrado.'], 404);
        }

        // 2. Verificar que está vigente
        if (!$beneficio->estaVigente()) {
            return response()->json([
                'message' => 'Este beneficio ya no está disponible.',
            ], 410);
        }

        // 3. Verificar stock
        if (!$beneficio->tieneStock()) {
            return response()->json([
                'message' => 'Este beneficio se agotó.',
            ], 410);
        }

        // 4. Verificar XP suficiente ← aquí usamos 422
        if ($usuario->xp_total_usuario < $beneficio->costo_xp_beneficio) {
            return response()->json([
                'message'     => 'XP insuficiente para canjear este beneficio.',
                'xp_actual'   => $usuario->xp_total_usuario,
                'xp_necesario'=> $beneficio->costo_xp_beneficio,
                'xp_faltante' => $beneficio->costo_xp_beneficio - $usuario->xp_total_usuario,
            ], 422);
        }

        // 5. Usar transacción — si algo falla, todo se revierte
        $canje = DB::transaction(function () use ($usuario, $beneficio) {

            // Descontar XP al usuario
            $usuario->decrement('xp_total_usuario', $beneficio->costo_xp_beneficio);

            // Incrementar contador de canjes en el beneficio
            $beneficio->increment('cantidad_canjeada_beneficio');

            // Crear el canje con código único generado en el modelo
            return Canje::create([
                'usuario_id'          => $usuario->id,
                'beneficio_id'        => $beneficio->id,
                'xp_descontado_canje' => $beneficio->costo_xp_beneficio,
                'codigo_unico_canje'  => strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8)),
            ]);
        });

        // Recargar usuario para mostrar XP actualizado
        $usuario->refresh();

        return response()->json([
            'message'         => '¡Beneficio canjeado exitosamente!',
            'codigo'          => $canje->codigo_unico_canje,
            'beneficio'       => $beneficio->descripcion_beneficio,
            'aliado'          => $beneficio->aliado->nombre_negocio_aliado,
            'xp_descontado'   => $beneficio->costo_xp_beneficio,
            'xp_restante'     => $usuario->xp_total_usuario,
            'valido_hasta'    => $beneficio->valido_hasta_beneficio?->toDateString(),
        ], 201);
    }

    // ─────────────────────────────────────────────────
    // GET /api/beneficios/mis-canjes
    // Mis canjes realizados
    // ─────────────────────────────────────────────────
    public function misCanjes(Request $request): JsonResponse
    {
        $canjes = Canje::with('beneficio.aliado')
            ->where('usuario_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $canjes->map(fn($c) => [
                'id'            => $c->id,
                'codigo'        => $c->codigo_unico_canje,
                'beneficio'     => $c->beneficio->descripcion_beneficio,
                'aliado'        => $c->beneficio->aliado->nombre_negocio_aliado,
                'xp_descontado' => $c->xp_descontado_canje,
                'usado'         => $c->usado_canje,
                'canjeado_en'   => $c->created_at->toDateTimeString(),
                'usado_en'      => $c->usado_en_canje?->toDateTimeString(),
            ]),
            'total'  => $canjes->total(),
            'pagina' => $canjes->currentPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/beneficios/canjes/{codigo}/usar
    // Marcar un canje como usado (lo hace el aliado)
    // ─────────────────────────────────────────────────
    public function usarCanje(Request $request, string $codigo): JsonResponse
    {
        $canje = Canje::where('codigo_unico_canje', strtoupper($codigo))->first();

        if (!$canje) {
            return response()->json(['message' => 'Código inválido.'], 404);
        }

        if ($canje->usado_canje) {
            return response()->json([
                'message'  => 'Este código ya fue usado.',
                'usado_en' => $canje->usado_en_canje?->toDateTimeString(),
            ], 409);
        }

        $canje->update([
            'usado_canje'    => true,
            'usado_en_canje' => now(),
        ]);

        return response()->json([
            'message'   => 'Canje marcado como usado.',
            'beneficio' => $canje->beneficio->descripcion_beneficio,
            'usuario'   => $canje->usuario->nombre_usuario,
        ]);
    }

    // ─────────────────────────────────────────────────
    // Formato reutilizable
    // ─────────────────────────────────────────────────
    private function formatoBeneficio(Beneficio $b): array
    {
        return [
            'id'                  => $b->id,
            'descripcion'         => $b->descripcion_beneficio,
            'costo_xp'            => $b->costo_xp_beneficio,
            'valido_hasta'        => $b->valido_hasta_beneficio?->toDateString(),
            'cantidad_disponible' => $b->cantidad_disponible_beneficio,
            'cantidad_canjeada'   => $b->cantidad_canjeada_beneficio,
            'stock_restante'      => $b->cantidad_disponible_beneficio
                                        ? $b->cantidad_disponible_beneficio - $b->cantidad_canjeada_beneficio
                                        : null, // null = ilimitado
            'aliado'              => [
                'id'        => $b->aliado?->id,
                'nombre'    => $b->aliado?->nombre_negocio_aliado,
                'logo'      => $b->aliado?->logo_url_aliado,
                'municipio' => $b->aliado?->municipio_aliado,
            ],
        ];
    }
}