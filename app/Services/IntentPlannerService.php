<?php

namespace App\Services;

class IntentPlannerService
{
    public function __construct(
        protected KeywordService $keywordService,
        protected AdminActionService $actionService,
        protected CrudPlanService $crudPlanService,
        protected OpenRouterService $openRouterService,
        protected SystemControlService $systemControlService
    ) {
    }

    public function plan($user, string $question, array $context = []): array
    {
        $question = trim($question);
        $m = mb_strtolower($question);
        $role = $this->keywordService->resolveRoleName($user);

        $plan = [
            'intent' => 'analyze',
            'operation' => 'analyze',
            'entity' => null,
            'target' => [],
            'data' => [],
            'filters' => [],
            'confidence' => 0.4,
            'source' => 'heuristic',
            'needs_clarification' => false,
            'route' => 'free',
            'raw' => [],
            'role' => $role,
        ];

        if ($question === '') {
            $plan['intent'] = 'empty';
            $plan['route'] = 'fallback';
            $plan['needs_clarification'] = true;
            return $plan;
        }

        $systemCommand = $this->systemControlService->parseCommand($question);
        if (($systemCommand['action'] ?? 'unknown') !== 'unknown') {
            return $this->finish($plan, 'system', 'system', null, 'system', 0.98, 'rule');
        }

        if ($this->keywordService->isGreeting($question)) {
            return $this->finish($plan, 'greeting', 'read', null, 'greeting', 1, 'rule');
        }

        if ($this->keywordService->isAskingSummary($question)) {
            return $this->finish($plan, 'summary', 'summary', null, 'summary', 0.95, 'rule');
        }

        if ($this->keywordService->detectBotQuery($question)) {
            return $this->finish($plan, 'bot', 'bot', null, 'bot', 1, 'rule');
        }

        if ($this->keywordService->detectSelfQuery($question)) {
            return $this->finish($plan, 'self', 'self', null, 'self', 1, 'rule');
        }

        if ($this->keywordService->detectCampusQuery($question) || $this->actionService->detectEntity($question) === 'trusted_websites') {
            return $this->finish($plan, 'campus', 'campus', 'trusted_websites', 'campus', 0.92, 'rule');
        }

        $contextDetect = $this->actionService->detectContext($question);
        $entity = $contextDetect['entity'] ?? null;
        $operation = $contextDetect['operation'] ?? 'analyze';

        $crudPlan = $this->crudPlanService->build($question, $entity, $operation, $context);
        $dataLike = $this->looksLikeDataRequest($question, $entity, $crudPlan, $contextDetect);

        if ($this->keywordService->isAskingTime($question) && ! $dataLike) {
            return $this->finish($plan, 'time', 'time', null, 'time', 1, 'rule');
        }
        $entity = $crudPlan['entity'] ?? $entity;
        $operation = $crudPlan['operation'] ?? $operation;

        $readHint = ($operation === 'read')
            || preg_match('/\b(ada|apakah ada|cari data|lihat data|tampilkan data|data)\b/u', $m);
        $softDeleteScope = (bool) preg_match('/\b(soft\s*delete|soft-delete|recycle|recycle\s+bin|trash|trash\s+bin|sampah|deleted\s+data|data\s+terhapus|data\s+yang\s+dihapus)\b/u', $m);
        $restoreHint = (bool) preg_match('/\b(restore|kembalikan|pulihkan|aktifkan kembali)\b/u', $m);

        if ($softDeleteScope && $entity && empty($crudPlan['target']) && empty($crudPlan['data']) && empty($crudPlan['items'])) {
            $operation = 'read';
            $crudPlan['operation'] = 'read';
            $crudPlan['route'] = 'read';
            $crudPlan['filters']['only_deleted'] = true;
            $crudPlan['filters']['include_deleted'] = true;
            $crudPlan['filters']['list_all'] = true;
            $crudPlan['notes'][] = 'soft_deleted_scope';
        } elseif ($restoreHint) {
            $operation = 'restore';
        } elseif ($operation === 'analyze') {
            if ($readHint && $entity) {
                $operation = 'read';
            } elseif (preg_match('/\b(buat|tambah|create|insert|simpan|catat)\b/u', $m)) {
                $operation = 'create';
            } elseif (preg_match('/\b(ubah|update|edit|ganti|perbarui|set|jadikan|menjadi)\b/u', $m)) {
                $operation = 'update';
            } elseif (preg_match('/\b(hapus|delete|remove|destroy|buang)\b/u', $m)) {
                $operation = 'delete';
            }
        }

        $plan['entity'] = $entity;
        $plan['operation'] = $operation;
        $plan['intent'] = $this->operationToIntent($operation, $entity);
        $plan['route'] = $this->routeFromOperation($operation);
        $plan['target'] = $crudPlan['target'] ?? [];
        $plan['data'] = $crudPlan['data'] ?? [];
        $plan['items'] = $crudPlan['items'] ?? [];
        $plan['filters'] = $crudPlan['filters'] ?? [];
        $plan['confidence'] = $crudPlan['confidence'] ?? 0.4;
        $plan['raw'] = $crudPlan;
        $plan['needs_clarification'] = in_array('update_masih_kurang_field', $crudPlan['notes'] ?? [], true)
            || in_array('delete_target_missing', $crudPlan['notes'] ?? [], true);

        if (($plan['route'] === 'free' || $plan['confidence'] < 0.65 || $plan['needs_clarification']) && $entity !== null) {
            $semantic = $this->semanticRefine($question, $plan, $context);
            if ($semantic) {
                $plan = $this->mergeSemantic($plan, $semantic);
            }
        }

        if ($plan['route'] === 'read' && ($plan['filters']['today'] ?? false) === true) {
            $plan['source'] = 'rule+modifier';
            $plan['confidence'] = max($plan['confidence'], 0.82);
        }

        if ($plan['route'] === 'time' && $this->looksLikeDataRequest($question, $entity, $crudPlan, $contextDetect)) {
            $plan['intent'] = $this->operationToIntent('read', $entity);
            $plan['operation'] = 'read';
            $plan['route'] = 'read';
            $plan['source'] = 'rule+override';
            $plan['confidence'] = max($plan['confidence'], 0.88);
            $plan['needs_clarification'] = false;
            if (empty($plan['filters']['limit'])) {
                $plan['filters']['limit'] = 10;
            }
        }

        return $plan;
    }

