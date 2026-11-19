<?php

use App\Contracts\SummaryGenerator;
use App\Models\Issue;
use App\Services\Summary\FallbackSummaryGenerator;
use App\Services\Summary\IssueSummary;
use App\Services\Summary\RulesBasedSummaryGenerator;
use App\Services\Summary\SummaryGenerationException;
use Illuminate\Support\Facades\Log;

function failingGenerator(): SummaryGenerator
{
    return new class implements SummaryGenerator
    {
        public function generate(Issue $issue): IssueSummary
        {
            throw new SummaryGenerationException('boom');
        }
    };
}

it('returns the rules result and logs a warning when the primary fails', function () {
    Log::spy();
    $issue = Issue::factory()->make(['id' => 7]);

    $result = (new FallbackSummaryGenerator(failingGenerator(), new RulesBasedSummaryGenerator))->generate($issue);

    expect($result)->toEqual((new RulesBasedSummaryGenerator)->generate($issue));
    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $context === ['issue_id' => 7, 'reason' => 'boom']);
});

it('returns the primary result when it succeeds', function () {
    $primary = new class implements SummaryGenerator
    {
        public function generate(Issue $issue): IssueSummary
        {
            return new IssueSummary('From primary.', 'Do the thing.');
        }
    };

    $result = (new FallbackSummaryGenerator($primary, failingGenerator()))->generate(Issue::factory()->make());

    expect($result->summary)->toBe('From primary.');
});
