<?php

namespace App\Services;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TrustedWebsiteService
{
    public function isTrustedWebsiteQuestion(string $message): bool
    {
        $m = mb_strtolower($message);

        return (bool) preg_match(
            '/\b(stmik|karang baru|smkn?\s*1\s*karang\s*baru|smkn1\s*karang\s*baru|trusted website|website terpercaya|situs resmi|website resmi)\b/u',
            $m
        );
    }

    public function matchTrustedWebsite(string $message): ?object
    {
        $m = mb_strtolower($message);

        $sites = DB::table('trusted_websites')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'url', 'description']);

        if ($sites->isEmpty()) {
            return null;
        }

        $matched = $sites->first(function ($site) use ($m) {
            $blob = mb_strtolower(
                ($site->name ?? '') . ' ' .
                    ($site->url ?? '') . ' ' .
                    ($site->description ?? '')
            );

            return str_contains($blob, 'stmik')
                || str_contains($blob, 'karang baru')
                || str_contains($blob, 'smkn')
                || str_contains($blob, 'smk negeri 1 karang baru')
                || str_contains($m, mb_strtolower($site->name ?? ''));
        });

        return $matched ?: $sites->first();
    }

    public function fetchWebsiteContent(string $url): ?string
    {
        try {
            $response = Http::timeout(20)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'TechNoteApp/2.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            if (! $response->ok()) {
                return null;
            }

            $html = (string) $response->body();

            $text = strip_tags($html);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\s+/u', ' ', $text);
            $text = trim($text);

            if ($text === '') {
                return null;
            }

            return mb_strimwidth($text, 0, 12000, '...');
        } catch (Throwable $e) {
            logger()->warning('Trusted website fetch failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function answerFromTrustedWebsite(
        string $question,
        OpenRouterService $openRouterService
    ): ?array {
        if (! $this->isTrustedWebsiteQuestion($question)) {
            return null;
        }

        $site = $this->matchTrustedWebsite($question);

        if (! $site) {
            return [
                'ok' => true,
                'reply' => 'Data trusted website belum tersedia.',
                'action' => 'trusted_website',
                'source' => 'trusted_website',
                'website_name' => null,
                'website_url' => null,
                'confidence' => 0.6,
            ];
        }

        $content = $this->fetchWebsiteContent($site->url);

        if (! $content) {
            return [
                'ok' => true,
                'reply' => 'Website trusted ditemukan, tetapi kontennya tidak dapat dibaca saat ini.',
                'action' => 'trusted_website',
                'source' => 'trusted_website_fetch_failed',
                'website_name' => $site->name,
                'website_url' => $site->url,
                'confidence' => 0.6,
            ];
        }

        $system = <<<PROMPT
Anda adalah AI informasi resmi.
Gunakan hanya data berikut sebagai sumber jawaban.
Jangan mengarang informasi di luar data.
Jika data tidak ditemukan, jawab: informasi tidak tersedia.

Sumber website:
Nama: {$site->name}
URL: {$site->url}
Deskripsi: {$site->description}

Isi website:
{$content}

Aturan:
- Jawaban singkat, langsung ke inti.
- Hanya jawab berdasarkan isi website.
- Jika pertanyaan tidak ada di sumber, jawab "informasi tidak tersedia".
PROMPT;

        $userPrompt = "Pertanyaan user: {$question}";

        $reply = $openRouterService->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ], 'deepseek/deepseek-chat', 30, 500, 0.2);

        if (! $reply) {
            $reply = 'Informasi tidak tersedia.';
        }

        return [
            'ok' => true,
            'reply' => trim($reply),
            'action' => 'trusted_website',
            'source' => 'trusted_website_ai',
            'website_name' => $site->name,
            'website_url' => $site->url,
            'confidence' => 1,
        ];
    }
}
