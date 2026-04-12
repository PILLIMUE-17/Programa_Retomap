<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_usuario' => ['required', 'email'],
            'password'      => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email_usuario.required' => 'El correo es obligatorio.',
            'email_usuario.email'    => 'Ingresa un correo válido.',
            'password.required'      => 'La contraseña es obligatoria.',
        ];
    }
}