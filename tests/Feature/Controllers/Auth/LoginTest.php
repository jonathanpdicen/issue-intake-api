<?php

use App\Models\User;

it('logs in with valid credentials and returns a working token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'cli',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['token']]);
    expect($user->tokens()->where('name', 'cli')->exists())->toBeTrue();

    $this->withToken($response->json('data.token'))
        ->getJson('/api/v1/issues')
        ->assertOk();
});

it('rejects wrong credentials and unknown users alike', function () {
    $user = User::factory()->create();

    $wrongPassword = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'nope',
        'device_name' => 'cli',
    ]);
    $unknownUser = $this->postJson('/api/v1/auth/login', [
        'email' => 'ghost@example.com',
        'password' => 'password',
        'device_name' => 'cli',
    ]);

    $wrongPassword->assertUnprocessable()->assertJsonValidationErrorFor('email', 'error.details');
    assertErrorShape($wrongPassword, 'validation_failed');
    expect($unknownUser->json())->toBe($wrongPassword->json());
    expect($user->tokens()->count())->toBe(0);
});

it('requires email, password and device name', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name'], 'error.details');
});
