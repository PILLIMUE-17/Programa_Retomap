<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AliadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Nombre del negocio: letras, números y algunos símbolos permitidos (&, -, .)
            'nombre_negocio_aliado'      => ['required', 'string', 'max:150', 'regex:/^[\pL\pN\s\-\.\&\/]+$/u'],

            // Tipo de negocio: solo texto
            'tipo_negocio_aliado'        => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-]+$/u'],

            'descripcion_aliado'         => ['nullable', 'string', 'max:1000'],

            // Email con formato válido
            'contacto_email_aliado'      => ['nullable', 'email:rfc', 'max:150'],

            // Teléfono: exactamente 10 dígitos numéricos — celular colombiano
            'contacto_telefono_aliado'   => ['nullable', 'digits:10'],

            'logo_url_aliado'            => ['nullable', 'url', 'max:500'],
            'sitio_web_aliado'           => ['nullable', 'url', 'max:500'],

            // Municipio: solo texto
            'municipio_aliado'           => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-]+$/u'],

            'activo_aliado'              => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_negocio_aliado.regex'       => 'El nombre del negocio solo puede contener letras, números, espacios y los símbolos & - . /',
            'tipo_negocio_aliado.regex'         => 'El tipo de negocio solo puede contener letras y espacios.',
            'contacto_email_aliado.email'       => 'El correo de contacto debe tener un formato válido (ej: negocio@correo.com).',
            'contacto_telefono_aliado.digits'   => 'El teléfono debe tener exactamente 10 dígitos numéricos (ej: 3101234567).',
            'municipio_aliado.regex'            => 'El municipio solo puede contener letras y espacios.',
            'sitio_web_aliado.url'              => 'El sitio web debe ser una URL válida (ej: https://minegocio.com).',
        ];
    }
}
