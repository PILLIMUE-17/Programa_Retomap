<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

class AdminUsuarioController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /api/admin/usuarios
    // Lista todos los usuarios con paginación y búsqueda
    // ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Usuario::with('nivel')->withCount('retosCompletados');

        // Búsqueda por nombre, email o username
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_usuario', 'like', "%{$buscar}%")
                  ->orWhere('email_usuario', 'like', "%{$buscar}%")
                  ->orWhere('username_usuario', 'like', "%{$buscar}%");
            });
        }

        // Filtro por estado activo/inactivo
        if ($request->filled('activo')) {
            $query->where('activo_usuario', $request->boolean('activo'));
        }

        // Filtro por admin
        if ($request->filled('es_admin')) {
            $query->where('es_admin', $request->boolean('es_admin'));
        }

        $usuarios = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($usuarios);
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/admin/usuarios/{id}
    // Ver detalle de un usuario
    // ─────────────────────────────────────────────────────────
    public function show(int $id)
    {
        $usuario = Usuario::with(['nivel', 'retosCompletados.reto', 'insignias'])
            ->withCount(['retosCompletados', 'publicaciones'])
            ->findOrFail($id);

        return response()->json($usuario);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/admin/usuarios/{id}
    // Editar usuario (activar/desactivar, cambiar rol admin)
    // ─────────────────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $usuario = Usuario::findOrFail($id);

        $datos = $request->validate([
            'activo_usuario' => 'sometimes|boolean',
            'es_admin'       => 'sometimes|boolean',
            'nombre_usuario' => 'sometimes|string|max:150',
            'ciudad_usuario' => 'sometimes|string|max:100',
        ]);

        $usuario->fill($datos);
        $usuario->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => $usuario,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/admin/usuarios/{id}/toggle-activo
    // Activar o desactivar un usuario
    // ─────────────────────────────────────────────────────────
    public function toggleActivo(int $id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->activo_usuario = !$usuario->activo_usuario;
        $usuario->save();

        $estado = $usuario->activo_usuario ? 'activado' : 'desactivado';

        return response()->json([
            'message' => "Usuario {$estado} correctamente.",
            'activo'  => $usuario->activo_usuario,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/admin/usuarios/{id}/toggle-admin
    // Dar o quitar permisos de admin
    // ─────────────────────────────────────────────────────────
    public function toggleAdmin(int $id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->es_admin = !$usuario->es_admin;
        $usuario->save();

        $rol = $usuario->es_admin ? 'ahora es administrador' : 'ya no es administrador';

        return response()->json([
            'message' => "El usuario {$rol}.",
            'es_admin' => $usuario->es_admin,
        ]);
    }
}
