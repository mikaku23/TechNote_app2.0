<?php

namespace App\Services;

use Throwable;
use Illuminate\Support\Facades\Http;

class OpenRouterService
{
    protected string $baseUrl = 'https://openrouter.ai/api/v1';
    protected string $defaultModel = 'deepseek/deepseek-chat';

    public function chat(
        array $messages,
        ?string $model = null,
        int $timeout = 30,
        int $maxTokens = 700,
        float $temperature = 0.2
    ): ?string {
        try {
            $apiKey = config('services.openrouter.api_key') ?: env('OPENROUTER_API_KEY');

            if (! $apiKey) {
                return null;
            }

            $response = Http::timeout($timeout)
                ->retry(2, 500)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'HTTP-Referer'  => config('app.url', 'http://localhost'),
                    'X-Title'       => config('app.name', 'TechNoteApp'),
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $model ?: $this->defaultModel,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'stream' => false,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            if (is_array($content)) {
                $content = implode("\n", $content);
            }

            return $content ? trim((string) $content) : null;
        } catch (Throwable $e) {
            logger()->warning('OpenRouter call failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function classifyIntent(string $message, array $availableIntents): ?string
    {
        $system = 'Anda adalah classifier. Klasifikasikan message menjadi salah satu intent berikut. Jawab hanya dengan nama intent atau none. Intent: ' . implode(', ', $availableIntents);
        $user   = 'Message: "' . $message . '"';

        $resp = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], $this->defaultModel, 20, 120, 0.0);

        if (! $resp) {
            return null;
        }

        $respLower = mb_strtolower($resp);

        foreach ($availableIntents as $intent) {
            if (str_contains($respLower, mb_strtolower($intent))) {
                return $intent;
            }
        }

        return null;
    }
}
