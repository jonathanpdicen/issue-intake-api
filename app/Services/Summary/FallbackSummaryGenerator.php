<?php

namespace App\Services\Summary;

use App\Contracts\SummaryGenerator;
use App\Models\Issue;
use Illuminate\Support\Facades\Log;

class FallbackSummaryGenerator implements SummaryGenerator
{
    public function __construct(
        private SummaryGenerator $primary,
        private SummaryGenerator $fallback,
    ) {}

    public function generate(Issue $issue): IssueSummary
    {
        try {
            return $this->primary->generate($issue);
        } catch (SummaryGenerationException $e) {
            Log::warning('Summary generation failed, using fallback generator.', [
                'issue_id' => $issue->id,
                'reason' => $e->getMessage(),
            ]);

            return $this->fallback->generate($issue);
        }
    }
}
