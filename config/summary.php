<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Summary Driver
    |--------------------------------------------------------------------------
    |
    | "rules" always uses the rules-based generator, "llm" uses the LLM with the
    | rules-based generator as fallback, and "auto" picks "llm" only when an LLM
    | base URL and model are configured.
    |
    */

    'driver' => env('SUMMARY_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | LLM Generator
    |--------------------------------------------------------------------------
    |
    | Any OpenAI-compatible Chat Completions endpoint works (Ollama, Groq,
    | Gemini, OpenRouter, ...). The API key is optional. Disable json_mode for
    | free models that reject the "response_format" parameter.
    |
    */

    'llm' => [
        'base_url' => env('SUMMARY_LLM_BASE_URL'),
        'api_key' => env('SUMMARY_LLM_API_KEY'),
        'model' => env('SUMMARY_LLM_MODEL'),
        'timeout' => (int) env('SUMMARY_LLM_TIMEOUT', 20),
        'json_mode' => (bool) env('SUMMARY_LLM_JSON_MODE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rules-Based Generator
    |--------------------------------------------------------------------------
    |
    | Keyword rules are checked in order against the title and description; each
    | keyword is a regex fragment matched case-insensitively from a word start.
    | When none matches, the category rule is used, then the default action.
    | Priority then shapes the final action; "%s" receives the base action.
    |
    */

    'rules' => [
        'keyword_actions' => [
            [
                'keywords' => ['outage', 'is down', 'went down', 'unavailable'],
                'action' => 'Open an incident, confirm the scope of the outage and post a status update.',
            ],
            [
                'keywords' => ['refund', 'charge', 'invoice', 'payment', 'billing'],
                'action' => 'Verify the transaction in the payment provider and issue a refund or correction if confirmed.',
            ],
            [
                'keywords' => ['password', 'log in', 'login', '2fa', 'locked'],
                'action' => 'Verify the customer\'s identity and reset their credentials or unlock the account.',
            ],
            [
                'keywords' => ['slow', 'timeout', 'timed out', 'latency'],
                'action' => 'Profile the affected request and check for missing indexes or slow queries.',
            ],
            [
                'keywords' => ['error', 'crash', '500\b', 'exception'],
                'action' => 'Reproduce the problem and check the logs for the matching stack trace.',
            ],
        ],

        'category_actions' => [
            'billing' => 'Review the customer\'s billing history and confirm the expected charges.',
            'login' => 'Check the customer\'s account access and recent login attempts.',
            'account' => 'Review the account settings and apply the requested change.',
            'performance' => 'Measure the current response times and identify the slowest component.',
            'bug' => 'Reproduce the problem and attach the steps to the ticket.',
        ],

        'default_action' => 'Review the details with the customer and route the issue to the right team.',

        'priority_actions' => [
            'high' => 'Escalate to the on-call engineer immediately, then %s',
            'medium' => '%s',
            'low' => 'Add to the backlog, then %s',
        ],

        'summary_excerpt_length' => 160,
    ],

];
