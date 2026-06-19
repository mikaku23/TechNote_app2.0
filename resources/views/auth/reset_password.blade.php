<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Baru | TechNoteApp 2.0</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body.login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: min(1100px, 100%);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            align-items: stretch;
        }

        .login-hero,
        .login-card {
            border-radius: 32px;
        }

        .login-hero {
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 640px;
        }

        .login-card {
            padding: 32px;
            min-height: 640px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero-kicker,
        .form-control,
        .btn-primary,
        .tn-alert {
            backdrop-filter: blur(18px);
        }

        .hero-kicker {
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
            border: 1px solid rgba(255, 255, 255, 0.16);
        }

        .hero-title {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 0.98;
            margin-top: 18px;
            margin-bottom: 14px;
            max-width: 560px;
            letter-spacing: -0.04em;
        }

        .hero-title-static {
            color: var(--text);
            font-weight: 800;
        }

        .hero-title-typing {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            min-height: 1.1em;
        }

        .login-header {
            margin-bottom: 20px;
        }

        .login-header .logo-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            margin-bottom: 18px;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .login-header h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .login-header p,
        .reset-hint,
        .helper-text {
            color: var(--text-light);
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            height: 52px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.18);
            padding: 0 16px;
            outline: none;
            color: var(--text);
            transition: 0.3s ease;
        }

        .form-control:focus {
            border-color: rgba(91, 92, 235, 0.45);
            box-shadow: 0 0 0 4px rgba(91, 92, 235, 0.12);
        }

        .login-submit {
            width: 100%;
            justify-content: center;
            height: 52px;
            margin-top: 8px;
        }

        .login-error,
        .login-success {
            margin-bottom: 16px;
        }

        .back-link {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            margin-top: 16px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .reset-summary {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .summary-card {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        .summary-card strong {
            display: block;
            margin-bottom: 4px;
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .login-hero,
            .login-card {
                min-height: auto;
            }
        }

        .logo {
            margin-bottom: 0;
        }
    </style>
</head>

<body class="login-page">
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="login-shell">
        <section class="login-hero glass motion-card">
            <div>
                <div class="logo">
                    <div class="logo-icon">T</div>
                    <div class="logo-text">
                        TechNote
                        <span>Smart Technician Service</span>
                    </div>
                </div>

                <div class="login-hero-top">
                    <span class="hero-kicker">Password Baru</span>
                    <h1 class="hero-title">
                        <span class="hero-title-static">Buat Password</span>
                        <span class="hero-title-typing">
                            <span id="typedTitle"></span><span class="typed-cursor">|</span>
                        </span>
                    </h1>

                    <p>
                        Setelah verifikasi berhasil, masukkan password baru dan konfirmasi ulang.
                        Password lama tidak dipakai lagi.
                    </p>
                </div>
            </div>

            <div class="reset-summary">
                <div class="summary-card">
                    <strong>Akun</strong>
                    {{ $user->name ?? '-' }}
                </div>
                <div class="summary-card">
                    <strong>Proses</strong>
                    Reset password aktif dan terverifikasi.
                </div>
            </div>
        </section>

        <section class="login-card glass motion-card">
            <div class="login-header">
                <div class="logo-icon">T</div>
                <h2>Password Baru</h2>
                <p>Isi password baru dan konfirmasi ulang.</p>
            </div>

            @if (session('success'))
            <div class="tn-alert tn-alert-success login-success">
                {{ session('success') }}
            </div>
            @endif

            @if (session('warning'))
            <div class="tn-alert tn-alert-warning login-warning">
                <div class="tn-alert-title">Peringatan</div>
                <div class="tn-alert-text">{{ session('warning') }}</div>
            </div>
            @endif

            @if ($errors->any())
            <div class="tn-alert tn-alert-danger login-error">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('password.reset.update') }}">
                @csrf

                <div class="form-group">
                    <label for="password">Password baru</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Masukkan password baru" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                        placeholder="Ulangi password baru" required>
                </div>

                <button type="submit" class="btn-primary login-submit">
                    <i data-lucide="save"></i>
                    Simpan Password Baru
                </button>
            </form>

            <a class="back-link" href="{{ route('login') }}">
                <i data-lucide="arrow-left"></i>
                Kembali ke login
            </a>
        </section>
    </div>

    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();

            const typedEl = document.getElementById("typedTitle");
            if (!typedEl) return;

            const texts = [
                "Aman",
                "Cepat",
                "Terkunci",
                "Siap Pakai"
            ];

            let textIndex = 0;
            let charIndex = 0;
            let isDeleting = false;

            function loop() {
                const current = texts[textIndex];

                if (isDeleting) {
                    charIndex--;
                    typedEl.textContent = current.substring(0, charIndex);
                } else {
                    charIndex++;
                    typedEl.textContent = current.substring(0, charIndex);
                }

                let delay = isDeleting ? 32 : 70;

                if (!isDeleting && charIndex === current.length) {
                    isDeleting = true;
                    delay = 1200;
                } else if (isDeleting && charIndex === 0) {
                    isDeleting = false;
                    textIndex = (textIndex + 1) % texts.length;
                    delay = 220;
                }

                setTimeout(loop, delay);
            }

            loop();
        });
    </script>
</body>

</html>