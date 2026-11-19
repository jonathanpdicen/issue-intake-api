<?php

namespace App\Contracts;

use App\Models\Issue;
use App\Services\Summary\IssueSummary;
use App\Services\Summary\SummaryGenerationException;

interface SummaryGenerator
{
    /**
     * @throws SummaryGenerationException When no valid result can be produced.
     */
    public function generate(Issue $issue): IssueSummary;
}
