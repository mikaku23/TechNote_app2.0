<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password | TechNoteApp 2.0</title>

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
        .method-chip,
        .method-card,
        .reset-hint,
        .security-box,
        .step-title,
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

        .login-hero-top p,
        .login-header p,
        .reset-hint,
        .helper-text {
            color: var(--text-light);
            line-height: 1.6;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        .hero-badge {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 13px;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .hero-footer {
            display: grid;
            gap: 12px;
            margin-top: 24px;
        }

        .hero-note {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.14);
        }

        .hero-note strong {
            display: block;
            margin-bottom: 4px;
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

        .step-title {
            font-size: 14px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text-light);
            margin-bottom: 14px;
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

        .method-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .method-card {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.10);
            cursor: pointer;
        }

        .method-card input {
            accent-color: var(--primary);
        }

        .method-card strong {
            display: block;
            margin-bottom: 2px;
        }

        .method-chip {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.16);
        }

        .btn-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .login-submit {
            width: 100%;
            justify-content: center;
            height: 52px;
            margin-top: 8px;
        }

        .login-error,
        .login-success,
        .login-info,
        .login-warning {
            margin-bottom: 16px;
        }

        .tn-alert-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .tn-alert-text {
            white-space: normal;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .tn-alert-list {
            margin: 0;
            padding-left: 18px;
            line-height: 1.6;
        }

        .reset-hint {
            margin-top: 14px;
            font-size: 13px;
        }

        .security-box {
            display: grid;
            gap: 12px;
            margin-bottom: 16px;
        }

        .security-box .form-control[disabled] {
            opacity: 0.92;
            cursor: not-allowed;
        }

        .otp-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .small-link {
            color: var(--text-light);
            text-decoration: none;
            font-size: 13px;
        }

        .small-link:hover {
            text-decoration: underline;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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

        .login-hero-top {
            margin-top: 20px;
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
                    <span class="hero-kicker">Pemulihan Akses</span>
                    <h1 class="hero-title">
                        <span class="hero-title-static">Reset Password</span>
                        <span class="hero-title-typing">
                            <span id="typedTitle"></span><span class="typed-cursor">|</span>
                        </span>
                    </h1>

                    <p>
                        Pilih jalur pemulihan akun melalui WhatsApp, email, atau pertanyaan keamanan.
                        Setelah verifikasi berhasil, password baru dapat dibuat langsung.
                    </p>

                    <div class="hero-badges">
                        <span class="hero-badge">OTP WhatsApp</span>
                        <span class="hero-badge">OTP Email</span>
                        <span class="hero-badge">Security Question</span>
                        <span class="hero-badge">Liquid Glass UI</span>
                    </div>
                </div>
            </div>

            <div class="hero-footer">
                <div class="hero-note">
                    <strong>WhatsApp</strong>
                    OTP dikirim ke nomor yang terdaftar.
                </div>
                <div class="hero-note">
                    <strong>Email</strong>
                    Cek inbox, termasuk folder spam.
                </div>
                <div class="hero-note">
                    <strong>Security Question</strong>
                    Jawab pertanyaan keamanan yang sudah disimpan.
                </div>
            </div>
        </section>

        <section class="login-card glass motion-card">
            <div class="login-header">
                <div class="logo-icon">T</div>
                <h2>Lupa Password</h2>
                <p>Masukkan identitas akun lalu pilih metode pemulihan.</p>
            </div>

            @if (session('success'))
            <div class="tn-alert tn-alert-success login-success">
                <div class="tn-alert-title">Berhasil</div>
                <div class="tn-alert-text">{{ session('success') }}</div>
            </div>
            @endif

            @if (session('info'))
            <div class="tn-alert tn-alert-info login-info">
                <div class="tn-alert-title">Informasi</div>
                <div class="tn-alert-text">{{ session('info') }}</div>
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
                <div class="tn-alert-title">Gagal</div>
                <ul class="tn-alert-list">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @php
            $currentStep = $step ?? request('step', 'choose');
            @endphp

            @if ($currentStep === 'choose')
            <div class="step-title">Langkah 1 — Pilih metode</div>

            <form method="POST" action="{{ route('password.reset.send') }}">
                @csrf

                <div class="form-group">
                    <label for="identity">NIP / NIM / Username / Email</label>
                    <input type="text" id="identity" name="identity" class="form-control"
                        placeholder="Masukkan identitas akun" value="{{ old('identity') }}" required>
                </div>

                <div class="form-group">
                    <label>Metode pemulihan</label>
                    <div class="method-grid">
                        <label class="method-card">
                            <input type="radio" name="channel" value="whatsapp" checked>
                            <div class="method-chip"><i data-lucide="message-circle"></i></div>
                            <div>
                                <strong>WhatsApp OTP</strong>
                                Kode dikirim ke nomor HP terdaftar.
                            </div>
                        </label>

                        <label class="method-card">
                            <input type="radio" name="channel" value="email">
                            <div class="method-chip"><i data-lucide="mail"></i></div>
                            <div>
                                <strong>Email OTP</strong>
                                Kode dikirim ke email terdaftar. Cek spam jika tidak terlihat.
                            </div>
                        </label>

                        <label class="method-card">
                            <input type="radio" name="channel" value="security">
                            <div class="method-chip"><i data-lucide="shield-check"></i></div>
                            <div>
                                <strong>Pertanyaan Keamanan</strong>
                                Jawaban pertanyaan keamanan digunakan untuk verifikasi.
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-primary login-submit">
                    <i data-lucide="send"></i>
                    Lanjutkan
                </button>
            </form>

            <div class="reset-hint">
                Kembali ke halaman login?
                <a class="small-link" href="{{ route('login') }}">Masuk sekarang</a>
            </div>
            @elseif ($currentStep === 'otp')
            <div class="step-title">Langkah 2 — Verifikasi OTP</div>

            <div class="tn-alert tn-alert-success login-success">
                <div class="tn-alert-title">OTP terkirim</div>
                <div class="tn-alert-text">
                    OTP sudah dikirim ke {{ $channel === 'email' ? 'Email' : 'WhatsApp' }}.
                </div>
            </div>

            <div class="tn-alert tn-alert-info login-info">
                <div class="tn-alert-title">Informasi</div>
                <div class="tn-alert-text">
                    Jika via email, cek juga folder Spam/Junk.
                </div>
            </div>

            @if (!empty($resendCooldownText))
            <div class="tn-alert tn-alert-warning login-warning">
                <div class="tn-alert-title">Cooldown aktif</div>
                <div class="tn-alert-text">
                    Kirim ulang OTP tersedia lagi dalam {{ $resendCooldownText }}.
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('password.reset.otp.verify') }}" id="otpForm">
                @csrf

                <div class="form-group">
                    <label>Kode OTP</label>
                    <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                        @for ($i = 1; $i <= 6; $i++)
                            <input
                            type="text"
                            maxlength="1"
                            inputmode="numeric"
                            class="form-control otp-input text-center"
                            style="width:52px; padding:0; text-align:center;"
                            data-index="{{ $i }}">
                            @endfor
                    </div>
                    <input type="hidden" name="otp" id="otpHidden">
                </div>

                <button type="submit" class="btn-primary login-submit">
                    <i data-lucide="check-circle-2"></i>
                    Verifikasi OTP
                </button>
            </form>

            <div class="otp-actions">
                <form method="POST" action="{{ route('password.reset.resend') }}">
                    @csrf
                    <button type="submit" class="btn-primary" style="height:48px;" @if(!empty($resendCooldownSeconds)) disabled @endif>
                        <i data-lucide="refresh-cw"></i>
                        @if(!empty($resendCooldownSeconds))
                        Tunggu dulu
                        @else
                        Kirim ulang OTP
                        @endif
                    </button>
                </form>

                <a href="{{ route('password.forgot.reset') }}"
                    class="btn-primary"
                    style="height:48px; display:inline-flex; align-items:center; text-decoration:none;">
                    <i data-lucide="arrow-left"></i>
                    Ganti metode
                </a>
            </div>
            @elseif ($currentStep === 'security')
            <div class="step-title">Langkah 2 — Jawab pertanyaan keamanan</div>

            <div class="tn-alert tn-alert-info login-info">
                <div class="tn-alert-title">Informasi</div>
                <div class="tn-alert-text">
                    Jawab pertanyaan di bawah untuk melanjutkan ke pengaturan password baru.
                </div>
            </div>

            <form method="POST" action="{{ route('password.reset.security.verify') }}">
                @csrf

                <div class="security-box">
                    <label for="question">Pertanyaan keamanan</label>
                    <input
                        type="text"
                        id="question"
                        class="form-control"
                        value="{{ $user->security_question ?? 'Pertanyaan keamanan belum tersedia.' }}"
                        disabled>
                </div>

                <div class="form-group">
                    <label for="answer">Jawaban</label>
                    <input type="text" id="answer" name="answer" class="form-control"
                        placeholder="Masukkan jawaban" required>
                </div>

                <button type="submit" class="btn-primary login-submit">
                    <i data-lucide="check-circle-2"></i>
                    Verifikasi Jawaban
                </button>
            </form>

            <div class="reset-hint">
                <a class="small-link" href="{{ route('password.forgot.reset') }}">Kembali ke pilihan metode</a>
            </div>
            @endif
        </section>
    </div>

    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();

            const typedEl = document.getElementById("typedTitle");
            if (!typedEl) return;

            const texts = ["Cepat", "Aman", "Terstruktur", "Tanpa Ribet"];
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = Array.from(document.querySelectorAll('.otp-input'));
            const hidden = document.getElementById('otpHidden');
            const form = document.getElementById('otpForm');

            if (!inputs.length || !hidden || !form) return;

            inputs[0].focus();

            inputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/\D/g, '').slice(0, 1);

                    if (input.value && inputs[index + 1]) {
                        inputs[index + 1].focus();
                    }

                    hidden.value = inputs.map(el => el.value).join('');
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && inputs[index - 1]) {
                        inputs[index - 1].focus();
                    }
                });

                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);

                    pasted.split('').forEach((digit, i) => {
                        if (inputs[i]) inputs[i].value = digit;
                    });

                    hidden.value = inputs.map(el => el.value).join('');
                    const nextEmpty = inputs.find(el => !el.value);
                    if (nextEmpty) nextEmpty.focus();
                });
            });

            form.addEventListener('submit', () => {
                hidden.value = inputs.map(el => el.value).join('');
            });
        });
    </script>
</body>

</html>