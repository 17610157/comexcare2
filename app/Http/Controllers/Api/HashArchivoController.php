<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConciliacionHashArchivo;
use App\Models\HashArchivoLote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class HashArchivoController extends Controller
{
    protected int $maxLoteBytes;

    public function __construct()
    {
        $this->maxLoteBytes = (int) config('services.conciliacion.hash_archivos_max_lote_bytes', 10 * 1024 * 1024);
    }

    public function registrarLote(Request $request)
    {
        $raw = $request->getContent();

        $sizeError = $this->checkSize($raw);
        if ($sizeError !== null) {
            return $sizeError;
        }

        $data = $this->decodeJson($raw);
        if ($data === null) {
            return $this->storeInvalid($request, $raw, [['message' => 'El cuerpo debe ser un JSON válido']]);
        }

        $tiendas = $data['Tiendas'] ?? null;
        if (! is_array($tiendas)) {
            return $this->storeInvalid($request, $raw, [['Tiendas' => ['El campo Tiendas debe ser un arreglo.']]]);
        }

        return $this->process($request, $raw, $tiendas);
    }

    public function registrar(Request $request)
    {
        $raw = $request->getContent();

        $sizeError = $this->checkSize($raw);
        if ($sizeError !== null) {
            return $sizeError;
        }

        $data = $this->decodeJson($raw);
        if ($data === null) {
            return $this->storeInvalid($request, $raw, [['message' => 'El cuerpo debe ser un JSON válido']]);
        }

        if (array_key_exists('Tiendas', $data)) {
            if (! is_array($data['Tiendas'])) {
                return $this->storeInvalid($request, $raw, [['Tiendas' => ['El campo Tiendas debe ser un arreglo.']]]);
            }

            $tiendas = $data['Tiendas'];
        } else {
            $tiendas = [$data];
        }

        return $this->process($request, $raw, $tiendas);
    }

    protected function checkSize(string $raw): ?Response
    {
        if (strlen($raw) > $this->maxLoteBytes) {
            return response()->json([
                'success' => false,
                'message' => 'El cuerpo de la petición excede el tamaño máximo permitido.',
            ], 413);
        }

        return null;
    }

    protected function decodeJson(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    protected function process(Request $request, string $raw, array $tiendas)
    {
        $validator = Validator::make($tiendas, $this->rules());

        if ($validator->fails()) {
            return $this->storeInvalid($request, $raw, $validator->errors()->toArray());
        }

        $numArchivos = 0;
        $pesoTotal = 0;
        foreach ($tiendas as $tienda) {
            foreach ($tienda['Archivos'] ?? [] as $archivo) {
                $numArchivos++;
                $pesoTotal += (int) ($archivo['Peso'] ?? 0);
            }
        }

        $first = $tiendas[0] ?? null;
        $lote = HashArchivoLote::create([
            'cliente' => $request->attributes->get('hash_client'),
            'sucursal' => $first['Sucursal'] ?? null,
            'nombre_carpeta' => $first['NombreCarpeta'] ?? null,
            'ruta_base' => $first['RutaBase'] ?? null,
            'fecha_envio' => isset($first['FechaEnvio']) ? Carbon::parse($first['FechaEnvio']) : null,
            'disparador' => $first['Disparador'] ?? null,
            'num_archivos' => $numArchivos,
            'peso_total' => $pesoTotal,
            'estado' => 'exitoso',
            'payload' => $raw,
            'errores' => null,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        foreach ($tiendas as $tienda) {
            $this->syncTienda($tienda);
        }

        Log::info('Hash archivos: lote registrado', [
            'lote_id' => $lote->id,
            'cliente' => $request->attributes->get('hash_client'),
            'tiendas' => count($tiendas),
            'archivos' => $numArchivos,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lote registrado correctamente',
            'tiendas' => count($tiendas),
            'archivos' => $numArchivos,
        ], 200);
    }

    protected function syncTienda(array $tienda): void
    {
        $fechaEnvio = isset($tienda['FechaEnvio']) ? Carbon::parse($tienda['FechaEnvio']) : now();

        foreach ($tienda['Archivos'] ?? [] as $archivo) {
            if (isset($archivo['Existe']) && $archivo['Existe'] === false) {
                continue;
            }

            $md5 = strtolower((string) ($archivo['Md5'] ?? ''));

            ConciliacionHashArchivo::updateOrCreate(
                [
                    'sucursal' => $tienda['Sucursal'] ?? '',
                    'archivo' => $archivo['Nombre'] ?? '',
                    'disparador' => $tienda['Disparador'] ?? '',
                ],
                [
                    'md5' => substr($md5, -5),
                    'md5_completo' => $md5,
                    'fecha_modificacion' => $this->parseFechaModificacion($archivo['FechaModificacion'] ?? null),
                    'fecha_consulta_api' => $fechaEnvio,
                ]
            );
        }
    }

    protected function parseFechaModificacion(?string $fecha): ?Carbon
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        $carbon = Carbon::parse($fecha);

        if ($carbon->year <= 1) {
            return null;
        }

        return $carbon;
    }

    protected function storeInvalid(Request $request, string $raw, array $errors)
    {
        HashArchivoLote::create([
            'cliente' => $request->attributes->get('hash_client'),
            'estado' => 'error_validacion',
            'payload' => $raw,
            'errores' => $errors,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        Log::warning('Hash archivos: lote rechazado por validación', [
            'cliente' => $request->attributes->get('hash_client'),
            'errors' => array_keys($errors),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Validación fallida',
            'errors' => $errors,
        ], 422);
    }

    protected function rules(): array
    {
        return [
            '*.NombreCarpeta' => 'required|string|max:255',
            '*.RutaBase' => 'required|string|max:500',
            '*.FechaEnvio' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/'],
            '*.Disparador' => 'required|string|max:50',
            '*.Sucursal' => 'required|string|max:100',
            '*.Archivos' => 'present|array',
            '*.Archivos.*.Nombre' => 'required|string|max:255',
            '*.Archivos.*.Existe' => 'required|boolean',
            '*.Archivos.*.Md5' => ['required_if:*.Archivos.*.Existe,true', 'string', 'regex:/^[0-9a-fA-F]{32}$/'],
            '*.Archivos.*.Peso' => 'required|integer|min:0',
            '*.Archivos.*.FechaModificacion' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d{1,9})?(?:Z|[+-]\d{2}:\d{2})?$/'],
        ];
    }
}
