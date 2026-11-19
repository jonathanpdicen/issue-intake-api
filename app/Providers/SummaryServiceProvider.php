<?php

namespace App\Providers;

use App\Contracts\SummaryGenerator;
use App\Services\Summary\FallbackSummaryGenerator;
use App\Services\Summary\LlmSummaryGenerator;
use App\Services\Summary\RulesBasedSummaryGenerator;
use Illuminate\Support\ServiceProvider;

class SummaryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SummaryGenerator::class, function ($app): SummaryGenerator {
            if (! $this->shouldUseLlm()) {
                return $app->make(RulesBasedSummaryGenerator::class);
            }

            return new FallbackSummaryGenerator(
                $app->make(LlmSummaryGenerator::class),
                $app->make(RulesBasedSummaryGenerator::class),
            );
        });
    }

    private function shouldUseLlm(): bool
    {
        return match (config('summary.driver')) {
            'rules' => false,
            'llm' => true,
            default => config('summary.llm.base_url') && config('summary.llm.model'),
        };
    }
}
