<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aliado;

class AdminAliadoController extends Controller
{
    public function index(Request $request)
    {
        $aliados = Aliado::orderByDesc('created_at')->paginate(15);
        return response()->json($aliados);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            "nombre_negocio_aliado" => "required|string|max:150",
            "tipo_negocio_aliado" => "nullable|string|max:100",
            "descripcion_aliado" => "nullable|string",
            "contacto_email_aliado" => "nullable|email|max:150",
            "contacto_telefono_aliado" => "nullable|string|max:20",
            "logo_url_aliado" => "nullable|url",
            "sitio_web_aliado" => "nullable|url",
            "municipio_aliado" => "required|string|max:100",
            "activo_aliado" => "boolean",
        ]);
        $aliado = Aliado::create($datos);
        return response()->json($aliado,201);
    }
    public function update(Request $request, int $id)
    {
        $aliado = Aliado::findOrFail($id);
        $datos = $request->validate([
            "nombre_negocio_aliado" => "sometimes|string|max:150",
            "tipo_negocio_aliado" => "nullable|string|max:100",
            "descripcion_aliado" => "nullable|string",
            "contacto_email_aliado" => "nullable|email|max:150",
            "contacto_telefono_aliado" => "nullable|string|max:20",
            "logo_url_aliado" => "nullable|url",
            "sitio_web_aliado" => "nullable|url",
            "municipio_aliado" => "sometimes|string|max:100",
            "activo_aliado" => "boolean",
        ]);
        $aliado->fill($datos);
        $aliado->save();
        return response()->json($aliado,200);
    }
    public function destroy (Request $request, int $id){
        $aliado = Aliado::findOrFail($id);
        $aliado->delete();
        return response()->json(["message"=>"Aliado eliminado"],200);
    }
}
