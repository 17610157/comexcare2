<?php

use App\Models\User;
use App\Services\LandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        'home.ver',
        'admin.usuarios.ver',
        'reportes.archivos-stock.ver',
    ] as $permiso) {
        Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
    }
});

function usuarioCon(string ...$permisos): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permisos);

    return $user;
}

it('deja ver el dashboard a quien tiene el permiso', function () {
    $this->actingAs(usuarioCon('home.ver'))
        ->get(route('home'))
        ->assertOk();
});

it('redirige al primer reporte permitido a quien no tiene el permiso', function () {
    $this->actingAs(usuarioCon('reportes.archivos-stock.ver'))
        ->get(route('home'))
        ->assertRedirect(route('reportes.archivos-stock'));
});

it('respeta el permiso heredado del rol', function () {
    $rol = Role::firstOrCreate(['name' => 'stock_lectura', 'guard_name' => 'web']);
    $rol->givePermissionTo('reportes.archivos-stock.ver');

    $user = User::factory()->create();
    $user->assignRole($rol);

    $this->actingAs($user)->get(route('home'))
        ->assertRedirect(route('reportes.archivos-stock'));
});

it('elige la primera opcion del menu segun el orden, no el orden de permisos', function () {
    // Usuarios aparece antes que Archivos de Stock en el menu, asi que gana
    // aunque el segundo permiso se haya entregado primero.
    $this->actingAs(usuarioCon('reportes.archivos-stock.ver', 'admin.usuarios.ver'))
        ->get(route('home'))
        ->assertRedirect(route('usuarios.index'));
});

it('lleva a sin-accesos cuando el rol no tiene nada visible', function () {
    $this->actingAs(usuarioCon())
        ->get(route('home'))
        ->assertRedirect(route('sin-accesos'));
});

it('la pagina sin-accesos existe y explica la situacion', function () {
    $this->actingAs(usuarioCon())
        ->get(route('sin-accesos'))
        ->assertOk()
        ->assertSee('Sin accesos asignados');
});

it('nunca manda a /home a quien no tiene el permiso, para no ciclar', function () {
    expect(LandingPage::firstAllowed(usuarioCon('reportes.archivos-stock.ver')))
        ->not->toBe(url('/home'))
        ->and(LandingPage::forUser(null))
        ->toBe(route('login'));
});

// Nota: se parte en dos tests a proposito. AdminLte es un singleton y
// construye el menu una sola vez por proceso, asi que encadenar dos
// usuarios distintos en un mismo test devolveria el menu del primero.

it('muestra Panel de Control en el menu cuando hay permiso', function () {
    $this->actingAs(usuarioCon('home.ver', 'reportes.archivos-stock.ver'))
        ->get(route('reportes.archivos-stock'))
        ->assertOk()
        ->assertSee('Panel de Control');
});

it('oculta Panel de Control del menu cuando no hay permiso', function () {
    $this->actingAs(usuarioCon('reportes.archivos-stock.ver'))
        ->get(route('reportes.archivos-stock'))
        ->assertOk()
        ->assertDontSee('Panel de Control');
});

it('la raiz lleva al dashboard solo si hay permiso', function () {
    $this->actingAs(usuarioCon('home.ver'))
        ->get('/')
        ->assertRedirect(route('home'));

    $this->actingAs(usuarioCon('reportes.archivos-stock.ver'))
        ->get('/')
        ->assertRedirect(route('reportes.archivos-stock'));
});

it('un invitado va a login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('los endpoints json del dashboard responden 403 sin permiso', function () {
    $this->actingAs(usuarioCon('reportes.archivos-stock.ver'))
        ->getJson(route('home.stats'))
        ->assertForbidden();

    $this->actingAs(usuarioCon('home.ver'))
        ->getJson(route('home.stats'))
        ->assertOk();
});
