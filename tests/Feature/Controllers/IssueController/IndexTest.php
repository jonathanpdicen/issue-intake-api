<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;

it('combines filters when listing issues', function () {
    $match = Issue::factory()
        ->highPriority()
        ->inProgress()
        ->create(['category' => 'billing']);
    Issue::factory()
        ->highPriority()
        ->open()
        ->create(['category' => 'billing']);
    Issue::factory()
        ->lowPriority()
        ->inProgress()
        ->create(['category' => 'billing']);
    Issue::factory()
        ->highPriority()
        ->inProgress()
        ->create(['category' => 'login']);

    $response = $this->getJson('/api/v1/issues?'.http_build_query([
        'status' => IssueStatus::IN_PROGRESS->value,
        'priority' => IssuePriority::HIGH->value,
        'category' => 'billing',
    ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('rejects an invalid filter value', function () {
    $response = $this->getJson('/api/v1/issues?status=archived');

    $response->assertUnprocessable()->assertJsonValidationErrorFor('status', 'error.details');
    assertErrorShape($response, 'validation_failed');
});

it('sorts and paginates issues', function () {
    $oldest = Issue::factory()->create(['created_at' => now()->subDays(2)]);
    Issue::factory()->create(['created_at' => now()->subDay()]);
    $newest = Issue::factory()->create(['created_at' => now()]);

    $this->getJson('/api/v1/issues')->assertJsonPath('data.0.id', $newest->id);

    $this->getJson('/api/v1/issues?sort=created_at&per_page=2')
        ->assertJsonPath('data.0.id', $oldest->id)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2);
});

it('rejects a per_page above the cap and a sort outside the whitelist', function () {
    $this->getJson('/api/v1/issues?per_page=101')->assertUnprocessable();
    $this->getJson('/api/v1/issues?sort=title')->assertUnprocessable();
});
