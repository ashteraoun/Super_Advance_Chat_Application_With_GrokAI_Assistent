<?php
namespace App\Services\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqProvider
{
    public function __construct(protected array $config)
    {
    }

    public function send(string $message, array $history = [], array $opts = []): array
    {
        $base = rtrim($this->config['base_url'] ?? 'https://api.groq.com/openai/v1', '/');
        $key = $this->config['api_key'] ?? null;
        if (empty($key)) {
            throw new \Exception('Groq API key not configured');
        }

        // Groq's API is OpenAI-compatible: it expects a `messages` array,
        // not a single "input" string with a separate "history" field.
        $messages = [];
        foreach ($history as $h) {
            $content = is_array($h) ? ($h['content'] ?? '') : '';
            if ($content === '') {
                continue;
            }
            $role = is_array($h) ? ($h['role'] ?? 'user') : 'user';
            $role = match ($role) {
                'user' => 'user',
                'assistant', 'ai', 'bot' => 'assistant',
                'system' => 'system',
                default => 'user',
            };
            $messages[] = ['role' => $role, 'content' => $content];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = [
            'model' => config('ai.model'),
            'messages' => $messages,
            'max_tokens' => (int) config('ai.max_tokens', 2048),
            'temperature' => (float) config('ai.temperature', 0.7),
        ];

        try {
            $response = Http::withToken($key)
                ->timeout(30)
                ->post($base . '/chat/completions', $payload);

            if (! $response->successful()) {
                $status = $response->status();
                $body = $response->body();
                Log::error("GroqProvider non-success status: {$status} body: {$body}");

                if (config('ai.allow_fallback', false)) {
                    return [
                        'message' => config('ai.fallback_message', 'AI Assistant is temporarily unavailable. Please try again later.'),
                        'meta' => ['status' => $status, 'body' => $body],
                    ];
                }

                throw new \Exception('Provider error: HTTP ' . $status);
            }

            $body = $response->json();
        } catch (\Exception $e) {
            Log::error('GroqProvider request failed: ' . $e->getMessage());

            if (config('ai.allow_fallback', false)) {
                return [
                    'message' => config('ai.fallback_message', 'AI Assistant is temporarily unavailable. Please try again later.'),
                    'meta' => ['error' => $e->getMessage()],
                ];
            }

            throw $e;
        }

        // The expected structure may vary by provider; normalize
        $text = '';
        if (isset($body['choices'][0]['message']['content'])) {
            $text = $body['choices'][0]['message']['content'];
        } elseif (isset($body['message'])) {
            $text = is_string($body['message']) ? $body['message'] : json_encode($body['message']);
        } elseif (isset($body['choices'][0]['text'])) {
            $text = $body['choices'][0]['text'];
        }

        return [
            'message' => $text,
            'meta' => $body,
        ];
    }
}
