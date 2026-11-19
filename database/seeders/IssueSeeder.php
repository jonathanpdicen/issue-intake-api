<?php

namespace Database\Seeders;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

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
                'comments' => [
                    ['author_name' => 'Priya Nair', 'body' => 'Confirmed in Stripe: two successful charges for order #48213, 41 seconds apart.'],
                    ['author_name' => 'Marcus Webb', 'body' => 'Refund for the duplicate charge issued. It should reach the customer in 5-10 business days.'],
                    ['author_name' => 'Priya Nair', 'body' => 'Looks like a double-submit on checkout. Opening a bug to disable the button after the first click.'],
                ],
            ],
            [
                'title' => 'Cannot log in after password reset',
                'description' => 'I reset my password and received the confirmation email, but the new password is rejected on the login page.',
                'priority' => IssuePriority::HIGH,
                'category' => 'login',
                'status' => IssueStatus::IN_PROGRESS,
                'comments' => [
                    ['author_name' => 'Sofia Alvarez', 'body' => 'Reproduced. The reset token is invalidated, but the old password hash is still cached for the session.'],
                    ['author_name' => 'Daniel Okafor', 'body' => 'Fix is in review. Meanwhile I can force a manual reset for this customer.'],
                ],
            ],
            [
                'title' => 'Dashboard takes 20 seconds to load',
                'description' => 'Since this week the dashboard is very slow for our account, especially the orders widget.',
                'priority' => IssuePriority::MEDIUM,
                'category' => 'performance',
                'status' => IssueStatus::IN_PROGRESS,
                'comments' => [
                    ['author_name' => 'Daniel Okafor', 'body' => 'The orders widget runs an unindexed query on accounts with more than 50k orders.'],
                    ['author_name' => 'Sofia Alvarez', 'body' => 'Added the missing index in staging, load time is down to about 2 seconds.'],
                    ['author_name' => 'Marcus Webb', 'body' => 'Please confirm with the customer once it ships to production.'],
                    ['author_name' => 'Daniel Okafor', 'body' => 'Deployed. Waiting on customer confirmation.'],
                ],
            ],
            [
                'title' => 'Export button downloads an empty CSV',
                'description' => 'Clicking Export on the reports page downloads a CSV that only contains the header row.',
                'priority' => IssuePriority::MEDIUM,
                'category' => 'bug',
                'status' => IssueStatus::OPEN,
                'comments' => [
                    ['author_name' => 'Marcus Webb', 'body' => 'Only happens when a date filter is active. The filter is applied after the export query runs.'],
                ],
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

        foreach ($issues as $attributes) {
            $comments = Arr::pull($attributes, 'comments', []);

            Issue::create($attributes)->comments()->createMany($comments);
        }
    }
}
