<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UserDataRetrieverService
{
    public function profile($user = null): string
    {
        $user = $this->resolveUser($user);

        if (! $user) {
            return 'Sesi login tidak ditemukan.';
        }

        $role = $this->resolveRoleName($user);
        $name = (string) data_get($user, 'name', '-');
        $username = (string) data_get($user, 'username', '-');
        $nim = (string) data_get($user, 'nim', '-');
        $nip = (string) data_get($user, 'nip', '-');
        $email = (string) data_get($user, 'email', '-');
        $phone = (string) data_get($user, 'no_hp', '-');
        $lastLogin = $this->formatDateTime(data_get($user, 'last_login_at'));

        $lines = [
            'Berikut data akun kamu yang boleh saya tampilkan:',
            "Nama: {$name}",
            "Username: {$username}",
            $role === 'dosen' ? "NIP: {$nip}" : "NIM: {$nim}",
            "Email: {$email}",
            "No. HP: {$phone}",
            "Role: {$role}",
            "Login terakhir: {$lastLogin}",
        ];

        return implode("\n", $lines);
    }

    public function loginStatus($user = null): string
    {
        $user = $this->resolveUser($user);

        if (! $user) {
            return 'Sesi login tidak ditemukan.';
        }

        $username = (string) data_get($user, 'username', '-');
        $role = $this->resolveRoleName($user);
        $lastLogin = $this->formatDateTime(data_get($user, 'last_login_at'));

        return "Status login akun kamu:\n"
            . "Username: {$username}\n"
            . "Role: {$role}\n"
            . "Login terakhir: {$lastLogin}\n"
            . "Password, OTP, dan data sensitif tidak ditampilkan.";
    }

    public function installation($user = null): string
    {
        $user = $this->resolveUser($user);

        if (! $user) {
            return 'Sesi login tidak ditemukan.';
        }

        $role = $this->resolveRoleName($user);
        $name = (string) data_get($user, 'name', 'akun kamu');

        return "Untuk instalasi/penyiapan akses {$role} atas nama {$name}, fokusnya hanya pada akses milikmu sendiri.\n"
            . "Langkah aman yang bisa saya bantu:\n"
            . "1. Pastikan login dengan username yang benar.\n"
            . "2. Cek data profil sendiri: NIM/NIP, email, dan nomor HP.\n"
            . "3. Gunakan link resmi yang ada di trusted_websites.\n"
            . "4. Jangan pakai data akun orang lain.";
    }

    public function trustedWebsiteHint(?string $question = null): string
    {
        if (! Schema::hasTable('trusted_websites')) {
            return 'Tabel trusted_websites belum tersedia.';
        }

        $query = DB::table('trusted_websites')->where('is_active', 1);

        if (is_string($question) && trim($question) !== '') {
            $keywords = $this->extractKeywords($question);

            if (! empty($keywords)) {
                $query->where(function ($q) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $like = '%' . $kw . '%';
                        $q->orWhere('name', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('url', 'like', $like);
                    }
                });
            }
        }

        $rows = $query->orderBy('name')->limit(8)->get(['name', 'url', 'description']);

        if ($rows->isEmpty()) {
            return 'Belum ada trusted website aktif di database.';
        }

        $out = ["Website tepercaya yang tersedia:"];
        foreach ($rows as $row) {
            $desc = trim((string) ($row->description ?? ''));
            $out[] = '- ' . $row->name . ' — ' . $row->url . ($desc !== '' ? " | {$desc}" : '');
        }

        return implode("\n", $out);
    }

    protected function resolveUser($user = null)
    {
        return $user ?: Auth::user();
    }

    protected function resolveRoleName($user): string
    {
        $roleName = strtolower(trim((string) data_get($user, 'role_name', '')));
        if ($roleName !== '') {
            return $roleName;
        }

        $relationRoleName = strtolower(trim((string) data_get($user, 'role.name', '')));
        if ($relationRoleName !== '') {
            return $relationRoleName;
        }

        if (isset($user->role_id)) {
            $role = DB::table('roles')->where('id', $user->role_id)->value('name');
            return strtolower(trim((string) $role));
        }

        return 'user';
    }

    protected function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    protected function extractKeywords(string $question): array
    {
        $parts = preg_split('/[\s,;:.\-\(\)\/\\\\]+/u', mb_strtolower($question)) ?: [];
        $parts = array_values(array_unique(array_filter(array_map('trim', $parts))));
        $parts = array_filter($parts, fn($v) => mb_strlen($v) >= 3);

        return array_slice($parts, 0, 6);
    }
}
