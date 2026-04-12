<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lugar;

class AdminLugarController extends Controller
{
    // función para crear un nuevo lugar
    public function store(Request $request){
        $datos = $request-> validate([
            "nombre_lugar" => "required|string|max:150",
            "descripcion_lugar" => "nullable|string",
            "latitud_lugar" => "required|numeric|between:-90,90",
            "longitud_lugar" => "required|numeric|between:-180,180",
            "direccion_lugar" => "nullable|string|max:255",
            "municipio_lugar" => "required|string|max:100",
            "departamento_lugar" => "required|string|max:100",
            "categoria_id" => "required|exists:categorias,id",
            "aliado_id" => "nullable|exists:aliados,id",
            "horario_lugar" => "nullable|string|max:200",    
        ]);
        $lugar = Lugar::create($datos);
        return response()->json($lugar,201);
    }
    // funcion que edita un lugar existente
    public function update(Request $request, int $id){
        $lugar = Lugar::findOrFail($id);
        $datos = $request->validate([
            "nombre_lugar" => "sometimes|string|max:150",
            "descripcion_lugar" => "nullable|string",
            "latitud_lugar" => "sometimes|numeric|between:-90,90",
            "longitud_lugar" => "sometimes|numeric|between:-180,180",
            "direccion_lugar" => "sometimes|string|max:255",
            "municipio_lugar" => "sometimes|string|max:100",
            "departamento_lugar" => "sometimes|string|max:100",
            "categoria_id" => "sometimes|exists:categorias,id",
            "aliado_id" => "nullable|exists:aliados,id",
            "horario_lugar" => "nullable|string|max:200",    
        ]);
        $lugar->fill($datos);
        $lugar->save();
        return response()->json($lugar,200);
    }
    public function destroy (Request $request, int $id){
        $lugar = Lugar::findOrFail($id);
        $lugar->delete();
        return response()->json(["message"=>"Lugar eliminado"],200);
    }

}
