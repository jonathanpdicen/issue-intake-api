<?php

use App\Models\Issue;
use App\Services\Summary\RulesBasedSummaryGenerator;

function sentenceCount(string $text): int
{
    return count(preg_split('/(?<=[.!?])\s+/', trim($text)));
}

it('picks the next action from a keyword match', function (string $title, string $expected) {
    $issue = Issue::factory()->mediumPriority()->make(['title' => $title, 'description' => 'Please look into this.', 'category' => 'account']);

    $result = (new RulesBasedSummaryGenerator)->generate($issue);

    expect($result->nextAction)->toContain($expected);
})->with([
    'billing' => ['Need a REFUND for my order', 'payment provider'],
    'account access' => ['My account is locked', 'reset their credentials'],
    'performance' => ['Reports page has high latency', 'Profile the affected request'],
    'bug triage' => ['App crash on save', 'stack trace'],
    'incident' => ['Total outage this morning', 'Open an incident'],
]);

it('does not match keywords inside other words', function () {
    $issue = Issue::factory()->mediumPriority()->make(['title' => 'Download button', 'description' => 'Moved to a new spot.', 'category' => 'bug']);

    expect((new RulesBasedSummaryGenerator)->generate($issue)->nextAction)->toContain('Reproduce the problem and attach');
});

it('falls back to the category rule when no keyword matches', function () {
    $issue = Issue::factory()->mediumPriority()->make(['title' => 'Rename company', 'description' => 'Please update it.', 'category' => 'Account']);

    expect((new RulesBasedSummaryGenerator)->generate($issue)->nextAction)->toContain('Review the account settings');
});

it('falls back to a generic action when nothing matches', function () {
    $issue = Issue::factory()->mediumPriority()->make(['title' => 'Question', 'description' => 'Not sure who to ask.', 'category' => 'other']);

    expect((new RulesBasedSummaryGenerator)->generate($issue)->nextAction)->toContain('route the issue to the right team');
});

it('shapes the next action by priority', function () {
    $attributes = ['title' => 'Question', 'description' => 'Not sure.', 'category' => 'other'];

    $high = (new RulesBasedSummaryGenerator)->generate(Issue::factory()->highPriority()->make($attributes));
    $low = (new RulesBasedSummaryGenerator)->generate(Issue::factory()->lowPriority()->make($attributes));

    expect($high->nextAction)->toStartWith('Escalate to the on-call engineer immediately, then review')
        ->and($low->nextAction)->toStartWith('Add to the backlog, then review');
});

it('summarizes in at most two sentences using the first description sentence', function () {
    $issue = Issue::factory()->highPriority()->make([
        'category' => 'billing',
        'description' => "Card was charged twice. Please refund the duplicate.\nThanks!",
    ]);

    $summary = (new RulesBasedSummaryGenerator)->generate($issue)->summary;

    expect($summary)->toBe('High priority billing issue: Card was charged twice.')
        ->and(sentenceCount($summary))->toBeLessThanOrEqual(2);
});

it('trims a long first sentence on a word boundary', function () {
    $issue = Issue::factory()->make(['description' => str_repeat('word ', 100)]);

    $summary = (new RulesBasedSummaryGenerator)->generate($issue)->summary;

    expect(mb_strlen($summary))->toBeLessThan(220)
        ->and($summary)->toEndWith('word…')
        ->and(sentenceCount($summary))->toBeLessThanOrEqual(2);
});
