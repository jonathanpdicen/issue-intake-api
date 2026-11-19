<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateIssueSummary;
use Illuminate\Support\Facades\Queue;

it('creates an issue with default status and summary status', function () {
    $response = $this->postJson('/api/v1/issues', issuePayload());

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Cannot log in')
        ->assertJsonPath('data.priority', IssuePriority::MEDIUM->value)
        ->assertJsonPath('data.status', IssueStatus::OPEN->value)
        ->assertJsonPath('data.summary_status', SummaryStatus::PENDING->value);

    $this->assertDatabaseCount('issues', 1);
});

it('rejects an issue with missing required fields', function () {
    $response = $this->postJson('/api/v1/issues', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrorFor('title', 'error.details')
        ->assertJsonValidationErrorFor('description', 'error.details')
        ->assertJsonValidationErrorFor('priority', 'error.details')
        ->assertJsonValidationErrorFor('category', 'error.details');
    assertErrorShape($response, 'validation_failed');
});

it('rejects an invalid priority', function () {
    $response = $this->postJson('/api/v1/issues', issuePayload(['priority' => 'urgent']));

    $response->assertUnprocessable()->assertJsonValidationErrorFor('priority', 'error.details');
    assertErrorShape($response, 'validation_failed');
});

it('rejects a whitespace-only title', function () {
    $response = $this->postJson('/api/v1/issues', issuePayload(['title' => '   ']));

    $response->assertUnprocessable()->assertJsonValidationErrorFor('title', 'error.details');
    assertErrorShape($response, 'validation_failed');
});

it('rejects a malformed JSON body', function () {
    $response = $this->call('POST', '/api/v1/issues', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], content: '{"title": ');

    $response->assertBadRequest();
    assertErrorShape($response, 'bad_request');
});

it('ignores needs_attention and summary fields sent by the client', function () {
    $response = $this->postJson('/api/v1/issues', issuePayload([
        'priority' => IssuePriority::LOW->value,
        'needs_attention' => true,
        'summary_status' => SummaryStatus::READY->value,
        'summary' => 'Injected summary',
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.needs_attention', false)
        ->assertJsonPath('data.summary_status', SummaryStatus::PENDING->value)
        ->assertJsonPath('data.summary', null);
});

it('queues summary generation and responds with a pending summary', function () {
    Queue::fake();

    $id = $this->postJson('/api/v1/issues', issuePayload())
        ->assertCreated()
        ->assertJsonPath('data.summary_status', SummaryStatus::PENDING->value)
        ->assertJsonPath('data.summary', null)
        ->json('data.id');

    Queue::assertPushed(GenerateIssueSummary::class, fn (GenerateIssueSummary $job) => $job->issue->id === $id);
});
