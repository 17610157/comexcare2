<?php

namespace App\Services;

use App\Models\RbfConfigStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RbfConfigStatusService
{
    protected string $endpoint = 'https://rbf.camposreyeros.com/config/status';

    public function fetchAndSync(): array
    {
        $response = Http::timeout(30)->get($this->endpoint);

        if (! $response->successful()) {
            Log::error('Error al obtener RBF Config Status: '.$response->status());

            return ['success' => false, 'message' => 'Error HTTP: '.$response->status()];
        }

        $data = $response->json();

        if (! is_array($data)) {
            Log::error('Respuesta inválida del endpoint RBF Config Status');

            return ['success' => false, 'message' => 'Respuesta inválida'];
        }

        $syncedAt = now();
        $records = [];

        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }
            $records[] = [
                'pl' => $row['pl'] ?? '',
                'rs' => $row['rs'] ?? '',
                'ti' => $row['ti'] ?? '',
                'ca' => $row['ca'] ?? '',
                'li' => $row['li'] ?? null,
                'of' => $row['of'] ?? null,
                'pr' => $row['pr'] ?? null,
                'co' => $row['co'] ?? null,
                'ex' => $row['ex'] ?? null,
                'db' => $row['db'] ?? null,
                'pv' => $row['pv'] ?? null,
                'us' => $row['us'] ?? null,
                'synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        }

        RbfConfigStatus::query()->truncate();
        RbfConfigStatus::query()->insert($records);

        $count = count($records);
        Log::info("Sincronización RBF Config Status completada: {$count} registros");

        return ['success' => true, 'count' => $count];
    }
}
