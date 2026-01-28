<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MetasMensualImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Support header cases (lower/upper) for robustness
            $plaza = $row['plaza'] ?? $row['Plaza'] ?? $row['PLAZA'] ?? null;
            $tienda = $row['tienda'] ?? $row['Tienda'] ?? $row['TIENDA'] ?? null;
            $periodo = $row['periodo'] ?? $row['Periodo'] ?? $row['PERIODO'] ?? null;
            $meta = $row['meta'] ?? $row['Meta'] ?? $row['META'] ?? null;
            if (empty($plaza) || empty($tienda) || empty($periodo)) {
                continue;
            }
            $plaza = (string) $plaza;
            $tienda = (string) $tienda;
            $periodo = (string) $periodo;
            $meta = isset($meta) ? (float) $meta : null;

            $exists = DB::table('metas_mensual')
                ->where('plaza', $plaza)
                ->where('tienda', $tienda)
                ->where('periodo', $periodo)
                ->exists();

            if (!$exists) {
                DB::table('metas_mensual')->insert([
                    'plaza' => $plaza,
                    'tienda' => $tienda,
                    'periodo' => $periodo,
                    'meta' => $meta,
                ]);
            }
        }
    }
}
