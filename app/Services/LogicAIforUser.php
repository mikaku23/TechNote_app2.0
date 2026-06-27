<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Throwable;

class LogicAIforUser
{
    public function __construct(
        protected KeywordService $keywordService,
        protected UserDataRetrieverService $userDataRetrieverService,
        protected TrustedWebsiteService $trustedWebsiteService,
        protected OpenRouterService $openRouterService,
    ) {}

    public function handle(string $question, $user, array $context = []): array
    {
        $question = trim($question);
        $roleName = $this->resolveRoleName($user);

        if ($question === '') {
            return $this->pack(
                reply: 'Pertanyaan masih kosong.',
                action: 'none',
                source: 'validator',
                confidence: 0.0,
                blocked: false,
                role: $roleName
            );
        }

        if (! in_array($roleName, ['mahasiswa', 'dosen'], true)) {
            return $this->pack(
                reply: 'Fitur ini hanya tersedia untuk mahasiswa dan dosen.',
                action: 'forbidden',
                source: 'policy',
                confidence: 1.0,
                blocked: true,
                role: $roleName
            );
        }

        $intent = $this->detectIntent($question, $roleName);

        $result = match ($intent) {
            'profile' => $this->answerProfile($user, $roleName),
            'login' => $this->answerLogin($user, $roleName),
            'installation' => $this->answerInstallation($user, $roleName),
            'trusted_website' => $this->answerTrustedWebsite($question, $roleName),
            default => $this->answerViaApiFallback($question, $user, $roleName, $context),
        };

        $this->pushHistory($user, $question, (string) ($result['reply'] ?? ''));

        return $result;
    }

    protected function detectIntent(string $question, string $roleName): string
    {
        $q = Str::lower($question);

        $trustedKeywords = [
            'trusted',
            'website',
            'web',
            'situs',
            'link',
            'url',
            'smkn',
            'stmik',
            'karang baru',
            'triguna dharma',
            'resmi',
            'kampus',
        ];
        if ($this->containsAny($q, $trustedKeywords)) {
            return 'trusted_website';
        }

        $profileKeywords = [
            'profil',
            'data saya',
            'dataku',
            'akun saya',
            'identitas',
            'nim',
            'nip',
            'email',
            'no hp',
            'nomor hp',
            'nama saya',
            'username',
            'kelas',
            'jurusan',
            'prodi',
        ];
        if ($this->containsAny($q, $profileKeywords)) {
            return 'profile';
        }

        $loginKeywords = [
            'login',
            'masuk',
            'password',
            'sandi',
            'reset password',
            'last login',
            'terakhir login',
            'otp',
            'signin',
            'status login',
        ];
        if ($this->containsAny($q, $loginKeywords)) {
            return 'login';
        }

        $installKeywords = [
            'install',
            'instal',
            'pemasangan',
            'setup',
            'konfigurasi',
            'pasang',
            'penginstalan',
            'onboarding',
            'penyiapan',
            'panduan',
        ];
        if ($this->containsAny($q, $installKeywords)) {
            return 'installation';
        }

        if (in_array($roleName, ['mahasiswa', 'dosen'], true) && $this->containsAny($q, ['akun', 'data', 'saya', 'profil'])) {
            return 'profile';
        }

        return 'fallback_api';
    }

    protected function answerProfile($user, string $roleName): array
    {
        $reply = method_exists($this->userDataRetrieverService, 'profile')
            ? $this->userDataRetrieverService->profile($user)
            : $this->fallbackProfileText($user, $roleName);

        return $this->pack(
            reply: $reply,
            action: 'show_profile',
            source: 'user_profile',
            confidence: 0.97,
            blocked: false,
            role: $roleName,
            suggestions: [
                'Cek status login saya',
                'Tampilkan website tepercaya',
                'Bantu instalasi akses akun saya',
            ]
        );
    }

    protected function answerLogin($user, string $roleName): array
    {
        $reply = method_exists($this->userDataRetrieverService, 'loginStatus')
            ? $this->userDataRetrieverService->loginStatus($user)
            : $this->fallbackLoginText($user, $roleName);

        return $this->pack(
            reply: $reply,
            action: 'show_login_status',
            source: 'user_login',
            confidence: 0.96,
            blocked: false,
            role: $roleName,
            suggestions: [
                'Lihat data profil saya',
                'Cari website tepercaya',
                'Bantu instalasi akun saya',
            ]
        );
    }

    protected function answerInstallation($user, string $roleName): array
    {
        $reply = method_exists($this->userDataRetrieverService, 'installation')
            ? $this->userDataRetrieverService->installation($user)
            : $this->fallbackInstallationText($user, $roleName);

        return $this->pack(
            reply: $reply,
            action: 'installation_help',
            source: 'policy_user_scope',
            confidence: 0.9,
            blocked: false,
            role: $roleName,
            suggestions: [
                'Tampilkan data saya',
                'Website tepercaya yang tersedia',
                'Cek status login saya',
            ]
        );
    }

