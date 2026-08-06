<?php

use App\Models\ConciliacionHashArchivo;
use App\Services\ConciliacionHashArchivoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Config::set('services.conciliacion.hash_archivos_endpoint', 'http://a561ebc317a38bf2a238baeb5c53e185.servicios.care/api_conciliaciones/index.php/hash-archivos/consulta');
    Config::set('services.conciliacion.hash_archivos_api_key', 'test-api-key');

    if (! Schema::hasTable('conciliacion_hash_archivos')) {
        Schema::create('conciliacion_hash_archivos', function (Blueprint $table) {
            $table->id();
            $table->string('sucursal', 100);
            $table->string('archivo', 255);
            $table->string('md5', 20);
            $table->timestamp('fecha_modificacion')->nullable();
            $table->string('disparador', 10);
            $table->timestamp('fecha_consulta_api')->nullable();
            $table->timestamps();
            $table->index('sucursal');
            $table->index('archivo');
            $table->index('disparador');
        });
    }

    ConciliacionHashArchivo::query()->truncate();
});

it('syncs file hashes from the conciliacion API', function () {
    Http::fake([
        'a561ebc317a38bf2a238baeb5c53e185.servicios.care/*' => Http::response([
            'success' => true,
            'codigo' => 200,
            'mensaje' => 'OK',
            'datos' => [
                'total_registros' => 3,
                'fecha_consulta' => '2026-07-25T09:00:00-06:00',
                'data' => [
                    ['sucursal' => 'calvo', 'archivo' => 'AJTFLU.DBF', 'md5' => '0F4E1', 'fecha_modificacion' => '2026-07-24 17:01:22', 'disparador' => 'pvsi'],
                    ['sucursal' => 'calvo', 'archivo' => 'CAJAS.DBF', 'md5' => '3747D', 'fecha_modificacion' => '2026-07-24 16:57:57', 'disparador' => 'rbf'],
                    ['sucursal' => 'xalap', 'archivo' => 'MASTER.DBF', 'md5' => '31083', 'fecha_modificacion' => '2026-07-24 17:01:03', 'disparador' => 'pvsi'],
                ],
            ],
        ]),
    ]);

    $service = new ConciliacionHashArchivoService;
    $result = $service->fetchAndSync();

    expect($result['success'])->toBeTrue();
    expect($result['count'])->toBe(3);

    $r1 = ConciliacionHashArchivo::where('archivo', 'AJTFLU.DBF')->first();
    expect($r1->sucursal)->toBe('calvo');
    expect($r1->md5)->toBe('0F4E1');
    expect($r1->disparador)->toBe('pvsi');
    expect($r1->fecha_consulta_api->format('Y-m-d H:i:s'))->toBe('2026-07-25 09:00:00');

    $r2 = ConciliacionHashArchivo::where('archivo', 'CAJAS.DBF')->first();
    expect($r2->sucursal)->toBe('calvo');
    expect($r2->md5)->toBe('3747D');
    expect($r2->disparador)->toBe('rbf');

    $r3 = ConciliacionHashArchivo::where('archivo', 'MASTER.DBF')->first();
    expect($r3->sucursal)->toBe('xalap');
});

it('handles HTTP errors gracefully', function () {
    Http::fake([
        'a561ebc317a38bf2a238baeb5c53e185.servicios.care/*' => Http::response(null, 500),
    ]);

    $service = new ConciliacionHashArchivoService;
    $result = $service->fetchAndSync();

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('500');
});

it('handles invalid response gracefully', function () {
    Http::fake([
        'a561ebc317a38bf2a238baeb5c53e185.servicios.care/*' => Http::response(['foo' => 'bar']),
    ]);

    $service = new ConciliacionHashArchivoService;
    $result = $service->fetchAndSync();

    expect($result['success'])->toBeFalse();
});

it('truncates old data before inserting new', function () {
    ConciliacionHashArchivo::query()->create([
        'sucursal' => 'old',
        'archivo' => 'OLD.DBF',
        'md5' => 'OLD00',
        'disparador' => 'old',
    ]);

    expect(ConciliacionHashArchivo::count())->toBe(1);

    Http::fake([
        'a561ebc317a38bf2a238baeb5c53e185.servicios.care/*' => Http::response([
            'success' => true,
            'codigo' => 200,
            'mensaje' => 'OK',
            'datos' => [
                'total_registros' => 1,
                'fecha_consulta' => '2026-07-25T09:00:00-06:00',
                'data' => [
                    ['sucursal' => 'calvo', 'archivo' => 'AJTFLU.DBF', 'md5' => '0F4E1', 'fecha_modificacion' => '2026-07-24 17:01:22', 'disparador' => 'pvsi'],
                ],
            ],
        ]),
    ]);

    $service = new ConciliacionHashArchivoService;
    $service->fetchAndSync();

    expect(ConciliacionHashArchivo::count())->toBe(1);
    expect(ConciliacionHashArchivo::first()->archivo)->toBe('AJTFLU.DBF');
});

it('parses multiple records with different dispatchers', function () {
    Http::fake([
        'a561ebc317a38bf2a238baeb5c53e185.servicios.care/*' => Http::response([
            'success' => true,
            'codigo' => 200,
            'mensaje' => 'OK',
            'datos' => [
                'total_registros' => 4,
                'fecha_consulta' => '2026-07-25T09:00:00-06:00',
                'data' => [
                    ['sucursal' => 'calvo', 'archivo' => 'AJTFLU.DBF', 'md5' => '0F4E1', 'fecha_modificacion' => '2026-07-24 17:01:22.121817', 'disparador' => 'pvsi'],
                    ['sucursal' => 'calvo', 'archivo' => 'AJTFLU.DBF', 'md5' => 'F9230', 'fecha_modificacion' => '2026-07-24 16:01:05', 'disparador' => 'rbf'],
                    ['sucursal' => 'calvo', 'archivo' => 'CAJAS.DBF', 'md5' => '3747D', 'fecha_modificacion' => '2026-07-24 16:57:57.302619', 'disparador' => 'pvsi'],
                    ['sucursal' => 'calvo', 'archivo' => 'CAJAS.DBF', 'md5' => '3E9EF', 'fecha_modificacion' => '2026-07-24 15:57:57', 'disparador' => 'rbf'],
                ],
            ],
        ]),
    ]);

    $service = new ConciliacionHashArchivoService;
    $result = $service->fetchAndSync();

    expect($result['success'])->toBeTrue();
    expect($result['count'])->toBe(4);
    expect(ConciliacionHashArchivo::where('disparador', 'pvsi')->count())->toBe(2);
    expect(ConciliacionHashArchivo::where('disparador', 'rbf')->count())->toBe(2);
});
