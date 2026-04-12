<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_usuario'   => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
            'email_usuario'    => ['required', 'email:rfc', 'max:150', 'unique:usuarios,email_usuario'],
            'username_usuario' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:usuarios,username_usuario'],
            'password'         => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).+$/', 'confirmed'],
            // 'confirmed' exige que también venga 'password_confirmation' igual
            'ciudad_usuario'   => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\-\.]+$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_usuario.regex'   => 'El nombre solo puede tener letras y espacios.',
            'email_usuario.unique'   => 'Este correo ya está registrado.',
            'username_usuario.unique'=> 'Este username ya está en uso.',
            'password.regex'         => 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un símbolo especial.',
            'password.confirmed'     => 'Las contraseñas no coinciden.',
            'ciudad_usuario.regex'   => 'La ciudad solo puede tener letras y espacios.',
        ];
    }
}