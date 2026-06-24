<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Throwable;

class KeywordService
{
    public function detectProfanityWarning(string $message): string
    {
        $warning = '';

        foreach ((array) config('jenis.profanity', []) as $bad) {
            $bad = trim((string) $bad);

            if ($bad !== '' && str_contains(mb_strtolower($message), mb_strtolower($bad))) {
                $warning = 'Tolong untuk tidak menggunakan kata atau kalimat kasar ya. ';
                break;
            }
        }

        return $warning;
    }

    public function getGreetingPrefix(string $message): string
    {
        $glist = $this->keywordGroup('greeting');

        foreach ($glist as $g) {
            if ($g !== '' && str_starts_with(mb_strtolower(trim($message)), mb_strtolower($g))) {
                return $this->timeGreeting();
            }
        }

        return '';
    }

    public function isGreetingOnly(string $message): bool
    {
        $m = mb_strtolower(trim($message));

        foreach ($this->keywordGroup('greeting') as $g) {
            if ($m === mb_strtolower(trim((string) $g))) {
                return true;
            }
        }

        return false;
    }

    public function isGreeting(string $message): bool
    {
        return $this->isGreetingOnly($message);
    }

    public function timeGreeting(): string
    {
        $h = (int) Carbon::now('Asia/Jakarta')->format('H');

        if ($h >= 4 && $h <= 11) {
            return 'Selamat Pagi';
        }

        if ($h >= 11 && $h <= 15) {
            return 'Selamat Siang';
        }

        if ($h >= 15 && $h <= 18) {
            return 'Selamat Sore';
        }

        return 'Selamat Malam';
    }

    public function isAskingTime(string $message): bool
    {
        $m = mb_strtolower($message);

        if ($this->looksLikeDataRequest($m)) {
            return false;
        }

        return (bool) preg_match(
            '/\b(hari ini hari apa|hari apa|jam berapa|sekarang jam berapa|sekarang pukul berapa|sekarang jam|sekarang pukul|tanggal berapa|waktu saat ini|pukul berapa)\b/u',
            $m
        );
    }

    public function isTimeModifier(string $message): bool
    {
        $m = mb_strtolower($message);

        return (bool) preg_match('/\b(hari ini|sekarang|bulan ini|tahun ini|kemarin|besok)\b/u', $m);
    }

    protected function looksLikeDataRequest(string $message): bool
    {
        return (bool) preg_match(
            '/\b(data|tampilkan|lihat|daftar|cari|cek|detail|apakah ada|ada|rekap|ticket|tiket|user|pengguna|software|role|perbaikan|penginstalan|login log|ai log|ai task|ai recommendation|notification|notifikasi)\b/u',
            $message
        );
    }

    public function handleTimeQuery(): string
    {
        $now = Carbon::now('Asia/Jakarta');
        $hari = $now->translatedFormat('l');
        $tanggal = $now->format('d F Y');
        $jam = $now->format('H:i');

        return "Waktu saat ini (WIB): {$hari}, {$tanggal} pukul {$jam}";
    }

    public function isAskingSummary(string $message): bool
    {
        return (bool) preg_match('/\b(kesimpulan|ringkasan|ringkas|summary|rekap percakapan|apa yang kita bahas)\b/u', mb_strtolower($message));
    }

    public function detectSelfQuery(string $message): bool
    {
        return $this->detectSelfQueryType($message) !== null;
    }

    public function detectSelfQueryType(string $message): ?string
    {
        $m = mb_strtolower($message);
        $config = config('keywords.self_query', []);

        $hasFirstPerson = $this->hasFirstPersonReference($m);

        if (! $hasFirstPerson) {
            return null;
        }

        foreach (($config['fields'] ?? []) as $field => $group) {
            foreach (($group['trigger'] ?? []) as $t) {
                $pattern = '/\b' . preg_quote(mb_strtolower((string) $t), '/') . '\b/u';
                if (preg_match($pattern, $m)) {
                    return 'self_' . $field;
                }
            }
        }

        foreach (($config['trigger'] ?? []) as $t) {
            $pattern = '/\b' . preg_quote(mb_strtolower((string) $t), '/') . '\b/u';
            if (preg_match($pattern, $m)) {
                return 'self_all';
            }
        }

        return null;
    }

