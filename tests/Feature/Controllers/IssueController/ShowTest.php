<?php

use App\Models\Issue;

it('shows an issue', function () {
    $issue = Issue::factory()->create();

    $this->getJson("/api/v1/issues/{$issue->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $issue->id);
});

it('returns a not found error for an unknown issue', function () {
    assertErrorShape($this->getJson('/api/v1/issues/999')->assertNotFound(), 'not_found');
});
