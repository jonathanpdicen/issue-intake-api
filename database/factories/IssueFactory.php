<?php

namespace Database\Factories;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(IssuePriority::cases()),
            'category' => fake()->randomElement(['billing', 'login', 'performance', 'bug', 'account']),
            'status' => IssueStatus::OPEN,
        ];
    }

    public function lowPriority(): static
    {
        return $this->state(['priority' => IssuePriority::LOW]);
    }

    public function mediumPriority(): static
    {
        return $this->state(['priority' => IssuePriority::MEDIUM]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => IssuePriority::HIGH]);
    }

    public function open(): static
    {
        return $this->state(['status' => IssueStatus::OPEN]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => IssueStatus::IN_PROGRESS]);
    }

    public function resolved(): static
    {
        return $this->state(['status' => IssueStatus::RESOLVED]);
    }
}
