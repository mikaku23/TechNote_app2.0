<?php

namespace App\Services;

use Throwable;
use App\Services\SystemControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LogicAIforAdmin
{
    protected int $cacheTtlMinutes = 15;
    protected ?string $apiKey = null;

    public function __construct(
        protected KeywordService $keywordService,
        protected AdminDataSnapshotService $snapshotService,
        protected AdminActionService $actionService,
        protected OpenRouterService $openRouterService,
        protected TrustedWebsiteService $trustedWebsiteService,
        protected AiCrudExecutorService $crudExecutorService,
        protected CrudPlanService $crudPlanService,
        protected IntentPlannerService $intentPlannerService,
        protected AiTraceService $traceService,
        protected SystemControlService $systemControlService
    ) {
        $this->apiKey = config('services.openrouter.api_key') ?: env('OPENROUTER_API_KEY');
    }

    public function handle($user, string $question, array $context = []): array
    {
        $question = trim($question);

        if ($question === '') {
            return $this->normalizeResult([
                'ok' => false,
                'reply' => 'Pertanyaan masih kosong.',
                'action' => 'none',
                'blocked' => false,
                'source' => 'validator',
                'confidence' => 0,
                'needs_confirmation' => false,
            ]);
        }

        try {
            $role = $this->resolveRoleName($user);
            $warning = $this->keywordService->detectProfanityWarning($question);
            $plan = $this->intentPlannerService->plan($user, $question, $context);

            $plan = $this->recheckDataRoute($question, $plan);

            if (($plan['intent'] ?? '') === 'empty') {
                return $this->normalizeResult([
                    'ok' => false,
                    'reply' => 'Pertanyaan masih kosong.',
                    'action' => 'none',
                    'blocked' => false,
                    'source' => 'validator',
                    'confidence' => 0,
                    'needs_confirmation' => false,
                ]);
            }

            if (($plan['route'] ?? '') === 'greeting') {
                $reply = $warning . $this->keywordService->getGreetingPrefix($question) . ' Saya AI Admin TechNoteApp 2.0. Ada yang bisa dibantu mengenai data, tiket, rekap, software, maintenance, atau trusted website?';
                $this->pushHistory($user, $question, $reply);
                $aiLogId = $this->logAi($user, $question, $reply, 'greeting', 'rule');
                $this->logAction($aiLogId, 'greeting', ['question' => $question], 'success');

                return $this->normalizeResult([
                    'ok' => true,
                    'reply' => $reply,
                    'action' => 'greeting',
                    'blocked' => false,
                    'source' => 'rule',
                    'confidence' => 1,
                    'needs_confirmation' => false,
                ]);
            }

            if (($plan['route'] ?? '') === 'summary') {
                $summary = $this->keywordService->isAskingSummary($question)
                    ? $this->summarizeConversation($user, $question)
                    : 'Belum ada percakapan yang bisa diringkas.';

                $reply = $warning . $summary;
                $this->pushHistory($user, $question, $reply);
                $aiLogId = $this->logAi($user, $question, $reply, 'summarize', 'session');
                $this->logAction($aiLogId, 'summarize', ['question' => $question], 'success');

                return $this->normalizeResult([
                    'ok' => true,
                    'reply' => $reply,
                    'action' => 'summarize',
                    'blocked' => false,
                    'source' => 'session',
                    'confidence' => 0.95,
                    'needs_confirmation' => false,
                ]);
            }

            if (($plan['route'] ?? '') === 'system') {
                $result = $this->handleSystemCommand($user, $question, $warning);
                return $result;
            }

            if (($plan['route'] ?? '') === 'self') {
                $reply = $warning . $this->keywordService->handleSelfQuery($question, $user, $role);
                $this->pushHistory($user, $question, $reply);
                $aiLogId = $this->logAi($user, $question, $reply, 'self_query', 'rule');
                $this->logAction($aiLogId, 'self_query', ['question' => $question], 'success');

                return $this->normalizeResult([
                    'ok' => true,
                    'reply' => $reply,
                    'action' => 'self_query',
                    'blocked' => false,
                    'source' => 'rule',
                    'confidence' => 1,
                    'needs_confirmation' => false,
                ]);
            }

            if (($plan['route'] ?? '') === 'bot') {
                $reply = $warning . $this->keywordService->handleBotQuery($question);
                $this->pushHistory($user, $question, $reply);
                $aiLogId = $this->logAi($user, $question, $reply, 'bot_query', 'rule');
                $this->logAction($aiLogId, 'bot_query', ['question' => $question], 'success');

                return $this->normalizeResult([
                    'ok' => true,
                    'reply' => $reply,
                    'action' => 'bot_query',
                    'blocked' => false,
                    'source' => 'rule',
                    'confidence' => 1,
                    'needs_confirmation' => false,
                ]);
            }

            if (($plan['route'] ?? '') === 'time') {
                $reply = $warning . $this->keywordService->handleTimeQuery();
                $this->pushHistory($user, $question, $reply);
                $aiLogId = $this->logAi($user, $question, $reply, 'time_query', 'rule');
                $this->logAction($aiLogId, 'time_query', ['question' => $question], 'success');

                return $this->normalizeResult([
                    'ok' => true,
                    'reply' => $reply,
                    'action' => 'time_query',
                    'blocked' => false,
                    'source' => 'rule',
                    'confidence' => 1,
                    'needs_confirmation' => false,
                ]);
            }

            if (($plan['route'] ?? '') === 'campus') {
                $trusted = $this->trustedWebsiteService->answerFromTrustedWebsite($question, $this->openRouterService);

                if (! $trusted) {
                    $trusted = [
                        'reply' => $this->answerFromCampusConfig($question),
                        'source' => 'campus_config',
                        'confidence' => 0.8,
                    ];
                }

                $reply = $warning . ($trusted['reply'] ?? 'Informasi tidak tersedia.');
                $this->pushHistory($user, $question, $reply);
                $aiLogId = $this->logAi($user, $question, $reply, 'trusted_website', $trusted['source'] ?? 'campus_config');
                $this->logAction($aiLogId, 'trusted_website', [
                    'question' => $question,
                    'source' => $trusted['source'] ?? 'campus_config',
                ], 'success');

                return $this->normalizeResult([
                    'ok' => true,
                    'reply' => $reply,
                    'action' => 'trusted_website',
                    'blocked' => false,
                    'source' => $trusted['source'] ?? 'campus_config',
                    'confidence' => (float) ($trusted['confidence'] ?? 0.8),
                    'needs_confirmation' => false,
                ]);
            }

            if (($plan['route'] ?? '') === 'crud') {
                return $this->handleCrudPlan($user, $question, $plan, $role, $warning);
            }

            if (($plan['route'] ?? '') === 'read') {
                return $this->handleReadPlan($user, $question, $plan, $role, $warning);
            }

            if (($plan['route'] ?? '') === 'contact') {
                $reply = $warning . 'Pesan dan kritik dapat dipantau melalui notifikasi atau modul kontak yang tersedia di sistem.';
                $this->pushHistory($user, $question, $reply);
                $aiLogId = $this->logAi($user, $question, $reply, 'contact', 'rule');
                $this->logAction($aiLogId, 'contact', ['question' => $question], 'success');

                return $this->normalizeResult([
                    'ok' => true,
                    'reply' => $reply,
                    'action' => 'contact',
                    'blocked' => false,
                    'source' => 'rule',
                    'confidence' => 1,
                    'needs_confirmation' => false,
                ]);
            }

            return $this->handleFallback($user, $question, $plan, $warning);
        } catch (Throwable $e) {
            logger()->error('AI ADMIN ERROR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->normalizeResult([
                'ok' => true,
                'reply' => 'Maaf, saya belum dapat memproses permintaan tersebut.',
                'action' => 'error',
                'blocked' => false,
                'source' => 'exception_fallback',
                'confidence' => 0.25,
                'needs_confirmation' => false,
            ]);
        }
    }

    protected function handleReadPlan($user, string $question, array $plan, string $role, string $warning): array
    {
        $entity = $plan['entity'] ?? null;

        if (! $entity) {
            return $this->handleFallback($user, $question, $plan, $warning);
        }

        $payload = [
            'target' => $plan['target'] ?? [],
            'filters' => $plan['filters'] ?? [],
        ];

        if (! empty($payload['filters']['today']) && $entity === 'tickets') {
            $payload['filters']['limit'] = 10;
        }

        $execution = $this->crudExecutorService->execute('read', $entity, $payload);

        if (($execution['status'] ?? '') === 'ambiguous') {
            $reply = $warning . $this->formatAmbiguousMatches($entity, $execution['matches'] ?? []);
            $this->pushHistory($user, $question, $reply);
            $aiLogId = $this->logAi($user, $question, $reply, 'read_' . $entity, 'selection_needed');
            $this->logAction($aiLogId, 'read_' . $entity, [
                'question' => $question,
                'payload' => $payload,
                'matches' => $execution['matches'] ?? [],
            ], 'failed');

            return $this->normalizeResult([
                'ok' => false,
                'reply' => $reply,
                'action' => 'read_' . $entity,
                'blocked' => false,
                'source' => 'selection_needed',
                'confidence' => 0.55,
                'needs_confirmation' => true,
            ]);
        }

        if (($execution['ok'] ?? false) === false) {
            $reply = $warning . ($execution['message'] ?? 'Data tidak ditemukan.');
            $this->pushHistory($user, $question, $reply);
            $aiLogId = $this->logAi($user, $question, $reply, 'read_' . $entity, 'not_found');
            $this->logAction($aiLogId, 'read_' . $entity, [
                'question' => $question,
                'payload' => $payload,
            ], 'failed');

            return $this->normalizeResult([
                'ok' => false,
                'reply' => $reply,
                'action' => 'read_' . $entity,
                'blocked' => false,
                'source' => 'not_found',
                'confidence' => 0.45,
                'needs_confirmation' => false,
            ]);
        }

        $rows = $execution['rows'] ?? [];
        $reply = $warning . $this->formatRows($entity, $rows, $plan['filters'] ?? []);
        $this->pushHistory($user, $question, $reply);
        $aiLogId = $this->logAi($user, $question, $reply, 'read_' . $entity, 'database');
        $this->logAction($aiLogId, 'read_' . $entity, [
            'question' => $question,
            'payload' => $payload,
            'result_count' => count($rows),
        ], 'success');

        try {
            $this->traceService->syncTicketRecommendations($entity, 'read', $execution, $plan, $question, 'database');
        } catch (Throwable $e) {
            logger()->warning('AI trace sync failed on read', [
                'entity' => $entity,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->normalizeResult([
            'ok' => true,
            'reply' => $reply,
            'action' => 'read_' . $entity,
            'blocked' => false,
            'source' => 'database',
            'confidence' => 0.9,
            'needs_confirmation' => false,
        ]);
    }

    protected function handleCrudPlan($user, string $question, array $plan, string $role, string $warning): array
    {
        $entity = $plan['entity'] ?? null;
        $operation = $plan['operation'] ?? 'analyze';

        if (! $entity) {
            return $this->handleFallback($user, $question, $plan, $warning);
        }

        if ($operation === 'delete' && $this->crudPlanService->isMassDelete($plan, $question)) {
            $reply = $warning . 'Permintaan hapus massal diblokir. Kirim ID target yang spesifik.';
            $this->pushHistory($user, $question, $reply);
            $aiLogId = $this->logAi($user, $question, $reply, 'blocked_delete', 'policy');
            $this->logAction($aiLogId, 'blocked_delete', ['question' => $question, 'plan' => $plan], 'blocked');

            return $this->normalizeResult([
                'ok' => false,
                'reply' => $reply,
                'action' => 'blocked_delete',
                'blocked' => true,
                'source' => 'policy',
                'confidence' => 1,
                'needs_confirmation' => false,
            ]);
        }

        $crud = [
            'data' => $plan['data'] ?? [],
            'target' => $plan['target'] ?? [],
            'filters' => $plan['filters'] ?? [],
        ];

        $execution = $this->crudExecutorService->execute($operation, $entity, $crud);

        if (($execution['status'] ?? '') === 'missing_fields') {
            $reply = $warning . ($execution['message'] ?? $this->buildMissingDataReply($entity, $operation));
            $this->pushHistory($user, $question, $reply);
            $aiLogId = $this->logAi($user, $question, $reply, $operation . '_' . $entity, 'data_missing');
            $this->logAction($aiLogId, $operation . '_' . $entity, [
                'question' => $question,
                'crud' => $crud,
            ], 'failed');

            return $this->normalizeResult([
                'ok' => false,
                'reply' => $reply,
                'action' => $operation . '_' . $entity,
                'blocked' => false,
                'source' => 'data_missing',
                'confidence' => 0.5,
                'needs_confirmation' => true,
            ]);
        }

        if (($execution['status'] ?? '') === 'ambiguous') {
            $reply = $warning . $this->formatAmbiguousMatches($entity, $execution['matches'] ?? []);
            $this->pushHistory($user, $question, $reply);
            $aiLogId = $this->logAi($user, $question, $reply, $operation . '_' . $entity, 'selection_needed');
            $this->logAction($aiLogId, $operation . '_' . $entity, [
                'question' => $question,
                'crud' => $crud,
                'matches' => $execution['matches'] ?? [],
            ], 'failed');

            return $this->normalizeResult([
                'ok' => false,
                'reply' => $reply,
                'action' => $operation . '_' . $entity,
                'blocked' => false,
                'source' => 'selection_needed',
                'confidence' => 0.55,
                'needs_confirmation' => true,
            ]);
        }

        $reply = $warning . ($execution['message'] ?? 'Selesai.');
        $this->pushHistory($user, $question, $reply);
        $aiLogId = $this->logAi($user, $question, $reply, $operation . '_' . $entity, 'direct');
        $this->logAction($aiLogId, $operation . '_' . $entity, [
            'question' => $question,
            'crud' => $crud,
            'result' => $execution,
        ], ($execution['ok'] ?? false) ? 'success' : 'failed');

        if (($execution['ok'] ?? false) === true) {
            try {
                $this->traceService->syncTicketRecommendations($entity, $operation, $execution, $plan, $question, 'direct');
            } catch (Throwable $e) {
                logger()->warning('AI trace sync failed on direct action', [
                    'entity' => $entity,
                    'operation' => $operation,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->normalizeResult([
            'ok' => (bool) ($execution['ok'] ?? false),
            'reply' => $reply,
            'action' => $operation . '_' . $entity,
            'blocked' => false,
            'source' => 'direct',
            'confidence' => (bool) ($execution['ok'] ?? false) ? 1 : 0.4,
            'needs_confirmation' => false,
        ]);
    }

    protected function handleSystemCommand($user, string $question, string $warning): array
    {
        $parsed = $this->systemControlService->parseCommand($question);

        if (($parsed['action'] ?? 'unknown') === 'unknown') {
            $reply = $warning . 'Perintah sistem tidak dikenali.';
            $this->pushHistory($user, $question, $reply);
            $aiLogId = $this->logAi($user, $question, $reply, 'system_unknown', 'rule');
            $this->logAction($aiLogId, 'system_unknown', ['question' => $question, 'parsed' => $parsed], 'failed');

            return $this->normalizeResult([
                'ok' => false,
                'reply' => $reply,
                'action' => 'system_unknown',
                'blocked' => false,
                'source' => 'validator',
                'confidence' => 0.45,
                'needs_confirmation' => false,
            ]);
        }

        if (($parsed['action'] ?? '') === 'list_features') {
            $message = $this->systemControlService->getSystemSettingFeatureText();

            $reply = $warning . $message;
            $this->pushHistory($user, $question, $reply);

            $aiLogId = $this->logAi($user, $question, $reply, 'list_system_settings', 'rule');
            $this->logAction($aiLogId, 'list_system_settings', [
                'question' => $question,
                'parsed' => $parsed,
            ], 'success');

            return $this->normalizeResult([
                'ok' => true,
                'reply' => $reply,
                'action' => 'list_system_settings',
                'blocked' => false,
                'source' => 'system',
                'confidence' => 0.98,
                'needs_confirmation' => false,
            ]);
        }

        $execution = $this->systemControlService->executeCommand($question);
        $reply = $warning . ($execution['message'] ?? 'Selesai.');

        $this->pushHistory($user, $question, $reply);
        $aiLogId = $this->logAi($user, $question, $reply, $execution['type'] ?? 'system', 'direct');
        $this->logAction($aiLogId, $execution['type'] ?? 'system', [
            'question' => $question,
            'parsed' => $parsed,
            'execution' => $execution,
        ], ($execution['ok'] ?? false) ? 'success' : 'failed');

        return $this->normalizeResult([
            'ok' => (bool) ($execution['ok'] ?? false),
            'reply' => $reply,
            'action' => $execution['type'] ?? 'system',
            'blocked' => false,
            'source' => 'system',
            'confidence' => 0.98,
            'needs_confirmation' => false,
        ]);
    }

    protected function handleFallback($user, string $question, array $plan, string $warning): array
    {
        $reply = $warning . $this->generateAnswerWithAI($user, $question, $plan);
        $this->pushHistory($user, $question, $reply);
        $aiLogId = $this->logAi($user, $question, $reply, 'analyze', 'openrouter');
        $this->logAction($aiLogId, 'analyze', ['question' => $question, 'plan' => $plan], 'success');

        return $this->normalizeResult([
            'ok' => true,
            'reply' => $reply,
            'action' => 'analyze',
            'blocked' => false,
            'source' => 'openrouter',
            'confidence' => 0.6,
            'needs_confirmation' => false,
        ]);
    }

    protected function recheckDataRoute(string $question, array $plan): array
    {
        $entity = $plan['entity'] ?? null;
        $m = mb_strtolower($question);
        $hasDataSignal = (bool) preg_match('/\b(data|tampilkan|lihat|daftar|cari|cek|detail|apakah ada|ada|rekap|ticket|tiket|user|pengguna|software|role|perbaikan|penginstalan|login log|ai log|ai task|ai recommendation|notification|notifikasi)\b/u', $m);

        if (($plan['route'] ?? '') === 'time' && ($entity || $hasDataSignal)) {
            $plan['intent'] = $this->operationToIntentForPlan('read', $entity);
            $plan['operation'] = 'read';
            $plan['route'] = 'read';
            $plan['source'] = 'route_override';
            $plan['confidence'] = max((float) ($plan['confidence'] ?? 0.4), 0.9);
            $plan['needs_clarification'] = false;

            if (empty($plan['filters']['limit'])) {
                $plan['filters']['limit'] = 10;
            }
        }

        return $plan;
    }

    protected function operationToIntentForPlan(string $operation, ?string $entity): string
    {
        return match ($operation) {
            'create' => 'crud.create',
            'update' => 'crud.update',
            'delete' => 'crud.delete',
            'restore' => 'crud.restore',
            'read', 'search', 'list' => 'crud.read',
            default => 'analyze',
        };
    }

    public function generateAnswerWithAI($user, string $message, array $plan = []): string
    {
        $historyKey = 'chat_history_' . data_get($user, 'id');
        $history = Session::get($historyKey, []);
        $recent = array_slice($history, -5);

        $context = '';
        foreach ($recent as $h) {
            $context .= 'User: ' . ($h['in'] ?? '') . "\nBot: " . ($h['out'] ?? '') . "\n";
        }

        $role = $this->resolveRoleName($user);
        $overview = $this->snapshotService->overview();

        $system = "
Anda adalah asisten teknis resmi STMIK Triguna Dharma.

Aturan:
- Role user saat ini: {$role}
- Jangan mengarang status, tanggal, atau data.
- Jika data resmi tersedia di snapshot, utamakan data tersebut.
- Jika pertanyaan meminta data spesifik, jangan jawab umum.
- Jika informasi kurang, minta detail yang diperlukan.
- Untuk permintaan sistem, ikuti state sistem yang ada; jangan menebak.
- Jawab singkat, jelas, dan langsung ke inti.
";

        $userPrompt = json_encode([
            'question' => $message,
            'plan' => $plan,
            'recent_context' => $context,
            'system_snapshot' => $overview,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $resp = $this->openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ], 'deepseek/deepseek-chat', 30, 600, 0.2);

        if (! $resp) {
            return $this->keywordService->fallbackReply($message, ['action' => $plan['operation'] ?? 'analyze']);
        }

        return trim($resp);
    }

    protected function answerFromCampusConfig(string $question): string
    {
        $campus = config('jenis.datastmik', []);
        $m = mb_strtolower($question);

        if (preg_match('/\b(alamat|lokasi|dimana)\b/u', $m)) {
            return 'Alamat kampus: ' . data_get($campus, 'profil.alamat', data_get($campus, 'kontak.alamat', 'informasi tidak tersedia.'));
        }

        if (preg_match('/\b(website|situs)\b/u', $m)) {
            return 'Website resmi: ' . data_get($campus, 'profil.website', 'informasi tidak tersedia.');
        }

        if (preg_match('/\b(telepon|kontak|email)\b/u', $m)) {
            return 'Kontak resmi: ' . data_get($campus, 'profil.telepon', data_get($campus, 'kontak.telepon', 'informasi tidak tersedia.')) . ' / ' . data_get($campus, 'profil.email', data_get($campus, 'kontak.email', 'informasi tidak tersedia.'));
        }

        return 'Informasi kampus resmi tersedia pada sumber terpercaya yang terhubung ke sistem.';
    }

    protected function buildMissingDataReply(string $entity, string $operation): string
    {
        $label = $this->actionService->label($entity);

        return match ($operation) {
            'create' => "Data {$label} belum lengkap. Kirim field yang mau disimpan.",
            'update' => "Data perubahan {$label} belum lengkap. Kirim field yang mau diubah.",
            'delete' => "Target data {$label} belum jelas. Kirim ID atau pembeda yang tepat.",
            'restore' => "Target data {$label} belum jelas. Kirim ID data yang ingin dipulihkan.",
            default => "Data {$label} belum lengkap.",
        };
    }

    protected function formatRows(string $entity, array $rows, array $filters = []): string
    {
        if (empty($rows)) {
            return 'Data tidak ditemukan.';
        }

        $count = count($rows);

        if ($count === 1) {
            return $this->formatSingleRow($entity, (array) $rows[0]);
        }

        $lines = ["Ditemukan {$count} data " . $this->actionService->label($entity) . ':'];
        $max = min($count, 10);

        for ($i = 0; $i < $max; $i++) {
            $lines[] = '- ' . $this->summarizeRow($entity, (array) $rows[$i]);
        }

        if ($count > 10) {
            $lines[] = 'Hanya 10 data pertama yang ditampilkan.';
        }

        return implode("\n", $lines);
    }

    protected function formatSingleRow(string $entity, array $row): string
    {
        $label = $this->actionService->label($entity);
        $parts = [];

        switch ($entity) {
            case 'users':
                $parts = [
                    'id=' . ($row['id'] ?? '-'),
                    'name=' . ($row['name'] ?? '-'),
                    'username=' . ($row['username'] ?? '-'),
                    'email=' . ($row['email'] ?? '-'),
                    'role=' . ($row['role_name'] ?? $row['role_id'] ?? '-'),
                    'status=' . $this->inferActiveText($row['is_active'] ?? null),
                ];
                break;
            case 'roles':
                $parts = [
                    'id=' . ($row['id'] ?? '-'),
                    'name=' . ($row['name'] ?? '-'),
                    'description=' . ($row['description'] ?? '-'),
                    'status=' . $this->inferActiveText($row['is_active'] ?? null),
                ];
                break;
            case 'software':
                $parts = [
                    'id=' . ($row['id'] ?? '-'),
                    'name=' . ($row['name'] ?? '-'),
                    'developer=' . ($row['developer'] ?? '-'),
                    'version=' . ($row['version'] ?? '-'),
                    'estimated_minutes=' . ($row['estimated_minutes'] ?? '-'),
                ];
                break;
            case 'tickets':
                $parts = [
                    'id=' . ($row['id'] ?? '-'),
                    'ticket_number=' . ($row['ticket_number'] ?? '-'),
                    'type=' . ($row['type'] ?? '-'),
                    'status=' . ($row['status'] ?? '-'),
                    'priority=' . ($row['priority'] ?? '-'),
                    'user_id=' . ($row['user_id'] ?? '-'),
                ];
                break;
            default:
                foreach (['id','name','username','title','ticket_number','status','type','priority','message','description','created_at'] as $field) {
                    if (isset($row[$field])) {
                        $parts[] = $field . '=' . (is_scalar($row[$field]) ? $row[$field] : json_encode($row[$field], JSON_UNESCAPED_UNICODE));
                    }
                }
                break;
        }

        return $label . ': ' . implode(', ', $parts);
    }

    protected function summarizeRow(string $entity, array $row): string
    {
        return match ($entity) {
            'users' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | ' . ($row['username'] ?? '-') . ' | ' . ($row['email'] ?? '-'),
            'roles' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | ' . ($row['description'] ?? '-'),
            'software' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | ' . ($row['developer'] ?? '-') . ' | ' . ($row['version'] ?? '-'),
            'tickets' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['ticket_number'] ?? '-') . ' | ' . ($row['type'] ?? '-') . ' | ' . ($row['status'] ?? '-'),
            'trusted_websites' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | ' . ($row['url'] ?? '-'),
            'rekaps' => '#' . ($row['id'] ?? '-') . ' | ' . ($row['rekap_date'] ?? '-') . ' | inst=' . ($row['total_installations'] ?? '-') . ' rep=' . ($row['total_repairs'] ?? '-'),
            default => '#' . ($row['id'] ?? '-') . ' | ' . ($row['name'] ?? $row['title'] ?? $row['ticket_number'] ?? $row['task_name'] ?? '-'),
        };
    }

    protected function formatAmbiguousMatches(string $entity, array $matches): string
    {
        $label = $this->actionService->label($entity);

        if (empty($matches)) {
            return "Ada lebih dari satu data {$label} yang cocok. Kirim ID yang lebih spesifik.";
        }

        $lines = ["Ada lebih dari satu data {$label} yang cocok:"];
        $max = min(count($matches), 10);

        for ($i = 0; $i < $max; $i++) {
            $lines[] = '- ' . $this->summarizeRow($entity, (array) $matches[$i]);
        }

        $lines[] = 'Kirim ID atau pembeda yang lebih spesifik supaya saya tidak salah record.';

        return implode("\n", $lines);
    }

    protected function inferActiveText(mixed $value): string
    {
        if ($value === null) {
            return 'unknown';
        }

        return (bool) $value ? 'active' : 'inactive';
    }

    protected function logAi($user, string $question, string $answer, string $action, string $source): int
    {
        return $this->traceService->recordInteraction($user, $question, $answer, $action, $source);
    }

    protected function logAction(?int $aiLogId, string $actionType, array $actionData, string $result): void
    {
        $this->traceService->recordAction($aiLogId, $actionType, $actionData, $result);
    }

    protected function pushHistory($user, string $question, string $answer): void
    {
        $key = 'chat_history_' . data_get($user, 'id');
        $history = Session::get($key, []);
        $history[] = ['in' => $question, 'out' => $answer, 'at' => now()->toDateTimeString()];
        $history = array_slice($history, -20);
        Session::put($key, $history);
    }

    protected function summarizeConversation($user, string $promptSuffix): string
    {
        $key = 'chat_history_' . data_get($user, 'id');
        $history = Session::get($key, []);

        if (empty($history)) {
            return 'Belum ada percakapan sebelumnya untuk disimpulkan.';
        }

        $text = '';
        foreach ($history as $h) {
            $text .= 'User: ' . ($h['in'] ?? '') . "\n";
            $text .= 'Bot: ' . ($h['out'] ?? '') . "\n";
        }

        $system = 'Anda adalah asisten ringkas untuk sistem helpdesk STMIK. Buatkan ringkasan singkat (3-4 kalimat) dari percakapan berikut dan berikan rekomendasi tindakan jika perlu.';
        $userPrompt = "Percakapan:\n{$text}\nInstruksi: Buat ringkasan singkat dan poin tindakan.";

        $aiResp = $this->openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ], 'deepseek/deepseek-chat', 25, 500, 0.2);

        return $aiResp ?: 'Maaf, gagal membuat ringkasan saat ini.';
    }

    protected function normalizeResult(array $result): array
    {
        return array_merge([
            'ok' => false,
            'reply' => '',
            'action' => 'analyze',
            'blocked' => false,
            'source' => 'system',
            'confidence' => 0.5,
            'needs_confirmation' => false,
        ], $result);
    }

    protected function resolveRoleName($user): string
    {
        $role = data_get($user, 'role.name')
            ?? data_get($user, 'role.status')
            ?? data_get($user, 'role')
            ?? 'admin';

        return mb_strtolower(trim((string) $role));
    }
}
