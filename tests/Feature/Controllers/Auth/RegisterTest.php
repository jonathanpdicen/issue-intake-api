<?php

use App\Models\User;

it('registers a user and returns a working token', function () {
    $response = $this->postJson('/api/v1/auth/register', registrationPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'priya@example.com')
        ->assertJsonMissingPath('data.user.password')
        ->assertJsonStructure(['data' => ['token']]);

    $user = User::query()->where('email', 'priya@example.com')->firstOrFail();
    expect($user->password)->not->toBe('Str0ng-Passw0rd!');

    $this->withToken($response->json('data.token'))
        ->getJson('/api/v1/issues')
        ->assertOk();
});

it('rejects invalid registrations', function (array $overrides, string $field) {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/auth/register', registrationPayload($overrides));

    $response->assertUnprocessable()->assertJsonValidationErrorFor($field, 'error.details');
    assertErrorShape($response, 'validation_failed');
    expect(User::query()->count())->toBe(1);
})->with([
    'duplicate email' => [['email' => 'taken@example.com'], 'email'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
    'weak password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'missing device name' => [['device_name' => ''], 'device_name'],
]);
