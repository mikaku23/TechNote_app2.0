<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KnowledgeRetrieverService
{
    public function __construct(
        protected AdminDataSnapshotService $snapshotService,
        protected TrustedWebsiteService $trustedWebsiteService,
        protected KeywordService $keywordService,
        protected AiCrudExecutorService $crudExecutorService
    ) {
    }

    public function retrieve(string $question, array $intent, string $role, array $context = []): array
    {
        $purpose = $intent['purpose'] ?? 'chat';
        $entity = $intent['entity'] ?? null;
        $sourceHint = $intent['source_hint'] ?? 'context';

        if ($purpose === 'summary') {
            $summary = $this->keywordService->summarizeConversation(data_get($context, 'user'), $question, app(OpenRouterService::class));

            return [
                'source' => 'session',
                'reply' => $summary,
                'payload' => ['summary' => $summary],
                'confidence' => 0.95,
                'official' => false,
            ];
        }

        if ($this->trustedWebsiteService->isTrustedWebsiteQuestion($question) || $sourceHint === 'trusted_website') {
            $trusted = $this->trustedWebsiteService->answerFromTrustedWebsite($question, app(OpenRouterService::class));

            if ($trusted) {
                return [
                    'source' => $trusted['source'] ?? 'trusted_website',
                    'reply' => $trusted['reply'] ?? null,
                    'payload' => $trusted,
                    'confidence' => (float) ($trusted['confidence'] ?? 1),
                    'official' => true,
                ];
            }
        }

        if ($purpose === 'data_lookup' || $purpose === 'question' || $purpose === 'help' || $this->looksLikeLookupQuestion($question)) {
            if ($entity) {
                $queryMode = $this->detectQueryMode($question);
                $target = $this->extractTarget($question);
                $filters = $this->detectFilters($question, $intent, $entity);
                $limit = $queryMode === 'single' ? 1 : 10;

                $result = $this->crudExecutorService->execute('read', $entity, [
                    'target' => $target,
                    'limit' => $limit,
                    'mode' => $queryMode,
                    'order' => 'asc',
                    'filters' => $filters,
                ]);

                if (($result['status'] ?? '') === 'ambiguous') {
                    return [
                        'source' => 'database',
                        'reply' => $this->formatAmbiguous($entity, $result['matches'] ?? [], $question),
                        'payload' => $result,
                        'confidence' => 0.58,
                        'official' => true,
                    ];
                }

                if (($result['ok'] ?? false) === true || (($result['status'] ?? '') === 'success')) {
                    $items = $result['items'] ?? [];
                    $payload = [
                        'entity' => $entity,
                        'items' => $items,
                        'count' => $result['count'] ?? count($items),
                        'limit' => $result['limit'] ?? $limit,
                        'has_more' => $result['has_more'] ?? false,
                    ];

                    return [
                        'source' => 'database',
                        'reply' => $this->formatReadReply($entity, $payload, $question, $queryMode, $target),
                        'payload' => $payload,
                        'confidence' => $this->scorePayload($payload, $entity),
                        'official' => true,
                    ];
                }

                return [
                    'source' => 'database',
                    'reply' => $result['message'] ?? 'Data tidak ditemukan.',
                    'payload' => $result,
                    'confidence' => 0.35,
                    'official' => true,
                ];
            }

            $overview = $this->snapshotService->overview();

            return [
                'source' => 'database',
                'reply' => $this->buildOverviewReply($overview),
                'payload' => $overview,
                'confidence' => 0.82,
                'official' => true,
            ];
        }

        if ($purpose === 'system_action') {
            $payload = [
                'entity' => $entity,
                'operation' => $intent['operation'] ?? null,
                'keyword' => $intent['keyword'] ?? null,
                'source_hint' => $sourceHint,
            ];

            return [
                'source' => 'context',
                'reply' => null,
                'payload' => $payload,
                'confidence' => 0.65,
                'official' => false,
            ];
        }

        return [
            'source' => 'context',
            'reply' => null,
            'payload' => [
                'question' => $question,
                'role' => $role,
                'context' => $context,
            ],
            'confidence' => 0.45,
            'official' => false,
        ];
    }

    protected function detectQueryMode(string $question): string
    {
        $q = mb_strtolower($question);

        if (preg_match('/\b(id|nomor|no|kode)\s*[:=]?\s*\d+\b/u', $q)) {
            return 'single';
        }

        if ($this->looksLikeListQuestion($q)) {
            return 'list';
        }

        return 'list';
    }

    protected function extractTarget(string $question): array
    {
        $q = mb_strtolower($question);
        $target = [];

        if (preg_match('/\b(?:id|nomor|no|kode)\s*[:=]?\s*(\d+)\b/u', $q, $m)) {
            $target['id'] = (int) $m[1];
        }

        if (preg_match('/\b(?:nama|name)\s*[:=]?\s*([\pL\pN\s\-_.]+?)(?=\s+(?:id|nomor|no|kode|dengan|untuk|pada|di)\b|$|,|\.)/u', $question, $m)) {
            $name = trim($m[1], " \t\n\r\0\x0B\"'");
            if ($name !== '') {
                $target['name'] = $name;
            }
        }

        if (preg_match('/\bsoftware\s+id\s*(\d+)\b/u', $q, $m)) {
            $target['id'] = (int) $m[1];
        }

        return $target;
    }

    protected function looksLikeLookupQuestion(string $question): bool
    {
        return (bool) preg_match('/\b(data|info|informasi|detail|status|daftar|lihat|tampil|tampilkan|cari|cek|tentang|profil|ticket|tiket|rekap|log|software|user|penginstalan|perbaikan)\b/ui', $question);
    }

    protected function looksLikeListQuestion(string $question): bool
    {
        return (bool) preg_match('/\b(semua|seluruh|daftar|list|tampilkan|lihat|data|isi|first|awal|pertama)\b/ui', $question);
    }

    protected function formatReadReply(string $entity, array $payload, string $question, string $mode, array $target = []): string
    {
        $items = $payload['items'] ?? [];
        $count = is_array($items) ? count($items) : 0;

        if ($count === 0) {
            return $this->fallbackReply($entity, $question);
        }

        if (! empty($target['id']) && $count === 1) {
            return 'Data ' . $this->entityLabel($entity) . ' ditemukan: ' . $this->summarizeRow((array) $items[0], $entity);
        }

        $lines = [];
        $limit = min($count, 10);
        $label = $this->entityLabel($entity);

        if ($entity === 'tickets') {
            $lines[] = 'Berikut ' . $limit . ' data ticket pertama dari ID terkecil:';
        } else {
            $lines[] = 'Berikut ' . $limit . ' data ' . strtolower($label) . ' pertama dari ID terkecil:';
        }

        for ($i = 0; $i < $limit; $i++) {
            $lines[] = ($i + 1) . '. ' . $this->summarizeRow((array) $items[$i], $entity);
        }

        if (($payload['has_more'] ?? false) === true) {
            $lines[] = 'Menampilkan 10 data pertama agar tetap ringkas.';
        }

        return implode("\n", $lines);
    }

    protected function formatAmbiguous(string $entity, array $matches, string $question): string
    {
        if (empty($matches)) {
            return 'Data ' . $this->entityLabel($entity) . ' yang cocok tidak ditemukan.';
        }

        $lines = ['Ada lebih dari satu data yang cocok. Kirim ID agar saya tidak salah target:'];
        $max = min(count($matches), 5);

        for ($i = 0; $i < $max; $i++) {
            $lines[] = '- ' . $this->summarizeRow((array) $matches[$i], $entity);
        }

        return implode("\n", $lines);
    }

    protected function buildOverviewReply(array $overview): string
    {
        $tickets = data_get($overview, 'tickets.total');
        $users = data_get($overview, 'counts.users');
        $software = data_get($overview, 'counts.software');

        $parts = [];
        if ($tickets !== null) {
            $parts[] = 'total ticket ' . $tickets;
        }
        if ($users !== null) {
            $parts[] = 'user ' . $users;
        }
        if ($software !== null) {
            $parts[] = 'software ' . $software;
        }

        return 'Ringkasan data resmi: ' . implode(', ', $parts) . '.';
    }

    protected function scorePayload(array $payload, ?string $entity = null): float
    {
        if (empty($payload)) {
            return 0.1;
        }

        if (isset($payload['items']) && is_array($payload['items']) && count($payload['items']) > 0) {
            return 0.9;
        }

        if ($entity) {
            return 0.75;
        }

        return 0.6;
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
            default => $this->genericRow($row),
        };
    }

    protected function genericRow(array $row): string
    {
        $parts = [];
        foreach (['id', 'name', 'title', 'ticket_number', 'status', 'type', 'username', 'url', 'developer', 'version', 'estimated_minutes', 'created_at'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null && $row[$field] !== '') {
                $parts[] = $field . '=' . $row[$field];
            }
        }

        if (empty($parts)) {
            foreach ($row as $k => $v) {
                if (is_scalar($v) || $v === null) {
                    $parts[] = $k . '=' . (string) $v;
                }
                if (count($parts) >= 6) {
                    break;
                }
            }
        }

        return implode(', ', $parts);
    }

    protected function entityLabel(string $entity): string
    {
        return match (mb_strtolower($entity)) {
            'users' => 'User',
            'roles' => 'Role',
            'software' => 'Software',
            'tickets' => 'Ticket',
            'penginstalans' => 'Penginstalan',
            'perbaikans' => 'Perbaikan',
            'trusted_websites' => 'Trusted Website',
            'login_logs' => 'Login Log',
            'user_activities' => 'User Activity',
            'ai_logs' => 'AI Log',
            'ai_tasks' => 'AI Task',
            'ai_recommendations' => 'AI Recommendation',
            'notifications' => 'Notification',
            'maintenances' => 'Maintenance',
            'system_settings' => 'System Setting',
            'rekaps' => 'Rekap',
            'vercel_sync_logs' => 'Vercel Sync Log',
            'ticket_status_logs' => 'Ticket Status Log',
            'ticket_comments' => 'Ticket Comment',
            default => ucfirst(str_replace('_', ' ', $entity)),
        };
    }

    protected function fallbackReply(?string $entity, string $question): string
    {
        if ($entity) {
            return 'Data ' . $this->entityLabel($entity) . ' tidak ditemukan.';
        }

        return 'Data tidak ditemukan.';
    }

    protected function detectFilters(string $question, array $intent, ?string $entity = null): array
    {
        $q = mb_strtolower($question);
        $filters = [];

        if (preg_match('/\b(hari ini|today)\b/ui', $q)) {
            $filters['today_only'] = true;
            $filters['created_date'] = now()->toDateString();
        }

        if ($entity === 'rekaps' && preg_match('/\b(hari ini|today)\b/ui', $q)) {
            $filters['rekap_date'] = now()->toDateString();
        }

        if (preg_match('/\b(username|email|nim|nip|nama|name)\b/ui', $q)) {
            $filters['search'] = $this->extractSearchTerm($question) ?: null;
        }

        return array_filter($filters, static fn ($v) => $v !== null && $v !== '');
    }

    protected function extractSearchTerm(string $question): ?string
    {
        if (preg_match('/\b(?:username|email|nim|nip|nama|name)\s*[:=]?\s*([\pL\pN\s\-_.@]+?)(?=\s+(?:dan|atau|dengan|untuk|pada|di|hari|id|nomor|no|kode)\b|$|,|\.)/ui', $question, $m)) {
            $term = trim($m[1], " \t\n\r\0\x0B\"'");
            return $term !== '' ? $term : null;
        }

        if (preg_match('/\busername\s+([\pL\pN\-_.@]+)\b/ui', $question, $m)) {
            $term = trim($m[1], " \t\n\r\0\x0B\"'");
            return $term !== '' ? $term : null;
        }

        return null;
    }


}
