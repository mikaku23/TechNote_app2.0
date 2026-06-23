<?php

namespace App\Services;

use Carbon\Carbon;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

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
        $glist = config('keywords.greeting.trigger')
            ?? config('keywords.greeting')
            ?? config('keyword.greeting')
            ?? [];

        foreach ((array) $glist as $g) {
            $g = mb_strtolower((string) $g);

            if ($g !== '' && str_starts_with(mb_strtolower(trim($message)), $g)) {
                return $this->timeGreeting();
            }
        }

        return '';
    }

    public function isGreetingOnly(string $message): bool
    {
        $glist = config('keywords.greeting.trigger')
            ?? config('keywords.greeting')
            ?? config('keyword.greeting')
            ?? [];

        foreach ((array) $glist as $g) {
            if (trim(mb_strtolower($message)) === trim(mb_strtolower((string) $g))) {
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
        return (bool) preg_match(
            '/\b(hari ini hari apa|hari apa|sekarang jam|jam berapa|tanggal berapa|hari ini)\b/u',
            mb_strtolower($message)
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
        return (bool) preg_match(
            '/\b(kesimpulan|apa yang kita bahas|ringkasan|simpulkan|summary)\b/u',
            mb_strtolower($message)
        );
    }

    public function summarizeConversation($user, string $promptSuffix, OpenRouterService $openRouterService): string
    {
        $key = 'chat_history_' . data_get($user, 'id');
        $history = Session::get($key, []);

        if (empty($history)) {
            return 'Belum ada percakapan sebelumnya untuk disimpulkan.';
        }

        $text = '';
        foreach ($history as $h) {
            $text .= 'User: ' . ($h['in'] ?? '') . "\n";
            $text .= 'Bot: ' . ($h['out'] ?? '') . "\n";
        }

        $system = 'Anda adalah asisten ringkas untuk sistem helpdesk STMIK. Buatkan ringkasan singkat (3-4 kalimat) dari percakapan berikut dan berikan rekomendasi tindakan jika perlu.';
        $userPrompt = "Percakapan:\n{$text}\nInstruksi: Buat ringkasan singkat dan poin tindakan.";

        $aiResp = $openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ], 'deepseek/deepseek-chat', 25, 500, 0.2);

        return $aiResp ?: 'Maaf, gagal membuat ringkasan saat ini.';
    }

    public function detectSelfQuery(string $message): bool
    {
        return $this->detectSelfQueryFromConfig($message) !== null;
    }

    public function handleSelfQuery(string $message, $user, string $role): string
    {
        $m = mb_strtolower($message);
        $name = data_get($user, 'name') ?? data_get($user, 'nama') ?? 'tidak tersedia';
        $username = data_get($user, 'username') ?? 'tidak tersedia';

        if (preg_match('/\b(password|kata sandi|sandi|pw)\b/u', $m)) {
            return 'Maaf, data tersebut tidak bisa ditampilkan.';
        }

        if (preg_match('/\b(username|user ?name|nama pengguna|user)\b/u', $m)) {
            return "Username Anda adalah: {$username}";
        }

        if (preg_match('/\b(nama saya|siapa nama saya|siapa saya|siapa namaku|namaku|nama)\b/u', $m)) {
            return "Nama Anda adalah: {$name}";
        }

        if (preg_match('/\b(role saya|status saya|role|peran saya|peran|jabatan)\b/u', $m)) {
            return "Role Anda adalah: {$role}";
        }

        if (preg_match('/\b(profil saya|data saya|tentang saya|info tentang saya|tentangku)\b/u', $m)) {
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

    public function builtinIntents(): array
    {
        return [
            'greeting',
            'contact',
            'profil',
            'ticket',
            'rekap',
            'penginstalan',
            'penginstalan_status',
            'perbaikan',
            'perbaikan_status',
            'login_log',
            'ai_log',
            'ai_task',
            'ai_recommendation',
            'software',
            'maintenance',
            'stmik',
            'trusted_website',
            'bot_identity',
            'bot_about',
            'bot_function',
            'bot_task',
            'time_query',
            'summarize',
            'self_query',
            'multi_intent',
            'analyze',
        ];
    }

    public function detectIntent(string $question): array
    {
        $q = mb_strtolower(trim($question));

        $map = [
            'greeting' => ['halo', 'hai', 'pagi', 'siang', 'sore', 'malam'],
            'contact' => ['contact', 'kontak', 'kritik', 'saran', 'pesan'],
            'profil' => ['profil saya', 'profil', 'data saya', 'info saya'],
            'ticket' => ['ticket', 'tiket', 'antrian', 'queue'],
            'rekap' => ['rekap', 'ringkasan', 'statistik', 'summary'],
            'penginstalan' => ['penginstalan', 'install', 'instal', 'instalasi'],
            'penginstalan_status' => ['status penginstalan', 'penginstalan terakhir', 'instalasi terakhir'],
            'perbaikan' => ['perbaikan', 'repair', 'servis', 'service'],
            'perbaikan_status' => ['status perbaikan', 'perbaikan terakhir'],
            'login_log' => ['login log', 'log login', 'log masuk', 'online offline'],
            'ai_log' => ['ai log', 'log ai', 'riwayat ai'],
            'ai_task' => ['ai task', 'task ai', 'tugas ai'],
            'ai_recommendation' => ['rekomendasi ai', 'ai rekom', 'ai recommendation'],
            'software' => ['software', 'aplikasi'],
            'maintenance' => ['maintenance', 'mode maintenance', 'pemeliharaan'],
            'stmik' => ['stmik', 'karang baru', 'smkn 1 karang baru', 'smkn1 karang baru'],
            'trusted_website' => ['trusted website', 'website terpercaya', 'situs terpercaya'],
            'bot_identity' => ['siapa kamu', 'siapa anda', 'kamu siapa', 'anda siapa'],
            'bot_about' => ['tentang kamu', 'tentang anda', 'tentang bot', 'mengenai bot'],
            'bot_function' => ['fungsi kamu', 'fungsi anda', 'fungsi bot'],
            'bot_task' => ['tugas kamu', 'tugas anda', 'apa tugasmu'],
            'time_query' => ['hari ini hari apa', 'hari apa', 'jam berapa', 'sekarang jam', 'tanggal berapa'],
        ];

        foreach ($this->builtinIntents() as $intent) {
            foreach ($map[$intent] ?? [] as $word) {
                if ($word !== '' && str_contains($q, mb_strtolower($word))) {
                    return [
                        'action' => $intent,
                        'keyword' => $word,
                    ];
                }
            }
        }

        return [
            'action' => 'analyze',
            'keyword' => null,
        ];
    }

    public function detectAllIntents(string $question): array
    {
        $q = mb_strtolower(trim($question));
        $found = [];

        $priority = [
            'penginstalan_status',
            'perbaikan_status',
            'login_log',
            'ai_log',
            'ai_task',
            'ai_recommendation',
            'maintenance',
            'ticket',
            'rekap',
            'penginstalan',
            'perbaikan',
            'software',
            'stmik',
            'trusted_website',
            'profil',
            'contact',
            'bot_identity',
            'bot_about',
            'bot_function',
            'bot_task',
            'time_query',
            'greeting',
        ];

        foreach ($priority as $intent) {
            $detected = $this->detectIntent($q);
            if (($detected['action'] ?? null) === $intent) {
                $found[] = $intent;
            }
        }

        $keywords = config('keywords', []);
        foreach ($keywords as $intent => $group) {
            if (($group['type'] ?? 'intent') !== 'intent') {
                continue;
            }

            foreach (($group['trigger'] ?? []) as $trigger) {
                $trigger = (string) $trigger;

                if ($trigger !== '' && str_contains($q, mb_strtolower($trigger))) {
                    $found[] = $intent;
                    break;
                }
            }
        }

        return array_values(array_unique($found));
    }

    public function intentExists(string $intent): bool
    {
        return in_array($intent, $this->builtinIntents(), true)
            || array_key_exists($intent, config('keywords', []));
    }

    public function isIntentAllowedForRole(string $role, string $intent): bool
    {
        if ($role === 'admin') {
            return true;
        }

        $common = [
            'greeting',
            'contact',
            'profil',
            'rekap',
            'stmik',
            'trusted_website',
            'bot_identity',
            'bot_about',
            'bot_function',
            'bot_task',
            'time_query',
        ];

        $student = array_merge($common, ['ticket', 'penginstalan', 'penginstalan_status']);
        $lecturer = array_merge($common, ['ticket', 'perbaikan', 'perbaikan_status']);

        return match ($role) {
            'mahasiswa' => in_array($intent, $student, true),
            'dosen'     => in_array($intent, $lecturer, true),
            default     => false,
        };
    }

    public function handleIntent(
        string $intent,
        $user,
        string $question,
        array $context,
        string $role,
        bool $antiMode,
        bool $canWrite,
        OpenRouterService $openRouterService,
        bool $fromAi = false
    ): string {
        return match ($intent) {
            'greeting' => 'Halo, ada yang bisa dibantu?',
            'contact' => 'Pesan dan kritik dapat dipantau melalui notifikasi atau modul kontak yang tersedia di sistem.',
            'profil' => $this->handleProfile($user, $role),
            'ticket' => $this->handleTicketSummary($question),
            'rekap' => $this->handleRekap($question),
            'penginstalan' => $this->handlePenginstalan($user, $question, $role),
            'penginstalan_status' => $this->handlePenginstalanStatus($user, $role),
            'perbaikan' => $this->handlePerbaikan($user, $question, $role),
            'perbaikan_status' => $this->handlePerbaikanStatus($user, $role),
            'login_log' => $this->handleLoginLog($question),
            'ai_log' => $this->handleAiLog($question),
            'ai_task' => $this->handleAiTask($question),
            'ai_recommendation' => $this->handleAiRecommendation($question),
            'software' => $this->handleSoftware($question),
            'maintenance' => $this->handleMaintenance($question),
            'stmik', 'trusted_website' => 'Gunakan trusted website resmi untuk informasi kampus.',
            'bot_identity' => 'Saya adalah AI Admin TechNoteApp 2.0.',
            'bot_about' => 'Saya adalah AI Admin TechNoteApp 2.0 yang membaca, menganalisis, dan membantu pengelolaan data internal.',
            'bot_function' => 'Fungsi saya adalah membantu admin membaca data, menganalisis kondisi sistem, dan menyusun ringkasan atau rekomendasi.',
            'bot_task' => 'Tugas saya adalah membantu admin dalam membaca log, tiket, rekap, software, maintenance, dan data internal lain.',
            'time_query' => $this->handleTimeQuery(),
            default => $this->generateAnswerWithAI($user, $question, $openRouterService),
        };
    }

    public function generateAnswerWithAI($user, string $message, OpenRouterService $openRouterService): string
    {
        $historyKey = 'chat_history_' . data_get($user, 'id');
        $history = Session::get($historyKey, []);
        $recent = array_slice($history, -5);

        $context = '';
        foreach ($recent as $h) {
            $context .= 'User: ' . ($h['in'] ?? '') . "\nBot: " . ($h['out'] ?? '') . "\n";
        }

        $role = $this->resolveRoleName($user);

        $system = "
Anda adalah asisten teknis resmi STMIK Triguna Dharma.

ATURAN WAJIB:
- Role user saat ini: {$role}
- Jangan memberikan data yang tidak diminta secara spesifik.
- Jangan mengarang status, tanggal, atau data.
- Jawab singkat, maksimal 3 kalimat.
- Jika informasi kurang, minta user menyebutkan detail yang dibutuhkan.
- Jika pertanyaan di luar topik, jawab persis: tidak tersedia

Pembatasan role:
- mahasiswa → hanya penginstalan dan status miliknya
- dosen → hanya perbaikan dan status miliknya
- admin → rekap, log, tiket, software, maintenance, analisis, dan informasi umum
";

        $userPrompt = "
Context percakapan sebelumnya:
{$context}

Pertanyaan user:
{$message}

Jawab sesuai aturan di atas.
";

        $resp = $openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ], 'deepseek/deepseek-chat', 30, 600, 0.2);

        if (! $resp) {
            return 'Maaf, saya belum dapat menjawab. Silakan tanyakan lebih spesifik.';
        }

        if (str_word_count($resp) > 40) {
            return $this->ringkasJawaban($resp, 40);
        }

        return trim($resp);
    }

    public function ringkasJawaban(string $text, int $maxWords = 30): string
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];

        if (count($words) <= $maxWords) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $maxWords)) . '...';
    }

    public function resolveRoleName($user): string
    {
        $role = data_get($user, 'role.name')
            ?? data_get($user, 'role.status')
            ?? data_get($user, 'role')
            ?? 'admin';

        return mb_strtolower(trim((string) $role));
    }

    public function isBlockedMessage(string $message): bool
    {
        $m = mb_strtolower($message);

        $blocked = [
            'password',
            'kata sandi',
            'sandi',
            'secret',
            'api key',
            'token',
            'security answer',
            'hapus semua',
            'drop table',
            'delete database',
        ];

        foreach ($blocked as $word) {
            if ($word !== '' && str_contains($m, $word)) {
                return true;
            }
        }

        return false;
    }

    public function isAiAdminPermissionEnabled(): bool
    {
        $setting = DB::table('system_settings')
            ->where('key', 'ai_admin_permission')
            ->value('value');

        return $this->castSettingValue($setting);
    }

    public function isAntiAiModeEnabled(): bool
    {
        $setting = DB::table('system_settings')
            ->where('key', 'anti_ai_mode')
            ->value('value');

        return $this->castSettingValue($setting);
    }

    /**
     * Mode aman untuk write:
     * - false kalau anti_ai_mode ON
     * - true kalau anti_ai_mode OFF
     */
    public function canAiWrite(): bool
    {
        return ! $this->isAntiAiModeEnabled();
    }

    /**
     * CRUD boleh, tapi masih butuh approval.
     * Syarat:
     * - anti_ai_mode OFF
     * - ai_admin_permission ON
     */
    public function needsAiAdminApproval(): bool
    {
        return ! $this->isAntiAiModeEnabled() && $this->isAiAdminPermissionEnabled();
    }

    /**
     * CRUD boleh langsung jalan.
     * Syarat:
     * - anti_ai_mode OFF
     * - ai_admin_permission OFF
     */
    public function canAiWriteDirectly(): bool
    {
        return ! $this->isAntiAiModeEnabled() && ! $this->isAiAdminPermissionEnabled();
    }

    public function approvalReply(string $action): string
    {
        return match ($action) {
            'create' => 'Permission AI Admin aktif. Pembuatan data memerlukan izin/approval.',
            'update' => 'Permission AI Admin aktif. Perubahan data memerlukan izin/approval.',
            'delete' => 'Permission AI Admin aktif. Penghapusan data memerlukan izin/approval.',
            default  => 'Permission AI Admin aktif. Aksi ini memerlukan izin/approval.',
        };
    }

    public function blockedReply(string $action, bool $antiMode, bool $canWrite): string
    {
        if ($antiMode) {
            return match ($action) {
                'create' => 'Anti AI Mode aktif. CRUD diblok total. Saya hanya boleh membaca dan menganalisis.',
                'update' => 'Anti AI Mode aktif. CRUD diblok total. Saya hanya boleh membaca dan menganalisis.',
                'delete' => 'Anti AI Mode aktif. CRUD diblok total. Saya hanya boleh membaca dan menganalisis.',
                default  => 'Anti AI Mode aktif. Aksi ini diblok total.',
            };
        }

        if (! $canWrite) {
            return $this->approvalReply($action);
        }

        return 'Aksi ini diblokir.';
    }

    public function isWriteAction(?string $action): bool
    {
        if (! is_string($action) || trim($action) === '') {
            return false;
        }

        $action = mb_strtolower(trim($action));

        return (bool) preg_match('/^(create|update|delete)(_|$)/', $action);
    }

    public function fallbackReply(string $question, array $intent): string
    {
        return match ($intent['action'] ?? 'analyze') {
            'read'   => 'Saya sudah membaca permintaan Anda, tetapi saya perlu konteks data yang lebih spesifik.',
            'create' => 'Saya bisa menyiapkan draft data, tetapi eksekusi tetap perlu validasi admin.',
            'update' => 'Saya bisa menyusun perubahan yang disarankan, tetapi target field harus jelas.',
            'delete' => 'Penghapusan data perlu konfirmasi eksplisit karena berdampak ke integritas data.',
            default  => 'Saya sudah menganalisis konteks sistem. Tambahkan target data agar rekomendasinya lebih presisi.',
        };
    }

    public function detectSelfQueryFromConfig(string $message): ?string
    {
        $m = mb_strtolower($message);

        $direct = [
            'self_password'  => ['\b(password|kata sandi|sandi|pw)\b'],
            'self_username'  => ['\b(username|user ?name|nama pengguna|user)\b'],
            'self_name'      => ['\b(nama saya|siapa nama saya|siapa saya|siapa namaku|namaku|nama)\b'],
            'self_role'      => ['\b(role saya|status saya|role|peran saya|peran|jabatan)\b'],
            'self_all'       => ['\b(profil saya|data saya|tentang saya|info tentang saya|tentangku)\b'],
        ];

        foreach ($direct as $intent => $patterns) {
            foreach ($patterns as $pat) {
                if (preg_match("/{$pat}/u", $m)) {
                    return $intent;
                }
            }
        }

        $config = config('self_query');

        if ($config && ! empty($config['fields']) && is_array($config['fields'])) {
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
        }

        if ($config && ! empty($config['trigger']) && is_array($config['trigger'])) {
            foreach ($config['trigger'] as $t) {
                $tLower = mb_strtolower((string) $t);
                $pattern = '/\b' . preg_quote($tLower, '/') . '\b/u';

                if (preg_match($pattern, $m)) {
                    if (str_contains($tLower, 'nama')) return 'self_name';
                    if (str_contains($tLower, 'username') || str_contains($tLower, 'user')) return 'self_username';
                    if (str_contains($tLower, 'role') || str_contains($tLower, 'peran') || str_contains($tLower, 'jabatan')) return 'self_role';
                    if (str_contains($tLower, 'password') || str_contains($tLower, 'kata sandi') || str_contains($tLower, 'sandi')) return 'self_password';
                    return 'self_all';
                }
            }
        }

        return null;
    }

    public function monthNameMap(): array
    {
        return [
            'jan' => 1,
            'januari' => 1,
            'feb' => 2,
            'februari' => 2,
            'mar' => 3,
            'maret' => 3,
            'apr' => 4,
            'april' => 4,
            'may' => 5,
            'mei' => 5,
            'jun' => 6,
            'juni' => 6,
            'jul' => 7,
            'juli' => 7,
            'aug' => 8,
            'agustus' => 8,
            'august' => 8,
            'sep' => 9,
            'september' => 9,
            'oct' => 10,
            'okt' => 10,
            'oktober' => 10,
            'october' => 10,
            'nov' => 11,
            'november' => 11,
            'dec' => 12,
            'des' => 12,
            'desember' => 12,
            'december' => 12,
        ];
    }

    public function hasFutureTimeKeyword(string $message): bool
    {
        $m = mb_strtolower($message);

        $futureKeywords = [
            'bulan depan',
            'tahun depan',
            'minggu depan',
            'hari depan',
            'tanggal depan',
            'besok lusa',
            'lusa',
            'next month',
            'next year',
        ];

        foreach ($futureKeywords as $k) {
            if (str_contains($m, $k)) {
                return true;
            }
        }

        if (preg_match('/\b(depan|berikutnya|selanjutnya)\b/u', $m)) {
            return true;
        }

        return false;
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

            if ($n >= 1 && $n <= 30) {
                $d = $now->copy()->subDays($n);
                return [$d->startOfDay(), $d->endOfDay()];
            }
        }

        if ($d = $this->parseSpecificDate($m)) {
            return [$d->copy()->startOfDay(), $d->copy()->endOfDay()];
        }

        if ($monthRange = $this->parseMonthYearFromMessage($message)) {
            return [$monthRange['start'], $monthRange['end']];
        }

        if ($yearRange = $this->parseYearFromMessage($message)) {
            return [$yearRange['start'], $yearRange['end']];
        }

        if (str_contains($m, 'hari ini') || str_contains($m, 'sekarang')) {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }

        if (str_contains($m, 'kemarin')) {
            $y = $now->copy()->subDay();
            return [$y->startOfDay(), $y->endOfDay()];
        }

        if (str_contains($m, 'besok')) {
            $t = $now->copy()->addDay();
            return [$t->startOfDay(), $t->endOfDay()];
        }

        return [null, null];
    }

    public function parseSpecificDate(string $message): ?Carbon
    {
        $m = mb_strtolower($message);

        if (preg_match('/\b(\d{1,2})[\/\-\.\s](\d{1,2})[\/\-\.\s](\d{4})\b/u', $m, $p)) {
            try {
                return Carbon::createFromDate((int) $p[3], (int) $p[2], (int) $p[1], 'Asia/Jakarta');
            } catch (Throwable $e) {
                return null;
            }
        }

        if (preg_match('/\b(\d{1,2})\s+([a-z]+)\s+(\d{4})\b/ui', $m, $p)) {
            $day = (int) $p[1];
            $monthName = mb_strtolower($p[2]);
            $year = (int) $p[3];

            foreach ($this->monthNameMap() as $key => $num) {
                if (str_contains($monthName, $key)) {
                    try {
                        return Carbon::createFromDate($year, $num, $day, 'Asia/Jakarta');
                    } catch (Throwable $e) {
                        return null;
                    }
                }
            }
        }

        if (preg_match('/\b(\d{1,2})\s+([a-z]+)\b/ui', $m, $p)) {
            $day = (int) $p[1];
            $monthName = mb_strtolower($p[2]);

            foreach ($this->monthNameMap() as $key => $num) {
                if (str_contains($monthName, $key)) {
                    try {
                        $year = Carbon::now('Asia/Jakarta')->year;
                        return Carbon::createFromDate($year, $num, $day, 'Asia/Jakarta');
                    } catch (Throwable $e) {
                        return null;
                    }
                }
            }
        }

        if (preg_match('/\b(\d{1,2})\s+(\d{1,2})\s+(\d{4})\b/u', $m, $p)) {
            try {
                return Carbon::createFromDate((int) $p[3], (int) $p[2], (int) $p[1], 'Asia/Jakarta');
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;
    }

    public function parseMonthYearFromMessage(string $message): ?array
    {
        $now = Carbon::now('Asia/Jakarta');
        $m = mb_strtolower($message);
        $map = $this->monthNameMap();

        if (str_contains($m, 'bulan ini')) {
            return ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()];
        }

        if (preg_match('/\b(\d{1,2})[\/\-\s](\d{4})\b/u', $m, $p)) {
            $mon = (int) $p[1];
            $yr = (int) $p[2];

            if ($mon >= 1 && $mon <= 12) {
                $start = Carbon::createFromDate($yr, $mon, 1, 'Asia/Jakarta')->startOfMonth();
                return ['start' => $start, 'end' => $start->copy()->endOfMonth()];
            }
        }

        foreach ($map as $k => $num) {
            if (str_contains($m, $k) && ! preg_match('/\d{4}/', $m)) {
                $year = $now->year;
                $start = Carbon::createFromDate($year, $num, 1, 'Asia/Jakarta')->startOfMonth();
                return ['start' => $start, 'end' => $start->copy()->endOfMonth()];
            }
        }

        return null;
    }

    public function parseYearFromMessage(string $message): ?array
    {
        $now = Carbon::now('Asia/Jakarta');
        $m = mb_strtolower($message);

        if (str_contains($m, 'tahun ini')) {
            return ['start' => $now->copy()->startOfYear(), 'end' => $now->copy()->endOfYear()];
        }

        if (str_contains($m, 'tahun kemarin') || preg_match('/1\s*tahun\s*lalu/', $m)) {
            $y = $now->copy()->subYear();
            return ['start' => $y->startOfYear(), 'end' => $y->endOfYear()];
        }

        if (preg_match('/\b(\d)\s*tahun\s*lalu\b/u', $m, $p)) {
            $n = (int) $p[1];

            if ($n >= 1 && $n <= 5) {
                $y = $now->copy()->subYears($n);
                return ['start' => $y->startOfYear(), 'end' => $y->endOfYear()];
            }
        }

        if (preg_match('/\b(20\d{2})\b/u', $m, $p)) {
            $yr = (int) $p[1];
            $s = Carbon::createFromDate($yr, 1, 1, 'Asia/Jakarta')->startOfYear();
            return ['start' => $s, 'end' => $s->copy()->endOfYear()];
        }

        return null;
    }

    public function formatPeriodLabel($start, $end): string
    {
        if (! $start || ! $end) {
            return 'periode tidak spesifik';
        }

        if ($start->isSameDay($end)) {
            return $start->format('d F Y');
        }

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->format('F Y');
        }

        if ($start->format('Y') === $end->format('Y')) {
            return $start->format('Y');
        }

        return $start->format('d F Y') . ' — ' . $end->format('d F Y');
    }

    public function isAskingSummaryPublic(string $message): bool
    {
        return $this->isAskingSummary($message);
    }

    protected function handleProfile($user, string $role): string
    {
        $name = data_get($user, 'name') ?? data_get($user, 'nama') ?? 'tidak tersedia';
        $username = data_get($user, 'username') ?? 'tidak tersedia';

        return "Nama: {$name}\nUsername: {$username}\nRole: {$role}";
    }

    protected function handleTicketSummary(string $question): string
    {
        [$start, $end] = $this->resolveDateFromMessage($question);
        $q = DB::table('tickets');

        if ($start && $end && $start !== '__FUTURE__') {
            $q->whereDate('created_at', '>=', $start->toDateString())
                ->whereDate('created_at', '<=', $end->toDateString());
        }

        $total = $q->count();

        if ($total === 0) {
            return 'Tidak ada data ticket pada periode tersebut.';
        }

        $waiting = (clone $q)->where('status', 'waiting')->count();
        $diagnosis = (clone $q)->where('status', 'diagnosis')->count();
        $processing = (clone $q)->where('status', 'processing')->count();
        $testing = (clone $q)->where('status', 'testing')->count();
        $completed = (clone $q)->where('status', 'completed')->count();
        $failed = (clone $q)->where('status', 'failed')->count();
        $cancelled = (clone $q)->where('status', 'cancelled')->count();

        return "Total tiket: {$total}\nWaiting: {$waiting}\nDiagnosis: {$diagnosis}\nProcessing: {$processing}\nTesting: {$testing}\nCompleted: {$completed}\nFailed: {$failed}\nCancelled: {$cancelled}";
    }

    protected function handleRekap(string $question): string
    {
        [$start, $end] = $this->resolveDateFromMessage($question);

        $q = DB::table('rekaps');

        if ($start && $end && $start !== '__FUTURE__') {
            $q->whereDate('rekap_date', '>=', $start->toDateString())
                ->whereDate('rekap_date', '<=', $end->toDateString());
        }

        $total = $q->count();

        if ($total === 0) {
            return 'Tidak ada data rekap pada periode tersebut.';
        }

        $installation = (clone $q)->sum('total_installations');
        $repair = (clone $q)->sum('total_repairs');
        $completed = (clone $q)->sum('completed_tickets');
        $failed = (clone $q)->sum('failed_tickets');
        $pending = (clone $q)->sum('pending_tickets');

        return "Total rekap: {$total}\nInstalasi: {$installation}\nPerbaikan: {$repair}\nCompleted: {$completed}\nFailed: {$failed}\nPending: {$pending}";
    }

    protected function handlePenginstalan($user, string $question, string $role): string
    {
        $q = DB::table('penginstalans as p')
            ->leftJoin('tickets as t', 't.id', '=', 'p.ticket_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('software as s', 's.id', '=', 'p.software_id')
            ->orderByDesc('p.id')
            ->limit(5)
            ->select(
                'p.id',
                'p.installation_result',
                'p.note',
                'p.created_at',
                't.ticket_number',
                't.status as ticket_status',
                'u.name as user_name',
                's.name as software_name',
                's.version as software_version'
            );

        if ($role !== 'admin') {
            $q->where('u.id', data_get($user, 'id'));
        }

        $rows = $q->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada data penginstalan yang ditemukan.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] =
                "#{$row->id} | Tiket: " . ($row->ticket_number ?? '-') .
                " | Software: " . ($row->software_name ?? '-') .
                " | Hasil: " . ($row->installation_result ?? '-') .
                " | Note: " . ($row->note ?? '-');
        }

        return implode("\n", $lines);
    }

    protected function handlePenginstalanStatus($user, string $role): string
    {
        $q = DB::table('penginstalans as p')
            ->leftJoin('tickets as t', 't.id', '=', 'p.ticket_id')
            ->leftJoin('software as s', 's.id', '=', 'p.software_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->orderByDesc('p.id')
            ->limit(1)
            ->select(
                'p.id',
                'p.created_at',
                't.ticket_number',
                't.status as ticket_status',
                's.name as software_name',
                'u.name as user_name'
            );

        if ($role !== 'admin') {
            $q->where('u.id', data_get($user, 'id'));
        }

        $row = $q->first();

        if (! $row) {
            return 'Tidak ditemukan status penginstalan terakhir.';
        }

        return "Penginstalan terakhir:\nTiket: {$row->ticket_number}\nStatus tiket: {$row->ticket_status}\nSoftware: {$row->software_name}\nTanggal: {$row->created_at}";
    }

    protected function handlePerbaikan($user, string $question, string $role): string
    {
        $q = DB::table('perbaikans as p')
            ->leftJoin('tickets as t', 't.id', '=', 'p.ticket_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->orderByDesc('p.id')
            ->limit(5)
            ->select(
                'p.id',
                'p.damage_type',
                'p.repair_result',
                'p.note',
                'p.created_at',
                't.ticket_number',
                't.status as ticket_status',
                'u.name as user_name'
            );

        if ($role !== 'admin') {
            $q->where('u.id', data_get($user, 'id'));
        }

        $rows = $q->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada data perbaikan yang ditemukan.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] =
                "#{$row->id} | Tiket: " . ($row->ticket_number ?? '-') .
                " | Kerusakan: " . ($row->damage_type ?? '-') .
                " | Hasil: " . ($row->repair_result ?? '-') .
                " | Note: " . ($row->note ?? '-');
        }

        return implode("\n", $lines);
    }

    protected function handlePerbaikanStatus($user, string $role): string
    {
        $q = DB::table('perbaikans as p')
            ->leftJoin('tickets as t', 't.id', '=', 'p.ticket_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->orderByDesc('p.id')
            ->limit(1)
            ->select(
                'p.id',
                'p.created_at',
                't.ticket_number',
                't.status as ticket_status',
                'p.damage_type',
                'u.name as user_name'
            );

        if ($role !== 'admin') {
            $q->where('u.id', data_get($user, 'id'));
        }

        $row = $q->first();

        if (! $row) {
            return 'Tidak ditemukan status perbaikan terakhir.';
        }

        return "Perbaikan terakhir:\nTiket: {$row->ticket_number}\nStatus tiket: {$row->ticket_status}\nKerusakan: {$row->damage_type}\nTanggal: {$row->created_at}";
    }

    protected function handleLoginLog(string $question): string
    {
        $rows = DB::table('login_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->orderByDesc('l.id')
            ->limit(5)
            ->select('l.id', 'u.name as user_name', 'l.status', 'l.ip_address', 'l.login_at', 'l.logout_at', 'l.location_status')
            ->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada data login log.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = "#{$row->id} | {$row->user_name} | {$row->status} | {$row->ip_address} | {$row->login_at}";
        }

        return implode("\n", $lines);
    }

    protected function handleAiLog(string $question): string
    {
        $rows = DB::table('ai_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->orderByDesc('a.id')
            ->limit(5)
            ->select('a.id', 'u.name as user_name', 'a.role', 'a.question', 'a.action', 'a.source', 'a.created_at')
            ->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada data AI log.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = "#{$row->id} | {$row->user_name} | {$row->action} | {$row->source}";
        }

        return implode("\n", $lines);
    }

    protected function handleAiTask(string $question): string
    {
        $rows = DB::table('ai_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->orderByDesc('t.id')
            ->limit(5)
            ->select('t.id', 'u.name as user_name', 't.task_name', 't.status', 't.created_at')
            ->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada data AI task.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = "#{$row->id} | {$row->user_name} | {$row->task_name} | {$row->status}";
        }

        return implode("\n", $lines);
    }

    protected function handleAiRecommendation(string $question): string
    {
        $rows = DB::table('ai_recommendations as r')
            ->leftJoin('tickets as t', 't.id', '=', 'r.ticket_id')
            ->orderByDesc('r.id')
            ->limit(5)
            ->select('r.id', 't.ticket_number', 'r.recommendation', 'r.reason', 'r.status', 'r.created_at')
            ->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada data rekomendasi AI.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = "#{$row->id} | Tiket: " . ($row->ticket_number ?? '-') . " | {$row->status}";
        }

        return implode("\n", $lines);
    }

    protected function handleSoftware(string $question): string
    {
        $rows = DB::table('software')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'developer', 'version', 'estimated_minutes', 'description']);

        if ($rows->isEmpty()) {
            return 'Tidak ada data software.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = "#{$row->id} | {$row->name} | {$row->developer} | {$row->version} | {$row->estimated_minutes} menit";
        }

        return implode("\n", $lines);
    }

    protected function handleMaintenance(string $question): string
    {
        $rows = DB::table('maintenances')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada data maintenance.';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = "#{$row->id} | " . ($row->title ?? 'Maintenance') . ' | ' . (($row->is_active ?? 0) ? 'aktif' : 'nonaktif');
        }

        return implode("\n", $lines);
    }

    protected function castSettingValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return false;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $value = mb_strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'on', 'aktif', 'enabled'], true);
    }
}
