<?php

namespace App\Imports;

use App\Models\MetaMensual;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class MetaMensualImport implements ToModel, WithHeadingRow, WithValidation
{
    private $rowCount = 0;
    private $errors = [];

    public function model(array $row)
    {
        try {
            $this->rowCount++;

            // Validar que los datos requeridos existan
            if (!isset($row['plaza']) || !isset($row['tienda']) || 
                !isset($row['periodo']) || !isset($row['meta'])) {
                $this->errors[] = "Fila {$this->rowCount}: Faltan datos requeridos";
                return null;
            }

            // Limpiar y formatear los datos
            $plaza = strtoupper(trim($row['plaza']));
            $tienda = strtoupper(trim($row['tienda']));
            $periodo = trim($row['periodo']);
            $meta = floatval($row['meta']);

            // Validar formato del periodo (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                $this->errors[] = "Fila {$this->rowCount}: Formato de periodo inválido (debe ser YYYY-MM)";
                return null;
            }

            // Validar que la meta sea mayor que cero
            if ($meta <= 0) {
                $this->errors[] = "Fila {$this->rowCount}: La meta debe ser mayor que cero";
                return null;
            }

            return new MetaMensual([
                'plaza' => $plaza,
                'tienda' => $tienda,
                'periodo' => $periodo,
                'meta' => $meta,
            ]);

        } catch (\Exception $e) {
            $this->errors[] = "Fila {$this->rowCount}: Error al procesar - " . $e->getMessage();
            Log::error("Error en fila {$this->rowCount}: " . $e->getMessage());
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'plaza' => 'required|string|max:10',
            'tienda' => 'required|string|max:10',
            'periodo' => 'required|string|max:7',
            'meta' => 'required|numeric|min:0',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'plaza.required' => 'La plaza es requerida',
            'tienda.required' => 'La tienda es requerida',
            'periodo.required' => 'El periodo es requerido',
            'meta.required' => 'La meta es requerida',
            'meta.numeric' => 'La meta debe ser un número',
            'meta.min' => 'La meta debe ser mayor o igual a cero',
        ];
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}