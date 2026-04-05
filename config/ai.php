<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Provider
    |--------------------------------------------------------------------------
    |
    | The active AI provider. Options: mock, openai, anthropic, replicate
    | Set via AI_PROVIDER env variable or here.
    |
    */
    'provider' => env('AI_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Provider Settings
    |--------------------------------------------------------------------------
    */
    'providers' => [
        // ============================================================
        // MOCK PROVIDER - For local development
        // ============================================================
        'mock' => [
            'enabled' => env('AI_MOCK_ENABLED', true),
            'delay_ms' => env('AI_MOCK_DELAY', 100),
            'description' => 'Local mock provider for development',
        ],

        // ============================================================
        // EXTERNAL TEXT PROVIDERS
        // ============================================================
        'openai' => [
            'enabled' => env('OPENAI_ENABLED', false),
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_TEXT_MODEL', 'gpt-4'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 2048),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'description' => 'OpenAI GPT-4 for text generation',
        ],

        'anthropic' => [
            'enabled' => env('ANTHROPIC_ENABLED', false),
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-opus-20240229'),
            'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 4096),
            'temperature' => env('ANTHROPIC_TEMPERATURE', 0.7),
            'description' => 'Anthropic Claude for text generation',
        ],

        // ============================================================
        // EXTERNAL IMAGE PROVIDERS
        // ============================================================
        'replicate' => [
            'enabled' => env('REPLICATE_ENABLED', false),
            'api_token' => env('REPLICATE_API_TOKEN'),
            'model' => env('REPLICATE_IMAGE_MODEL', 'stability-ai/stable-diffusion-3'),
            'description' => 'Replicate for image generation',
        ],

        'stability' => [
            'enabled' => env('STABILITY_ENABLED', false),
            'api_key' => env('STABILITY_API_KEY'),
            'engine' => env('STABILITY_ENGINE', 'esd-768-v2-jpg'),
            'description' => 'Stability AI for image generation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Mapping
    |--------------------------------------------------------------------------
    |
    | Map capabilities to providers. Each capability can use a different provider.
    |
    */
    'features' => [
        'text' => [
            'provider' => env('AI_TEXT_PROVIDER', 'mock'),
            'fallback' => 'mock',
        ],
        'image' => [
            'provider' => env('AI_IMAGE_PROVIDER', 'mock'),
            'fallback' => 'mock',
        ],
        'storyboard' => [
            'provider' => env('AI_STORYBOARD_PROVIDER', 'mock'),
            'fallback' => 'mock',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Generation Options
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'text' => [
            'max_tokens' => 2048,
            'temperature' => 0.7,
            'top_p' => 1.0,
        ],
        'image' => [
            'width' => 512,
            'height' => 512,
            'steps' => 30,
            'cfg_scale' => 7.5,
        ],
        'storyboard' => [
            'frame_count' => 4,
            'width' => 640,
            'height' => 360,
            'steps' => 25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, only local/mock providers are used.
    | No external API calls will be made.
    |
    */
    'local_only' => env('AI_LOCAL_ONLY', true),
];
