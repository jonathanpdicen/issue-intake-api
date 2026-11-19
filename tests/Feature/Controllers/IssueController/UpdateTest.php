<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Models\Issue;

it('updates the status of an issue', function () {
    $issue = Issue::factory()
        ->open()
        ->create();

    $this->patchJson("/api/v1/issues/{$issue->id}", ['status' => IssueStatus::RESOLVED->value])
        ->assertOk()
        ->assertJsonPath('data.status', IssueStatus::RESOLVED->value);

    expect($issue->refresh()->status)->toBe(IssueStatus::RESOLVED);
});

it('rejects an invalid status', function () {
    $issue = Issue::factory()->create();

    $response = $this->patchJson("/api/v1/issues/{$issue->id}", ['status' => 'archived']);

    $response->assertUnprocessable()->assertJsonValidationErrorFor('status', 'error.details');
    assertErrorShape($response, 'validation_failed');
});

it('rejects emptying a field', function () {
    $issue = Issue::factory()->create();

    $this->patchJson("/api/v1/issues/{$issue->id}", ['title' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('title', 'error.details');
});

it('flags only high priority issues as needing attention and recomputes on change', function () {
    $issue = Issue::factory()
        ->lowPriority()
        ->create();
    expect($issue->needs_attention)->toBeFalse();

    $this->patchJson("/api/v1/issues/{$issue->id}", ['priority' => IssuePriority::HIGH->value])
        ->assertJsonPath('data.needs_attention', true);

    $this->patchJson("/api/v1/issues/{$issue->id}", ['priority' => IssuePriority::MEDIUM->value])
        ->assertJsonPath('data.needs_attention', false);
});

it('ignores needs_attention and summary_status sent by the client', function () {
    $issue = Issue::factory()
        ->highPriority()
        ->create();

    $this->patchJson("/api/v1/issues/{$issue->id}", [
        'needs_attention' => false,
        'summary_status' => SummaryStatus::FAILED->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.needs_attention', true)
        ->assertJsonPath('data.summary_status', SummaryStatus::PENDING->value);
});
