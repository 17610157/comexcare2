<?php

namespace App\Services;

use App\Models\RbfFileHash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RbfFileHashService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.rbf.endpoint', 'https://rbf.camposreyeros.com/api/queryHashFileServicesJson');
    }

    public function fetchAndSync(): array
    {
        $response = Http::timeout(30)->get($this->endpoint);

        if (! $response->successful()) {
            Log::error('Error al obtener datos de RBF FileServices: '.$response->status());

            return [
                'success' => false,
                'message' => 'Error HTTP: '.$response->status(),
            ];
        }

        $data = $response->json();

        if (! isset($data['files']) || ! is_array($data['files'])) {
            Log::error('Respuesta inválida del endpoint RBF FileServices');

            return [
                'success' => false,
                'message' => 'Respuesta inválida del endpoint',
            ];
        }

        $lastSync = $data['last_sync'] ?? now();
        $records = [];

        foreach ($data['files'] as $file) {
            $path = $file['path'] ?? '';
            $parts = array_values(array_filter(explode('/', $path), fn ($p) => $p !== ''));

            $segments = count($parts);

            $servicio = $parts[0] ?? null;
            $plaza = $segments >= 3 ? $parts[1] : null;
            $zona = $segments >= 4 ? $parts[2] : null;

            $records[] = [
                'servicio' => $servicio,
                'plaza' => $plaza,
                'zona' => $zona,
                'path' => $path,
                'name' => strtoupper($file['name'] ?? ''),
                'hash' => strtoupper(substr($file['hash'] ?? '', -5)),
                'last_modified' => $file['last_modified'] ?? null,
                'last_sync' => $lastSync,
                'manual' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        RbfFileHash::query()->where('manual', false)->delete();

        foreach ($records as $record) {
            RbfFileHash::query()->updateOrCreate(
                ['path' => $record['path']],
                $record
            );
        }

        $count = count($records);
        Log::info("Sincronización RBF FileServices completada: {$count} registros");

        return [
            'success' => true,
            'count' => $count,
        ];
    }
}
