<?php

namespace App\Services;

use App\Models\ConciliacionHashArchivo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConciliacionHashArchivoService
{
    protected string $endpoint;

    protected string $apiKey;

    public function __construct()
    {
        $this->endpoint = config('services.conciliacion.hash_archivos_endpoint');
        $this->apiKey = config('services.conciliacion.hash_archivos_api_key');
    }

    public function fetchAndSync(): array
    {
        $response = Http::timeout(60)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->get($this->endpoint, ['null']);

        if (! $response->successful()) {
            Log::error('Error al obtener datos de Conciliación Hash Archivos: '.$response->status());

            return [
                'success' => false,
                'message' => 'Error HTTP: '.$response->status(),
            ];
        }

        $body = $response->json();

        if (! isset($body['datos']['data']) || ! is_array($body['datos']['data'])) {
            Log::error('Respuesta inválida del endpoint de Conciliación Hash Archivos');

            return [
                'success' => false,
                'message' => 'Respuesta inválida del endpoint',
            ];
        }

        $fechaConsulta = $body['datos']['fecha_consulta'] ?? now();
        $data = $body['datos']['data'];
        $records = [];

        foreach ($data as $item) {
            $records[] = [
                'sucursal' => $item['sucursal'] ?? '',
                'archivo' => $item['archivo'] ?? '',
                'md5' => $item['md5'] ?? '',
                'fecha_modificacion' => $item['fecha_modificacion'] ?? null,
                'disparador' => $item['disparador'] ?? '',
                'fecha_consulta_api' => $fechaConsulta,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ConciliacionHashArchivo::query()->truncate();
        ConciliacionHashArchivo::query()->insert($records);

        $count = count($records);
        Log::info("Sincronización Conciliación Hash Archivos completada: {$count} registros");

        return [
            'success' => true,
            'count' => $count,
        ];
    }
}
