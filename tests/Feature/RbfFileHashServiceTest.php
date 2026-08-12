<?php

use App\Models\RbfFileHash;
use App\Services\RbfFileHashService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('rbf_file_hashes')) {
        Schema::create('rbf_file_hashes', function (Blueprint $table) {
            $table->id();
            $table->string('servicio', 50);
            $table->string('plaza', 50)->nullable();
            $table->string('zona', 50)->nullable();
            $table->text('path');
            $table->string('name', 255);
            $table->string('hash', 20);
            $table->timestamp('last_modified')->nullable();
            $table->timestamp('last_sync')->nullable();
            $table->boolean('manual')->default(false);
            $table->timestamps();
            $table->index('servicio');
            $table->index('plaza');
            $table->index('zona');
            $table->unique('path');
        });
    }

    RbfFileHash::query()->truncate();
});

it('parses paths correctly for different depths', function () {
    Http::fake([
        'rbf.camposreyeros.com/*' => Http::response([
            'last_sync' => '2026-07-03 14:08:00',
            'files' => [
                ['path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF', 'hash' => '8B7060', 'last_modified' => '2026-07-01 04:38:40'],
                ['path' => '/combo/xalap/PCOMB.DBF', 'name' => 'PCOMB.DBF', 'hash' => '50BEFA', 'last_modified' => '2026-06-23 17:15:35'],
                ['path' => '/webcomex/CLIECATP.DBF', 'name' => 'CLIECATP.DBF', 'hash' => 'F23923', 'last_modified' => '2026-02-04 21:21:38'],
                ['path' => '/dbf/manza/general/LEALTAD/CP_SUCS.DBF', 'name' => 'CP_SUCS.DBF', 'hash' => '9456D6', 'last_modified' => '2026-06-10 16:56:16'],
            ],
        ]),
    ]);

    $service = new RbfFileHashService;
    $result = $service->fetchAndSync();

    expect($result['success'])->toBeTrue();
    expect($result['count'])->toBe(4);

    $r1 = RbfFileHash::where('path', '/combo/bajac/norte/PCOMB.DBF')->first();
    expect($r1->servicio)->toBe('combo');
    expect($r1->plaza)->toBe('bajac');
    expect($r1->zona)->toBe('norte');
    expect($r1->name)->toBe('PCOMB.DBF');
    expect($r1->hash)->toBe('B7060');

    $r2 = RbfFileHash::where('path', '/combo/xalap/PCOMB.DBF')->first();
    expect($r2->servicio)->toBe('combo');
    expect($r2->plaza)->toBe('xalap');
    expect($r2->zona)->toBeNull();

    $r3 = RbfFileHash::where('path', '/webcomex/CLIECATP.DBF')->first();
    expect($r3->servicio)->toBe('webcomex');
    expect($r3->plaza)->toBeNull();
    expect($r3->zona)->toBeNull();

    $r4 = RbfFileHash::where('path', '/dbf/manza/general/LEALTAD/CP_SUCS.DBF')->first();
    expect($r4->servicio)->toBe('dbf');
    expect($r4->plaza)->toBe('manza');
    expect($r4->zona)->toBe('general');
    expect($r4->name)->toBe('CP_SUCS.DBF');
});

it('handles HTTP errors gracefully', function () {
    Http::fake([
        'rbf.camposreyeros.com/*' => Http::response(null, 500),
    ]);

    $service = new RbfFileHashService;
    $result = $service->fetchAndSync();

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('500');
});

it('handles invalid response gracefully', function () {
    Http::fake([
        'rbf.camposreyeros.com/*' => Http::response(['foo' => 'bar']),
    ]);

    $service = new RbfFileHashService;
    $result = $service->fetchAndSync();

    expect($result['success'])->toBeFalse();
});

it('truncates old data before inserting new', function () {
    RbfFileHash::query()->create([
        'servicio' => 'old', 'plaza' => 'old', 'zona' => 'old',
        'path' => '/old/file.DBF', 'name' => 'file.DBF', 'hash' => 'OLD',
    ]);

    expect(RbfFileHash::count())->toBe(1);

    Http::fake([
        'rbf.camposreyeros.com/*' => Http::response([
            'last_sync' => '2026-07-03 14:08:00',
            'files' => [
                ['path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF', 'hash' => '8B7060', 'last_modified' => '2026-07-01 04:38:40'],
            ],
        ]),
    ]);

    $service = new RbfFileHashService;
    $service->fetchAndSync();

    expect(RbfFileHash::count())->toBe(1);
    expect(RbfFileHash::first()->servicio)->toBe('combo');
    expect(RbfFileHash::first()->hash)->toBe('B7060');
});

it('sets last_sync from the response', function () {
    Http::fake([
        'rbf.camposreyeros.com/*' => Http::response([
            'last_sync' => '2026-07-03 14:08:00',
            'files' => [
                ['path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF', 'hash' => '8B7060', 'last_modified' => '2026-07-01 04:38:40'],
            ],
        ]),
    ]);

    $service = new RbfFileHashService;
    $service->fetchAndSync();

    $record = RbfFileHash::first();
    expect($record->last_sync->format('Y-m-d H:i:s'))->toBe('2026-07-03 14:08:00');
    expect($record->hash)->toBe('B7060');
});
