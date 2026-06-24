<?php

namespace App\Services;

class ConfidenceScorerService
{
    public function score(array $intent, array $retrieval, array $permission, array $policy): array
    {
        $score = 0.42;
        $breakdown = [];

        $purpose = $intent['purpose'] ?? 'chat';
        $entity = $intent['entity'] ?? null;
        $operation = $intent['operation'] ?? null;
        $source = $retrieval['source'] ?? 'context';

        $baseMap = [
            'summary' => 0.84,
            'data_lookup' => 0.76,
            'system_action' => 0.70,
            'edit_text' => 0.68,
            'complaint' => 0.63,
            'help' => 0.66,
            'question' => 0.58,
            'chat' => 0.45,
        ];

        if (isset($baseMap[$purpose])) {
            $score = $baseMap[$purpose];
            $breakdown[] = ['factor' => 'base_purpose', 'delta' => $score];
        }

        if ($entity) {
            $score += 0.08;
            $breakdown[] = ['factor' => 'entity_found', 'delta' => 0.08];
        }

        if ($operation) {
            $score += 0.05;
            $breakdown[] = ['factor' => 'operation_found', 'delta' => 0.05];
        }

        if (! empty($intent['keyword'])) {
            $score += 0.05;
            $breakdown[] = ['factor' => 'keyword_match', 'delta' => 0.05];
        }

        if ($source !== 'context') {
            $score += 0.08;
            $breakdown[] = ['factor' => 'official_source', 'delta' => 0.08];
        }

        if (! empty($retrieval['reply'])) {
            $score += 0.08;
            $breakdown[] = ['factor' => 'retrieval_reply', 'delta' => 0.08];
        }

        if (! empty($retrieval['payload'])) {
            $score += 0.10;
            $breakdown[] = ['factor' => 'payload_found', 'delta' => 0.10];
        }

        if (! empty($permission['allowed'])) {
            $score += 0.06;
            $breakdown[] = ['factor' => 'permission_allowed', 'delta' => 0.06];
        }

        if (($policy['state'] ?? '') === 'approval') {
            $score -= 0.05;
            $breakdown[] = ['factor' => 'approval_penalty', 'delta' => -0.05];
        }

        if (($policy['state'] ?? '') === 'fallback') {
            $score -= 0.18;
            $breakdown[] = ['factor' => 'fallback_penalty', 'delta' => -0.18];
        }

        if (($policy['state'] ?? '') === 'clarify') {
            $score -= 0.20;
            $breakdown[] = ['factor' => 'clarify_penalty', 'delta' => -0.20];
        }

        if (($policy['state'] ?? '') === 'block') {
            $score = min($score, 0.20);
            $breakdown[] = ['factor' => 'block_cap', 'delta' => -0.50];
        }

        if (empty($retrieval['reply']) && empty($retrieval['payload']) && in_array($purpose, ['data_lookup', 'summary', 'system_action'], true)) {
            $score -= 0.15;
            $breakdown[] = ['factor' => 'data_missing', 'delta' => -0.15];
        }

        $score = max(0.0, min(1.0, $score));

        return [
            'score' => $score,
            'tier' => $score >= 0.8 ? 'high' : ($score >= 0.55 ? 'medium' : 'low'),
            'breakdown' => $breakdown,
        ];
    }
}
