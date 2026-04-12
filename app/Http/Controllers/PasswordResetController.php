<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class PasswordResetController extends Controller
{
    // ─────────────────────────────────────────────────
    // POST /api/password/forgot
    // Solicitar token de recuperacion
    // ─────────────────────────────────────────────────
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $usuario = Usuario::where('email_usuario', $request->email)
                          ->where('activo_usuario', true)
                          ->first();

        // Respuesta vaga para evitar enumeracion de usuarios
        if (!$usuario) {
            return response()->json(['message' => 'Si el email existe recibirás instrucciones.'], 200);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        return response()->json([
            'message' => 'Token generado correctamente.',
            'token'   => $token,
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /api/password/reset
    // Cambiar contraseña con el token
    // ─────────────────────────────────────────────────
    public function reset(Request $request)
    {
        $request->validate([
            'email'                  => 'required|email',
            'token'                  => 'required|string',
            'password_nuevo'         => 'required|string|min:8|confirmed',
        ]);

        $registro = DB::table('password_reset_tokens')
                      ->where('email', $request->email)
                      ->first();

        if (!$registro) {
            return response()->json(['message' => 'Token inválido.'], 400);
        }

        // Verificar que no hayan pasado mas de 60 minutos
        if (Carbon::parse($registro->created_at)->diffInMinutes(now()) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'El token ha expirado.'], 400);
        }

        if (!Hash::check($request->token, $registro->token)) {
            return response()->json(['message' => 'Token inválido.'], 400);
        }

        Usuario::where('email_usuario', $request->email)->update([
            'password_hash_usuario' => Hash::make($request->password_nuevo),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
