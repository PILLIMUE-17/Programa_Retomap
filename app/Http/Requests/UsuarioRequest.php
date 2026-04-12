<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario'); // Para ignorar el propio usuario en unique al editar

        return [
            // Solo letras, tildes, espacios y guiones — sin números ni símbolos
            'nombre_usuario'    => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],

            'email_usuario'     => ['required', 'email:rfc,dns', 'max:150', 'unique:usuarios,email_usuario,' . $usuarioId],

            'username_usuario'  => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:usuarios,username_usuario,' . $usuarioId],

            // Contraseña: mínimo 8 caracteres, al menos 1 mayúscula, 1 número y 1 carácter especial
            'password_hash_usuario' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).+$/'],

            'avatar_url_usuario' => ['nullable', 'url', 'max:500'],

            // Ciudad: solo texto, sin números
            'ciudad_usuario'    => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_usuario.regex'              => 'El nombre solo puede contener letras, espacios y guiones. Sin números ni símbolos.',
            'email_usuario.email'               => 'El correo electrónico debe tener un formato válido (ej: usuario@correo.com).',
            'email_usuario.unique'              => 'Este correo ya está registrado.',
            'username_usuario.alpha_dash'       => 'El username solo puede tener letras, números, guiones y guiones bajos.',
            'username_usuario.unique'           => 'Este username ya está en uso.',
            'password_hash_usuario.regex'       => 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un carácter especial (ej: !, @, #).',
            'ciudad_usuario.regex'              => 'La ciudad solo puede contener letras y espacios.',
        ];
    }
}
