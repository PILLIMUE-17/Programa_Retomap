<?php

namespace App\Http\Controllers;

use App\Models\Beneficio;
use Illuminate\Http\Request;

class AdminBeneficioController extends Controller
{
    // ─────────────────────────────────────────────────
    // POST /api/admin/beneficios
    // Crear un beneficio nuevo
    // ─────────────────────────────────────────────────
    public function store(Request $request)
    {
        $datos = $request->validate([
            'aliado_id'                     => 'required|exists:aliados,id',
            'descripcion_beneficio'         => 'required|string|max:500',
            'costo_xp_beneficio'            => 'required|integer|min:1',
            'valido_hasta_beneficio'        => 'nullable|date|after:today',
            'cantidad_disponible_beneficio' => 'nullable|integer|min:1',
            'activo_beneficio'              => 'boolean',
        ]);

        $beneficio = Beneficio::create($datos);

        return response()->json($beneficio, 201);
    }

    // ─────────────────────────────────────────────────
    // PUT /api/admin/beneficios/{id}
    // Editar un beneficio existente
    // ─────────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $beneficio = Beneficio::findOrFail($id);

        $datos = $request->validate([
            'aliado_id'                     => 'sometimes|exists:aliados,id',
            'descripcion_beneficio'         => 'sometimes|string|max:500',
            'costo_xp_beneficio'            => 'sometimes|integer|min:1',
            'valido_hasta_beneficio'        => 'nullable|date|after:today',
            'cantidad_disponible_beneficio' => 'nullable|integer|min:1',
            'activo_beneficio'              => 'boolean',
        ]);

        $beneficio->fill($datos);
        $beneficio->save();

        return response()->json($beneficio);
    }

    // ─────────────────────────────────────────────────
    // DELETE /api/admin/beneficios/{id}
    // Eliminar un beneficio (soft delete)
    // ─────────────────────────────────────────────────
    public function destroy(int $id)
    {
        $beneficio = Beneficio::findOrFail($id);
        $beneficio->delete();

        return response()->json(['message' => 'Beneficio eliminado correctamente.']);
    }
}
