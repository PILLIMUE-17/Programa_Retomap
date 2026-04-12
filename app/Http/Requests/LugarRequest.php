<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LugarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Nombre: letras, números y algunos símbolos permitidos
            'nombre_lugar'       => ['required', 'string', 'max:150', 'regex:/^[\pL\pN\s\-\.\&\/]+$/u'],

            'descripcion_lugar'  => ['nullable', 'string', 'max:2000'],

            // Coordenadas válidas para Colombia (Huila aprox)
            'latitud_lugar'      => ['required', 'numeric', 'between:-4,3'],
            'longitud_lugar'     => ['required', 'numeric', 'between:-77,-73'],

            'direccion_lugar'    => ['nullable', 'string', 'max:300'],

            // Municipio: solo texto
            'municipio_lugar'    => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-]+$/u'],

            // Departamento: solo texto
            'departamento_lugar' => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-]+$/u'],

            'verificado_lugar'   => ['nullable', 'boolean'],
            'imagen_url_lugar'   => ['nullable', 'url', 'max:500'],

            // Horario: texto libre pero con límite
            'horario_lugar'      => ['nullable', 'string', 'max:200'],

            'calificacion_promedio_lugar' => ['nullable', 'integer', 'min:0', 'max:50'],

            'categoria_id'       => ['nullable', 'integer', 'exists:categorias,id'],
            'aliado_id'          => ['nullable', 'string', 'size:36'], // UUID
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_lugar.regex'            => 'El nombre del lugar solo puede contener letras, números y los símbolos - . & /',
            'latitud_lugar.between'         => 'La latitud debe estar en el rango de Colombia (-4 a 3).',
            'longitud_lugar.between'        => 'La longitud debe estar en el rango de Colombia (-77 a -73).',
            'municipio_lugar.regex'         => 'El municipio solo puede contener letras y espacios.',
            'departamento_lugar.regex'      => 'El departamento solo puede contener letras y espacios.',
            'categoria_id.exists'           => 'La categoría seleccionada no existe.',
            'calificacion_promedio_lugar.max' => 'La calificación máxima es 50.',
        ];
    }
}
