<?php

namespace App\Services\Summary;

use App\Contracts\SummaryGenerator;
use App\Models\Issue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;

class LlmSummaryGenerator implements SummaryGenerator
{
    private const SYSTEM_PROMPT = 'You are a support triage assistant. Reply with JSON only.';

    private const MAX_SUMMARY_LENGTH = 300;

    private const MAX_SUMMARY_SENTENCES = 2;

    private const MAX_NEXT_ACTION_LENGTH = 200;

    public function generate(Issue $issue): IssueSummary
    {
        try {
            $response = $this->client()->post('chat/completions', $this->requestBody($issue))->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new SummaryGenerationException('LLM request failed: '.$e->getMessage(), previous: $e);
        }

        return $this->parse((string) $response->json('choices.0.message.content'));
    }

    private function client(): PendingRequest
    {
        $apiKey = config('summary.llm.api_key');

        return Http::baseUrl(rtrim(config('summary.llm.base_url'), '/').'/')
            ->timeout(config('summary.llm.timeout'))
            ->acceptJson()
            ->when($apiKey, fn (PendingRequest $request) => $request->withToken($apiKey));
    }

    /**
     * @return array<string, mixed>
     */
    private function requestBody(Issue $issue): array
    {
        return array_filter([
            'model' => config('summary.llm.model'),
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $this->prompt($issue)],
            ],
            'temperature' => 0.2,
            'response_format' => config('summary.llm.json_mode') ? ['type' => 'json_object'] : null,
        ]);
    }

    private function prompt(Issue $issue): string
    {
        return strtr(file_get_contents(resource_path('prompts/issue-summary.md')), [
            '{{title}}' => $issue->title,
            '{{category}}' => $issue->category,
            '{{priority}}' => $issue->priority->value,
            '{{status}}' => $issue->status->value,
            '{{description}}' => $issue->description,
        ]);
    }

    private function parse(string $content): IssueSummary
    {
        $json = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));

        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new SummaryGenerationException('LLM returned invalid JSON.', previous: $e);
        }

        $summary = is_array($data) ? $data['summary'] ?? null : null;
        $nextAction = is_array($data) ? $data['next_action'] ?? null : null;

        if (! $this->isValidText($summary, self::MAX_SUMMARY_LENGTH) || ! $this->isValidText($nextAction, self::MAX_NEXT_ACTION_LENGTH)) {
            throw new SummaryGenerationException('LLM returned a missing, empty or too long field.');
        }

        if (count(preg_split('/(?<=[.!?])\s+/', trim($summary))) > self::MAX_SUMMARY_SENTENCES) {
            throw new SummaryGenerationException('LLM summary has more than two sentences.');
        }

        return new IssueSummary(trim($summary), trim($nextAction));
    }

    private function isValidText(mixed $value, int $maxLength): bool
    {
        return is_string($value) && trim($value) !== '' && mb_strlen(trim($value)) <= $maxLength;
    }
}
