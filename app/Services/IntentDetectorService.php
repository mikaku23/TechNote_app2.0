<?php

namespace App\Services;

class IntentDetectorService
{
    public function __construct(
        protected AdminActionService $actionService,
        protected OpenRouterService $openRouterService
    ) {
    }

    public function detect(string $message, ?string $role = null, array $context = []): array
    {
        $message = trim($message);
        $m = mb_strtolower($message);

        $actionContext = $this->actionService->detectContext($message);
        $sourceHint = $this->detectSourceHint($m);
        $signals = $this->collectSignals($m, $actionContext, $context);

        $lastLookup = is_array(data_get($context, 'last_lookup')) ? data_get($context, 'last_lookup') : [];
        if (! empty($lastLookup) && $this->looksLikeFollowUpReference($m)) {
            if (empty($actionContext['entity']) && ! empty($lastLookup['entity'])) {
                $actionContext['entity'] = mb_strtolower((string) $lastLookup['entity']);
            }

            if (($actionContext['operation'] ?? 'analyze') === 'analyze') {
                $followUpOperation = $this->inferOperationFromMessage($m);
                if ($followUpOperation !== null) {
                    $actionContext['operation'] = $followUpOperation;
                    $actionContext['is_write'] = $this->actionService->isWriteOperation($followUpOperation);
                }
            }
        }

        $purpose = $this->detectPurpose($m, $actionContext, $context);
        $intent = $this->mapPurposeToIntent($purpose, $actionContext);

        $confidence = $this->estimateInitialConfidence($purpose, $actionContext, $signals);

        $result = [
            'purpose' => $purpose,
            'intent' => $intent,
            'route' => $this->resolveRoute($purpose, $actionContext),
            'operation' => $actionContext['operation'] ?? 'analyze',
            'entity' => $actionContext['entity'] ?? null,
            'keyword' => $actionContext['keyword'] ?? null,
            'target_label' => $actionContext['target_label'] ?? null,
            'is_write' => (bool) ($actionContext['is_write'] ?? false),
            'source_hint' => $sourceHint,
            'confidence_seed' => $confidence,
            'needs_clarification' => $this->needsClarification($purpose, $actionContext, $signals),
            'signals' => $signals,
            'role' => $role ? mb_strtolower(trim($role)) : null,
        ];

        if ($this->shouldRefineWithAi($result)) {
            $result = $this->refineWithAi($message, (string) ($result['role'] ?? 'admin'), $result);
        }

        return $result;
    }

    protected function detectPurpose(string $message, array $actionContext, array $context): string
    {
        $entity = $actionContext['entity'] ?? null;
        $operation = $actionContext['operation'] ?? 'analyze';
        $hasCrudVerb = in_array($operation, ['create', 'update', 'delete', 'restore'], true);

        if ($this->looksLikeGreeting($message)) {
            return 'chat';
        }

        if ($this->matchAny($message, ['ringkas', 'ringkasan', 'summary', 'simpulkan', 'kesimpulan', 'resume', 'overview'])) {
            return 'summary';
        }

        if ($this->matchAny($message, [
            'komplain', 'keluhan', 'laporan masalah', 'error', 'bug', 'gagal', 'rusak', 'bermasalah',
            'tidak bisa', 'tidak jalan', 'lambat', 'lemot', 'crash',
        ])) {
            return 'complaint';
        }

        if ($this->matchAny($message, [
            'edit teks', 'ubah teks', 'rewrite', 'parafrase', 'perbaiki kalimat', 'rapikan kalimat',
            'susun ulang', 'ubah kata', 'tulis ulang',
        ])) {
            return 'edit_text';
        }

        if ($this->looksLikeCapabilityQuestion($message) && $hasCrudVerb) {
            return 'help';
        }

        if ($hasCrudVerb && ($entity !== null || $this->hasStructuredCrudPayload($message))) {
            return 'system_action';
        }

        if ($this->isReadStyleQuery($message, $entity, $actionContext)) {
            return 'data_lookup';
        }

        if ($entity && $this->matchAny($message, ['data', 'info', 'informasi', 'detail', 'status', 'daftar', 'lihat', 'tampilkan', 'cari', 'cek', 'tentang'])) {
            return 'data_lookup';
        }

        if ($this->looksLikeQuestion($message)) {
            return 'question';
        }

        if ($this->matchAny($message, ['obrolan bebas', 'ngobrol', 'chat', 'curhat'])) {
            return 'chat';
        }

        if ($entity && $hasCrudVerb) {
            return 'system_action';
        }

        return 'chat';
    }