    protected function answerTrustedWebsite(string $question, string $roleName): array
    {
        $reply = null;

        try {
            if (method_exists($this->trustedWebsiteService, 'answerFromTrustedWebsite')) {
                $reply = $this->trustedWebsiteService->answerFromTrustedWebsite($question, $this->openRouterService);
            }
        } catch (Throwable $e) {
            logger()->warning('trustedWebsiteService failed', [
                'message' => $e->getMessage(),
            ]);
        }

        if (! is_string($reply) || trim($reply) === '') {
            $reply = $this->lookupTrustedWebsitesLocally($question);
        }

        return $this->pack(
            reply: $reply,
            action: 'list_trusted_websites',
            source: 'trusted_websites',
            confidence: 0.93,
            blocked: false,
            role: $roleName,
            suggestions: [
                'Cari website SMKN 1 Karang Baru',
                'Cari website STMIK Triguna Dharma',
                'Tampilkan data profil saya',
            ]
        );
    }

    protected function answerViaApiFallback(string $question, $user, string $roleName, array $context = []): array
    {
        $apiReply = $this->callOpenRouterFallback($question, $user, $roleName, $context);

        if (is_string($apiReply) && trim($apiReply) !== '') {
            return $this->pack(
                reply: $apiReply,
                action: 'ai_fallback',
                source: 'api',
                confidence: 0.72,
                blocked: false,
                role: $roleName,
                suggestions: [
                    'Tampilkan data profil saya',
                    'Cek login saya',
                    'Website tepercaya apa saja?',
                ]
            );
        }

        $localFallback = method_exists($this->keywordService, 'fallbackReply')
            ? $this->keywordService->fallbackReply($question, [
                'action' => 'user_ai',
                'role' => $roleName,
            ])
            : $this->basicScopeReply($roleName);

        return $this->pack(
            reply: $localFallback,
            action: 'fallback_local',
            source: 'local',
            confidence: 0.42,
            blocked: false,
            role: $roleName,
            suggestions: [
                'Tampilkan data profil saya',
                'Cek status login saya',
                'Website tepercaya apa saja?',
            ]
        );
    }

