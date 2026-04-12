<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\RetoCompletado;
use App\Models\Publicacion;
use App\Models\Nivel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\select;

class PerfilController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /api/perfil
    // Mi perfil completo con estadísticas
    // ─────────────────────────────────────────────────
    public function miPerfil(Request $request): JsonResponse
    {
        $usuario = $request->user()->load('nivel', 'insignias');

        // Estadísticas del usuario
        $totalRetos      = RetoCompletado::where('usuario_id', $usuario->id)
                            ->where('estado_reto_completado', 'aprobado')
                            ->count();

        $totalPublicaciones = Publicacion::where('usuario_id', $usuario->id)
                                ->where('visible_publicacion', true)
                                ->count();

        // Posición en el ranking global
        $posicion = Usuario::where('xp_total_usuario', '>', $usuario->xp_total_usuario)
                        ->where('activo_usuario', true)
                        ->count() + 1;

        return response()->json([
            'perfil' => [
                'id'               => $usuario->id,
                'nombre'           => $usuario->nombre_usuario,
                'email'            => $usuario->email_usuario,
                'username'         => $usuario->username_usuario,
                'avatar_url'       => $usuario->avatar_url_usuario,
                'ciudad'           => $usuario->ciudad_usuario,
                'xp_total'         => $usuario->xp_total_usuario,
                'racha_dias'       => $usuario->racha_dias_usuario,
                'nivel'            => $usuario->nivel?->nombre_nivel,
                'nivel_icono'      => $usuario->nivel?->insignia_url_nivel,
                'posicion_ranking' => $posicion,
                'estadisticas'     => [
                    'retos_completados' => $totalRetos,
                    'publicaciones'     => $totalPublicaciones,
                    'insignias'         => $usuario->insignias->count(),
                ],
                'insignias' => $usuario->insignias->map(fn($i) => [
                    'nombre'      => $i->nombre_insignia,
                    'icono'       => $i->icono_insignia,
                    'obtenida_en' => $i->pivot->obtenida_en_usuario_insignia,
                ]),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/perfil
    // Actualizar mi perfil
    // ─────────────────────────────────────────────────
    public function actualizar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $request->validate([
            'nombre_usuario'     => ['sometimes', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'username_usuario'   => ['sometimes', 'string', 'min:3', 'max:50', 'alpha_dash',
                                     'unique:usuarios,username_usuario,' . $usuario->id],
            'ciudad_usuario'     => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'avatar_url_usuario' => ['nullable', 'url', 'max:500'],
        ]);

        $usuario->update($request->only([
            'nombre_usuario',
            'username_usuario',
            'ciudad_usuario',
            'avatar_url_usuario',
        ]));

        return response()->json([
            'message' => 'Perfil actualizado.',
            'perfil'  => [
                'nombre'     => $usuario->nombre_usuario,
                'username'   => $usuario->username_usuario,
                'ciudad'     => $usuario->ciudad_usuario,
                'avatar_url' => $usuario->avatar_url_usuario,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/perfil/password
    // Cambiar contraseña
    // ─────────────────────────────────────────────────
    public function cambiarPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password_actual' => ['required', 'string'],
            'password_nuevo'  => ['required', 'string', 'min:8',
                                  'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).+$/',
                                  'confirmed'],
        ]);

        $usuario = $request->user();

        // Verificar contraseña actual
        if (!Hash::check($request->password_actual, $usuario->password_hash_usuario)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.',
            ], 400);
        }

        $usuario->update([
            'password_hash_usuario' => Hash::make($request->password_nuevo),
        ]);

        // Revocar todos los tokens — forzar re-login
        $usuario->tokens()->delete();

        return response()->json([
            'message' => 'Contraseña actualizada. Por favor inicia sesión de nuevo.',
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/perfil/{id}
    // Ver perfil público de otro usuario
    // ─────────────────────────────────────────────────
    public function verPerfil(int $id): JsonResponse
    {
        $usuario = Usuario::with('nivel', 'insignias')->find($id);

        if (!$usuario || !$usuario->activo_usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $totalRetos = RetoCompletado::where('usuario_id', $id)
                        ->where('estado_reto_completado', 'aprobado')
                        ->count();

        $posicion = Usuario::where('xp_total_usuario', '>', $usuario->xp_total_usuario)
                        ->where('activo_usuario', true)
                        ->count() + 1;

        return response()->json([
            'perfil' => [
                'id'               => $usuario->id,
                'nombre'           => $usuario->nombre_usuario,
                'username'         => $usuario->username_usuario,
                'avatar_url'       => $usuario->avatar_url_usuario,
                'ciudad'           => $usuario->ciudad_usuario,
                'xp_total'         => $usuario->xp_total_usuario,
                'nivel'            => $usuario->nivel?->nombre_nivel,
                'posicion_ranking' => $posicion,
                'estadisticas'     => [
                    'retos_completados' => $totalRetos,
                    'insignias'         => $usuario->insignias->count(),
                ],
                'insignias' => $usuario->insignias->map(fn($i) => [
                    'nombre' => $i->nombre_insignia,
                    'icono'  => $i->icono_insignia,
                ]),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/ranking
    // Ranking global de usuarios por XP
    // ─────────────────────────────────────────────────
    public function ranking(Request $request): JsonResponse
    {
        $usuarios = Usuario::with('nivel')
            ->where('activo_usuario', true)
            ->orderByDesc('xp_total_usuario')
            ->paginate(20);

        return response()->json([
            'data' => $usuarios->map(fn($u, $index) => [
                'posicion'   => (($usuarios->currentPage() - 1) * $usuarios->perPage()) + $index + 1,
                'id'         => $u->id,
                'nombre'     => $u->nombre_usuario,
                'username'   => $u->username_usuario,
                'avatar_url' => $u->avatar_url_usuario,
                'xp_total'   => $u->xp_total_usuario,
                'nivel'      => $u->nivel?->nombre_nivel,
                'racha_dias' => $u->racha_dias_usuario,
            ]),
            'total'      => $usuarios->total(),
            'pagina'     => $usuarios->currentPage(),
            'por_pagina' => $usuarios->perPage(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/ranking/mi-posicion
    // Mi posición exacta en el ranking
    // ─────────────────────────────────────────────────
    public function miPosicion(Request $request): JsonResponse
    {
        $usuario = $request->user()->load('nivel');

        $posicion = Usuario::where('xp_total_usuario', '>', $usuario->xp_total_usuario)
                        ->where('activo_usuario', true)
                        ->count() + 1;

        $total = Usuario::where('activo_usuario', true)->count();

        $xpSiguienteNivel = Nivel::where('xp_requerido_nivel', '>', $usuario->xp_total_usuario)
                                ->orderBy('xp_requerido_nivel')
                                ->first();

        return response()->json([
            'posicion'           => $posicion,
            'total_usuarios'     => $total,
            'xp_actual'          => $usuario->xp_total_usuario,
            'nivel_actual'       => $usuario->nivel?->nombre_nivel,
            'xp_siguiente_nivel' => $xpSiguienteNivel?->xp_requerido_nivel,
            'xp_faltante'        => $xpSiguienteNivel
                                        ? $xpSiguienteNivel->xp_requerido_nivel - $usuario->xp_total_usuario
                                        : 0,
        ]);
    }
    public function buscarUsuarios(Request $request): JsonResponse{
        $request->validate([
            "q" => ['required', 'string', 'max:100']
        ]);
        $termino = $request->input('q');
        $usuarios = Usuario::where("activo_usuario", true)
              ->where(function($query) use ($termino) {
                    $query->where("nombre_usuario", "LIKE", "%" . $termino . "%")
                          ->orWhere("username_usuario", "LIKE", "%" . $termino . "%");
              })
              ->select("id", "nombre_usuario", "username_usuario", "avatar_url_usuario", "ciudad_usuario", "xp_total_usuario")
              ->paginate(15);
        return response()->json($usuarios);   

    }
}
