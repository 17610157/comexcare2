<?php

namespace App\Http\Controllers;

use App\Models\RbfFileHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RbfFileHashesController extends Controller
{
    public function index()
    {
        $plazas = RbfFileHash::query()
            ->select('plaza')
            ->distinct()
            ->whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->orderBy('plaza')
            ->pluck('plaza')
            ->values();

        return view('admin.rbf-file-hashes.index', compact('plazas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'archivos' => ['required', 'array', 'min:1'],
            'archivos.*' => ['required', 'file', 'max:20480'],
            'plaza' => ['nullable', 'array'],
            'plaza.*' => ['nullable', 'string', 'max:50'],
        ], [
            'archivos.required' => 'Debe seleccionar al menos un archivo.',
            'archivos.*.file' => 'Uno de los archivos subidos no es válido.',
            'archivos.*.max' => 'El archivo no puede ser mayor a 20MB.',
        ]);

        $servicio = 'manual';

        $plazasInput = $request->input('plaza', []);
        $plazas = is_array($plazasInput)
            ? array_values(array_filter(array_map('trim', $plazasInput), fn ($p) => $p !== ''))
            : [];

        if (count($plazas) === 0) {
            $plazas = [null];
        }

        $resultados = [];
        $insertados = 0;
        $actualizados = 0;
        $errores = 0;

        foreach ($request->file('archivos') as $archivo) {
            if (! $archivo->isValid()) {
                $errores++;
                Log::warning('RBF File Hashes: archivo inválido', [
                    'archivo' => $archivo->getClientOriginalName(),
                    'error' => $archivo->getErrorMessage(),
                ]);

                continue;
            }

            try {
                $nombre = strtoupper($archivo->getClientOriginalName());
                $md5 = md5_file($archivo->getRealPath());

                if ($md5 === false) {
                    throw new \RuntimeException('No fue posible calcular el MD5 del archivo.');
                }

                $hash = strtoupper(substr($md5, -5));

                foreach ($plazas as $plaza) {
                    $partes = array_values(array_filter([$servicio, $plaza, $nombre], fn ($p) => $p !== null && $p !== ''));
                    $ruta = '/'.implode('/', $partes);

                    $registro = RbfFileHash::updateOrCreate(
                        ['path' => $ruta],
                        [
                            'servicio' => $servicio,
                            'plaza' => $plaza,
                            'zona' => null,
                            'name' => $nombre,
                            'hash' => $hash,
                            'last_sync' => now(),
                            'manual' => 1,
                        ]
                    );

                    if ($registro->wasRecentlyCreated) {
                        $insertados++;
                    } else {
                        $actualizados++;
                    }

                    $resultados[] = [
                        'name' => $nombre,
                        'plaza' => $plaza,
                        'path' => $ruta,
                        'hash' => $hash,
                        'created' => $registro->wasRecentlyCreated,
                    ];
                }
            } catch (\Throwable $e) {
                $errores++;
                Log::warning('RBF File Hashes: error al procesar archivo', [
                    'archivo' => $archivo->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (count($resultados) === 0) {
            $mensaje = 'No se pudo procesar ningún archivo.';
            if ($errores > 0) {
                $mensaje .= " {$errores} archivos con error. Revise el tamaño (máx 20MB) y que los archivos sean válidos.";
            }

            return redirect()->back()->with('error', $mensaje);
        }

        $archivosProcesados = count($request->file('archivos'));
        $message = 'Se registraron '.count($resultados)." registros (de {$archivosProcesados} archivos): {$insertados} nuevos, {$actualizados} actualizados.";

        if ($errores > 0) {
            $message .= " {$errores} con error.";
        }

        return redirect()->back()->with('success', $message);
    }

    public function data(Request $request)
    {
        $query = RbfFileHash::query();

        $plaza = $request->input('plaza');
        if (! empty($plaza)) {
            $query->where('plaza', $plaza);
        }

        $searchInput = $request->input('search');
        $search = is_array($searchInput) ? ($searchInput['value'] ?? '') : (string) $searchInput;

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('servicio', 'ILIKE', '%'.$search.'%')
                    ->orWhere('plaza', 'ILIKE', '%'.$search.'%')
                    ->orWhere('zona', 'ILIKE', '%'.$search.'%')
                    ->orWhere('name', 'ILIKE', '%'.$search.'%')
                    ->orWhere('hash', 'ILIKE', '%'.$search.'%')
                    ->orWhere('path', 'ILIKE', '%'.$search.'%');
            });
        }

        $recordsTotal = $query->count();

        $columns = ['id', 'servicio', 'plaza', 'zona', 'name', 'hash', 'path', 'last_modified', 'last_sync', 'created_at'];
        $orderColumn = $columns[(int) ($request->input('order.0.column', 9))] ?? 'id';
        $orderDirection = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 50);
        $length = $length < 0 ? 100000 : $length;

        $records = $query->orderBy($orderColumn, $orderDirection)
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $records->map(function (RbfFileHash $registro) {
                return [
                    'id' => $registro->id,
                    'servicio' => $registro->servicio,
                    'plaza' => $registro->plaza,
                    'zona' => $registro->zona,
                    'name' => $registro->name,
                    'hash' => $registro->hash,
                    'path' => $registro->path,
                    'last_modified' => $registro->last_modified?->format('Y-m-d H:i:s'),
                    'last_sync' => $registro->last_sync?->format('Y-m-d H:i:s'),
                    'created_at' => $registro->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    public function destroy(RbfFileHash $rbfFileHash)
    {
        $rbfFileHash->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado correctamente.',
        ]);
    }
}
