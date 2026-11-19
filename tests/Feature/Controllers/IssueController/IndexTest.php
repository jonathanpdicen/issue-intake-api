<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Comment;
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

it('lists issues with their comment counts without loading comments', function () {
    $withComments = Issue::factory()->has(Comment::factory()->count(3))->create(['created_at' => now()]);
    $withoutComments = Issue::factory()->create(['created_at' => now()->subDay()]);

    $this->getJson('/api/v1/issues')
        ->assertOk()
        ->assertJsonPath('data.0.id', $withComments->id)
        ->assertJsonPath('data.0.comments_count', 3)
        ->assertJsonPath('data.1.id', $withoutComments->id)
        ->assertJsonPath('data.1.comments_count', 0)
        ->assertJsonMissingPath('data.0.comments');
});

it('lists issues with the same number of queries regardless of issue count', function () {
    Issue::factory()->has(Comment::factory()->count(2))->create();
    $queriesForOne = countQueries(fn () => $this->getJson('/api/v1/issues')->assertOk());

    Issue::factory()->count(5)->has(Comment::factory()->count(2))->create();
    $queriesForSix = countQueries(fn () => $this->getJson('/api/v1/issues')->assertOk());

    expect($queriesForSix)->toBe($queriesForOne);
});
