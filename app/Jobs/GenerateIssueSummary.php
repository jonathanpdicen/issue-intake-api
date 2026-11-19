<?php

namespace App\Jobs;

use App\Contracts\SummaryGenerator;
use App\Enums\SummaryStatus;
use App\Models\Issue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateIssueSummary implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Longer than the LLM request timeout so the job outlives a slow call.
     */
    public int $timeout = 60;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Issue $issue) {}

    public function handle(SummaryGenerator $generator): void
    {
        $summary = $generator->generate($this->issue);

        $this->issue->forceFill([
            'summary' => $summary->summary,
            'suggested_next_action' => $summary->nextAction,
            'summary_status' => SummaryStatus::READY,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $this->issue->forceFill(['summary_status' => SummaryStatus::FAILED])->save();

        Log::error('Issue summary generation failed.', [
            'issue_id' => $this->issue->id,
            'reason' => $exception->getMessage(),
        ]);
    }
}
