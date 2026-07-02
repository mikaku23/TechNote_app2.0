<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TechNoteApp 2.0</title>

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

        .login-hero-top {
            max-width: 560px;
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
            backdrop-filter: blur(18px);
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
            text-wrap: balance;
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

        #typedTitle {
            display: inline-block;
            min-width: 1px;
        }

        .typed-cursor {
            color: var(--primary);
            animation: blink 0.8s infinite;
            margin-left: 2px;
        }

        @keyframes blink {

            0%,
            49% {
                opacity: 1;
            }

            50%,
            100% {
                opacity: 0;
            }
        }

        .login-hero-top p {
            max-width: 520px;
            color: var(--text-light);
            line-height: 1.7;
            font-size: 15px;
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
            backdrop-filter: blur(18px);
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
            backdrop-filter: blur(18px);
        }

        .hero-note strong {
            display: block;
            margin-bottom: 4px;
        }

        .login-header {
            margin-bottom: 24px;
        }

        .login-header .logo-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            margin-bottom: 18px;
        }

        .login-header h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .login-header p {
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
            backdrop-filter: blur(18px);
            padding: 0 16px;
            outline: none;
            color: var(--text);
            transition: 0.3s ease;
        }

        .form-control:focus {
            border-color: rgba(91, 92, 235, 0.45);
            box-shadow: 0 0 0 4px rgba(91, 92, 235, 0.12);
        }

        .password-box {
            position: relative;
        }

        .password-box .toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 12px;
            background: transparent;
            cursor: pointer;
            display: grid;
            place-items: center;
            color: var(--text-light);
        }

        .login-submit {
            width: 100%;
            justify-content: center;
            height: 52px;
            margin-top: 8px;
        }

        .login-error {
            margin-bottom: 16px;
        }

        .login-footer {
            margin-top: 18px;
            color: var(--text-light);
            font-size: 13px;
            line-height: 1.6;
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

        .btn-link {
    background: none;
    border: none;
    padding: 0;
    color: inherit;
    text-decoration: underline;
    cursor: pointer;
    font: inherit;
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
                <div class="logo" style="margin-bottom: 0;">
                    <div class="logo-icon">T</div>
                    <div class="logo-text">
                        TechNote
                        <span>Smart Technician Service</span>
                    </div>
                </div>

                @if(Auth::check())
                @php
                $role = Auth::user()->role->name ?? null;
                @endphp

                @if($role === 'Admin')
                <script>
                    window.location = "{{ route('dashboard.admin') }}";
                </script>
                @elseif($role === 'Mahasiswa')
                <script>
                    window.location = "{{ route('mahasiswa.booking.index') }}";
                </script>
                @elseif($role === 'Dosen')
                <script>
                    window.location = "{{ route('dosen.index') }}";
                </script>

                @endif
                @endif

                <div class="login-hero-top">
                    <span class="hero-kicker hero-kicker-spaced">TechNoteApp 2.0</span>
                    <h1 class="hero-title">
                        <span class="hero-title-static">Smart Service</span>
                        <span class="hero-title-typing">
                            <span id="typedTitle"></span><span class="typed-cursor">|</span>
                        </span>
                    </h1>

                    <p>
                        Sistem layanan ruang teknisi untuk penginstalan software, perbaikan barang, ticketing, monitoring, dan AI internal.
                    </p>

                    <div class="hero-badges">
                        <span class="hero-badge">Ticketing System</span>
                        <span class="hero-badge">AI Assistant</span>
                        <span class="hero-badge">Real-Time Monitoring</span>
                        <span class="hero-badge">Liquid Glass UI</span>
                    </div>
                </div>
            </div>

            <div class="hero-footer">
                <div class="hero-note">
                    <strong>Mahasiswa</strong>
                    Akses dengan NIM.
                </div>
                <div class="hero-note">
                    <strong>Dosen</strong>
                    Akses dengan NIP.
                </div>
            </div>
        </section>

        <section class="login-card glass motion-card">
            <div class="login-header">
                <div class="logo-icon">T</div>
                <h2>Login Sistem</h2>
                <p>Masukkan identitas dan password untuk melanjutkan.</p>
            </div>

            @if ($errors->any())
            <div class="tn-alert tn-alert-danger login-error">
                <div>
                    <strong>Gagal login</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('login.process') }}" autocomplete="off">
                @csrf

                <div class="form-group">
                    <label for="identity">NIP / NIM</label>
                    <input
                        type="text"
                        id="identity"
                        name="identity"
                        class="form-control"
                        placeholder="Masukkan NIP / NIM"
                        value="{{ old('identity') }}"
                        required
                        autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-box">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            required>
                        <button type="button" class="toggle-btn" id="togglePassword" aria-label="Tampilkan password">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="accuracy_m" id="accuracy_m">

                <button type="submit" class="btn-primary login-submit">
                    <i data-lucide="log-in"></i>
                    Masuk
                </button>
            </form>



            @php
            $systemModes = \Illuminate\Support\Facades\Cache::get('technote:system:modes', []);
            $forgotPasswordEnabled = $systemModes['forgot_password'] ?? true;
            @endphp

            <div class="login-footer">
                Pastikan data identitas dan password sesuai dengan akun yang terdaftar.<br>

                @if($forgotPasswordEnabled)
                <a href="{{ route('password.forgot') }}" style="color: inherit; text-decoration: underline;">
                    Lupa password?
                </a>
                @else
                <button
                    type="button"
                    class="btn-link"
                    data-tn-blocked
                    data-tn-only-cancel="true"
                    data-tn-type="warning"
                    data-tn-title="Fitur dinonaktifkan"
                    data-tn-message="Mode lupa password sedang dimatikan sementara oleh admin.">
                    Lupa password?
                </button>
                @endif
            </div>
        </section>
    </div>

    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();

            const toggle = document.getElementById("togglePassword");
            const input = document.getElementById("password");

            if (toggle && input) {
                toggle.addEventListener("click", () => {
                    const isHidden = input.type === "password";
                    input.type = isHidden ? "text" : "password";
                    toggle.innerHTML = isHidden ?
                        '<i data-lucide="eye-off"></i>' :
                        '<i data-lucide="eye"></i>';
                    lucide.createIcons();
                });
            }

            const typedEl = document.getElementById("typedTitle");
            if (!typedEl) return;

            const texts = [
                "for Modern Tech",
                "for Smart Ticketing",
                "for Faster Support",
                "for Technician Flow"
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
    <script>
        let bestFix = null;
        let watchId = null;

        function applyLocation(position) {
            const fix = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                acc: position.coords.accuracy
            };

            if (!bestFix || fix.acc < bestFix.acc) {
                bestFix = fix;

                const lat = document.getElementById('latitude');
                const lng = document.getElementById('longitude');
                const acc = document.getElementById('accuracy_m');

                if (lat) lat.value = fix.lat;
                if (lng) lng.value = fix.lng;
                if (acc) acc.value = fix.acc;
            }
        }

        function startLocationCapture() {
            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition(
                applyLocation,
                function(error) {
                    console.log('Geolocation error:', error.message);
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );

            watchId = navigator.geolocation.watchPosition(
                applyLocation,
                function(error) {
                    console.log('Watch error:', error.message);
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );

            setTimeout(function() {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                }
            }, 8000);
        }

        document.addEventListener('DOMContentLoaded', startLocationCapture);
    </script>
</body>

</html>