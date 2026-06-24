<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

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
            logger()->warning('OpenRouter call failed', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function classifyPlan(string $message, array $options = []): ?array
    {
        $system = <<<PROMPT
Anda adalah classifier intent untuk sistem admin.
Pahami maksud pengguna, bukan hanya kata kunci.
Kembalikan JSON valid saja, tanpa markdown, tanpa penjelasan.

Skema JSON:
{
  "intent": "crud.create|crud.read|crud.update|crud.delete|crud.restore|lookup|summary|time|self|bot|campus|contact|analyze",
  "operation": "create|read|update|delete|restore|search|list|summary|time|self|bot|campus|contact|analyze",
  "entity": "users|roles|software|tickets|penginstalans|perbaikans|trusted_websites|login_logs|user_activities|ai_logs|ai_tasks|ai_recommendations|notifications|maintenances|system_settings|rekaps|vercel_sync_logs|ticket_status_logs|ticket_comments|contacts|null",
  "target_field": "id|name|username|email|nim|nip|ticket_number|url|key|null",
  "target_value": "string|null",
  "field": "string|null",
  "value": "string|null",
  "confidence": 0.0,
  "needs_clarification": false,
  "reason": "singkat"
}

Aturan:
- Jika pertanyaan meminta data, pilih read/search/list, bukan self.
- Jika pertanyaan punya kata "hari ini" dan membahas data, itu modifier tanggal, bukan time.
- Jika pertanyaan jelas meminta waktu sekarang, barulah intent=time.
- Jika kalimat mengandung "ubah status ... nonactive", petakan ke update dan is_active bila relevan.
- Jika data target lebih dari satu, set needs_clarification=true.
PROMPT;

        if (! empty($options)) {
            $system .= "\n\nOpsi tambahan:\n" . json_encode($options, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $reply = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $message],
        ], $this->defaultModel, 20, 220, 0.0);

        if (! $reply) {
            return null;
        }

        $decoded = $this->safeDecodeJson($reply);

        return is_array($decoded) ? $decoded : null;
    }

    public function safeDecodeJson(string $content): array|string|null
    {
        $content = trim($content);

        if ($content === '') {
            return null;
        }

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $maybe = substr($content, $start, $end - $start + 1);
            $decoded = json_decode($maybe, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
