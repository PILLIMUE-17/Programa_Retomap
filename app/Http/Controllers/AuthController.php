<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────
    // POST /api/register
    // ─────────────────────────────────────────────────
    public function register(RegisterRequest $request): JsonResponse
    {
        // 1. Crear el usuario — los datos ya pasaron el Form Request
        $usuario = Usuario::create([
            'nombre_usuario'        => $request->nombre_usuario,
            'email_usuario'         => $request->email_usuario,
            'username_usuario'      => $request->username_usuario,
            'password_hash_usuario' => Hash::make($request->password), // encriptamos la contraseña
            'ciudad_usuario'        => $request->ciudad_usuario,
            'nivel_id'              => 1,
            'xp_total_usuario'  => 0,    
            'racha_dias_usuario' => 0,   
             // Principiante por defecto
        ]);

        // 2. Generar token para que quede logueado inmediatamente
        $token = $usuario->createToken('retomap-mobile')->plainTextToken;

        // 3. Responder con el usuario y el token
        return response()->json([
            'message' => '¡Bienvenido a Retomap!',
            'token'   => $token,
            'usuario' => [
                'id'               => $usuario->id,
                'nombre'           => $usuario->nombre_usuario,
                'email'            => $usuario->email_usuario,
                'username'         => $usuario->username_usuario,
                'xp_total'         => $usuario->xp_total_usuario,
                'nivel_id'         => $usuario->nivel_id,
            ],
        ], 201); // 201 = Created
    }

    // ─────────────────────────────────────────────────
    // POST /api/login
    // ─────────────────────────────────────────────────
    public function login(LoginRequest $request): JsonResponse
    {
        // 1. Buscar el usuario por email
        $usuario = Usuario::where('email_usuario', $request->email_usuario)->first();

        // 2. Verificar que existe y que la contraseña es correcta
        if (!$usuario || !Hash::check($request->password, $usuario->password_hash_usuario)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401); // 401 = Unauthorized
        }

        // 3. Verificar que la cuenta está activa
        if (!$usuario->activo_usuario) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al soporte.',
            ], 403); // 403 = Forbidden
        }

        // 4. Actualizar último acceso
        $usuario->update(['ultimo_acceso_usuario' => now()]);

        // 5. Revocar tokens viejos y crear uno nuevo
        $usuario->tokens()->delete();
        $token = $usuario->createToken('retomap-mobile')->plainTextToken;

        // 6. Cargar el nivel del usuario para devolverlo
        $usuario->load('nivel');

        return response()->json([
            'message' => '¡Bienvenido de vuelta, ' . $usuario->nombre_usuario . '!',
            'token'   => $token,
            'usuario' => [
                'id'           => $usuario->id,
                'nombre'       => $usuario->nombre_usuario,
                'email'        => $usuario->email_usuario,
                'username'     => $usuario->username_usuario,
                'avatar_url'   => $usuario->avatar_url_usuario,
                'xp_total'     => $usuario->xp_total_usuario,
                'racha_dias'   => $usuario->racha_dias_usuario,
                'nivel'        => $usuario->nivel?->nombre_nivel,
                'ciudad'       => $usuario->ciudad_usuario,
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────
    // POST /api/logout   (requiere token)
    // ─────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        // Revocar SOLO el token que se usó en esta petición
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    // ─────────────────────────────────────────────────
    // GET /api/me   (requiere token)
    // ─────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        // auth()->user() devuelve el usuario dueño del token
        $usuario = $request->user()->load('nivel');

       return response()->json([
            'usuario' => [
                'id'           => $usuario->id,
                'nombre'       => $usuario->nombre_usuario,
                'email'        => $usuario->email_usuario,
                'username'     => $usuario->username_usuario,
                'avatar_url'   => $usuario->avatar_url_usuario,
                'xp_total'     => $usuario->xp_total_usuario,
                'racha_dias'   => $usuario->racha_dias_usuario,
                'nivel'        => $usuario->nivel?->nombre_nivel,
                'ciudad'       => $usuario->ciudad_usuario,
                'insignias'    => $usuario->insignias->map(fn($i) => [
                    'nombre' => $i->nombre_insignia,
                    'icono'  => $i->icono_insignia,
                ]),
            ],
        ], 200);
       
    }
}

