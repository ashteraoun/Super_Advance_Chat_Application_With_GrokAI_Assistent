<?php
namespace App\Services\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekProvider
{
    public function __construct(protected array $config)
    {
    }

    public function send(string $message, array $history = [], array $opts = []): array
    {
        $base = rtrim($this->config['base_url'] ?? 'https://api.deepseek.com', '/');
        $key = $this->config['api_key'] ?? null;
        if (empty($key)) {
            throw new \Exception('DeepSeek API key not configured');
        }

        $messages = [];

        $systemPrompt = $this->config['system_prompt'] ?? null;
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // Normalize incoming history into DeepSeek's expected role names
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

        // DeepSeek's API is OpenAI-compatible: POST {base}/chat/completions
        // with a `messages` array (NOT a single "input" string).
        $payload = [
            'model' => config('ai.model') ?: 'deepseek-chat',
            'messages' => $messages,
            'max_tokens' => (int) config('ai.max_tokens', 2048),
            'temperature' => (float) config('ai.temperature', 0.7),
            'stream' => false,
        ];

        try {
            $response = Http::withToken($key)
                ->timeout(30)
                ->post($base . '/chat/completions', $payload);

            if (! $response->successful()) {
                $status = $response->status();
                $body = $response->body();
                Log::error("DeepSeekProvider non-success status: {$status} body: {$body}");

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
            Log::error('DeepSeekProvider request failed: ' . $e->getMessage());

            if (config('ai.allow_fallback', false)) {
                return [
                    'message' => config('ai.fallback_message', 'AI Assistant is temporarily unavailable. Please try again later.'),
                    'meta' => ['error' => $e->getMessage()],
                ];
            }

            throw $e;
        }

        $text = '';
        if (isset($body['choices'][0]['message']['content'])) {
            $text = $body['choices'][0]['message']['content'];
        } elseif (isset($body['message'])) {
            $text = is_string($body['message']) ? $body['message'] : json_encode($body['message']);
        }

        return [
            'message' => $text,
            'meta' => $body,
        ];
    }
}