    protected function looksLikeDataRequest(string $question, ?string $entity, array $crudPlan, array $contextDetect = []): bool
    {
        $m = mb_strtolower(trim($question));

        if (($contextDetect['is_write'] ?? false) === true) {
            return false;
        }

        if (! empty($entity)) {
            return true;
        }

        if (! empty($crudPlan['target']) || ! empty($crudPlan['data'])) {
            return true;
        }

        return (bool) preg_match(
            '/\b(data|tampilkan|lihat|daftar|cari|cek|detail|apakah ada|ada|rekap|ticket|tiket|user|pengguna|software|role|perbaikan|penginstalan|login log|ai log|ai task|ai recommendation|notifications?)\b/u',
            $m
        );
    }

    protected function semanticRefine(string $question, array $plan, array $context): ?array
    {
        $options = [
            'allowed_intents' => [
                'crud.create',
                'crud.read',
                'crud.update',
                'crud.delete',
                'crud.restore',
                'lookup',
                'summary',
                'time',
                'self',
                'bot',
                'campus',
                'contact',
                'system.control',
                'analyze',
            ],
            'entities' => $this->actionService->availableEntities(),
            'current_plan' => $plan,
            'context' => $context,
        ];

        $semantic = $this->openRouterService->classifyPlan($question, $options);

        if (! is_array($semantic)) {
            return null;
        }

        if (($semantic['confidence'] ?? 0) < 0.5) {
            return null;
        }

        return $semantic;
    }

    protected function mergeSemantic(array $plan, array $semantic): array
    {
        if (! empty($semantic['intent'])) {
            $plan['intent'] = $semantic['intent'];
        }

        if (! empty($semantic['operation'])) {
            $plan['operation'] = $semantic['operation'];
            $plan['route'] = $this->routeFromOperation($semantic['operation']);
        }

        if (! empty($semantic['entity'])) {
            $plan['entity'] = $semantic['entity'];
        }

        if (! empty($semantic['target_field']) && ! empty($semantic['target_value'])) {
            $plan['target'][$semantic['target_field']] = $semantic['target_value'];
        }

        if (! empty($semantic['field']) && array_key_exists('value', $semantic)) {
            $plan['data'][$semantic['field']] = $semantic['value'];
        }

        if (isset($semantic['confidence'])) {
            $plan['confidence'] = max((float) $plan['confidence'], (float) $semantic['confidence']);
        }

        if (isset($semantic['needs_clarification'])) {
            $plan['needs_clarification'] = (bool) $semantic['needs_clarification'];
        }

        $plan['source'] = 'hybrid';
        $plan['raw']['semantic'] = $semantic;

        return $plan;
    }

    protected function finish(array $plan, string $intent, string $operation, ?string $entity, string $route, float $confidence, string $source): array
    {
        $plan['intent'] = $intent;
        $plan['operation'] = $operation;
        $plan['entity'] = $entity;
        $plan['route'] = $route;
        $plan['confidence'] = $confidence;
        $plan['source'] = $source;
        return $plan;
    }

    protected function routeFromOperation(string $operation): string
    {
        return match ($operation) {
            'create', 'update', 'delete', 'restore' => 'crud',
            'read', 'lookup', 'search', 'list' => 'read',
            'summary' => 'summary',
            'time' => 'time',
            'self' => 'self',
            'bot' => 'bot',
            'campus' => 'campus',
            'contact' => 'contact',
            'system' => 'system',
            default => 'free',
        };
    }

    protected function operationToIntent(string $operation, ?string $entity): string
    {
        return match ($operation) {
            'create' => 'crud.create',
            'update' => 'crud.update',
            'delete' => 'crud.delete',
            'restore' => 'crud.restore',
            'read', 'search', 'list' => 'crud.read',
            'system' => 'system.control',
            default => 'analyze',
        };
    }
}
