<?php
namespace App\Services\Providers;

class MockProvider
{
    public function __construct(protected array $config = [])
    {
        $this->config = $config;
    }

    public function send(string $message, array $history = [], array $opts = []): array
    {
        $name = $this->config['name'] ?? config('ai.mock.name', 'Assistant');

        $reply = $this->generateReply($message, $history, $name);

        return [
            'message' => $reply,
            'meta' => [
                'mock' => true,
                'assistant_name' => $name,
                'history_count' => count($history),
                'opts' => $opts,
            ],
        ];
    }

    protected function generateReply(string $message, array $history = [], string $name = 'Assistant'): string
    {
        $trim = trim($message);
        if (empty($trim)) {
            return "Hello — I'm {$name}. How can I help you today?";
        }

        $lower = strtolower($trim);

        if (str_contains($lower, 'who are you') || str_contains($lower, 'what are you')) {
            return "I'm {$name}, a local development assistant. I can help with general questions and guide you while you work on your app.";
        }

        if (str_contains($lower, 'apple')) {
            // Provide a short informative summary about Apple (company)
            return "Apple Inc. is a technology company known for the iPhone, iPad, Mac computers, and services like the App Store and iCloud. If you want details about a specific product, tell me which one.";
        }

        if (str_ends_with($trim, '?')) {
            return "I don't have web access from here, but I can provide general guidance: try asking for step-by-step instructions or a concise summary and I'll help.";
        }

        return "Thanks — tell me more or ask a specific question and I'll do my best to help.";
    }
}
