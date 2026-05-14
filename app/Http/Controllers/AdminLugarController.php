<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Lugar;

class AdminLugarController extends Controller
{
    // Lista todos los lugares con paginación
    public function index(Request $request): JsonResponse
    {
        $query = Lugar::with(['categoria:id,nombre_categoria,icono_categoria', 'aliado:id,nombre_negocio_aliado']);

        if ($request->filled('buscar')) {
            $query->where('nombre_lugar', 'like', '%' . $request->input('buscar') . '%');
        }

        $lugares = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($lugares);
    }

    // Función para crear un nuevo lugar
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre_lugar'      => 'required|string|max:150',
            'descripcion_lugar' => 'nullable|string',
            'latitud_lugar'     => 'required|numeric|between:-90,90',
            'longitud_lugar'    => 'required|numeric|between:-180,180',
            'direccion_lugar'   => 'nullable|string|max:255',
            'municipio_lugar'   => 'required|string|max:100',
            'departamento_lugar'=> 'required|string|max:100',
            'categoria_id'      => 'required|exists:categorias,id',
            'aliado_id'         => 'nullable|exists:aliados,id',
            'horario_lugar'     => 'nullable|string|max:200',
        ]);

        /** @var Lugar $lugar */
        $lugar = Lugar::create($datos);

        return response()->json($lugar, 201);
    }

    // Función que edita un lugar existente
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var Lugar $lugar */
        $lugar = Lugar::findOrFail($id);

        $datos = $request->validate([
            'nombre_lugar'      => 'sometimes|string|max:150',
            'descripcion_lugar' => 'nullable|string',
            'latitud_lugar'     => 'sometimes|numeric|between:-90,90',
            'longitud_lugar'    => 'sometimes|numeric|between:-180,180',
            'direccion_lugar'   => 'sometimes|string|max:255',
            'municipio_lugar'   => 'sometimes|string|max:100',
            'departamento_lugar'=> 'sometimes|string|max:100',
            'categoria_id'      => 'sometimes|exists:categorias,id',
            'aliado_id'         => 'nullable|exists:aliados,id',
            'horario_lugar'     => 'nullable|string|max:200',
        ]);

        $lugar->fill($datos);
        $lugar->save();

        return response()->json($lugar, 200);
    }

    // Función que elimina un lugar
    public function destroy(int $id): JsonResponse
    {
        /** @var Lugar $lugar */
        $lugar = Lugar::findOrFail($id);
        $lugar->delete();

        return response()->json(['message' => 'Lugar eliminado'], 200);
    }
}
