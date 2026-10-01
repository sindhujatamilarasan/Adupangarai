<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Any OpenAI-compatible chat API. Default: local Ollama (free, no key).
    // Free hosted alternatives: Gemini (https://generativelanguage.googleapis.com/v1beta/openai)
    // or Groq (https://api.groq.com/openai/v1) with their free API keys.
    'ai' => [
        'base_url' => env('AI_BASE_URL', 'http://ollama:11434/v1'),
        'key' => env('AI_API_KEY', 'ollama'),
        'model' => env('AI_MODEL', 'qwen2.5:3b'),
        // Tried once if the main model is busy or rate-limited (common on free tiers).
        'fallback_model' => env('AI_FALLBACK_MODEL'),
        'timeout' => (int) env('AI_TIMEOUT', 300),
        // Cost control: switch AI off entirely, and cap AI calls per person per day (0 = no cap).
        'enabled' => (bool) env('AI_ENABLED', true),
        'daily_limit' => (int) env('AI_DAILY_LIMIT', 10),
    ],

    // "Sign in with Google": OAuth Web client ID from Google Cloud console. Empty = button hidden.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],

];
