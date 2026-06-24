<?php

namespace App\Services;

class FallbackHandlerService
{
    public function clarify(array $intent): string
    {
        $purpose = $intent['purpose'] ?? 'chat';

        return match ($purpose) {
            'edit_text' => 'Teks yang ingin diedit belum terlihat jelas. Kirim teksnya dalam tanda kutip atau tempelkan langsung.',
            'system_action' => 'Aksi sistemnya belum jelas. Sebutkan objek data, operasi, dan target yang dimaksud.',
            'data_lookup' => 'Data yang dicari belum spesifik. Sebutkan nama data, ID, atau kata kunci yang lebih tepat.',
            'summary' => 'Ringkasan yang diminta masih terlalu umum. Sebutkan periode atau topik yang ingin diringkas.',
            'help' => 'Saya bisa bantu CRUD, baca data, ringkasan, dan aksi sistem. Sebutkan entity dan tujuan yang jelas.',
            default => 'Maksud permintaan masih belum jelas. Kirim detail yang lebih spesifik agar saya bisa memprosesnya.',
        };
    }

    public function dataNotFound(array $intent): string
    {
        $entity = $intent['entity'] ?? null;

        if ($entity) {
            return 'Data ' . $entity . ' tidak ditemukan pada sumber resmi yang tersedia.';
        }

        return 'Data tidak ditemukan pada sumber resmi yang tersedia.';
    }

    public function blocked(string $reason = 'policy'): string
    {
        return match ($reason) {
            'anti_ai_mode' => 'Permintaan ini diblokir karena Anti AI Mode aktif.',
            'role_tidak_boleh_write' => 'Role Anda tidak memiliki izin untuk melakukan aksi tulis.',
            'source_tidak_diizinkan' => 'Sumber yang diminta tidak diizinkan oleh kebijakan sistem.',
            'purpose_tidak_diizinkan' => 'Jenis permintaan ini tidak diizinkan untuk role saat ini.',
            'mass_action_blocked' => 'Aksi massal diblokir untuk mencegah perubahan seluruh data sekaligus.',
            default => 'Permintaan ini tidak diizinkan oleh kebijakan sistem.',
        };
    }

    public function fallback(array $intent): string
    {
        $purpose = $intent['purpose'] ?? 'chat';

        return match ($purpose) {
            'summary' => 'Ringkasan belum bisa dibuat karena konteks belum cukup.',
            'data_lookup' => 'Data belum cukup untuk dijawab secara pasti.',
            'system_action' => 'Saya tidak akan menebak aksi sistem tanpa konteks yang jelas.',
            'edit_text' => 'Saya butuh teks sumber untuk melakukan edit dengan aman.',
            'help' => 'Bisa. Sebutkan entity, operasi, dan detail data yang diminta.',
            default => 'Saya belum cukup yakin untuk menjawab. Silakan beri detail tambahan.',
        };
    }

    public function genericLowConfidence(): string
    {
        return 'Saya belum cukup yakin untuk menjawab secara pasti. Silakan beri detail tambahan atau pilih kategori yang lebih spesifik.';
    }
}
