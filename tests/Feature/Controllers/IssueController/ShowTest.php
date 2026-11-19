<?php

use App\Models\Comment;
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

it('shows an issue with its comments oldest first', function () {
    $issue = Issue::factory()->create();
    $newest = Comment::factory()->for($issue)->create(['created_at' => now()]);
    $oldest = Comment::factory()->for($issue)->create(['created_at' => now()->subDays(2)]);
    $middle = Comment::factory()->for($issue)->create(['created_at' => now()->subDay()]);

    $this->getJson("/api/v1/issues/{$issue->id}")
        ->assertOk()
        ->assertJsonCount(3, 'data.comments')
        ->assertJsonPath('data.comments.0.id', $oldest->id)
        ->assertJsonPath('data.comments.1.id', $middle->id)
        ->assertJsonPath('data.comments.2.id', $newest->id);
});

it('shows an issue without comments as an empty list', function () {
    $issue = Issue::factory()->create();

    $this->getJson("/api/v1/issues/{$issue->id}")
        ->assertOk()
        ->assertJsonPath('data.comments', []);
});

it('loads comments with the same number of queries regardless of comment count', function () {
    $withOne = Issue::factory()->has(Comment::factory())->create();
    $withTen = Issue::factory()->has(Comment::factory()->count(10))->create();

    $queriesForOne = countQueries(fn () => $this->getJson("/api/v1/issues/{$withOne->id}")->assertOk());
    $queriesForTen = countQueries(fn () => $this->getJson("/api/v1/issues/{$withTen->id}")->assertOk());

    expect($queriesForTen)->toBe($queriesForOne);
});
