<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    |--------------------------------------------------------------------------
    | Provedores de IA do Assistente
    |--------------------------------------------------------------------------
    |
    | Modelos padrão escolhidos pelo custo-benefício (nível econômico):
    | Claude Haiku 5.5 e GPT-6 Luna, ambos a US$ 0,10 / 0,50 por 1 milhão de
    | tokens de entrada / saída. Para trocar de modelo, basta alterar o .env.
    |
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-5-5'),
        'effort' => env('ANTHROPIC_EFFORT', 'low'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 8000),
        'timeout' => (float) env('ANTHROPIC_TIMEOUT', 60),
        // O SDK repete sozinho as falhas temporárias (limite de requisições e erros 5xx).
        'max_retries' => (int) env('ANTHROPIC_MAX_RETRIES', 2),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-6-luna'),
        'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'low'),
        'max_output_tokens' => (int) env('OPENAI_MAX_OUTPUT_TOKENS', 8000),
        'responses_url' => env('OPENAI_RESPONSES_URL', 'https://api.openai.com/v1/responses'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 60),
    ],

];
