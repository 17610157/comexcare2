<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnviarBitacoraNuevas extends Command
{
    protected $signature = 'bitacora:enviar-nuevas
                            {--dir= : Directorio con las evidencias (por defecto el de .env)}
                            {--empleado_id= : ID del empleado (por defecto el de .env)}
                            {--categoria= : Categoría/título (por defecto el de .env)}
                            {--descripcion= : Descripción base (por defecto el de .env)}
                            {--fecha= : Fecha de la bitácora YYYY-MM-DD (por defecto hoy)}
                            {--dry-run : Solo listar lo que se enviaría sin enviar}';

    protected $description = 'Envía las evidencias nuevas (JPG/PNG) de la bitácora al endpoint remoto';

    protected $exts = ['jpg' => true, 'jpeg' => true, 'png' => true];

    public function handle()
    {
        $dir = $this->option('dir') ?: storage_path(config('bitacora.evidencias_dir', 'app/evidencias'));

        if (! is_dir($dir)) {
            $this->error("No existe el directorio de evidencias: $dir");
            $this->line('Créalo con: mkdir -p '.$dir.'/enviadas');

            return 1;
        }

        $empleadoId = $this->option('empleado_id') ?: config('bitacora.empleado_id', '');
        $categoria  = $this->option('categoria') ?: config('bitacora.categoria', 'Evidencia diaria');
        $descripcion = $this->option('descripcion') ?: config('bitacora.descripcion', 'Captura diaria de evidencias');
        $fecha      = $this->option('fecha') ?: now()->format('Y-m-d');

        if ($empleadoId === '') {
            $this->error('No se definió BITACORA_EMPLEADO_ID en .env');

            return 1;
        }

        $imagenes = $this->scandirImages($dir);

        if (empty($imagenes)) {
            $this->info('No hay evidencias nuevas que enviar.');

            return 0;
        }

        $this->info(count($imagenes).' evidencias por enviar.');
        $this->line("Empleado: $empleadoId | Fecha: $fecha | Categoría: $categoria");
        $this->line('');

        $enviadas = 0;
        $falladas = 0;
        $enviadasDir = rtrim($dir, '/').'/enviadas/';
        if (! is_dir($enviadasDir)) {
            @mkdir($enviadasDir, 0755, true);
        }

        foreach ($imagenes as $i => $img) {
            if ($this->option('dry-run')) {
                $this->info("[DRY-RUN] $i - ".basename($img));

                continue;
            }

            $result = $this->enviar($img, [
                'empleado_id' => $empleadoId,
                'fecha'       => $fecha,
                'descripcion' => $descripcion,
                'categoria'   => $categoria,
                'hora_inicio' => now()->format('H:i'),
            ]);

            if ($result['ok']) {
                @rename($img, $enviadasDir.basename($img));
                $enviadas++;
                $this->info("[OK] ".basename($img).' -> '.($result['body'] ?? '')." (HTTP {$result['http']})");
            } else {
                $falladas++;
                $this->warn("[FALLO] ".basename($img).' - '.($result['error'] ?? "HTTP {$result['http']}"));
            }
        }

        $this->line('');
        if ($this->option('dry-run')) {
            $this->info('Dry-run completado: no se envió nada.');

            return 0;
        }

        $this->info("Resumen: $enviadas enviadas, $falladas falladas.");

        return $falladas > 0 ? 1 : 0;
    }

    protected function scandirImages($dir)
    {
        $imagenes = [];
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $full = $dir.'/'.$f;
            if (is_dir($full)) {
                continue;
            }
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (isset($this->exts[$ext])) {
                $imagenes[] = $full;
            }
        }
        sort($imagenes);

        return $imagenes;
    }

    protected function enviar($archivo, array $campos)
    {
        $url = config('bitacora.endpoint', 'http://diario.camposreyeros.com/api_bitacora.php');
        $key = config('bitacora.api_key', '');

        $post = array_filter($campos, fn ($v) => $v !== null && $v !== '');
        if (function_exists('curl_file_create')) {
            $post['archivo'] = curl_file_create($archivo, mime_content_type($archivo) ?: 'application/octet-stream', basename($archivo));
        } else {
            $post['archivo'] = '@'.$archivo;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_HTTPHEADER     => ['X-API-KEY: '.$key],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $body  = curl_exec($ch);
        $http  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            return ['ok' => false, 'http' => $http, 'error' => "cURL error ($errno): $err"];
        }

        return ['ok' => $http >= 200 && $http < 300, 'http' => $http, 'body' => $body];
    }
}
