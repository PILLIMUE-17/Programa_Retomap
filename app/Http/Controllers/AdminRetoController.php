<?php

namespace App\Http\Controllers;

use App\Models\Reto;
use Illuminate\Http\Request;
use App\Models\RetoCompletado;

// Controlador para funciones administrativas relacionadas con retos
class AdminRetoController extends Controller
{
    // Lista todos los retos con paginación (para gestión admin)
    public function listar(Request $request)
    {
        $query = Reto::with(['lugar:id,nombre_lugar', 'tipoReto:id,nombre_tipo_reto']);

        if ($request->filled('buscar')) {
            $query->where('nombre_reto', 'like', '%' . $request->input('buscar') . '%');
        }

        $retos = $query->orderByDesc('created_at')->paginate(15);
        return response()->json($retos);
    }

    //lista los retos completados pendientes
    public function index(Request $request)
    {
        $pendientes = RetoCompletado::with("usuario","reto")
          ->where("estado_reto_completado", "pendiente")
          ->paginate(15);
        return response()->json($pendientes);
    }
    public function aprobar(Request $request, int $id)
    {
        $retoCompletado = RetoCompletado::findOrFail($id);
        if($retoCompletado->estado_reto_completado !== "pendiente"){
            return response()->json(["message"=>" este reto ya fue revisado"],422);
        }
        $retoCompletado->estado_reto_completado="aprobado";
        $retoCompletado->save();
        return response()->json(["message"=>"reto aprobado"],200);
        
    }
    public function rechazar(Request $request, int $id){
        $retocompletado = RetoCompletado:: findOrFail($id);
        if($retocompletado->estado_reto_completado !== "pendiente"){
            return response()->json (["message"=>" este reto ya fue revisado"],422);
        }
        $retocompletado->estado_reto_completado="rechazado";
        $retocompletado->motivo_rechazo_reto_completado= $request->input("motivo", null);
        $retocompletado->save();
        return response()->json(["message"=>"reto rechazado"],200);
    }
// función para crear un nuevo reto
    public function store(Request $request){
        $datos = $request->validate([
            "nombre_reto"               => "required|string|max:150",
            'descripcion_reto'          => 'required|string|min:10',
            'xp_recompensa_reto'        => 'required|integer|min:10|max:500',
            'instruccion_evidencia_reto'=> 'required|string',
            'dificultad_reto'           => 'required|integer|in:1,2,3',
            'lugar_id'                  => 'required|exists:lugares,id',
            'tipo_reto_id'              => 'required|exists:tipo_retos,id',
            'expira_en_reto'            => 'nullable|date|after:today',
            'activo_reto'               => 'boolean',
        ]);
        $reto = Reto::create($datos);
        return response()->json($reto,201);
    }
    // editar o actualiza un reto existente
    public function update(Request $request, int $id){
        $reto = Reto::findOrFail($id);
        $datos = $request->validate([
            "nombre_reto"               =>"sometimes|string|max:150",
            'descripcion_reto'          => 'sometimes|string|min:10',
            'xp_recompensa_reto'        => 'sometimes|integer|min:10|max:500',
            'instruccion_evidencia_reto'=> 'sometimes|string',
            'dificultad_reto'           => 'sometimes|integer|in:1,2,3',
            'lugar_id'                  => 'sometimes|exists:lugares,id',
            'tipo_reto_id'              => 'sometimes|exists:tipo_retos,id',
            'expira_en_reto'            => 'nullable|date|after:today',
            'activo_reto'               => 'boolean',
        ]);
    $reto->fill($datos);
    $reto->save();
    return response()->json($reto);
}

public function destroy(Request $request, int $id){
    $reto = Reto::findOrFail($id);
    $reto->delete();
    return response()->json(["message"=>"Reto eliminado correctamente"],200);
}



}
