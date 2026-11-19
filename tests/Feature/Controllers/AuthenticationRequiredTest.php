<?php

use App\Models\Issue;

it('rejects unauthenticated requests to protected issue endpoints', function (string $method, string $uri) {
    $issue = Issue::factory()->create();

    $response = $this->json($method, str_replace('{issue}', (string) $issue->id, $uri));

    $response->assertUnauthorized();
    assertErrorShape($response, 'unauthenticated');
})->with([
    'index' => ['GET', '/api/v1/issues'],
    'store' => ['POST', '/api/v1/issues'],
    'show' => ['GET', '/api/v1/issues/{issue}'],
    'update' => ['PATCH', '/api/v1/issues/{issue}'],
    'comment' => ['POST', '/api/v1/issues/{issue}/comments'],
]);

it('rejects an invalid token', function () {
    $this->withToken('1|invalid')->getJson('/api/v1/issues')->assertUnauthorized();
});