    public function handleSelfQuery(string $message, $user, string $role): string
    {
        $m = mb_strtolower($message);
        $name = data_get($user, 'name') ?? data_get($user, 'nama') ?? 'tidak tersedia';
        $username = data_get($user, 'username') ?? 'tidak tersedia';

        if (preg_match('/\b(password|kata sandi|sandi|pw)\b/u', $m)) {
            return 'Maaf, data tersebut tidak bisa ditampilkan.';
        }

        if (preg_match('/\b(username|user ?name|nama pengguna)\b/u', $m)) {
            return "Username Anda adalah: {$username}";
        }

        if (preg_match('/\b(nama saya|siapa nama saya|siapa saya|siapa namaku|namaku|nama)\b/u', $m)) {
            return "Nama Anda adalah: {$name}";
        }

        if (preg_match('/\b(role saya|status saya|role|peran saya|peran|jabatan)\b/u', $m)) {
            return "Role Anda adalah: {$role}";
        }

        if (preg_match('/\b(profil saya|data saya|tentang saya|info tentang saya|akun saya|tentangku)\b/u', $m)) {
            return "Nama: {$name}\nUsername: {$username}\nRole: {$role}";
        }

        return 'Maaf, perintah belum dipahami.';
    }

    public function detectBotQuery(string $message): bool
    {
        return $this->detectBotQueryType($message) !== null;
    }

    public function handleBotQuery(string $message): string
    {
        $type = $this->detectBotQueryType($message);

        return match ($type) {
            'bot_identity' => 'Saya adalah AI Admin TechNoteApp 2.0.',
            'bot_about' => 'Saya adalah AI Admin TechNoteApp 2.0 yang membaca, menganalisis, dan membantu pengelolaan data internal.',
            'bot_function' => 'Fungsi saya adalah membantu admin membaca data, menganalisis kondisi sistem, dan menyusun ringkasan atau rekomendasi.',
            'bot_task' => 'Tugas saya adalah membantu admin dalam membaca log, tiket, rekap, software, maintenance, dan data internal lain.',
            default => 'Maaf, saya tidak memahami pertanyaan tersebut.',
        };
    }

    public function detectBotQueryTypePublic(string $message): ?string
    {
        return $this->detectBotQueryType($message);
    }

    public function detectCampusQuery(string $message): bool
    {
        $m = mb_strtolower($message);

        return (bool) preg_match('/\b(stmik|karang baru|smkn?\s*1\s*karang\s*baru|kampus|triguna dharma|website resmi|trusted website|situs resmi)\b/u', $m);
    }

    public function fallbackReply(string $question, array $intent): string
    {
        return match ($intent['action'] ?? 'analyze') {
            'read' => 'Saya sudah membaca permintaan Anda, tetapi saya perlu target data yang lebih spesifik.',
            'create' => 'Saya bisa menyiapkan draft data, tetapi eksekusi tetap perlu validasi field.',
            'update' => 'Saya bisa menyusun perubahan yang disarankan, tetapi target atau field harus jelas.',
            'delete' => 'Penghapusan data perlu target yang eksplisit dan aman.',
            'restore' => 'Saya perlu target data yang sudah terhapus agar bisa dipulihkan.',
            default => 'Saya sudah menganalisis konteks sistem. Tambahkan target data agar hasilnya lebih presisi.',
        };
    }

    public function detectSelfQueryFromConfig(string $message): ?string
    {
        return $this->detectSelfQueryType($message);
    }

    public function monthNameMap(): array
    {
        return [
            'jan' => 1, 'januari' => 1,
            'feb' => 2, 'februari' => 2,
            'mar' => 3, 'maret' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5, 'mei' => 5,
            'jun' => 6, 'juni' => 6,
            'jul' => 7, 'juli' => 7,
            'aug' => 8, 'agustus' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'oct' => 10, 'okt' => 10, 'oktober' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'des' => 12, 'desember' => 12, 'december' => 12,
        ];
    }

