<?php

/**
 * Script para enviar registros de bitácora (datos + evidencias) al endpoint externo.
 *
 * URL del endpoint: http://diario.camposreyeros.com/api_bitacora.php
 *
 * Uso (línea de comandos):
 *
 *   # Registro único con evidencia
 *   php scripts/enviar_bitacora.php \
 *       --empleado_id 20241 \
 *       --fecha 2026-08-27 \
 *       --descripcion "descripción de prueba" \
 *       --categoria "titulo de prueba" \
 *       --hora_inicio 08:29 \
 *       --hora_fin 08:59 \
 *       --archivo /ruta/a/evidencia.jpg
 *
 *   # Lote: un registro por cada imagen (JPG/PNG) dentro de un directorio
 *   php scripts/enviar_bitacora.php \
 *       --dir /ruta/evidencias \
 *       --empleado_id 20241 \
 *       --fecha 2026-08-27 \
 *       --descripcion "Evidencia capturada"
 *
 *   # Datos desde JSON (el archivo va aparte con --archivo)
 *   php scripts/enviar_bitacora.php \
 *       --json '{"empleado_id":"20241","fecha":"2026-08-27","descripcion":"x","categoria":"y"}' \
 *       --archivo /ruta/a/evidencia.jpg
 *
 * Opciones:
 *   --endpoint <url>        URL del endpoint (por defecto el de diario)
 *   --api-key <key>         API key (por defecto la del .env BITACORA_API_KEY)
 *   --categoria <texto>     Título/categoría a usar en el lote (por defecto "Evidencia diaria")
 *   --descripcion <texto>   Descripción a usar en el lote (o por registro)
 */

// ---------- Lectura de argumentos ----------

function arg($name, $default = null)
{
    global $argv;
    foreach ($argv as $i => $a) {
        if ($a === '--'.$name && isset($argv[$i + 1])) {
            return $argv[$i + 1];
        }
    }

    return $default;
}

$root = dirname(__DIR__);
$env  = loadEnv($root.'/.env');

$endpoint    = arg('endpoint', 'http://diario.camposreyeros.com/api_bitacora.php');
$apiKey      = arg('api-key', $env['BITACORA_API_KEY'] ?? '');
$dir         = arg('dir');
$archivo     = arg('archivo');
$json        = arg('json');
$dataDir     = arg('data-dir');

$campos = [
    'empleado_id' => arg('empleado_id'),
    'fecha'       => arg('fecha'),
    'descripcion' => arg('descripcion'),
    'categoria'   => arg('categoria'),
    'hora_inicio' => arg('hora_inicio'),
    'hora_fin'    => arg('hora_fin'),
];

// ---------- Helpers ----------

function loadEnv($path)
{
    $vars = [];
    if (is_file($path)) {
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $vars[trim($k)] = trim($v);
        }
    }

    return $vars;
}

function endpointPost($url, $apiKey, array $campos, ?string $archivoPath)
{
    $post = [];
    foreach ($campos as $k => $v) {
        if ($v !== null && $v !== '') {
            $post[$k] = $v;
        }
    }

    if ($archivoPath) {
        if (! is_file($archivoPath)) {
            return ['ok' => false, 'error' => "No existe el archivo: $archivoPath"];
        }
        if (function_exists('curl_file_create')) {
            $post['archivo'] = curl_file_create($archivoPath, mime_content_type($archivoPath) ?: 'application/octet-stream', basename($archivoPath));
        } else {
            $post['archivo'] = '@'.$archivoPath;
        }
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post,
        CURLOPT_HTTPHEADER     => ['X-API-KEY: '.$apiKey],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $body    = curl_exec($ch);
    $http    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno   = curl_errno($ch);
    $err     = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'http' => $http, 'error' => "cURL error ($errno): $err"];
    }

    return ['ok' => $http >= 200 && $http < 300, 'http' => $http, 'body' => $body];
}

// ---------- Lógica principal ----------

if ($apiKey === '') {
    fwrite(STDERR, "ERROR: no se definió API key (usa --api-key o BITACORA_API_KEY en .env)\n");
    exit(1);
}

if (! in_array('--dir', $argv, true) && ! in_array('--archivo', $argv, true) && ! in_array('--json', $argv, true)) {
    fwrite(STDERR, "ERROR: usa --archivo <ruta> (registro único), --dir <ruta> (lote por imágenes) o --json '<json>'\n");
    exit(1);
}

// Construir lista de trabajos: [ ['campos'=>..., 'archivo'=>?], ... ]
$trabajos = [];

if ($json) {
    $decoded = json_decode($json, true);
    if (! is_array($decoded)) {
        fwrite(STDERR, "ERROR: --json no es un JSON válido\n");
        exit(1);
    }
    foreach ($decoded as $d) {
        $trabajos[] = ['campos' => $d, 'archivo' => null];
    }
    // Si hay --archivo y el JSON fue un objeto único, asociarlo
    if ($archivo && count($trabajos) === 1) {
        $trabajos[0]['archivo'] = $archivo;
    }
} elseif ($archivo) {
    $trabajos[] = ['campos' => $campos, 'archivo' => $archivo];
} elseif ($dir) {
    if (! is_dir($dir)) {
        fwrite(STDERR, "ERROR: no existe el directorio: $dir\n");
        exit(1);
    }
    $exts = ['jpg' => true, 'jpeg' => true, 'png' => true];
    $imagenes = [];
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (isset($exts[$ext])) {
            $imagenes[] = $dir.'/'.$f;
        }
    }
    sort($imagenes);
    if (empty($imagenes)) {
        fwrite(STDOUT, "0 imágenes JPG/PNG encontradas en $dir\n");
        exit(0);
    }
    foreach ($imagenes as $img) {
        $v = $campos;
        $v['categoria']   = $campos['categoria'] ?? 'Evidencia diaria';
        $v['descripcion'] = $campos['descripcion'] ?? basename($img);
        $trabajos[] = ['campos' => $v, 'archivo' => $img];
    }
}

// Enviar
$enviados = 0;
$fallidos = 0;

foreach ($trabajos as $i => $t) {
    $res = endpointPost($endpoint, $apiKey, $t['campos'], $t['archivo']);
    if ($res['ok']) {
        $enviados++;
        fwrite(STDOUT, "[{$i}] OK (HTTP {$res['http']}) archivo=".($t['archivo'] ? basename($t['archivo']) : 'sin evidencia')." -> ".($res['body'] ?? '')."\n");
    } else {
        $fallidos++;
        fwrite(STDOUT, "[{$i}] FALLÓ ".($res['error'] ?? ('HTTP '.$res['http']))." archivo=".($t['archivo'] ? basename($t['archivo']) : 'sin evidencia')."\n");
    }
}

fwrite(STDOUT, "\nResumen: $enviados enviados, $fallidos fallidos\n");
exit($fallidos > 0 ? 1 : 0);
