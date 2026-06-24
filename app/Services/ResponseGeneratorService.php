<?php

namespace App\Services;

class ResponseGeneratorService
{
    public function __construct(
        protected OpenRouterService $openRouterService,
        protected FallbackHandlerService $fallbackHandlerService
    ) {
    }

    public function replyForClarification(array $intent): string
    {
        return $this->fallbackHandlerService->clarify($intent);
    }

    public function replyForBlock(string $reason): string
    {
        return $this->fallbackHandlerService->blocked($reason);
    }

    public function replyForFallback(array $intent): string
    {
        return $this->fallbackHandlerService->fallback($intent);
    }

    public function replyForLowConfidence(array $intent): string
    {
        return $this->fallbackHandlerService->genericLowConfidence();
    }

    public function rewriteText(string $inputText, string $role, array $intent): string
    {
        $inputText = trim($inputText);

        if ($inputText === '') {
            return 'Teks sumber belum diberikan.';
        }

        $system = <<<PROMPT
Anda adalah editor teks yang ketat.
Aturan:
- Ubah hanya gaya bahasa, struktur, atau kejelasan.
- Jangan menambah fakta baru.
- Jangan mengubah makna inti.
- Jawab ringkas dan langsung berisi hasil edit.
- Jika teks terlalu pendek, perbaiki secukupnya.
PROMPT;

        $user = <<<USER
Role: {$role}
Intent: {$intent['purpose']}
Teks:
{$inputText}
USER;

        $reply = $this->openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], 'deepseek/deepseek-chat', 35, 500, 0.2);

        return trim($reply ?: $inputText);
    }

    public function generateBoundedAnswer(string $question, array $retrieval, array $intent, string $role): string
    {
        $payload = $retrieval['payload'] ?? null;
        $reply = $retrieval['reply'] ?? null;
        $purpose = $intent['purpose'] ?? 'chat';

        if ($purpose === 'help' && preg_match('/\b(bisa|dapat|mampu|support|supports|supporting)\b/ui', $question)) {
            $entity = $intent['entity'] ?? null;
            $operation = $intent['operation'] ?? null;
            $entityText = $entity ? "entity {$entity}" : 'entity yang dimaksud';
            $opText = $operation ? $operation : 'CRUD';

            return "Bisa. Kirim {$entityText} dan detail {$opText} yang jelas supaya saya bisa memprosesnya dengan aman.";
        }

        if (is_string($reply) && trim($reply) !== '') {
            return trim($reply);
        }

        if (empty($payload)) {
            return $this->replyForFallback($intent);
        }

        if (isset($payload['items']) && is_array($payload['items'])) {
            $items = $payload['items'];
            $entity = $payload['entity'] ?? ($intent['entity'] ?? 'data');
            if (count($items) === 0) {
                return 'Data ' . $entity . ' tidak ditemukan.';
            }

            $lines = [];
            $limit = min(count($items), 10);
            $title = $entity === 'tickets'
                ? 'Berikut ' . $limit . ' data ticket pertama'
                : 'Berikut ' . $limit . ' data ' . $entity . ' pertama';

            $lines[] = $title . ':';

            for ($i = 0; $i < $limit; $i++) {
                $lines[] = ($i + 1) . '. ' . $this->summarizeRow((array) $items[$i], $entity);
            }

            if (count($items) > $limit) {
                $lines[] = 'Menampilkan 10 data pertama agar tidak terlalu panjang.';
            }

            return implode("\n", $lines);
        }

        if (is_array($payload) && ! empty($payload)) {
            return $this->formatPayloadToText($payload);
        }

        $system = <<<PROMPT
Anda adalah asisten internal TechNoteApp.
Aturan wajib:
- Gunakan hanya data yang diberikan.
- Jangan mengarang atau menambah fakta.
- Jika data tidak cukup, katakan "Data tidak ditemukan" atau minta klarifikasi.
- Jawaban maksimal 4 kalimat.
- Jika ada angka atau status, tampilkan apa adanya.
PROMPT;

        $user = json_encode([
            'role' => $role,
            'question' => $question,
            'intent' => $intent,
            'source' => $retrieval['source'] ?? 'official',
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $reply = $this->openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], 'deepseek/deepseek-chat', 40, 650, 0.15);

        return trim($reply ?: $this->replyForFallback($intent));
    }

    protected function summarizeRow(array $row, ?string $entity = null): string
    {
        $entity = $entity ? mb_strtolower($entity) : null;

        return match ($entity) {
            'tickets' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['ticket_number'] ?? '-') . ' | type=' . ($row['type'] ?? '-') . ' | status=' . ($row['status'] ?? '-') . ' | priority=' . ($row['priority'] ?? '-'),
            'software' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | developer=' . ($row['developer'] ?? '-') . ' | version=' . ($row['version'] ?? '-') . ' | est=' . ($row['estimated_minutes'] ?? '-'),
            'users' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | username=' . ($row['username'] ?? '-') . ' | role=' . ($row['role_name'] ?? $row['role'] ?? '-'),
            'trusted_websites' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | ' . ($row['url'] ?? '-') . ' | active=' . (($row['is_active'] ?? null) ? '1' : '0'),
            'login_logs' => '#' . ($row['id'] ?? '-') . ' | user=' . ($row['user_name'] ?? '-') . ' | status=' . ($row['status'] ?? '-') . ' | at=' . ($row['login_at'] ?? '-'),
            default => $this->formatPayloadToText($row),
        };
    }

    protected function formatPayloadToText(array $payload): string
    {
        $pieces = [];
        foreach (['id', 'name', 'title', 'ticket_number', 'status', 'type', 'username', 'url', 'developer', 'version', 'estimated_minutes', 'created_at'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null && $payload[$field] !== '') {
                $pieces[] = $field . '=' . $payload[$field];
            }
        }

        if (empty($pieces)) {
            foreach ($payload as $k => $v) {
                if (is_scalar($v) || $v === null) {
                    $pieces[] = $k . '=' . (string) $v;
                }
                if (count($pieces) >= 6) {
                    break;
                }
            }
        }

        return implode(', ', $pieces);
    }

    public function generateComplaintReply(string $question, array $intent): string
    {
        $system = 'Anda adalah asisten helpdesk yang empatik dan singkat. Balas dengan sopan, akui masalah, dan minta detail yang relevan bila diperlukan.';
        $user = 'Keluhan user: ' . $question;

        $reply = $this->openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], 'deepseek/deepseek-chat', 30, 350, 0.25);

        return trim($reply ?: 'Keluhan Anda sudah diterima. Silakan kirim detail tambahan agar bisa diproses.');
    }

    public function generateGeneralChat(string $question, string $role, array $context = []): string
    {
        $system = <<<PROMPT
Anda adalah asisten internal yang aman.
Aturan:
- Jangan mengarang fakta penting.
- Jika tidak ada data, katakan tidak tersedia.
- Jawaban singkat dan jelas.
- Bila pertanyaan di luar ruang lingkup internal, arahkan kembali secara sopan.
PROMPT;

        $user = json_encode([
            'role' => $role,
            'question' => $question,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $reply = $this->openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], 'deepseek/deepseek-chat', 35, 500, 0.2);

        return trim($reply ?: 'Saya belum punya data yang cukup untuk menjawab itu.');
    }

    public function generateApprovalDraft(string $question, array $crud, array $intent, string $role): string
    {
        $text = "Draft aksi {$intent['operation']} untuk entity {$intent['entity']} sudah disiapkan.";
        $text .= "\nPayload:\n" . json_encode($crud, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $text;
    }
}
