<?php

use App\Enums\IssuePriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->beforeEach(fn () => Http::preventStrayRequests())
    ->in('Feature');

pest()
    ->use(RefreshDatabase::class)
    ->in('Feature/Controllers');

pest()
    ->beforeEach(fn () => Sanctum::actingAs(User::factory()->create()))
    ->in('Feature/Controllers/IssueController', 'Feature/Controllers/Issue');

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

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return [
        'name' => 'Priya Nair',
        'email' => 'priya@example.com',
        'password' => 'Str0ng-Passw0rd!',
        'password_confirmation' => 'Str0ng-Passw0rd!',
        'device_name' => 'cli',
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
