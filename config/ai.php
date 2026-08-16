<?php
return [
    'provider' => env('AI_PROVIDER', 'deepseek'),
    'model' => env('AI_MODEL', 'deepseek-chat'),
    'max_history' => env('AI_MAX_HISTORY', 6),
    'max_tokens' => env('AI_MAX_TOKENS', 2048),
    'temperature' => env('AI_TEMPERATURE', 0.7),
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_API_URL', 'https://api.deepseek.com'),
        'system_prompt' => env('AI_SYSTEM_PROMPT', 'You are a helpful, friendly AI assistant inside a chat application. Keep answers clear and concise.'),
    ],
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
    ],
    // If true, return a safe fallback message when the provider is unreachable
    'allow_fallback' => env('AI_ALLOW_FALLBACK', true),
    'fallback_message' => env('AI_FALLBACK_MESSAGE', 'AI Assistant is temporarily unavailable. Please try again later.'),
    'mock' => [
        'name' => env('AI_MOCK_NAME', 'Assistant'),
    ],
    'local' => [
        // directories to search for local knowledge files (text, md)
        'paths' => [resource_path('ai_knowledge')],
    ],
];
