<?php

use Illuminate\Support\Facades\Route;

it('returns a not found error for an unknown route', function () {
    assertErrorShape($this->getJson('/api/unknown')->assertNotFound(), 'not_found');
});

it('hides internals on server errors unless debugging', function () {
    Route::get('api/boom', fn () => throw new RuntimeException('secret detail'));

    config(['app.debug' => false]);
    $response = $this->getJson('/api/boom')->assertServerError();
    assertErrorShape($response, 'server_error');
    expect($response->getContent())
        ->not->toContain('secret detail')
        ->not->toContain('trace');

    config(['app.debug' => true]);
    $this->getJson('/api/boom')
        ->assertServerError()
        ->assertJsonPath('error.message', 'secret detail')
        ->assertJsonPath('error.debug.exception', RuntimeException::class);
});