    protected function mapPurposeToIntent(string $purpose, array $actionContext): string
    {
        return match ($purpose) {
            'summary' => 'summary',
            'complaint' => 'complaint',
            'edit_text' => 'edit_text',
            'system_action' => 'system_action',
            'data_lookup' => 'data_lookup',
            'help' => 'help',
            'question' => 'question',
            default => 'chat',
        };
    }

    protected function resolveRoute(string $purpose, array $actionContext): string
    {
        return match ($purpose) {
            'summary' => 'summary',
            'complaint' => 'complaint',
            'edit_text' => 'edit_text',
            'system_action' => $actionContext['is_write'] ? 'crud' : 'system_action',
            'data_lookup' => 'retrieval',
            'help' => 'help',
            'question' => 'question',
            default => 'chat',
        };
    }

    protected function detectSourceHint(string $message): string
    {
        if ($this->matchAny($message, ['database', 'db', 'tabel', 'record', 'data internal'])) {
            return 'database';
        }

        if ($this->matchAny($message, ['dokumen', 'document', 'manual', 'policy', 'aturan', 'sop'])) {
            return 'document';
        }

        if ($this->matchAny($message, ['knowledge base', 'kb', 'base pengetahuan'])) {
            return 'knowledge_base';
        }

        if ($this->matchAny($message, ['website resmi', 'situs resmi', 'trusted website', 'sumber resmi', 'website terpercaya', 'stmik', 'karang baru', 'smkn 1 karang baru', 'smkn1 karang baru'])) {
            return 'trusted_website';
        }

        return 'context';
    }

    protected function collectSignals(string $message, array $actionContext, array $context): array
    {
        $signals = [];

        if ($actionContext['entity'] ?? null) {
            $signals[] = 'entity:' . $actionContext['entity'];
        }

        if ($actionContext['operation'] ?? null) {
            $signals[] = 'operation:' . $actionContext['operation'];
        }

        if (($actionContext['keyword'] ?? null) !== null) {
            $signals[] = 'keyword:' . $actionContext['keyword'];
        }

        if ($this->looksLikeQuestion($message)) {
            $signals[] = 'question_mark_or_interrogative';
        }

        if ($this->hasQuotedText($message)) {
            $signals[] = 'quoted_text';
        }

        if ($this->looksLikeCapabilityQuestion($message)) {
            $signals[] = 'capability_question';
        }

        return $signals;
    }

    protected function estimateInitialConfidence(string $purpose, array $actionContext, array $signals): float
    {
        $score = match ($purpose) {
            'summary' => 0.84,
            'data_lookup' => 0.76,
            'system_action' => 0.72,
            'edit_text' => 0.68,
            'complaint' => 0.63,
            'help' => 0.66,
            'question' => 0.58,
            default => 0.42,
        };

        if (! empty($actionContext['entity'])) {
            $score += 0.08;
        }

        if (($actionContext['keyword'] ?? null) !== null) {
            $score += 0.05;
        }

        if (! empty($actionContext['is_write'])) {
            $score += 0.05;
        }

        if (in_array('quoted_text', $signals, true)) {
            $score += 0.08;
        }

        if (in_array('question_mark_or_interrogative', $signals, true)) {
            $score += 0.03;
        }

        if (in_array('capability_question', $signals, true)) {
            $score += 0.05;
        }

        if ($this->needsClarification($purpose, $actionContext, $signals)) {
            $score -= 0.18;
        }

        return max(0.0, min(1.0, $score));
    }

