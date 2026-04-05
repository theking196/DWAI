<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | The default AI provider to use. Currently supports: mock
    | Later: openai, anthropic, replicate, stability
    |
    */
    'provider' => env('AI_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'mock' => [
            'enabled' => env('AI_MOCK_ENABLED', true),
        ],
        // 'openai' => [
        //     'api_key' => env('OPENAI_API_KEY'),
        //     'model' => env('OPENAI_MODEL', 'gpt-4'),
        // ],
        // 'anthropic' => [
        //     'api_key' => env('ANTHROPIC_API_KEY'),
        //     'model' => env('ANTHROPIC_MODEL', 'claude-3-opus'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'text' => [
            'max_tokens' => 2048,
            'temperature' => 0.7,
        ],
        'image' => [
            'width' => 512,
            'height' => 512,
            'steps' => 30,
        ],
        'storyboard' => [
            'frame_count' => 4,
            'width' => 512,
            'height' => 288,
        ],
    ],
];
