<?php
namespace App\Services\Providers;

use Illuminate\Support\Str;

class LocalKnowledgeProvider
{
    protected array $documents = [];

    public function __construct(protected array $config = [])
    {
        $paths = $this->config['paths'] ?? [resource_path('ai_knowledge')];
        $this->loadDocuments($paths);
    }

    protected function loadDocuments(array $paths): void
    {
        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (glob($path . '/*.*') as $file) {
                $content = @file_get_contents($file);
                if ($content === false) {
                    continue;
                }

                $text = strip_tags($content);
                $this->documents[] = [
                    'path' => $file,
                    'content' => $content,
                    'text' => $text,
                ];
            }
        }
    }

    public function send(string $message, array $history = [], array $opts = []): array
    {
        $query = strtolower($message);
        $words = preg_split('/\s+/', $query);

        $best = null;
        $bestScore = 0;

        foreach ($this->documents as $doc) {
            $score = 0;
            $hay = strtolower($doc['text']);
            foreach ($words as $w) {
                if (strlen($w) < 3) continue;
                $score += substr_count($hay, $w);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $doc;
            }
        }

        if ($bestScore > 0 && $best !== null) {
            $snippet = trim(Str::limit($best['text'], 1200));
            return [
                'message' => "I found this in local knowledge (source: " . basename($best['path']) . "):\n\n" . $snippet,
                'meta' => [
                    'source' => $best['path'],
                    'score' => $bestScore,
                ],
            ];
        }

        // fallback to mock provider for conversational behavior
        $mock = new MockProvider(config('ai.mock') ?? []);
        return $mock->send($message, $history, $opts);
    }
}
