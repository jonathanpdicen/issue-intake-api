<?php

namespace Database\Seeders;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $issues = [
            [
                'title' => 'Charged twice for the same order',
                'description' => 'My card was charged twice for order #48213 on the same day. Please refund the duplicate charge.',
                'priority' => IssuePriority::HIGH,
                'category' => 'billing',
                'status' => IssueStatus::OPEN,
            ],
            [
                'title' => 'Cannot log in after password reset',
                'description' => 'I reset my password and received the confirmation email, but the new password is rejected on the login page.',
                'priority' => IssuePriority::HIGH,
                'category' => 'login',
                'status' => IssueStatus::IN_PROGRESS,
            ],
            [
                'title' => 'Dashboard takes 20 seconds to load',
                'description' => 'Since this week the dashboard is very slow for our account, especially the orders widget.',
                'priority' => IssuePriority::MEDIUM,
                'category' => 'performance',
                'status' => IssueStatus::IN_PROGRESS,
            ],
            [
                'title' => 'Export button downloads an empty CSV',
                'description' => 'Clicking Export on the reports page downloads a CSV that only contains the header row.',
                'priority' => IssuePriority::MEDIUM,
                'category' => 'bug',
                'status' => IssueStatus::OPEN,
            ],
            [
                'title' => 'Update the company name on my account',
                'description' => 'We rebranded last month and need the company name on our account and invoices changed.',
                'priority' => IssuePriority::LOW,
                'category' => 'account',
                'status' => IssueStatus::RESOLVED,
            ],
            [
                'title' => 'Typo on the pricing page',
                'description' => 'The Pro plan description says "unlimitted" users instead of "unlimited".',
                'priority' => IssuePriority::LOW,
                'category' => 'bug',
                'status' => IssueStatus::RESOLVED,
            ],
        ];

        foreach ($issues as $issue) {
            Issue::create($issue);
        }
    }
}
