<?php

use App\Contracts\SummaryGenerator;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateIssueSummary;
use App\Models\Issue;
use App\Services\Summary\RulesBasedSummaryGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores the generated summary and marks the issue ready', function () {
    app()->bind(SummaryGenerator::class, RulesBasedSummaryGenerator::class);
    $issue = Issue::factory()->create(['title' => 'Charged twice', 'category' => 'billing']);

    app()->call([new GenerateIssueSummary($issue), 'handle']);

    expect($issue->refresh())
        ->summary->not->toBeEmpty()
        ->suggested_next_action->not->toBeEmpty()
        ->summary_status->toBe(SummaryStatus::READY);
});

it('marks the issue failed and keeps the api responding', function () {
    $issue = Issue::factory()->create();

    (new GenerateIssueSummary($issue))->failed(new RuntimeException('LLM down'));

    expect($issue->refresh()->summary_status)->toBe(SummaryStatus::FAILED);
    $this->getJson("/api/v1/issues/{$issue->id}")
        ->assertOk()
        ->assertJsonPath('data.summary_status', SummaryStatus::FAILED->value);
});
