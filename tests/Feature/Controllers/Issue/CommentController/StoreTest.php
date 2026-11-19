<?php

use App\Models\Issue;

it('adds a comment to an issue', function () {
    $issue = Issue::factory()->create();

    $response = $this->postJson("/api/v1/issues/{$issue->id}/comments", [
        'author_name' => 'Priya Nair',
        'body' => 'Refund issued.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.issue_id', $issue->id)
        ->assertJsonPath('data.author_name', 'Priya Nair')
        ->assertJsonPath('data.body', 'Refund issued.');

    $this->assertDatabaseHas('comments', [
        'issue_id' => $issue->id,
        'author_name' => 'Priya Nair',
        'body' => 'Refund issued.',
    ]);
});

it('rejects a comment with missing required fields', function () {
    $issue = Issue::factory()->create();

    $response = $this->postJson("/api/v1/issues/{$issue->id}/comments", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrorFor('author_name', 'error.details')
        ->assertJsonValidationErrorFor('body', 'error.details');
    assertErrorShape($response, 'validation_failed');
});

it('rejects a whitespace-only body', function () {
    $issue = Issue::factory()->create();

    $response = $this->postJson("/api/v1/issues/{$issue->id}/comments", [
        'author_name' => 'Priya Nair',
        'body' => "   \n  ",
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrorFor('body', 'error.details');
    assertErrorShape($response, 'validation_failed');
    $this->assertDatabaseCount('comments', 0);
});

it('returns a not found error when commenting on an unknown issue', function () {
    $response = $this->postJson('/api/v1/issues/999/comments', [
        'author_name' => 'Priya Nair',
        'body' => 'Refund issued.',
    ]);

    assertErrorShape($response->assertNotFound(), 'not_found');
});

it('ignores a client-supplied issue_id', function () {
    $issue = Issue::factory()->create();
    $otherIssue = Issue::factory()->create();

    $this->postJson("/api/v1/issues/{$issue->id}/comments", [
        'issue_id' => $otherIssue->id,
        'author_name' => 'Priya Nair',
        'body' => 'Refund issued.',
    ])->assertCreated()->assertJsonPath('data.issue_id', $issue->id);

    expect($issue->comments()->count())->toBe(1)
        ->and($otherIssue->comments()->count())->toBe(0);
});
