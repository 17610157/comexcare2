<?php

use App\Models\User;
use App\Observers\AdminAuditObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cambiosRelevantes(\Illuminate\Database\Eloquent\Model $model): bool
{
    $reflection = new ReflectionClass(AdminAuditObserver::class);
    $method = $reflection->getMethod('tieneCambiosRelevantes');
    $method->setAccessible(true);

    return $method->invoke(new AdminAuditObserver, $model);
}

it('ignora la actualización que hace el login al rotar remember_token', function () {
    $user = User::factory()->create();
    $user->remember_token = 'token-rotado-por-login';
    $user->save();

    expect(cambiosRelevantes($user))->toBeFalse();
});

it('detecta cambios reales de nombre en un usuario', function () {
    $user = User::factory()->create();
    $user->name = 'Nombre Nuevo';
    $user->save();

    expect(cambiosRelevantes($user))->toBeTrue();
});

it('detecta cambios reales de activo en un usuario', function () {
    $user = User::factory()->create();
    $user->activo = false;
    $user->save();

    expect(cambiosRelevantes($user))->toBeTrue();
});