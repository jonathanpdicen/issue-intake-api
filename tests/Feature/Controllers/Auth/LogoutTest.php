<?php

use App\Models\User;

it('revokes only the token used for the request', function () {
    $user = User::factory()->create();
    $current = $user->createToken('current');
    $user->createToken('other');

    $this->withToken($current->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($user->tokens()->pluck('name')->all())->toBe(['other']);
});

it('requires authentication to log out', function () {
    $response = $this->postJson('/api/v1/auth/logout')->assertUnauthorized();

    assertErrorShape($response, 'unauthenticated');
});
