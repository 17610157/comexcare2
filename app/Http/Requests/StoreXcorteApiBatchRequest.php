<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreXcorteApiBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_is_list($data) && $data !== []) {
            $this->merge(['cortes' => $data]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'cortes' => 'required|array|min:1',
            'cortes.*.fecha_corte' => 'required|date',
            'cortes.*.clave_tienda' => 'required|string|max:50',
            'cortes.*.monto_contado' => 'required|numeric|min:0',
            'cortes.*.monto_credito' => 'required|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cortes.required' => 'El campo cortes es obligatorio.',
            'cortes.array' => 'El campo cortes debe ser un arreglo.',
            'cortes.min' => 'El campo cortes debe contener al menos un registro.',
            'cortes.*.fecha_corte.required' => 'El campo fecha_corte es obligatorio.',
            'cortes.*.fecha_corte.date' => 'El campo fecha_corte debe ser una fecha válida.',
            'cortes.*.clave_tienda.required' => 'El campo clave_tienda es obligatorio.',
            'cortes.*.clave_tienda.string' => 'El campo clave_tienda debe ser texto.',
            'cortes.*.clave_tienda.max' => 'El campo clave_tienda no debe exceder 50 caracteres.',
            'cortes.*.monto_contado.required' => 'El campo monto_contado es obligatorio.',
            'cortes.*.monto_contado.numeric' => 'El campo monto_contado debe ser numérico.',
            'cortes.*.monto_contado.min' => 'El campo monto_contado no puede ser negativo.',
            'cortes.*.monto_credito.required' => 'El campo monto_credito es obligatorio.',
            'cortes.*.monto_credito.numeric' => 'El campo monto_credito debe ser numérico.',
            'cortes.*.monto_credito.min' => 'El campo monto_credito no puede ser negativo.',
        ];
    }
}
