<?php
namespace App\Services;

use App\Services\Providers\GroqProvider;
use App\Services\Providers\DeepSeekProvider;
use App\Services\Providers\MockProvider;
use App\Services\Providers\LocalKnowledgeProvider;
use Illuminate\Support\Facades\Log;

class AIService
{
    private $provider;

    public function __construct()
    {
        $providerName = config('ai.provider', 'deepseek');
        $this->provider = match ($providerName) {
            'deepseek' => new DeepSeekProvider(config('ai.deepseek')),
            'groq' => new GroqProvider(config('ai.groq')),
            'mock' => new MockProvider(config('ai.mock') ?? []),
            'local' => new LocalKnowledgeProvider(config('ai.local') ?? []),
            default => new DeepSeekProvider(config('ai.deepseek')),
        };
    }

    /**
     * Send a message to the configured provider
     * Returns array with 'message' and optional 'meta'
     */
    public function sendMessage(string $message, array $history = [], array $opts = []): array
    {
        // Truncate history to configured length
        $max = (int) config('ai.max_history', 6);
        if (count($history) > $max) {
            $history = array_slice($history, -1 * $max);
        }

        try {
            return $this->provider->send($message, $history, $opts);
        } catch (\Exception $e) {
            Log::error('AIService provider error: ' . $e->getMessage());
            throw $e;
        }
    }
}