    protected function callOpenRouterFallback(string $question, $user, string $roleName, array $context = []): ?string
    {
        if (! method_exists($this->openRouterService, 'chat')) {
            return null;
        }

        $profile = [
            'id' => data_get($user, 'id'),
            'name' => data_get($user, 'name'),
            'username' => data_get($user, 'username'),
            'nim' => data_get($user, 'nim'),
            'nip' => data_get($user, 'nip'),
            'email' => data_get($user, 'email'),
            'no_hp' => data_get($user, 'no_hp'),
            'role_name' => $roleName,
            'last_login_at' => $this->formatDateTime(data_get($user, 'last_login_at')),
        ];

        $history = $this->recentHistory($user, 8);
        $trustedPreview = $this->trustedWebsitesPreview(6);

        $system = <<<SYS
Kamu adalah AI khusus untuk mahasiswa/dosen TechNote App 2.0.

Batasan:
- Hanya bantu tentang akun milik pengguna sendiri, login, instalasi/penyiapan akses, dan trusted website resmi yang tersimpan di sistem.
- Jangan bahas data orang lain, admin tool, CRUD, maintenance admin, tabel internal sensitif, atau perubahan database.
- Jika pertanyaan di luar scope, tolak singkat dan arahkan ke topik yang diizinkan.
- Jawab dalam Bahasa Indonesia, ringkas, jelas, dan aman.
SYS;

        $userPrompt = json_encode([
            'question' => $question,
            'role' => $roleName,
            'profile' => $profile,
            'recent_history' => $history,
            'trusted_websites_preview' => $trustedPreview,
            'context' => $context,
            'instructions' => [
                'Jawab langsung bila masih dalam scope.',
                'Jika samar, bantu tafsirkan ke salah satu scope yang diizinkan.',
                'Jika benar-benar di luar scope, katakan tidak bisa membantu lalu arahkan ke topik yang sesuai.',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        try {
            $resp = $this->openRouterService->chat(
                [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'deepseek/deepseek-chat',
                25,
                500,
                0.2
            );

            if (is_string($resp) && trim($resp) !== '') {
                return trim($resp);
            }
        } catch (Throwable $e) {
            logger()->warning('OpenRouter fallback failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function lookupTrustedWebsitesLocally(string $question): string
    {
        if (! Schema::hasTable('trusted_websites')) {
            return 'Tabel trusted_websites belum tersedia.';
        }

        $keywords = $this->extractKeywords($question);
        $query = DB::table('trusted_websites')->where('is_active', 1);

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

        $rows = $query->orderBy('name')->limit(10)->get(['name', 'url', 'description']);

        if ($rows->isEmpty()) {
            $rows = DB::table('trusted_websites')
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(10)
                ->get(['name', 'url', 'description']);
        }

        if ($rows->isEmpty()) {
            return 'Belum ada trusted website aktif di database.';
        }

        $lines = ["Saya temukan website tepercaya berikut:"];
        foreach ($rows as $row) {
            $desc = trim((string) ($row->description ?? ''));
            $lines[] = '- ' . $row->name . ' — ' . $row->url . ($desc !== '' ? " | {$desc}" : '');
        }

        return implode("\n", $lines);
    }

    protected function recentHistory($user, int $limit = 8): array
    {
        $key = 'user_ai_history_' . data_get($user, 'id');
        $history = Session::get($key, []);
        if (! is_array($history)) {
            return [];
        }

        return array_slice($history, -$limit);
    }

    protected function pushHistory($user, string $question, string $answer): void
    {
        $key = 'user_ai_history_' . data_get($user, 'id');
        $history = Session::get($key, []);

        if (! is_array($history)) {
            $history = [];
        }

        $history[] = [
            'in' => $question,
            'out' => $answer,
            'at' => now()->toDateTimeString(),
        ];

        Session::put($key, array_slice($history, -20));
    }

    protected function trustedWebsitesPreview(int $limit = 6): array
    {
        if (! Schema::hasTable('trusted_websites')) {
            return [];
        }

        return DB::table('trusted_websites')
            ->where('is_active', 1)
            ->orderBy('name')
            ->limit($limit)
            ->get(['name', 'url', 'description'])
            ->map(fn($row) => [
                'name' => $row->name,
                'url' => $row->url,
                'description' => $row->description,
            ])
            ->all();
    }

    protected function extractKeywords(string $question): array
    {
        $parts = preg_split('/[\s,;:.\-\(\)\/\\\\]+/u', Str::lower($question)) ?: [];
        $parts = array_values(array_unique(array_filter(array_map('trim', $parts))));
        $parts = array_filter($parts, fn($v) => mb_strlen($v) >= 3);

        return array_slice($parts, 0, 6);
    }

    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    protected function fallbackProfileText($user, string $roleName): string
    {
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
            $roleName === 'dosen' ? "NIP: {$nip}" : "NIM: {$nim}",
            "Email: {$email}",
            "No. HP: {$phone}",
            "Role: {$roleName}",
            "Login terakhir: {$lastLogin}",
        ];

        return implode("\n", $lines);
    }

    protected function fallbackLoginText($user, string $roleName): string
    {
        $username = (string) data_get($user, 'username', '-');
        $lastLogin = $this->formatDateTime(data_get($user, 'last_login_at'));

        return "Akun kamu terdeteksi dengan username {$username}.\n"
            . "Role: {$roleName}\n"
            . "Login terakhir: {$lastLogin}\n"
            . "Password, OTP, dan data sensitif tidak saya tampilkan.\n"
            . "Kalau perlu reset akses, gunakan alur reset resmi di sistem.";
    }

    protected function fallbackInstallationText($user, string $roleName): string
    {
        $name = (string) data_get($user, 'name', 'akun kamu');

        return "Untuk instalasi/penyiapan akses {$roleName} atas nama {$name}, fokusnya hanya pada akses milikmu sendiri.\n"
            . "Langkah aman yang bisa saya bantu:\n"
            . "1. Pastikan login dengan username yang benar.\n"
            . "2. Cek data profil sendiri: NIM/NIP, email, dan nomor HP.\n"
            . "3. Gunakan link resmi yang ada di trusted_websites.\n"
            . "4. Jangan pakai data akun orang lain.";
    }

    protected function basicScopeReply(string $roleName): string
    {
        return "Saya hanya melayani topik yang berkaitan dengan akun milikmu sendiri dan trusted website aktif.\n"
            . "Coba tanya salah satu dari ini:\n"
            . "- data profil saya\n"
            . "- status login saya\n"
            . "- website tepercaya apa saja\n"
            . "- bantuan instalasi akses akun saya";
    }

    protected function pack(
        string $reply,
        string $action,
        string $source,
        float $confidence,
        bool $blocked,
        string $role,
        array $suggestions = []
    ): array {
        return [
            'ok' => ! $blocked,
            'reply' => trim($reply),
            'action' => $action,
            'source' => $source,
            'confidence' => $confidence,
            'blocked' => $blocked,
            'needs_confirmation' => false,
            'role' => $role,
            'suggestions' => $suggestions,
        ];
    }
}
