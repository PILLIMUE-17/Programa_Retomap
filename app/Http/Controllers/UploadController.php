<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class UploadController extends Controller
{
    public function evidencia(Request $request){
        $request->validate([
            "archivo" => "required|file|mimes:jpg,png,jpeg,mp4|max:5120", // max 5MB
        ]);
        $ruta = $request->file('archivo')->store('evidencias', 'public');
        return response()->json([
            'url' => asset(Storage::url($ruta)),
        ],201);
    }
}