    protected function needsClarification(string $purpose, array $actionContext, array $signals): bool
    {
        if ($purpose === 'edit_text' && ! in_array('quoted_text', $signals, true)) {
            return true;
        }

        if ($purpose === 'system_action' && empty($actionContext['entity']) && empty($actionContext['keyword'])) {
            return true;
        }

        if ($purpose === 'data_lookup' && empty($actionContext['entity']) && empty($actionContext['keyword'])) {
            return true;
        }

        if ($purpose === 'help' && ! in_array('capability_question', $signals, true)) {
            return true;
        }

        return false;
    }

    protected function looksLikeQuestion(string $message): bool
    {
        return str_contains($message, '?')
            || $this->matchAny($message, ['apa', 'kenapa', 'bagaimana', 'dimana', 'di mana', 'kapan', 'siapa', 'berapa', 'mengapa']);
    }

    protected function hasQuotedText(string $message): bool
    {
        return (bool) preg_match("/\"[^\"]{3,}\"|\'[^\']{3,}\'/u", $message);
    }

    protected function looksLikeGreeting(string $message): bool
    {
        return $this->matchAny($message, ['halo', 'hai', 'pagi', 'siang', 'sore', 'malam']);
    }

    protected function looksLikeCapabilityQuestion(string $message): bool
    {
        return $this->matchAny($message, ['bisa', 'dapat', 'mampu', 'support', 'sanggup', 'bisa kah', 'apakah bisa'])
            && $this->matchAny($message, ['create', 'buat', 'tambah', 'update', 'ubah', 'delete', 'hapus', 'restore', 'pulihkan', 'kembalikan', 'crud']);
    }

    protected function hasStructuredCrudPayload(string $message): bool
    {
        return (bool) preg_match('/(?:=>|:|=)\s*[\'"A-Za-z0-9_\-\.]+/u', $message)
            || (bool) preg_match('/\b(id|name|username|ticket_number|developer|version|description|url)\b\s*(?:=>|:|=)/u', $message);
    }

    protected function isReadStyleQuery(string $message, ?string $entity, array $actionContext): bool
    {
        if ($this->looksLikeCapabilityQuestion($message)) {
            return false;
        }

        if ($this->matchAny($message, ['tampilkan data', 'lihat data', 'daftar data', 'detail data', 'cari data', 'data ', 'data\s', 'list data'])) {
            return true;
        }

        if ($entity && $this->matchAny($message, ['lihat', 'tampilkan', 'detail', 'cek', 'cari', 'daftar', 'show', 'baca'])) {
            return true;
        }

        if (($actionContext['operation'] ?? null) === 'read') {
            return true;
        }

        return false;
    }

    protected function shouldRefineWithAi(array $intent): bool
    {
        $purpose = $intent['purpose'] ?? 'chat';
        $seed = (float) ($intent['confidence_seed'] ?? 0.0);

        if ($seed < 0.68) {
            return true;
        }

        return in_array($purpose, ['chat', 'question', 'help'], true) || ! empty($intent['needs_clarification']);
    }

    protected function looksLikeFollowUpReference(string $message): bool
    {
        return $this->matchAny($message, ['nya', 'itu', 'tersebut', 'yang tadi', 'data tadi', 'data itu', 'user itu', 'akun itu', 'record itu']);
    }

    protected function inferOperationFromMessage(string $message): ?string
    {
        if ($this->matchAny($message, ['restore', 'pulihkan', 'kembalikan', 'embalikan'])) {
            return 'restore';
        }

        if ($this->matchAny($message, ['hapus', 'delete', 'remove', 'hapuskan'])) {
            return 'delete';
        }

        if ($this->matchAny($message, ['ubah', 'update', 'edit', 'ganti', 'nonaktifkan', 'aktifkan', 'set'])) {
            return 'update';
        }

        if ($this->matchAny($message, ['buat', 'create', 'tambah', 'insert', 'add'])) {
            return 'create';
        }

        return null;
    }

