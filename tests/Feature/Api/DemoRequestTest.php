<?php

use App\Models\DemoRequest;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('api_demo')) {
        Schema::create('api_demo', function ($table) {
            $table->id();
            $table->string('param1');
            $table->string('param2');
            $table->timestamps();
        });
    }
});

it('guarda dos parámetros correctamente', function () {
    $response = $this->postJson('/api/demo', [
        'param1' => 'Hola Mundo',
        'param2' => 'Test 123',
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Datos guardados correctamente',
        ]);

    expect($response['data']['param1'])->toBe('Hola Mundo');
    expect($response['data']['param2'])->toBe('Test 123');

    $this->assertDatabaseHas('api_demo', [
        'param1' => 'Hola Mundo',
        'param2' => 'Test 123',
    ]);
});

it('valida que ambos parámetros son requeridos', function () {
    $response = $this->postJson('/api/demo', [
        'param1' => 'solo esto',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('param2');
});

it('valida que los parámetros sean strings', function () {
    $response = $this->postJson('/api/demo', [
        'param1' => 123,
        'param2' => true,
    ]);

    $response->assertStatus(422);
});

it('guarda múltiples registros', function () {
    $this->postJson('/api/demo', ['param1' => 'A', 'param2' => 'B']);
    $this->postJson('/api/demo', ['param1' => 'C', 'param2' => 'D']);
    $this->postJson('/api/demo', ['param1' => 'E', 'param2' => 'F']);

    expect(DemoRequest::count())->toBe(3);
});
