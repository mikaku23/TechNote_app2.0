<?php

namespace App\Services;

class AdminActionService
{
    protected array $operations = [
        'create' => ['buat', 'tambah', 'tambahkan', 'insert', 'create', 'add'],
        'update' => ['ubah', 'edit', 'perbarui', 'update', 'ganti', 'sesuaikan'],
        'delete' => ['hapus', 'delete', 'remove', 'destroy'],
        'read' => ['lihat', 'tampilkan', 'detail', 'cek', 'cari', 'show', 'baca', 'daftar'],
        'analyze' => ['analisis', 'analisa', 'ringkas', 'rekomendasi', 'insight', 'evaluasi', 'statistik', 'masalah', 'bottleneck'],
    ];

    protected array $entities = [
        'users' => ['user', 'pengguna', 'mahasiswa', 'dosen', 'akun'],
        'roles' => ['role', 'peran', 'jabatan'],
        'software' => ['software', 'aplikasi', 'program'],
        'tickets' => ['ticket', 'tiket', 'antrian'],
        'penginstalans' => ['penginstalan', 'instalasi', 'install', 'instal'],
        'perbaikans' => ['perbaikan', 'repair', 'service', 'servis'],
        'trusted_websites' => ['trusted website', 'website terpercaya', 'situs resmi', 'website resmi', 'sumber resmi'],
        'login_logs' => ['login log', 'log login', 'online offline', 'log masuk'],
        'user_activities' => ['aktivitas', 'user activity', 'log aktivitas'],
        'ai_logs' => ['ai log', 'log ai', 'riwayat ai'],
        'ai_tasks' => ['ai task', 'task ai', 'tugas ai'],
        'ai_recommendations' => ['rekomendasi ai', 'ai rekom', 'ai recommendation'],
        'notifications' => ['notifikasi', 'notification'],
        'maintenances' => ['maintenance', 'pemeliharaan'],
        'system_settings' => ['setting', 'pengaturan', 'system setting'],
        'rekaps' => ['rekap', 'rekapan', 'summary'],
        'vercel_sync_logs' => ['vercel sync', 'sync vercel', 'log vercel'],
    ];

    public function detectContext(string $message): array
    {
        $m = mb_strtolower(trim($message));

        $operation = $this->detectOperation($m);
        $entity = $this->detectEntity($m);
        $keyword = $this->detectKeyword($m);

        return [
            'operation' => $operation,
            'entity' => $entity,
            'keyword' => $keyword,
            'is_write' => $this->isWriteOperation($operation),
            'target_label' => $entity ? $this->label($entity) : null,
        ];
    }

    public function detectOperation(string $message): string
    {
        $m = mb_strtolower(trim($message));

        foreach (['create', 'update', 'delete', 'read', 'analyze'] as $operation) {
            foreach ($this->operations[$operation] ?? [] as $word) {
                if ($word !== '' && str_contains($m, mb_strtolower($word))) {
                    return $operation;
                }
            }
        }

        return 'analyze';
    }

    public function detectEntity(string $message): ?string
    {
        $m = mb_strtolower(trim($message));

        foreach ($this->entities as $entity => $keywords) {
            foreach ($keywords as $word) {
                if ($word !== '' && str_contains($m, mb_strtolower($word))) {
                    return $entity;
                }
            }
        }

        return null;
    }

    public function detectKeyword(string $message): ?string
    {
        $m = mb_strtolower(trim($message));

        foreach ($this->operations as $operation => $keywords) {
            foreach ($keywords as $word) {
                if ($word !== '' && str_contains($m, mb_strtolower($word))) {
                    return $word;
                }
            }
        }

        return null;
    }

    public function isWriteOperation(?string $operation): bool
    {
        return in_array($operation, ['create', 'update', 'delete'], true);
    }

    public function label(?string $entity): string
    {
        return match ($entity) {
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
            default => $entity ? ucfirst($entity) : 'Unknown',
        };
    }

    public function availableEntities(): array
    {
        return array_keys($this->entities);
    }

    public function availableOperations(): array
    {
        return ['create', 'update', 'delete', 'read', 'analyze'];
    }
}