    protected function matchAny(string $message, array $keywords): bool
    {
        $m = mb_strtolower($message);

        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower((string) $keyword);
            if ($keyword !== '' && str_contains($m, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function isExplicitDataLookup(string $message): bool
    {
        return $this->matchAny($message, ['data', 'info', 'informasi', 'detail', 'status', 'daftar', 'lihat', 'tampilkan', 'cari', 'cek', 'tentang', 'rekap'])
            || $this->matchAny($message, ['apakah ada', 'adakah', 'ada tidak', 'ada gak', 'ada ga'])
            || $this->looksLikeUserLookupQuestion($message)
            || $this->looksLikeTodayDataQuery($message);
    }

    protected function looksLikeUserLookupQuestion(string $message): bool
    {
        return (bool) preg_match('/(user|pengguna|akun)/ui', $message)
            && (bool) preg_match('/(username|nama pengguna|email|nim|nip|role|status|nama)/ui', $message);
    }

    protected function looksLikeTodayDataQuery(string $message): bool
    {
        return (bool) preg_match('/(hari ini|today|data hari ini|rekap hari ini|hari ini)/ui', $message)
            && $this->matchAny($message, ['data', 'rekap', 'tiket', 'ticket', 'user', 'pengguna', 'software', 'log', 'login']);
    }

    protected function refineWithAi(string $message, string $role, array $localIntent): array
    {
        $availablePurposes = ['data_lookup', 'system_action', 'summary', 'edit_text', 'complaint', 'help', 'question', 'chat'];
        $ai = $this->openRouterService->classifyStructuredIntent($message, $availablePurposes, method_exists($this->actionService, 'availableEntities') ? $this->actionService->availableEntities() : []);

        if (! is_array($ai) || empty($ai['purpose'])) {
            return $localIntent;
        }

        $localPurpose = $localIntent['purpose'] ?? 'chat';
        $localSeed = (float) ($localIntent['confidence_seed'] ?? 0.0);
        $aiPurpose = mb_strtolower((string) $ai['purpose']);
        $aiConfidence = (float) ($ai['confidence'] ?? 0.5);

        $canOverride = in_array($localPurpose, ['chat', 'question', 'help'], true) || $localSeed < 0.68;

        if (! $canOverride && $aiConfidence < 0.75) {
            return $localIntent;
        }

        if (! in_array($aiPurpose, $availablePurposes, true)) {
            return $localIntent;
        }

        $merged = $localIntent;
        $merged['ai_used'] = true;
        $merged['ai_confidence'] = $aiConfidence;
        $merged['ai_reason'] = $ai['reason'] ?? null;
        $merged['ai_purpose'] = $aiPurpose;

        if ($canOverride) {
            $merged['purpose'] = $aiPurpose;
            $merged['intent'] = $this->mapPurposeToIntent($aiPurpose, $localIntent);
            $merged['route'] = $this->resolveRoute($aiPurpose, $localIntent);
        }

        if (! empty($ai['entity']) && empty($merged['entity'])) {
            $merged['entity'] = $ai['entity'];
        }

        if (! empty($ai['operation'])) {
            $merged['operation'] = $ai['operation'];
            $merged['is_write'] = in_array($ai['operation'], ['create', 'update', 'delete', 'restore'], true);
        }

        if (! empty($ai['source_hint'])) {
            $merged['source_hint'] = $ai['source_hint'];
        }

        if (! empty($ai['time_scope'])) {
            $merged['time_scope'] = $ai['time_scope'];
        }

        $merged['needs_clarification'] = (bool) ($ai['needs_clarification'] ?? $merged['needs_clarification'] ?? false);
        $merged['confidence_seed'] = max((float) ($merged['confidence_seed'] ?? 0.0), $aiConfidence);

        return $merged;
    }

}
