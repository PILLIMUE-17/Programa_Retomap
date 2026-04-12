<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Nombre: letras, números y símbolos básicos
            'nombre_reto'                   => ['required', 'string', 'max:150', 'regex:/^[\pL\pN\s\-\.\!\?]+$/u'],

            'descripcion_reto'              => ['required', 'string', 'min:10', 'max:2000'],

            // XP: entre 10 y 500
            'xp_recompensa_reto'            => ['nullable', 'integer', 'min:10', 'max:500'],

            'instruccion_evidencia_reto'    => ['nullable', 'string', 'max:500'],

            // Fecha de expiración: debe ser futura
            'expira_en_reto'                => ['nullable', 'date', 'after:now'],

            'activo_reto'                   => ['nullable', 'boolean'],

            // Dificultad: solo 1, 2 o 3
            'dificultad_reto'               => ['nullable', 'integer', 'in:1,2,3'],

            // FK a lugar (UUID)
            'lugar_id'                      => ['required', 'string', 'size:36'],

            // FK a tipo_reto (BIGINT)
            'tipo_reto_id'                  => ['nullable', 'integer', 'exists:tipo_retos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_reto.regex'             => 'El nombre del reto solo puede contener letras, números y los símbolos - . ! ?',
            'descripcion_reto.min'          => 'La descripción debe tener al menos 10 caracteres.',
            'xp_recompensa_reto.min'        => 'El mínimo de XP a recompensar es 10.',
            'xp_recompensa_reto.max'        => 'El máximo de XP a recompensar es 500.',
            'expira_en_reto.after'          => 'La fecha de expiración debe ser una fecha futura.',
            'dificultad_reto.in'            => 'La dificultad debe ser 1 (fácil), 2 (medio) o 3 (difícil).',
            'lugar_id.required'             => 'El reto debe estar asociado a un lugar.',
            'lugar_id.size'                 => 'El lugar seleccionado no es válido.',
            'tipo_reto_id.exists'           => 'El tipo de reto seleccionado no existe.',
        ];
    }
}
