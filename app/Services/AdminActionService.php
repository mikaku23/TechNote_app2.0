<?php

namespace App\Services;

class AdminActionService
{
    protected array $operations = [
        'create' => ['buat', 'tambah', 'tambahkan', 'insert', 'create', 'add', 'simpan', 'catat'],
        'read'   => ['lihat', 'tampilkan', 'detail', 'cek', 'cari', 'show', 'baca', 'daftar', 'data', 'list', 'semua'],
        'update' => ['ubah', 'edit', 'perbarui', 'update', 'ganti', 'sesuaikan', 'set', 'jadikan', 'menjadi'],
        'delete' => ['hapus', 'delete', 'remove', 'destroy', 'buang'],
        'restore' => ['restore', 'kembalikan', 'pulihkan', 'aktifkan kembali'],
        'analyze' => ['analisis', 'analisa', 'ringkas', 'rekomendasi', 'insight', 'evaluasi', 'statistik', 'masalah', 'bottleneck'],
    ];

    protected array $entities = [
        'users' => ['user', 'pengguna', 'mahasiswa', 'dosen', 'akun', 'username'],
        'roles' => ['role', 'peran', 'jabatan'],
        'software' => ['software', 'aplikasi', 'program'],
        'tickets' => ['ticket', 'tiket', 'antrian'],
        'penginstalans' => ['penginstalan', 'instalasi', 'install', 'instal', 'pemasangan'],
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
        'rekaps' => ['rekap', 'rekapan', 'summary', 'ringkasan data'],
        'vercel_sync_logs' => ['vercel sync', 'sync vercel', 'log vercel'],
        'ticket_status_logs' => ['ticket status log', 'status log', 'log status tiket'],
        'ticket_comments' => ['ticket comment', 'komentar tiket', 'catatan tiket'],
        'contacts' => ['contact', 'kontak', 'kritik', 'saran', 'pesan'],
        'password_reset_otps' => ['otp', 'password reset otp', 'reset otp'],
        'sessions' => ['session', 'sesi'],
    ];

    public function detectContext(string $message): array
    {
        $m = mb_strtolower(trim($message));

        $operation = $this->detectOperation($m);
        $entity = $this->detectEntity($m);
        $keyword = $this->detectKeyword($m);
        $hasId = (bool) preg_match('/\b(?:id|data\s+ke|record\s+ke)\s*[:=]?\s*\d+\b/u', $m);
        $hasName = (bool) preg_match('/\b(?:nama|name|username|ticket\s*number|ticket_number|url|email|key)\b/u', $m);

        return [
            'operation' => $operation,
            'entity' => $entity,
            'keyword' => $keyword,
            'is_write' => $this->isWriteOperation($operation),
            'is_read' => $this->isReadOperation($operation),
            'has_id_hint' => $hasId,
            'has_name_hint' => $hasName,
            'target_label' => $entity ? $this->label($entity) : null,
        ];
    }

    public function detectOperation(string $message): string
    {
        $m = mb_strtolower(trim($message));

        foreach (['create', 'update', 'delete', 'restore', 'read', 'analyze'] as $operation) {
            foreach ($this->operations[$operation] ?? [] as $word) {
                $pattern = '/\b' . preg_quote(mb_strtolower($word), '/') . '\b/u';

                if (preg_match($pattern, $m)) {
                    if ($operation === 'read' && $this->looksLikeModifierOnly($m, $word)) {
                        continue;
                    }

                    return $operation;
                }
            }
        }

        if (preg_match('/\b(apakah\s+ada|ada\s+data|cari\s+data|temukan|lihat\s+data|tampilkan\s+data|data\s+itu|data\s+tersebut)\b/u', $m)) {
            return 'read';
        }

        if (preg_match('/\b(ubah|update|edit|ganti|perbarui|set|jadikan|menjadi)\b/u', $m)) {
            return 'update';
        }

        if (preg_match('/\b(hapus|delete|remove|destroy|buang)\b/u', $m)) {
            return 'delete';
        }

        return 'analyze';
    }

    public function detectEntity(string $message): ?string
    {
        $m = mb_strtolower(trim($message));

        foreach ($this->entities as $entity => $keywords) {
            foreach ($keywords as $word) {
                $pattern = '/\b' . preg_quote(mb_strtolower($word), '/') . '\b/u';

                if (preg_match($pattern, $m)) {
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
                $pattern = '/\b' . preg_quote(mb_strtolower($word), '/') . '\b/u';

                if (preg_match($pattern, $m)) {
                    return $word;
                }
            }
        }

        return null;
    }

    public function isWriteOperation(?string $operation): bool
    {
        return in_array($operation, ['create', 'update', 'delete', 'restore'], true);
    }

    public function isReadOperation(?string $operation): bool
    {
        return in_array($operation, ['read', 'analyze'], true);
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
            'ticket_status_logs' => 'Ticket Status Log',
            'ticket_comments' => 'Ticket Comment',
            'contacts' => 'Contact',
            'password_reset_otps' => 'Password Reset OTP',
            'sessions' => 'Session',
            default => $entity ? ucfirst(str_replace('_', ' ', $entity)) : 'Unknown',
        };
    }

    public function availableEntities(): array
    {
        return array_keys($this->entities);
    }

    public function availableOperations(): array
    {
        return array_keys($this->operations);
    }

    public function entityKeywords(string $entity): array
    {
        return $this->entities[$entity] ?? [];
    }

    public function entityAliases(): array
    {
        return $this->entities;
    }

    protected function looksLikeModifierOnly(string $message, string $word): bool
    {
        if ($word !== 'data') {
            return false;
        }

        return (bool) preg_match('/\b(data\s+hari\s+ini|hari\s+ini|sekarang|tanggal|bulan\s+ini|tahun\s+ini)\b/u', $message);
    }
}
