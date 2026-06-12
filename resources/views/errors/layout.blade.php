<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') | TechNoteApp 2.0</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body.error-page {
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
        }

        .error-shell {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 24px;
            align-items: stretch;
            position: relative;
            z-index: 2;
        }

        .error-panel,
        .error-card {
            border-radius: 32px;
        }

        .error-panel {
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 620px;
        }

        .error-card {
            padding: 34px;
            min-height: 620px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-light);
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            width: fit-content;
        }

        .error-code {
            font-size: clamp(72px, 12vw, 128px);
            line-height: 0.9;
            font-weight: 800;
            margin: 18px 0 10px;
            letter-spacing: -0.08em;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .error-title {
            font-size: clamp(28px, 4vw, 44px);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin: 0 0 14px;
            color: var(--text);
        }

        .error-desc {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.8;
            max-width: 520px;
            margin: 0;
        }

        .error-meta {
            display: grid;
            gap: 12px;
            margin-top: 28px;
        }

        .error-box {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .error-box strong {
            display: block;
            margin-bottom: 4px;
            color: var(--text);
        }

        .error-box span {
            color: var(--text-light);
            font-size: 13px;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .error-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            text-decoration: none;
            min-height: 52px;
            padding: 0 18px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.12);
            color: var(--text);
            backdrop-filter: blur(18px);
            transition: 0.25s ease;
        }

        .error-link:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.18);
        }

        .error-link.primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border-color: transparent;
        }

        .error-link.primary:hover {
            filter: brightness(1.03);
        }

        .error-footer {
            margin-top: 22px;
            color: var(--text-light);
            font-size: 13px;
            line-height: 1.6;
        }

        .error-icon-box {
            width: 68px;
            height: 68px;
            border-radius: 22px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(18px);
            margin-bottom: 18px;
        }

        .error-icon-box i {
            width: 30px;
            height: 30px;
        }

        @media (max-width: 900px) {
            .error-shell {
                grid-template-columns: 1fr;
            }

            .error-panel,
            .error-card {
                min-height: auto;
            }
        }
    </style>
</head>

<body class="error-page">
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="error-shell">
        <section class="error-panel glass motion-card">
            <div>
                <div class="logo" style="margin-bottom: 0;">
                    <div class="logo-icon">T</div>
                    <div class="logo-text">
                        TechNote
                        <span>Smart Technician Service</span>
                    </div>
                </div>

                <div style="margin-top: 26px;">
                    <span class="error-badge">
                        <i data-lucide="triangle-alert"></i>
                        @yield('badge', 'Terjadi Gangguan')
                    </span>

                    <div class="error-code">@yield('code', '500')</div>
                    <h1 class="error-title">@yield('headline', 'Halaman tidak dapat diakses')</h1>
                    <p class="error-desc">
                        @yield('message', 'Sistem tidak dapat memproses permintaan saat ini. Silakan kembali ke halaman utama atau ulangi beberapa saat lagi.')
                    </p>

                    <div class="error-actions">
                        <a href="{{ route('login') }}" class="error-link primary">
                            <i data-lucide="home"></i>
                            Beranda
                        </a>

                        <button
                            type="button"
                            class="error-link"
                            data-login-url="{{ route('login') }}"
                            onclick="goBackOrLogin(this)">
                            <i data-lucide="arrow-left"></i>
                            Kembali
                        </button>

                        <script>
                            function goBackOrLogin(button) {
                                if (window.history.length > 1) {
                                    history.back();
                                } else {
                                    window.location.href = button.dataset.loginUrl;
                                }
                            }
                        </script>
                    </div>
                </div>
            </div>

            <div class="error-footer">
                TechNoteApp 2.0 — sistem layanan penginstalan dan perbaikan.
            </div>
        </section>

        <section class="error-card glass motion-card">
            <div class="error-icon-box">
                <i data-lucide="@yield('icon', 'shield-alert')"></i>
            </div>

            <div class="error-meta">
                <div class="error-box">
                    <strong>Langkah aman</strong>
                    <span>Gunakan tombol beranda untuk kembali ke area yang valid. Jika masalah berulang, kemungkinan terjadi pembatasan akses, tautan salah, atau gangguan sistem.</span>
                </div>

                <div class="error-box">
                    <strong>Status halaman</strong>
                    <span>@yield('status_text', 'Permintaan tidak dapat diselesaikan.')</span>
                </div>

                <div class="error-box">
                    <strong>Catatan sistem</strong>
                    <span>@yield('note', 'TechNoteApp menjaga akses berdasarkan role, status sistem, dan data yang tersedia.')</span>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });
    </script>
</body>

</html>