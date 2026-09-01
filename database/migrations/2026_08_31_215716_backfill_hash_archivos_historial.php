<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        $fechaMod = fn ($f) => $f === null || $f === '' ? null : (str_starts_with((string) $f, '0001-01-01') ? null : $f);

        DB::table('hash_archivos_lotes')
            ->where('estado', 'exitoso')
            ->orderBy('id')
            ->chunkById(200, function ($lotes) use ($fechaMod) {
                $rows = [];
                foreach ($lotes as $lote) {
                    $data = json_decode((string) $lote->payload, true);
                    if (! is_array($data)) {
                        continue;
                    }

                    $tiendas = $data['Tiendas'] ?? [];
                    if (! is_array($tiendas)) {
                        $tiendas = [$data];
                    }

                    foreach ($tiendas as $tienda) {
                        $sucursal = $tienda['Sucursal'] ?? $lote->sucursal;
                        $disparador = $tienda['Disparador'] ?? $lote->disparador;
                        $fechaEnvio = $tienda['FechaEnvio'] ?? $lote->fecha_envio;
                        $archivos = $tienda['Archivos'] ?? [];
                        if (! is_array($archivos)) {
                            continue;
                        }

                        foreach ($archivos as $archivo) {
                            if (isset($archivo['Existe']) && $archivo['Existe'] === false) {
                                continue;
                            }
                            $nombre = $archivo['Nombre'] ?? null;
                            if ($nombre === null || $nombre === '') {
                                continue;
                            }
                            $md5 = strtolower((string) ($archivo['Md5'] ?? ''));
                            $rows[] = [
                                'sucursal' => (string) $sucursal,
                                'ip' => $lote->ip,
                                'archivo' => (string) $nombre,
                                'md5' => $md5 === '' ? '' : substr($md5, -5),
                                'md5_completo' => $md5 === '' ? null : $md5,
                                'disparador' => (string) $disparador,
                                'fecha_modificacion' => $fechaMod($archivo['FechaModificacion'] ?? null),
                                'fecha_consulta_api' => $fechaEnvio ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    if (! empty($chunk)) {
                        DB::table('hash_archivos_historial')->insert($chunk);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('hash_archivos_historial')->truncate();
    }
};
