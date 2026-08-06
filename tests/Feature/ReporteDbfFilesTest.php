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
            $table->timestamps();
            $table->index('servicio');
            $table->index('plaza');
            $table->index('zona');
            $table->unique('path');
        });
    }

    RbfFileHash::query()->truncate();
});

it('builds lookup map correctly for matching plaza+hash+name', function () {
    RbfFileHash::query()->create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060', 'last_modified' => '2026-07-01 04:38:40', 'last_sync' => '2026-07-03 14:08:00',
    ]);

    RbfFileHash::query()->create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'sur',
        'path' => '/combo/bajac/sur/PDCOMB.DBF', 'name' => 'PDCOMB.DBF',
        'hash' => 'A32E52', 'last_modified' => '2024-05-09 23:59:58', 'last_sync' => '2026-07-03 14:08:00',
    ]);

    $records = RbfFileHash::all();
    $lookup = [];
    foreach ($records as $r) {
        $key = strtolower($r->plaza ?? '').'|'.($r->hash ?? '').'|'.($r->name ?? '');
        $lookup[$key] = $r;
    }

    // Match exists
    $key = 'bajac|8B7060|PCOMB.DBF';
    expect(isset($lookup[$key]))->toBeTrue();
    expect($lookup[$key]->path)->toBe('/combo/bajac/norte/PCOMB.DBF');
    expect($lookup[$key]->hash)->toBe('8B7060');

    $key2 = 'bajac|A32E52|PDCOMB.DBF';
    expect(isset($lookup[$key2]))->toBeTrue();
    expect($lookup[$key2]->path)->toBe('/combo/bajac/sur/PDCOMB.DBF');

    // No match
    $key3 = 'bajac|FFFFFF|NOEXISTE.DBF';
    expect(isset($lookup[$key3]))->toBeFalse();

    // Case insensitive plaza - controller uses strtolower on both sides
    $computerPlaza = 'BAJAC';
    $lookupKey = strtolower($computerPlaza).'|'.'8B7060'.'|'.'PCOMB.DBF';
    expect(isset($lookup[$lookupKey]))->toBeTrue();
});

it('finds rbf records using the same lookup logic as the controller', function () {
    RbfFileHash::query()->create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060', 'last_modified' => '2026-07-01 04:38:40', 'last_sync' => '2026-07-03 14:08:00',
    ]);

    // Simulate what the controller does with computer data
    $computerPlaza = 'BAJAC'; // from DB (uppercase)
    $dbfFile = [
        'name' => 'PCOMB.DBF',
        'hash_md5' => '8B7060',
    ];

    $records = RbfFileHash::all();
    $lookup = [];
    foreach ($records as $r) {
        $key = strtolower($r->plaza ?? '').'|'.($r->hash ?? '').'|'.($r->name ?? '');
        $lookup[$key] = $r;
    }

    $key = strtolower($computerPlaza).'|'.($dbfFile['hash_md5'] ?? '').'|'.($dbfFile['name'] ?? '');
    $rbfRecord = $lookup[$key] ?? null;

    expect($rbfRecord)->not->toBeNull();
    expect($rbfRecord->path)->toBe('/combo/bajac/norte/PCOMB.DBF');
    expect($rbfRecord->hash)->toBe('8B7060');
});

it('returns null when no match is found', function () {
    RbfFileHash::query()->create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060', 'last_modified' => '2026-07-01 04:38:40', 'last_sync' => '2026-07-03 14:08:00',
    ]);

    $computerPlaza = 'NOMATCH';
    $dbfFile = ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060'];

    $records = RbfFileHash::all();
    $lookup = [];
    foreach ($records as $r) {
        $key = strtolower($r->plaza ?? '').'|'.($r->hash ?? '').'|'.($r->name ?? '');
        $lookup[$key] = $r;
    }

    $key = strtolower($computerPlaza).'|'.($dbfFile['hash_md5'] ?? '').'|'.($dbfFile['name'] ?? '');
    $rbfRecord = $lookup[$key] ?? null;

    expect($rbfRecord)->toBeNull();
});

it('verifies sync then lookup flow end-to-end', function () {
    Http::fake([
        'rbf.camposreyeros.com/*' => Http::response([
            'last_sync' => '2026-07-03 14:08:00',
            'files' => [
                ['path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF', 'hash' => '8B7060', 'last_modified' => '2026-07-01 04:38:40'],
                ['path' => '/combo/bajac/sur/PDCOMB.DBF', 'name' => 'PDCOMB.DBF', 'hash' => 'A32E52', 'last_modified' => '2024-05-09 23:59:58'],
            ],
        ]),
    ]);

    $service = new RbfFileHashService;
    $service->fetchAndSync();

    expect(RbfFileHash::count())->toBe(2);

    // Now simulate controller lookup
    $records = RbfFileHash::all();
    $lookup = [];
    foreach ($records as $r) {
        $key = strtolower($r->plaza ?? '').'|'.($r->hash ?? '').'|'.($r->name ?? '');
        $lookup[$key] = $r;
    }

    // This simulates what the controller does for a computer with plaza=BAJAC
    $computerPlaza = 'BAJAC';
    $dbfFile = ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060'];

    $key = strtolower($computerPlaza).'|'.($dbfFile['hash_md5'] ?? '').'|'.($dbfFile['name'] ?? '');
    $rbfRecord = $lookup[$key] ?? null;

    expect($rbfRecord)->not->toBeNull();
    expect($rbfRecord->path)->toBe('/combo/bajac/norte/PCOMB.DBF');
    expect($rbfRecord->hash)->toBe('8B7060');
});
