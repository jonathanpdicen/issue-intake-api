<?php

use App\Models\Issue;
use App\Services\Summary\IssueSummary;
use App\Services\Summary\LlmSummaryGenerator;
use App\Services\Summary\SummaryGenerationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    config([
        'summary.llm.base_url' => 'https://llm.test/v1',
        'summary.llm.model' => 'test-model',
        'summary.llm.api_key' => null,
        'summary.llm.json_mode' => true,
    ]);
});

function llmResponse(string $content): array
{
    return ['choices' => [['message' => ['content' => $content]]]];
}

function validLlmContent(): string
{
    return json_encode(['summary' => 'Customer was charged twice.', 'next_action' => 'Refund the duplicate charge.']);
}

it('returns the summary from a valid response', function () {
    Http::fake(['llm.test/*' => Http::response(llmResponse(validLlmContent()))]);

    $result = (new LlmSummaryGenerator)->generate(Issue::factory()->make());

    expect($result)->toEqual(new IssueSummary('Customer was charged twice.', 'Refund the duplicate charge.'));
});

it('accepts JSON wrapped in code fences', function () {
    Http::fake(['llm.test/*' => Http::response(llmResponse("```json\n".validLlmContent()."\n```"))]);

    expect((new LlmSummaryGenerator)->generate(Issue::factory()->make())->nextAction)->toBe('Refund the duplicate charge.');
});

it('sends the model, the issue text and json mode to the chat completions endpoint', function () {
    Http::fake(['llm.test/*' => Http::response(llmResponse(validLlmContent()))]);

    (new LlmSummaryGenerator)->generate(Issue::factory()->make(['title' => 'Unique title here']));

    Http::assertSent(fn (Request $request) => $request->url() === 'https://llm.test/v1/chat/completions'
        && $request['model'] === 'test-model'
        && $request['temperature'] === 0.2
        && $request['response_format'] === ['type' => 'json_object']
        && str_contains($request['messages'][1]['content'], 'Title: Unique title here'));
});

it('omits response_format when json mode is disabled', function () {
    config(['summary.llm.json_mode' => false]);
    Http::fake(['llm.test/*' => Http::response(llmResponse(validLlmContent()))]);

    (new LlmSummaryGenerator)->generate(Issue::factory()->make());

    Http::assertSent(fn (Request $request) => ! isset($request['response_format']));
});

it('sends the authorization header only when an api key is configured', function (?string $apiKey, bool $expected) {
    config(['summary.llm.api_key' => $apiKey]);
    Http::fake(['llm.test/*' => Http::response(llmResponse(validLlmContent()))]);

    (new LlmSummaryGenerator)->generate(Issue::factory()->make());

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization') === $expected);
})->with([
    'key set' => ['secret', true],
    'no key' => [null, false],
]);

it('throws when the response cannot be used', function (string $content) {
    Http::fake(['llm.test/*' => Http::response(llmResponse($content))]);

    (new LlmSummaryGenerator)->generate(Issue::factory()->make());
})->throws(SummaryGenerationException::class)->with([
    'invalid json' => ['not json at all'],
    'missing key' => ['{"summary": "Only a summary."}'],
    'empty value' => ['{"summary": "Fine.", "next_action": "  "}'],
    'non-string value' => ['{"summary": ["a"], "next_action": "Do it."}'],
    'summary too long' => ['{"summary": "'.str_repeat('a', 301).'", "next_action": "Do it."}'],
    'too many sentences' => ['{"summary": "One. Two. Three.", "next_action": "Do it."}'],
    'next action too long' => ['{"summary": "Fine.", "next_action": "'.str_repeat('a', 201).'"}'],
]);

it('wraps http failures in a generation exception', function (callable $fake) {
    Http::fake($fake());

    try {
        (new LlmSummaryGenerator)->generate(Issue::factory()->make());
    } catch (SummaryGenerationException $e) {
        expect($e->getPrevious())->not->toBeNull();

        return;
    }

    $this->fail('Expected a SummaryGenerationException.');
})->with([
    'rate limited' => [fn () => ['llm.test/*' => Http::response('slow down', 429)]],
    'server error' => [fn () => ['llm.test/*' => Http::response('boom', 500)]],
    'connection failure' => [fn () => ['llm.test/*' => fn () => throw new ConnectionException('unreachable')]],
]);
