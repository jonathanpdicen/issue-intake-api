<?php

use App\Contracts\SummaryGenerator;
use App\Services\Summary\FallbackSummaryGenerator;
use App\Services\Summary\RulesBasedSummaryGenerator;

it('resolves the generator from the configuration', function (array $config, string $expected) {
    config($config);

    expect(app(SummaryGenerator::class))->toBeInstanceOf($expected);
})->with([
    'auto without llm config' => [['summary.driver' => 'auto', 'summary.llm.base_url' => null, 'summary.llm.model' => null], RulesBasedSummaryGenerator::class],
    'auto with only a base url' => [['summary.driver' => 'auto', 'summary.llm.base_url' => 'https://llm.test/v1', 'summary.llm.model' => null], RulesBasedSummaryGenerator::class],
    'auto with base url and model' => [['summary.driver' => 'auto', 'summary.llm.base_url' => 'https://llm.test/v1', 'summary.llm.model' => 'm'], FallbackSummaryGenerator::class],
    'rules ignores llm config' => [['summary.driver' => 'rules', 'summary.llm.base_url' => 'https://llm.test/v1', 'summary.llm.model' => 'm'], RulesBasedSummaryGenerator::class],
    'llm' => [['summary.driver' => 'llm'], FallbackSummaryGenerator::class],
]);
