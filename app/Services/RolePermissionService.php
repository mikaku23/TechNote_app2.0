<?php

namespace App\Services;

class RolePermissionService
{
    protected array $purposeAliases = [
        'bertanya' => ['bertanya', 'question', 'ask'],
        'minta_bantuan' => ['minta_bantuan', 'help', 'bantu', 'bantuan'],
        'komplain' => ['komplain', 'complaint', 'keluhan'],
        'cari_data' => ['cari_data', 'data_lookup', 'lookup', 'data', 'info', 'informasi', 'detail', 'status', 'daftar', 'lihat', 'cari', 'cek', 'tentang', 'read'],
        'minta_ringkasan' => ['minta_ringkasan', 'summary', 'ringkasan', 'rekap', 'resume'],
        'minta_edit_teks' => ['minta_edit_teks', 'edit_text', 'edit', 'ubah_teks', 'parafrase', 'rewrite'],
        'minta_aksi_sistem' => ['minta_aksi_sistem', 'system_action', 'crud', 'aksi_sistem'],
        'obrolan_bebas' => ['obrolan_bebas', 'chat', 'greeting', 'salam'],
    ];

    protected array $roleMap = [
        'admin' => [
            'purposes' => ['bertanya', 'minta_bantuan', 'help', 'komplain', 'cari_data', 'minta_ringkasan', 'minta_edit_teks', 'minta_aksi_sistem', 'obrolan_bebas'],
            'sources' => ['database', 'document', 'knowledge_base', 'trusted_website', 'context', 'session'],
            'can_write' => true,
            'can_system_action' => true,
            'requires_approval_by_default' => false,
        ],
        'mahasiswa' => [
            'purposes' => ['bertanya', 'minta_bantuan', 'help', 'cari_data', 'minta_ringkasan', 'obrolan_bebas'],
            'sources' => ['database', 'document', 'knowledge_base', 'trusted_website', 'context', 'session'],
            'can_write' => false,
            'can_system_action' => false,
            'requires_approval_by_default' => false,
        ],
        'dosen' => [
            'purposes' => ['bertanya', 'minta_bantuan', 'help', 'komplain', 'cari_data', 'minta_ringkasan', 'obrolan_bebas'],
            'sources' => ['database', 'document', 'knowledge_base', 'trusted_website', 'context', 'session'],
            'can_write' => false,
            'can_system_action' => false,
            'requires_approval_by_default' => false,
        ],
    ];

    protected array $writeEntities = [
        'users', 'roles', 'software', 'tickets', 'penginstalans', 'perbaikans',
        'trusted_websites', 'login_logs', 'user_activities', 'ai_logs', 'ai_tasks',
        'ai_recommendations', 'notifications', 'maintenances', 'system_settings',
        'rekaps', 'vercel_sync_logs', 'ticket_status_logs', 'ticket_comments',
    ];

    public function normalizeRole(?string $role): string
    {
        $role = mb_strtolower(trim((string) $role));

        return match ($role) {
            'administrator', 'superadmin', 'admin' => 'admin',
            'mahasiswa', 'student', 'user' => 'mahasiswa',
            'dosen', 'lecturer', 'teacher' => 'dosen',
            default => 'admin',
        };
    }

    public function normalizePurpose(?string $purpose): string
    {
        $purpose = mb_strtolower(trim((string) $purpose));

        foreach ($this->purposeAliases as $canonical => $aliases) {
            if (in_array($purpose, $aliases, true)) {
                return $canonical;
            }
        }

        return $purpose !== '' ? $purpose : 'obrolan_bebas';
    }

    public function canAccessPurpose(string $role, string $purpose): bool
    {
        $role = $this->normalizeRole($role);
        $purpose = $this->normalizePurpose($purpose);

        if ($role === 'admin') {
            return true;
        }

        $purposes = $this->roleMap[$role]['purposes'] ?? [];

        return in_array($purpose, $purposes, true);
    }

    public function canUseSource(string $role, string $source): bool
    {
        $role = $this->normalizeRole($role);

        if ($role === 'admin') {
            return true;
        }

        $sources = $this->roleMap[$role]['sources'] ?? [];

        return in_array($source, $sources, true);
    }

    public function canWrite(string $role, ?string $entity = null): bool
    {
        $role = $this->normalizeRole($role);

        if (! ($this->roleMap[$role]['can_write'] ?? false)) {
            return false;
        }

        if ($entity && ! in_array(mb_strtolower($entity), $this->writeEntities, true)) {
            return false;
        }

        return true;
    }

    public function canRunSystemAction(string $role, ?string $entity = null): bool
    {
        $role = $this->normalizeRole($role);

        if ($role !== 'admin') {
            return false;
        }

        if ($entity && ! in_array(mb_strtolower($entity), $this->writeEntities, true)) {
            return false;
        }

        return true;
    }

    public function requiresApproval(string $role, ?string $entity = null, ?string $operation = null): bool
    {
        $role = $this->normalizeRole($role);

        if ($role !== 'admin') {
            return true;
        }

        return (bool) ($this->roleMap[$role]['requires_approval_by_default'] ?? false);
    }

    public function canAccessEntity(string $role, ?string $entity): bool
    {
        if (! $entity) {
            return true;
        }

        $entity = mb_strtolower(trim($entity));
        $role = $this->normalizeRole($role);

        if ($role === 'admin') {
            return true;
        }

        $publicEntities = ['trusted_websites', 'system_settings', 'maintenances'];

        return in_array($entity, $publicEntities, true)
            || in_array($entity, ['tickets', 'penginstalans', 'perbaikans'], true);
    }

    public function decisionSummary(string $role): array
    {
        $role = $this->normalizeRole($role);

        return [
            'role' => $role,
            'can_write' => $this->roleMap[$role]['can_write'] ?? false,
            'can_system_action' => $this->roleMap[$role]['can_system_action'] ?? false,
            'allowed_purposes' => $this->roleMap[$role]['purposes'] ?? [],
            'allowed_sources' => $this->roleMap[$role]['sources'] ?? [],
        ];
    }
}
