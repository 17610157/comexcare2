<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreXcorteApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'fecha_corte' => 'required|date',
            'clave_tienda' => 'required|string|max:50',
            'monto_contado' => 'required|numeric|min:0',
            'monto_credito' => 'required|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_corte.required' => 'El campo fecha_corte es obligatorio.',
            'fecha_corte.date' => 'El campo fecha_corte debe ser una fecha válida.',
            'clave_tienda.required' => 'El campo clave_tienda es obligatorio.',
            'clave_tienda.string' => 'El campo clave_tienda debe ser texto.',
            'clave_tienda.max' => 'El campo clave_tienda no debe exceder 50 caracteres.',
            'monto_contado.required' => 'El campo monto_contado es obligatorio.',
            'monto_contado.numeric' => 'El campo monto_contado debe ser numérico.',
            'monto_contado.min' => 'El campo monto_contado no puede ser negativo.',
            'monto_credito.required' => 'El campo monto_credito es obligatorio.',
            'monto_credito.numeric' => 'El campo monto_credito debe ser numérico.',
            'monto_credito.min' => 'El campo monto_credito no puede ser negativo.',
        ];
    }
}
