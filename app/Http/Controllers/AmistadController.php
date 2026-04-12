<?php

namespace App\Http\Controllers;

use App\Models\Amistad;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmistadController extends Controller
{
    // ─────────────────────────────────────────────────
    // POST /api/amistades/solicitar/{id}
    // Enviar solicitud de amistad a otro usuario
    // ─────────────────────────────────────────────────
    public function solicitar(Request $request, int $id): JsonResponse
    {
        $usuarioActual = $request->user();

        // 1. Validar que el ID existe
        $usuarioDestino = Usuario::find($id);
        if (!$usuarioDestino || !$usuarioDestino->activo_usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        // 2. No puedes enviarte solicitud a ti mismo
        if ($usuarioActual->id === $id) {
            return response()->json([
                'message' => 'No puedes enviarte una solicitud de amistad a ti mismo.',
            ], 400);
        }

        // 3. Verificar que no haya ya una solicitud pendiente en ambas direcciones
        $yaExiste = Amistad::where(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $usuarioActual->id)
                  ->where('receptor_id', $id);
        })->orWhere(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $id)
                  ->where('receptor_id', $usuarioActual->id);
        })->first();

        if ($yaExiste) {
            $mensaje = match($yaExiste->estado_amistad) {
                'pendiente' => 'Ya existe una solicitud pendiente con este usuario.',
                'aceptada'  => 'Ya son amigos.',
                'bloqueada' => 'No puedes enviar solicitud a este usuario.',
            };
            return response()->json(['message' => $mensaje], 409);
        }

        // 4. Crear la solicitud de amistad
        $amistad = Amistad::create([
            'solicitante_id' => $usuarioActual->id,
            'receptor_id'    => $id,
            'estado_amistad' => 'pendiente',
        ]);

        // 5. Crear notificación para el receptor
        $usuarioDestino->notificaciones()->create([
            'tipo_notificacion'         => 'amistad_solicitud',
            'titulo_notificacion'       => '¡Nueva solicitud de amistad!',
            'cuerpo_notificacion'       => $usuarioActual->nombre_usuario . ' quiere ser tu amigo.',
            'entidad_tipo_notificacion' => 'amistad',
            'entidad_id_notificacion'   => $amistad->id,
        ]);

        return response()->json([
            'message' => 'Solicitud de amistad enviada.',
            'amistad' => [
                'id'       => $amistad->id,
                'receptor' => [
                    'id'       => $usuarioDestino->id,
                    'nombre'   => $usuarioDestino->nombre_usuario,
                    'username' => $usuarioDestino->username_usuario,
                    'avatar'   => $usuarioDestino->avatar_url_usuario,
                ],
                'estado'   => $amistad->estado_amistad,
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/amistades/{id}/aceptar
    // Aceptar una solicitud de amistad pendiente
    // ─────────────────────────────────────────────────
    public function aceptar(Request $request, int $id): JsonResponse
    {
        $usuarioActual = $request->user();

        // 1. Buscar la solicitud
        $amistad = Amistad::find($id);
        if (!$amistad) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        // 2. Verificar que el usuario actual es el receptor
        if ((int)$amistad->receptor_id !== (int)$usuarioActual->id) {
            return response()->json([
                'message' => 'No tienes permiso para aceptar esta solicitud.',
            ], 403); // 403 = Forbidden
        }

        // 3. Verificar que está en estado pendiente
        if ($amistad->estado_amistad !== 'pendiente') {
            return response()->json([
                'message' => 'Esta solicitud ya fue procesada.',
            ], 400);
        }

        // 4. Aceptar la amistad
        $amistad->update(['estado_amistad' => 'aceptada']);

        // 5. Notificar al solicitante
        $amistad->solicitante->notificaciones()->create([
            'tipo_notificacion'         => 'amistad_aceptada',
            'titulo_notificacion'       => '¡Solicitud aceptada!',
            'cuerpo_notificacion'       => $usuarioActual->nombre_usuario . ' aceptó tu solicitud de amistad.',
            'entidad_tipo_notificacion' => 'amistad',
            'entidad_id_notificacion'   => $amistad->id,
        ]);

        // Cargar los datos del solicitante para la respuesta
        $amistad->load('solicitante');

        return response()->json([
            'message' => 'Amistad aceptada.',
            'amistad' => [
                'id'       => $amistad->id,
                'amigo'    => [
                    'id'       => $amistad->solicitante->id,
                    'nombre'   => $amistad->solicitante->nombre_usuario,
                    'username' => $amistad->solicitante->username_usuario,
                    'avatar'   => $amistad->solicitante->avatar_url_usuario,
                ],
                'estado'   => $amistad->estado_amistad,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────
    // DELETE /api/amistades/{id}/rechazar
    // Rechazar una solicitud de amistad
    // ─────────────────────────────────────────────────
    public function rechazar(Request $request, int $id): JsonResponse
    {
        $usuarioActual = $request->user();

        // 1. Buscar la solicitud
        $amistad = Amistad::find($id);
        if (!$amistad) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        // 2. Verificar que el usuario actual es el receptor
        if ((int)$amistad->receptor_id !== (int)$usuarioActual->id) {
            return response()->json([
                'message' => 'No tienes permiso para rechazar esta solicitud.',
            ], 403);
        }

        // 3. Verificar que está pendiente
        if ($amistad->estado_amistad !== 'pendiente') {
            return response()->json([
                'message' => 'Esta solicitud ya fue procesada.',
            ], 400);
        }

        // 4. Eliminar la solicitud rechazada
        $amistad->delete();

        return response()->json([
            'message' => 'Solicitud rechazada.',
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/amistades
    // Mi lista de amigos (aceptadas)
    // ─────────────────────────────────────────────────
    public function misAmigos(Request $request): JsonResponse
    {
        $usuarioActual = $request->user();

        // Recibidas: cuando yo soy el receptor
        $amigosRecibidos = Amistad::with('solicitante')
            ->where('receptor_id', $usuarioActual->id)
            ->aceptadas()
            ->get()
            ->map(fn($a) => $a->solicitante);

        // Enviadas: cuando yo soy el solicitante
        $amigosEnviados = Amistad::with('receptor')
            ->where('solicitante_id', $usuarioActual->id)
            ->aceptadas()
            ->get()
            ->map(fn($a) => $a->receptor);

        // Combinar y eliminar duplicados
        $amigos = $amigosRecibidos->merge($amigosEnviados)
            ->unique('id')
            ->values();

        return response()->json([
            'total'   => $amigos->count(),
            'amigos'  => $amigos->map(fn($amigo) => [
                'id'       => $amigo->id,
                'nombre'   => $amigo->nombre_usuario,
                'username' => $amigo->username_usuario,
                'avatar'   => $amigo->avatar_url_usuario,
                'ciudad'   => $amigo->ciudad_usuario,
                'xp_total' => $amigo->xp_total_usuario,
                'nivel'    => $amigo->nivel?->nombre_nivel,
            ]),
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/amistades/pendientes
    // Mis solicitudes pendientes (recibidas)
    // ─────────────────────────────────────────────────
    public function pendientes(Request $request): JsonResponse
    {
        $usuarioActual = $request->user();

        $solicitudes = Amistad::with('solicitante')
            ->where('receptor_id', $usuarioActual->id)
            ->where('estado_amistad', 'pendiente')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'total'       => $solicitudes->count(),
            'solicitudes' => $solicitudes->map(fn($s) => [
                'id'        => $s->id,
                'solicitante' => [
                    'id'       => $s->solicitante->id,
                    'nombre'   => $s->solicitante->nombre_usuario,
                    'username' => $s->solicitante->username_usuario,
                    'avatar'   => $s->solicitante->avatar_url_usuario,
                    'ciudad'   => $s->solicitante->ciudad_usuario,
                ],
                'fecha'     => $s->created_at->toDateTimeString(),
            ]),
        ]);
    }

    // ─────────────────────────────────────────────────
    // DELETE /api/amistades/{id}
    // Eliminar una amistad (ambos lados)
    // ─────────────────────────────────────────────────
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $usuarioActual = $request->user();

        // 1. Buscar la amistad en ambas direcciones
        $amistad = Amistad::where(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $usuarioActual->id)
                  ->where('receptor_id', $id);
        })->orWhere(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $id)
                  ->where('receptor_id', $usuarioActual->id);
        })->first();

        if (!$amistad) {
            return response()->json(['message' => 'Amistad no encontrada.'], 404);
        }

        // 2. Verificar que está aceptada
        if ($amistad->estado_amistad !== 'aceptada') {
            return response()->json([
                'message' => 'Solo puedes eliminar amistades aceptadas.',
            ], 400);
        }

        // 3. Notificar al otro usuario
        $otro = $amistad->solicitante_id === $usuarioActual->id
            ? $amistad->receptor
            : $amistad->solicitante;

        $otro->notificaciones()->create([
            'tipo_notificacion'         => 'amistad_eliminada',
            'titulo_notificacion'       => 'Amistad eliminada',
            'cuerpo_notificacion'       => $usuarioActual->nombre_usuario . ' eliminó su amistad contigo.',
            'entidad_tipo_notificacion' => 'amistad',
            'entidad_id_notificacion'   => $amistad->id,
        ]);

        // 4. Eliminar
        $amistad->delete();

        return response()->json([
            'message' => 'Amistad eliminada.',
        ]);
    }

    // ─────────────────────────────────────────────────
    // GET /api/amistades/estado/{id}
    // Ver el estado de amistad con un usuario específico
    // ─────────────────────────────────────────────────
    public function estado(Request $request, int $id): JsonResponse
    {
        $usuarioActual = $request->user();

        $usuario = Usuario::find($id);
        if (!$usuario || !$usuario->activo_usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $amistad = Amistad::where(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $usuarioActual->id)
                  ->where('receptor_id', $id);
        })->orWhere(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $id)
                  ->where('receptor_id', $usuarioActual->id);
        })->first();

        // No hay relación
        if (!$amistad) {
            return response()->json([
                'estado' => 'ninguno',
            ]);
        }

        // Si hay amistad, decir si yo soy solicitante o receptor
        $esSolicitante = $amistad->solicitante_id === $usuarioActual->id;

        return response()->json([
            'estado'           => $amistad->estado_amistad,
            'soy_solicitante'  => $esSolicitante,
            'amistad_id'       => $amistad->id,
        ]);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/amistades/bloquear/{id}
    // Bloquear a un usuario
    // ─────────────────────────────────────────────────
    public function bloquear(Request $request, int $id): JsonResponse
    {
        $usuarioActual = $request->user();

        // No puedes bloquearte a ti mismo
        if ($usuarioActual->id === $id) {
            return response()->json([
                'message' => 'No puedes bloquearte a ti mismo.',
            ], 400);
        }

        // Verificar que el usuario existe
        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        // Buscar si ya existe relación
        $amistad = Amistad::where(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $usuarioActual->id)
                  ->where('receptor_id', $id);
        })->orWhere(function ($query) use ($usuarioActual, $id) {
            $query->where('solicitante_id', $id)
                  ->where('receptor_id', $usuarioActual->id);
        })->first();

        if ($amistad) {
            // Si ya existe, solo cambiar el estado a bloqueada
            if ($amistad->estado_amistad === 'bloqueada') {
                return response()->json(['message' => 'Este usuario ya está bloqueado.'], 409);
            }
            $amistad->update(['estado_amistad' => 'bloqueada']);
        } else {
            // Si no existe, crear un registro de bloqueo
            Amistad::create([
                'solicitante_id' => $usuarioActual->id,
                'receptor_id'    => $id,
                'estado_amistad' => 'bloqueada',
            ]);
        }

        return response()->json(['message' => 'Usuario bloqueado.']);
    }
}
