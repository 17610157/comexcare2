<?php

/**
 * Endpoint de bitácora diaria
 * URL: /api_bitacora.php
 *
 * Métodos:
 *   POST            -> crea un registro de bitácora
 *   PUT | PATCH /?id=N -> actualiza el registro con id N
 *
 * Autenticación: header X-API-KEY
 *
 * Acepta (Content-Type):
 *   - application/json  -> {"empleado_id":"...","fecha":"...","descripcion":"...","categoria":"...","hora_inicio":"...","hora_fin":"..."}
 *   - multipart/form-data -> campos + archivo (campo "archivo", JPG/PNG, máx 5MB)
 */

// ---------- Configuración ----------

define('MAX_ARCHIVO_BYTES', 5 * 1024 * 1024); // 5 MB

// Lee credenciales desde el .env del proyecto (estamos en public/, el .env está en el padre)
function load_env($path)
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

$env = load_env(dirname(__DIR__).'/.env');

$dbConfig = [
    'host'     => $env['DB_HOST'] ?? '127.0.0.1',
    'port'     => $env['DB_PORT'] ?? '5432',
    'dbname'   => $env['DB_DATABASE'] ?? 'comexcare',
    'user'     => $env['DB_USERNAME'] ?? 'comexcare',
    'password' => $env['DB_PASSWORD'] ?? '',
];

$apiKey = $env['BITACORA_API_KEY'] ?? '';

// ---------- Helpers ----------

function respond($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function db(array $cfg)
{
    $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}";
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    return $pdo;
}

function validation_errors(array $data, bool $partial = false)
{
    $errors = [];
    $required = ['empleado_id', 'fecha', 'descripcion', 'categoria'];

    foreach ($required as $field) {
        if (! $partial && (! isset($data[$field]) || trim((string) $data[$field]) === '')) {
            $errors[$field] = 'El campo es obligatorio';
        } elseif ($partial && array_key_exists($field, $data) && trim((string) $data[$field]) === '') {
            $errors[$field] = 'El campo no puede estar vacío';
        }
    }

    if (isset($data['empleado_id']) && mb_strlen((string) $data['empleado_id']) > 50) {
        $errors['empleado_id'] = 'Máximo 50 caracteres';
    }

    if (isset($data['categoria']) && mb_strlen((string) $data['categoria']) > 255) {
        $errors['categoria'] = 'Máximo 255 caracteres';
    }

    if (isset($data['fecha']) && $data['fecha'] !== '' && @date('Y-m-d', strtotime((string) $data['fecha'])) != $data['fecha']) {
        $errors['fecha'] = 'Formato de fecha inválido (YYYY-MM-DD)';
    }

    foreach (['hora_inicio', 'hora_fin'] as $field) {
        if (isset($data[$field]) && $data[$field] !== '' && ! preg_match('/^\d{2}:\d{2}$/', (string) $data[$field])) {
            $errors[$field] = 'Formato de hora inválido (HH:MM)';
        }
    }

    return $errors;
}

function handle_upload(&$data, array &$errors)
{
    if (isset($_FILES['archivo']) && is_uploaded_file($_FILES['archivo']['tmp_name'])) {
        $file = $_FILES['archivo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['archivo'] = 'Error al subir el archivo';

            return;
        }

        if ($file['size'] > MAX_ARCHIVO_BYTES) {
            $errors['archivo'] = 'El archivo excede el tamaño máximo de 5MB';

            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $errors['archivo'] = 'El archivo debe ser JPG o PNG';

            return;
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            $errors['archivo'] = 'El archivo no es una imagen válida';

            return;
        }

        $relDir = 'bitacoras/'.date('Y/m');
        $absDir = __DIR__.'/'.$relDir;
        if (! is_dir($absDir) && ! mkdir($absDir, 0755, true) && ! is_dir($absDir)) {
            $errors['archivo'] = 'No se pudo crear el directorio de archivos';

            return;
        }

        $name = md5(uniqid('', true)).'.'.$ext;
        $dest = $absDir.'/'.$name;

        if (! move_uploaded_file($file['tmp_name'], $dest)) {
            $errors['archivo'] = 'No se pudo guardar el archivo';

            return;
        }

        $data['archivo'] = $relDir.'/'.$name;
        $data['archivo_nombre'] = $file['name'];
    }
}

function truncate($value, $len)
{
    $v = (string) $value;

    return mb_strlen($v) > $len ? mb_substr($v, 0, $len) : $v;
}

// ---------- Autenticación ----------

$headerKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_SERVER['HTTP_X_APIKEY'] ?? '');

if ($apiKey === '' || $headerKey === '' || ! hash_equals($apiKey, $headerKey)) {
    respond(['error' => 'No Autorizado', 'message' => 'Se requiere un X-API-KEY válido'], 401);
}

// ---------- Entrada ----------

$method = $_SERVER['REQUEST_METHOD'];

if (! in_array($method, ['POST', 'PUT', 'PATCH'])) {
    respond(['success' => false, 'message' => 'Método no permitido'], 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (! is_array($data)) {
        respond(['success' => false, 'message' => 'El cuerpo debe ser un JSON válido'], 422);
    }
} else {
    $data = $_POST;
}

// Si viene por JSON, no hay archivo adjunto; si viene multipart, se procesa $_FILES
if (stripos($contentType, 'application/json') === false) {
    // multipart: procesar posible archivo
    $errors = [];
    handle_upload($data, $errors);
    if (! empty($errors)) {
        respond(['success' => false, 'message' => 'Validación fallida', 'errors' => $errors], 422);
    }
}

// ---------- Ejecución ----------

try {
    $pdo = db($dbConfig);
    $isUpdate = in_array($method, ['PUT', 'PATCH']);

    if ($isUpdate) {
        $id = $_GET['id'] ?? null;
        if (! $id || ! ctype_digit((string) $id)) {
            respond(['success' => false, 'message' => 'Se requiere el parámetro ?id=N'], 422);
        }

        $stmt = $pdo->prepare('SELECT * FROM bitacoras WHERE id = :id');
        $stmt->execute([':id' => (int) $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            respond(['success' => false, 'message' => 'Registro de bitácora no encontrado'], 404);
        }

        $errors = validation_errors($data, true);
        if (! empty($errors)) {
            respond(['success' => false, 'message' => 'Validación fallida', 'errors' => $errors], 422);
        }

        $fields = ['empleado_id', 'fecha', 'descripcion', 'categoria', 'hora_inicio', 'hora_fin', 'archivo', 'archivo_nombre'];
        $set = [];
        $params = [':id' => (int) $id];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field = :$field";
                $params[":$field"] = ($field === 'archivo' || $field === 'archivo_nombre' || $field === 'descripcion')
                    ? truncate($data[$field], ($field === 'descripcion') ? 100000 : (($field === 'archivo') ? 500 : 255))
                    : ($data[$field] === '' ? null : $data[$field]);
            }
        }
        $set[] = 'updated_at = NOW()';
        $sql = 'UPDATE bitacoras SET '.implode(', ', $set).' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $stmt = $pdo->prepare('SELECT * FROM bitacoras WHERE id = :id');
        $stmt->execute([':id' => (int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        respond([
            'success' => true,
            'message' => 'Registro de bitácora actualizado correctamente',
            'bitacora' => $row,
        ]);
    }

    $errors = validation_errors($data, false);
    if (! empty($errors)) {
        respond(['success' => false, 'message' => 'Validación fallida', 'errors' => $errors], 422);
    }

    $sql = 'INSERT INTO bitacoras (empleado_id, fecha, descripcion, categoria, hora_inicio, hora_fin, archivo, archivo_nombre, created_at, updated_at)
            VALUES (:empleado_id, :fecha, :descripcion, :categoria, :hora_inicio, :hora_fin, :archivo, :archivo_nombre, NOW(), NOW())
            RETURNING *';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':empleado_id'     => truncate($data['empleado_id'], 50),
        ':fecha'           => $data['fecha'],
        ':descripcion'     => (string) $data['descripcion'],
        ':categoria'       => truncate($data['categoria'], 255),
        ':hora_inicio'     => ($data['hora_inicio'] ?? '') === '' ? null : $data['hora_inicio'],
        ':hora_fin'        => ($data['hora_fin'] ?? '') === '' ? null : $data['hora_fin'],
        ':archivo'         => $data['archivo'] ?? null,
        ':archivo_nombre'  => $data['archivo_nombre'] ?? null,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    respond([
        'success' => true,
        'message' => 'Registro de bitácora creado correctamente',
        'bitacora' => $row,
    ], 201);
} catch (Throwable $e) {
    $log = dirname(__DIR__).'/storage/logs/bitacora-api-error.log';
    @file_put_contents($log, date('Y-m-d H:i:s').' '.$e->getMessage()."\n", FILE_APPEND);

    respond(['success' => false, 'message' => 'Error interno del servidor'], 500);
}
