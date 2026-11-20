<?php

namespace App\Services\Summary;

use App\Contracts\SummaryGenerator;
use App\Enums\IssuePriority;
use App\Models\Issue;
use Illuminate\Support\Str;

class RulesBasedSummaryGenerator implements SummaryGenerator
{
    public function generate(Issue $issue): IssueSummary
    {
        return new IssueSummary(
            summary: $this->summarize($issue),
            nextAction: $this->shapeByPriority($this->baseAction($issue), $issue->priority),
        );
    }

    private function summarize(Issue $issue): string
    {
        $excerpt = $this->firstSentence($issue->description);

        $ending = str_ends_with($excerpt, '…') ? '' : '.';

        return sprintf('%s priority %s issue: %s%s', ucfirst($issue->priority->value), $issue->category, $excerpt, $ending);
    }

    private function firstSentence(string $description): string
    {
        $squished = Str::squish($description);
        $sentence = (preg_split('/(?<=[.!?])\s+/', $squished, 2) ?: [$squished])[0];

        return Str::limit(rtrim($sentence, '.!?'), config('summary.rules.summary_excerpt_length'), '…', preserveWords: true);
    }

    private function baseAction(Issue $issue): string
    {
        $text = "{$issue->title} {$issue->description}";

        foreach (config('summary.rules.keyword_actions') as $rule) {
            $pattern = '/\b(?:'.implode('|', $rule['keywords']).')/i';

            if (preg_match($pattern, $text) === 1) {
                return $rule['action'];
            }
        }

        return config('summary.rules.category_actions.'.Str::lower($issue->category))
            ?? config('summary.rules.default_action');
    }

    private function shapeByPriority(string $action, IssuePriority $priority): string
    {
        $template = config("summary.rules.priority_actions.{$priority->value}");

        return sprintf($template, $priority === IssuePriority::MEDIUM ? $action : lcfirst($action));
    }
}