    public function hasFutureTimeKeyword(string $message): bool
    {
        $m = mb_strtolower($message);

        foreach (['bulan depan','tahun depan','minggu depan','hari depan','tanggal depan','besok lusa','lusa','next month','next year'] as $k) {
            if (str_contains($m, $k)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(depan|berikutnya|selanjutnya)\b/u', $m);
    }

    public function resolveDateFromMessage(string $message): array
    {
        if ($this->hasFutureTimeKeyword($message)) {
            return ['__FUTURE__', '__FUTURE__'];
        }

        $now = Carbon::now('Asia/Jakarta');
        $m = mb_strtolower($message);

        if (preg_match('/\b(\d+)\s*hari\s*(yang|yg\s*)?lalu\b/u', $m, $match)) {
            $n = (int) $match[1];
            $date = $now->copy()->subDays($n);
            return [$date->format('Y-m-d'), $date->format('d/m/Y')];
        }

        if (preg_match('/\b(\d+)\s*minggu\s*(yang|yg\s*)?lalu\b/u', $m, $match)) {
            $n = (int) $match[1];
            $date = $now->copy()->subWeeks($n);
            return [$date->format('Y-m-d'), $date->format('d/m/Y')];
        }

        if (preg_match('/\b(\d+)\s*bulan\s*(yang|yg\s*)?lalu\b/u', $m, $match)) {
            $n = (int) $match[1];
            $date = $now->copy()->subMonths($n);
            return [$date->format('Y-m-d'), $date->format('d/m/Y')];
        }

        if (preg_match('/\b(\d+)\s*tahun\s*(yang|yg\s*)?lalu\b/u', $m, $match)) {
            $n = (int) $match[1];
            $date = $now->copy()->subYears($n);
            return [$date->format('Y-m-d'), $date->format('d/m/Y')];
        }

        foreach (['hari ini' => 0, 'sekarang' => 0, 'kemarin' => -1, 'besok' => 1] as $keyword => $offset) {
            if (str_contains($m, $keyword)) {
                $date = $now->copy()->addDays($offset);
                return [$date->format('Y-m-d'), $date->format('d/m/Y')];
            }
        }

        return [$now->format('Y-m-d'), $now->format('d/m/Y')];
    }

    public function isBlockedMessage(string $message): bool
    {
        $m = mb_strtolower($message);

        $blocked = [
            'password',
            'kata sandi',
            'sandi',
            'api key',
            'token rahasia',
            'secret key',
        ];

        foreach ($blocked as $word) {
            if (str_contains($m, $word)) {
                return false;
            }
        }

        return false;
    }

    public function resolveRoleName($user): string
    {
        $role = data_get($user, 'role.name')
            ?? data_get($user, 'role.status')
            ?? data_get($user, 'role')
            ?? 'admin';

        return mb_strtolower(trim((string) $role));
    }

    public function canAiWrite(): bool
    {
        return (bool) config('system.ai_write_enabled', true);
    }

    public function canAiWriteDirectly(): bool
    {
        return (bool) config('system.ai_write_direct', true);
    }

    public function needsAiAdminApproval(): bool
    {
        return (bool) config('system.ai_admin_approval', false);
    }

    public function isAntiAiModeEnabled(): bool
    {
        return (bool) config('system.anti_ai_mode', false);
    }

    protected function keywordGroup(string $name): array
    {
        $config = config('keywords.' . $name, config('keyword.' . $name, []));

        return is_array($config['trigger'] ?? null) ? $config['trigger'] : [];
    }

    protected function hasFirstPersonReference(string $message): bool
    {
        $config = config('keywords.self_query', []);
        $triggerWords = array_merge(
            $config['first_person_trigger'] ?? [],
            ['saya', 'aku', 'gue', 'gw', 'profilku', 'punyaku', 'milikku']
        );

        foreach ($triggerWords as $word) {
            $word = mb_strtolower(trim((string) $word));
            if ($word !== '' && str_contains($message, $word)) {
                return true;
            }
        }

        return false;
    }

    protected function detectBotQueryType(string $message): ?string
    {
        $m = mb_strtolower($message);

        if (preg_match('/\b(siapa kamu|siapa anda|kamu siapa|anda siapa)\b/u', $m)) {
            return 'bot_identity';
        }

        if (preg_match('/\b(tentang kamu|tentang anda|mengenai anda|mengenai bot|tentang bot)\b/u', $m)) {
            return 'bot_about';
        }

        if (preg_match('/\b(fungsi kamu|fungsi anda|fungsi bot|fungsi mu)\b/u', $m)) {
            return 'bot_function';
        }

        if (preg_match('/\b(tugas kamu|tugas anda|apa tugasmu|apa tugas anda)\b/u', $m)) {
            return 'bot_task';
        }

        return null;
    }
}
