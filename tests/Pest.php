<?php

use App\Enums\IssuePriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->in('Feature');

pest()
    ->use(RefreshDatabase::class)
    ->in('Feature/Controllers');

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function issuePayload(array $overrides = []): array
{
    return [
        'title' => 'Cannot log in',
        'description' => 'The login page rejects my password.',
        'priority' => IssuePriority::MEDIUM->value,
        'category' => 'login',
        ...$overrides,
    ];
}

function assertErrorShape(TestResponse $response, string $code): void
{
    $response->assertJsonStructure(['error' => ['code', 'message', 'details']])
        ->assertJsonPath('error.code', $code);
}

function countQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());

    DB::disableQueryLog();

    return $count;
}
