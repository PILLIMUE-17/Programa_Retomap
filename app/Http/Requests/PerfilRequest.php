<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // El ID del usuario logueado — para ignorarlo en la regla unique
        $usuarioId = $this->user()->id;

        return [
            // 'sometimes' = solo valida si el campo fue enviado
            'nombre_usuario'     => ['sometimes', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],

            'username_usuario'   => [
                'sometimes', 'string', 'min:3', 'max:50', 'alpha_dash',
                'unique:usuarios,username_usuario,' . $usuarioId,
            ],

            'ciudad_usuario'     => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],

            'avatar_url_usuario' => ['sometimes', 'nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_usuario.regex'            => 'El nombre solo puede contener letras, espacios y guiones.',
            'username_usuario.alpha_dash'     => 'El username solo puede tener letras, números, guiones y guiones bajos.',
            'username_usuario.unique'         => 'Este username ya está en uso.',
            'ciudad_usuario.regex'            => 'La ciudad solo puede contener letras y espacios.',
            'avatar_url_usuario.url'          => 'El avatar debe ser una URL válida.',
        ];
    }
}
