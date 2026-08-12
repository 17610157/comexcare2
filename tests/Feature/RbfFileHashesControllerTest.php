<?php

use App\Models\RbfFileHash;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin.ver', 'rbf-file-hashes.ver', 'rbf-file-hashes.crear', 'rbf-file-hashes.eliminar'] as $permiso) {
        Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['admin.ver', 'rbf-file-hashes.ver', 'rbf-file-hashes.crear', 'rbf-file-hashes.eliminar']);
});

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('admin.rbf-file-hashes.index'));

    $response->assertOk();
    $response->assertSee('Subir archivos');
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.rbf-file-hashes.index'));

    $response->assertForbidden();
});

it('stores uploaded files computing the short md5', function () {
    $archivo = UploadedFile::fake()->createWithContent('PCOMB.DBF', 'contenido de prueba');
    $hashEsperado = strtoupper(substr(md5('contenido de prueba'), -5));

    $response = $this->actingAs($this->user)->post(route('admin.rbf-file-hashes.store'), [
        'archivos' => [$archivo],
        'plaza' => ['bajac'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $registro = RbfFileHash::first();
    expect($registro)->not->toBeNull();
    expect($registro->servicio)->toBe('manual');
    expect($registro->plaza)->toBe('bajac');
    expect($registro->zona)->toBeNull();
    expect($registro->name)->toBe('PCOMB.DBF');
    expect($registro->hash)->toBe($hashEsperado);
    expect($registro->path)->toBe('/manual/bajac/PCOMB.DBF');
    expect($registro->last_sync)->not->toBeNull();
    expect($registro->manual)->toBe(1);
});

it('registers the files for each selected plaza', function () {
    $archivo = UploadedFile::fake()->createWithContent('PCOMB.DBF', 'contenido de prueba');
    $hashEsperado = strtoupper(substr(md5('contenido de prueba'), -5));

    $response = $this->actingAs($this->user)->post(route('admin.rbf-file-hashes.store'), [
        'archivos' => [$archivo],
        'plaza' => ['bajac', 'xalap', 'guada'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(RbfFileHash::count())->toBe(3);

    $bajac = RbfFileHash::where('plaza', 'bajac')->first();
    expect($bajac->path)->toBe('/manual/bajac/PCOMB.DBF');
    expect($bajac->hash)->toBe($hashEsperado);
    expect($bajac->manual)->toBe(1);

    $xalap = RbfFileHash::where('plaza', 'xalap')->first();
    expect($xalap->path)->toBe('/manual/xalap/PCOMB.DBF');
    expect($xalap->manual)->toBe(1);

    $guada = RbfFileHash::where('plaza', 'guada')->first();
    expect($guada->path)->toBe('/manual/guada/PCOMB.DBF');
    expect($guada->manual)->toBe(1);
});

it('stores multiple files in a single request', function () {
    $archivo1 = UploadedFile::fake()->createWithContent('A.DBF', 'contenido a');
    $archivo2 = UploadedFile::fake()->createWithContent('B.EXE', 'contenido b');

    $response = $this->actingAs($this->user)->post(route('admin.rbf-file-hashes.store'), [
        'archivos' => [$archivo1, $archivo2],
        'plaza' => ['bajac'],
    ]);

    $response->assertRedirect();

    expect(RbfFileHash::count())->toBe(2);
    expect(RbfFileHash::where('name', 'A.DBF')->exists())->toBeTrue();
    expect(RbfFileHash::where('name', 'B.EXE')->exists())->toBeTrue();
    expect(RbfFileHash::where('manual', true)->count())->toBe(2);
});

it('updates existing records by auto path', function () {
    RbfFileHash::create([
        'servicio' => 'manual',
        'plaza' => 'bajac',
        'path' => '/manual/bajac/PCOMB.DBF',
        'name' => 'PCOMB.DBF',
        'hash' => 'ANTIGUO',
    ]);

    $archivo = UploadedFile::fake()->createWithContent('PCOMB.DBF', 'nuevo contenido');
    $hashEsperado = strtoupper(substr(md5('nuevo contenido'), -5));

    $response = $this->actingAs($this->user)->post(route('admin.rbf-file-hashes.store'), [
        'archivos' => [$archivo],
        'plaza' => ['bajac'],
    ]);

    $response->assertRedirect();

    expect(RbfFileHash::count())->toBe(1);
    $registro = RbfFileHash::where('path', '/manual/bajac/PCOMB.DBF')->first();
    expect($registro->hash)->toBe($hashEsperado);
    expect($registro->manual)->toBe(1);
});

it('validates that at least one file is required', function () {
    $response = $this->actingAs($this->user)->post(route('admin.rbf-file-hashes.store'), [
        'archivos' => [],
    ]);

    $response->assertSessionHasErrors('archivos');
});

it('returns data for the datatable', function () {
    RbfFileHash::create([
        'servicio' => 'manual',
        'plaza' => 'bajac',
        'path' => '/manual/bajac/PCOMB.DBF',
        'name' => 'PCOMB.DBF',
        'hash' => '12345',
    ]);

    $response = $this->actingAs($this->user)->getJson(route('admin.rbf-file-hashes.data'));

    $response->assertOk();
    $json = $response->json();
    expect($json['recordsTotal'])->toBe(1);
    expect($json['data'])->toHaveCount(1);
    expect($json['data'][0]['hash'])->toBe('12345');
});

it('filters datatable data by search term', function () {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('ILIKE requires PostgreSQL');
    }

    RbfFileHash::create(['servicio' => 'manual', 'path' => '/manual/a/A.DBF', 'name' => 'A.DBF', 'hash' => '11111']);
    RbfFileHash::create(['servicio' => 'webcomex', 'path' => '/webcomex/B.DBF', 'name' => 'B.DBF', 'hash' => '22222']);

    $response = $this->actingAs($this->user)->getJson(route('admin.rbf-file-hashes.data', ['search' => ['value' => 'webcomex']]));

    $response->assertOk();
    $json = $response->json();
    expect($json['recordsTotal'])->toBe(1);
    expect($json['data'][0]['servicio'])->toBe('webcomex');
});

it('destroys a record', function () {
    $registro = RbfFileHash::create([
        'servicio' => 'manual',
        'path' => '/manual/PCOMB.DBF',
        'name' => 'PCOMB.DBF',
        'hash' => '12345',
    ]);

    $response = $this->actingAs($this->user)->deleteJson(route('admin.rbf-file-hashes.destroy', $registro));

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect(RbfFileHash::count())->toBe(0);
});
