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
        'base_url' => env('AI_BASE_URL', 'http://localhost:11435/v1'),
        'key' => env('AI_API_KEY', 'ollama'),
        'model' => env('AI_MODEL', 'qwen2.5:3b'),
        'timeout' => (int) env('AI_TIMEOUT', 300),
    ],

];
