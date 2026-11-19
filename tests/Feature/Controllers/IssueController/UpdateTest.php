<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateIssueSummary;
use App\Models\Issue;
use Illuminate\Support\Facades\Queue;

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

it('resets the summary and queues generation when the description changes', function () {
    Queue::fake();
    $issue = Issue::factory()->create([
        'summary' => 'Old summary.',
        'suggested_next_action' => 'Old action.',
        'summary_status' => SummaryStatus::READY,
    ]);

    $this->patchJson("/api/v1/issues/{$issue->id}", ['description' => 'A brand new description.'])
        ->assertOk()
        ->assertJsonPath('data.summary_status', SummaryStatus::PENDING->value)
        ->assertJsonPath('data.summary', null)
        ->assertJsonPath('data.suggested_next_action', null);

    Queue::assertPushed(GenerateIssueSummary::class, fn (GenerateIssueSummary $job) => $job->issue->is($issue));
});

it('does not regenerate the summary when other fields change', function () {
    Queue::fake();
    $issue = Issue::factory()->create([
        'summary' => 'Existing summary.',
        'suggested_next_action' => 'Existing action.',
        'summary_status' => SummaryStatus::READY,
    ]);

    $this->patchJson("/api/v1/issues/{$issue->id}", [
        'status' => IssueStatus::RESOLVED->value,
        'description' => $issue->description,
    ])->assertOk();

    Queue::assertNothingPushed();
    expect($issue->refresh())
        ->summary->toBe('Existing summary.')
        ->suggested_next_action->toBe('Existing action.')
        ->summary_status->toBe(SummaryStatus::READY);
});
