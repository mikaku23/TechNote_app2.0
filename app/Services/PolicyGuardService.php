<?php

namespace App\Services;

class PolicyGuardService
{
    public function __construct(
        protected RolePermissionService $permissionService
    ) {
    }

    public function decide(
        string $role,
        array $intent,
        array $retrieval,
        bool $antiMode,
        bool $canWriteDirectly,
        bool $needsApproval,
        float $confidence
    ): array {
        $role = $this->permissionService->normalizeRole($role);
        $purpose = $intent['purpose'] ?? 'obrolan_bebas';
        $entity = $intent['entity'] ?? null;
        $operation = $intent['operation'] ?? null;
        $source = $retrieval['source'] ?? 'context';
        $isWrite = (bool) ($intent['is_write'] ?? false);

        if ($antiMode && $isWrite) {
            return $this->result('block', 'anti_ai_mode', true, false);
        }

        if ($role !== 'admin') {
            if (! $this->permissionService->canAccessPurpose($role, $purpose)) {
                return $this->result('block', 'purpose_tidak_diizinkan', false, false);
            }

            if (! $this->permissionService->canUseSource($role, $source)) {
                return $this->result('block', 'source_tidak_diizinkan', false, false);
            }
        }

        if ($isWrite && ! $this->permissionService->canWrite($role, $entity)) {
            return $this->result('block', 'role_tidak_boleh_write', true, false);
        }

        if ($isWrite && $this->permissionService->requiresApproval($role, $entity, $operation)) {
            return $this->result('approval', 'butuh_persetujuan', false, true);
        }

        if ($confidence < 0.35) {
            return $this->result('clarify', 'confidence_rendah', false, false);
        }

        if ($confidence < 0.6 && empty($retrieval['reply']) && empty($retrieval['payload'])) {
            return $this->result('fallback', 'data_tidak_cukup', false, false);
        }

        if ($isWrite && ! $canWriteDirectly) {
            return $this->result('approval', 'write_butuh_verifikasi', false, true);
        }

        return $this->result('allow', 'ok', false, false);
    }

    protected function result(string $state, string $reason, bool $blocked, bool $needsConfirmation): array
    {
        return [
            'state' => $state,
            'reason' => $reason,
            'blocked' => $blocked,
            'needs_confirmation' => $needsConfirmation,
        ];
    }
}
