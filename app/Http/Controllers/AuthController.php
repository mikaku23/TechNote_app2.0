<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\login_log;
use App\Models\user_activitie;
use App\Services\SystemControlService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Models\role;

class AuthController extends Controller
{
    public function __construct(
        protected SystemControlService $systemControlService
    ) {}

    public function login(Request $request)
    {
        $request->validate([
            'identity'    => ['required', 'string'],
            'password'    => ['required', 'string'],
            'latitude'    => ['nullable', 'numeric'],
            'longitude'   => ['nullable', 'numeric'],
            'accuracy_m'  => ['nullable', 'numeric'],
        ]);

        $identity = trim($request->identity);

        $cooldownSeconds = $this->getLoginCooldownSeconds($identity);
        if ($cooldownSeconds !== null) {
            throw ValidationException::withMessages([
                'password' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $this->formatCooldown($cooldownSeconds) . '.',
            ]);
        }

        $user = User::with('role')
            ->where(function ($query) use ($identity) {
                $query->where('nim', $identity)
                    ->orWhere('nip', $identity)
                    ->orWhere('username', $identity);
            })
            ->first();

        if (! $user) {
            $throttle = $this->registerLoginFailure($identity);

            throw ValidationException::withMessages([
                'identity' => $throttle['message'],
            ]);
        }

        if ($this->systemControlService->isUserBlockedByMaintenance($user)) {
            throw ValidationException::withMessages([
                'identity' => 'Maaf sistem dalam maintenance, silahkan coba lagi nanti.',
            ]);
        }

        if (! Hash::check($request->password, $user->password)) {
            $throttle = $this->registerLoginFailure($identity);

            $message = Cache::has($this->passwordResetNoticeKey($user->id))
                ? 'Password Anda telah diperbarui, silakan masukkan password baru Anda.'
                : 'Password anda salah.';

            if (($throttle['locked'] ?? false) === true) {
                $message = 'Terlalu banyak percobaan login. Coba lagi dalam ' . $this->formatCooldown((int) $throttle['cooldown_seconds']) . '.';
            } elseif (($throttle['remaining_attempts'] ?? 0) === 1) {
                $message .= ' Satu kali percobaan lagi sebelum cooldown 5 menit.';
            }

            throw ValidationException::withMessages([
                'password' => $message,
            ]);
        }

        $this->systemControlService->syncMaintenanceState();

        $maintenanceActive = $this->systemControlService->isMaintenanceActive();
        if ($maintenanceActive && $this->systemControlService->isUserBlockedByMaintenance($user)) {
            throw ValidationException::withMessages([
                'identity' => 'Maaf sistem sedang maintenance, silakan coba lagi nanti.',
            ]);
        }

        $this->clearLoginThrottle($identity);
        Cache::forget($this->passwordResetNoticeKey($user->id));

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
        ]);

        $anchorLat = (float) config('services.login_anchor.latitude');
        $anchorLng = (float) config('services.login_anchor.longitude');
        $radius = (float) config('services.login_anchor.radius', 50);

        $latitude = $request->filled('latitude') ? (float) $request->latitude : null;
        $longitude = $request->filled('longitude') ? (float) $request->longitude : null;
        $accuracyM = $request->filled('accuracy_m') ? (float) $request->accuracy_m : null;

        $distance = null;
        $locationStatus = 'unknown';

        if (! is_null($latitude) && ! is_null($longitude) && ! empty($anchorLat) && ! empty($anchorLng)) {
            $distance = $this->haversineMeters($anchorLat, $anchorLng, $latitude, $longitude);
            $locationStatus = $distance <= $radius ? 'inside' : 'outside';
        }

        login_log::create([
            'user_id'                => $user->id,
            'ip_address'             => $request->ip(),
            'user_agent'             => $request->userAgent(),
            'status'                 => 'online',
            'login_at'               => now(),
            'latitude'               => $latitude,
            'longitude'              => $longitude,
            'accuracy_m'             => $accuracyM,
            'distance_from_anchor_m' => $distance,
            'location_status'        => $locationStatus,
        ]);

        $this->logAuthenticationActivity(
            Auth::id(),
            'login',
            'melakukan login ke sistem.'
        );

        return match ($user->role?->name) {
            'Admin'     => redirect()->route('dashboard.admin'),
            'Mahasiswa' => redirect()->route('mahasiswa.booking.index'),
            'Dosen'     => redirect()->route('dashboard.dosen'),
            default     => abort(403),
        };
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            login_log::where('user_id', $user->id)
                ->where('status', 'online')
                ->latest('login_at')
                ->first()
                ?->update([
                    'status'    => 'offline',
                    'logout_at' => now(),
                ]);

            $this->logAuthenticationActivity(
                Auth::id(),
                'logout',
                'melakukan logout dari sistem.'
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotPassword(Request $request)
    {
        $modes = array_merge($this->systemControlService->defaultModes(), $this->systemControlService->loadModes());

        if (!($modes['forgot_password'] ?? true)) {
            return redirect()
                ->route('login')
                ->with('warning', 'Fitur lupa password sedang dinonaktifkan sementara.');
        }

        $step = $request->query('step', session('password_reset.step', 'choose'));

        $user = null;
        if (session()->has('password_reset.user_id')) {
            $user = User::find(session('password_reset.user_id'));
        }

        $channel = session('password_reset.channel');

        if ($step === 'otp' && (! $user || ! $channel)) {
            $step = 'choose';
        }

        if ($step === 'security' && ! $user) {
            $step = 'choose';
        }

        if ($step === 'otp') {
            if ($channel === 'whatsapp' && !($modes['otp_whatsapp'] ?? true)) {
                $step = 'choose';
                return redirect()
                    ->route('password.forgot')
                    ->with('warning', 'OTP WhatsApp sedang dinonaktifkan sementara.');
            }

            if ($channel === 'email' && !($modes['otp_email'] ?? true)) {
                $step = 'choose';
                return redirect()
                    ->route('password.forgot')
                    ->with('warning', 'OTP Email sedang dinonaktifkan sementara.');
            }
        }

        if ($step === 'security' && !($modes['security_question'] ?? true)) {
            $step = 'choose';
            return redirect()
                ->route('password.forgot')
                ->with('warning', 'Pertanyaan keamanan sedang dinonaktifkan sementara.');
        }

        $resendCooldownSeconds = null;
        $resendCooldownText = null;

        if ($user && in_array($channel, ['whatsapp', 'email'], true)) {
            $resendCooldownSeconds = $this->getOtpResendCooldownSeconds(
                $user->id,
                (string) $channel
            );

            if ($resendCooldownSeconds !== null) {
                $resendCooldownText = $this->formatCooldown($resendCooldownSeconds);
            }
        }

        return view('auth.forgot_password', [
            'step'                  => $step,
            'user'                  => $user,
            'channel'               => $channel,
            'resendCooldownSeconds' => $resendCooldownSeconds,
            'resendCooldownText'    => $resendCooldownText,
            'systemModes'           => $modes,
        ]);
    }

    public function resetForgotPasswordFlow()
    {
        $userId = session('password_reset.user_id');
        $channel = session('password_reset.channel');

        if ($userId && in_array($channel, ['whatsapp', 'email'], true)) {
            Cache::forget($this->otpCacheKey((int) $userId, (string) $channel));
            Cache::forget($this->otpResendThrottleKey((int) $userId, (string) $channel));
        }

        session()->forget([
            'password_reset.user_id',
            'password_reset.channel',
            'password_reset.step',
            'password_reset.verified',
        ]);

        return redirect()->route('password.forgot', ['step' => 'choose']);
    }

    public function sendResetCode(Request $request)
    {
        $validated = $request->validate([
            'identity' => ['required', 'string'],
            'channel'  => ['required', 'in:whatsapp,email,security'],
        ]);

        $user = $this->findResetUser($validated['identity']);

        if (! $user) {
            throw ValidationException::withMessages([
                'identity' => 'Akun tidak ditemukan.',
            ]);
        }

        if ($validated['channel'] === 'whatsapp' && blank($user->no_hp)) {
            throw ValidationException::withMessages([
                'channel' => 'Nomor WhatsApp belum tersedia pada akun ini.',
            ]);
        }

        if ($validated['channel'] === 'email' && blank($user->email)) {
            throw ValidationException::withMessages([
                'channel' => 'Email belum tersedia pada akun ini.',
            ]);
        }

        $this->startPasswordResetContext($user, $validated['channel']);

        if ($validated['channel'] === 'security') {
            $this->logAuthenticationActivity(
                $user->id,
                'start reset password',
                'memulai reset password melalui pertanyaan keamanan.'
            );

            return redirect()
                ->route('password.forgot', ['step' => 'security'])
                ->with('success', 'Jawab pertanyaan keamanan untuk melanjutkan reset password.');
        }

        $otp = (string) random_int(100000, 999999);
        $this->storeOtp($user, $validated['channel'], $otp);

        $sent = $validated['channel'] === 'whatsapp'
            ? $this->sendResetOtpViaWhatsapp($user, $otp)
            : $this->sendResetOtpViaEmail($user, $otp);

        if ($sent) {
            $this->initializeOtpResendThrottle($user, $validated['channel']);

            $this->logAuthenticationActivity(
                $user->id,
                'request otp reset password',
                $validated['channel'] === 'whatsapp'
                    ? 'meminta OTP reset password melalui WhatsApp.'
                    : 'meminta OTP reset password melalui email.'
            );
        }

        if (! $sent) {
            throw ValidationException::withMessages([
                'channel' => 'OTP gagal dikirim. Coba lagi beberapa saat kemudian.',
            ]);
        }

        $notice = $validated['channel'] === 'email'
            ? 'OTP sudah dikirim ke email Anda. Cek juga folder Spam/Junk jika belum muncul.'
            : 'OTP sudah dikirim ke WhatsApp Anda.';

        return redirect()
            ->route('password.forgot', ['step' => 'otp'])
            ->with('success', $notice);
    }

    public function resendResetCode(Request $request)
    {
        $user = $this->currentResetUser();

        if (! $user) {
            return redirect()->route('password.forgot');
        }

        $channel = session('password_reset.channel');

        if (! in_array($channel, ['whatsapp', 'email'], true)) {
            return redirect()->route('password.forgot');
        }

        $cooldownSeconds = $this->getOtpResendCooldownSeconds($user->id, $channel);

        if ($cooldownSeconds !== null) {
            return back()->withErrors([
                'channel' => 'Tunggu ' . $this->formatCooldown($cooldownSeconds) . ' sebelum kirim ulang OTP lagi.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $this->storeOtp($user, $channel, $otp);

        $sent = $channel === 'whatsapp'
            ? $this->sendResetOtpViaWhatsapp($user, $otp)
            : $this->sendResetOtpViaEmail($user, $otp);

        if (! $sent) {
            return back()->withErrors([
                'channel' => 'OTP gagal dikirim ulang.',
            ]);
        }

        $this->registerOtpResend($user, $channel);

        $this->logAuthenticationActivity(
            $user->id,
            'resend otp reset password',
            $channel === 'whatsapp'
                ? 'mengirim ulang OTP reset password melalui WhatsApp.'
                : 'mengirim ulang OTP reset password melalui email.'
        );

        $notice = $channel === 'email'
            ? 'OTP baru sudah dikirim ke email Anda. Periksa juga folder Spam/Junk.'
            : 'OTP baru sudah dikirim ke WhatsApp Anda.';

        return back()->with('success', $notice);
    }

    public function verifyResetOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $this->currentResetUser();

        if (! $user) {
            return redirect()->route('password.forgot');
        }

        $channel = session('password_reset.channel');

        if (! in_array($channel, ['whatsapp', 'email'], true)) {
            return redirect()->route('password.forgot');
        }

        $payload = Cache::get($this->otpCacheKey($user->id, $channel));

        if (! $payload) {
            return back()->withErrors([
                'otp' => 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang.',
            ]);
        }

        $attempts = (int) ($payload['attempts'] ?? 0);

        if ($attempts >= 5) {
            Cache::forget($this->otpCacheKey($user->id, $channel));

            return back()->withErrors([
                'otp' => 'Terlalu banyak percobaan. Kirim ulang OTP terlebih dahulu.',
            ]);
        }

        if (! Hash::check($validated['otp'], $payload['otp_hash'] ?? '')) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($this->otpCacheKey($user->id, $channel), $payload, now()->addMinutes(10));

            return back()->withErrors([
                'otp' => 'Kode OTP salah.',
            ]);
        }

        $this->markPasswordResetVerified($user);

        $this->logAuthenticationActivity(
            $user->id,
            'verify otp reset password',
            $channel === 'whatsapp'
                ? 'berhasil memverifikasi OTP reset password melalui WhatsApp.'
                : 'berhasil memverifikasi OTP reset password melalui email.'
        );

        return redirect()
            ->route('password.reset.form')
            ->with('success', 'OTP valid. Silakan buat password baru.');
    }

    public function verifyResetSecurity(Request $request)
    {
        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->currentResetUser();

        if (! $user) {
            return redirect()->route('password.forgot');
        }

        if (! $this->securityAnswerMatches($user, $validated['answer'])) {
            return back()->withErrors([
                'answer' => 'Jawaban pertanyaan keamanan salah.',
            ]);
        }

        $this->markPasswordResetVerified($user);

        $this->logAuthenticationActivity(
            $user->id,
            'verify security answer',
            'berhasil memverifikasi jawaban pertanyaan keamanan.'
        );

        return redirect()
            ->route('password.reset.form')
            ->with('success', 'Jawaban benar. Silakan buat password baru.');
    }

    public function showResetPasswordForm()
    {
        $user = $this->currentResetUser();

        if (! $user || ! session('password_reset.verified')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset_password', [
            'user' => $user,
        ]);
    }

    public function updateResetPassword(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'min:3', 'confirmed'],
        ]);

        $user = $this->currentResetUser();

        if (! $user || ! session('password_reset.verified')) {
            return redirect()->route('password.forgot');
        }

        if (Hash::check($validated['password'], $user->password)) {
            return back()->with('warning', 'Password baru harus berbeda dari password saat ini. Silakan gunakan password yang benar-benar baru.');
        }

        $user->update([
            'password'       => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ]);

        Cache::put($this->passwordResetNoticeKey($user->id), true, now()->addHours(24));

        user_activitie::create([
            'user_id'     => $user->id,
            'module'      => 'Authentication',
            'activity'    => 'reset password',
            'description' => 'mengganti password melalui fitur lupa password.',
            'old_data'    => null,
            'new_data'    => null,
        ]);

        $this->logAuthenticationActivity(
            $user->id,
            'update password',
            'berhasil mengganti password melalui fitur lupa password.'
        );

        $this->clearPasswordResetContext($user);

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil diubah. Silakan login kembali.');
    }

    private function findResetUser(string $identity): ?User
    {
        $identity = trim($identity);

        return User::with('role')
            ->where(function ($query) use ($identity) {
                $query->where('nim', $identity)
                    ->orWhere('nip', $identity)
                    ->orWhere('username', $identity)
                    ->orWhere('email', $identity)
                    ->orWhere('no_hp', $identity);
            })
            ->first();
    }

    private function currentResetUser(): ?User
    {
        $userId = session('password_reset.user_id');

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    private function startPasswordResetContext(User $user, string $channel): void
    {
        session([
            'password_reset.user_id' => $user->id,
            'password_reset.channel'  => $channel,
            'password_reset.step'     => $channel === 'security' ? 'security' : 'otp',
            'password_reset.verified' => false,
        ]);
    }

    private function markPasswordResetVerified(User $user): void
    {
        session([
            'password_reset.user_id' => $user->id,
            'password_reset.verified' => true,
            'password_reset.step'     => 'new_password',
        ]);
    }

    private function clearPasswordResetContext(User $user): void
    {
        $channel = session('password_reset.channel');

        if (in_array($channel, ['whatsapp', 'email'], true)) {
            Cache::forget($this->otpCacheKey($user->id, $channel));
            Cache::forget($this->otpResendThrottleKey($user->id, $channel));
        }

        session()->forget([
            'password_reset.user_id',
            'password_reset.channel',
            'password_reset.step',
            'password_reset.verified',
        ]);
    }

    private function otpCacheKey(int $userId, string $channel): string
    {
        return 'password-reset:' . $userId . ':' . $channel;
    }

    private function passwordResetNoticeKey(int $userId): string
    {
        return 'password-reset-notice:' . $userId;
    }

    private function storeOtp(User $user, string $channel, string $otp): void
    {
        Cache::put(
            $this->otpCacheKey($user->id, $channel),
            [
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
            ],
            now()->addMinutes(10)
        );
    }

    private function sendResetOtpViaWhatsapp(User $user, string $otp): bool
    {
        $chatId = $this->normalizePhoneToChatId($user->no_hp);

        if (! $chatId) {
            return false;
        }

        $apiUrl = rtrim((string) env('GREEN_API_API_URL'), '/');
        $idInstance = (string) env('GREEN_API_ID_INSTANCE');
        $token = (string) env('GREEN_API_API_TOKEN');

        if ($apiUrl === '' || $idInstance === '' || $token === '') {
            return false;
        }

        $message = "Halo {$user->name},\n\n"
            . "Kode OTP reset password TechNoteAPP Anda adalah:\n"
            . "{$otp}\n\n"
            . "Kode ini berlaku selama 10 menit.\n"
            . "_Jika Anda tidak merasa meminta reset password, abaikan pesan ini._";

        $response = Http::post(
            "{$apiUrl}/waInstance{$idInstance}/sendMessage/{$token}",
            [
                'chatId'  => $chatId,
                'message' => $message,
            ]
        );

        return $response->successful();
    }

    private function sendResetOtpViaEmail(User $user, string $otp): bool
    {
        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $subject = 'OTP Reset Password TechNoteAPP';
        $message = "Halo {$user->name},\n\n"
            . "Kode OTP reset password TechNoteAPP Anda adalah: {$otp}\n\n"
            . "Kode ini berlaku selama 10 menit.\n"
            . "Jika email ini tidak terlihat di inbox, cek folder Spam/Junk.\n\n"
            . "Jika Anda tidak meminta reset password, abaikan pesan ini.";

        try {
            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo(config('mail.from.address'), config('mail.from.name'))
                    ->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    private function securityAnswerMatches(User $user, string $answer): bool
    {
        $stored = trim((string) ($user->security_answer ?? ''));

        if ($stored === '') {
            return false;
        }

        $answer = trim($answer);

        if (
            str_starts_with($stored, '$2y$') ||
            str_starts_with($stored, '$argon2i$') ||
            str_starts_with($stored, '$argon2id$')
        ) {
            return Hash::check($answer, $stored);
        }

        return mb_strtolower($stored) === mb_strtolower($answer);
    }

    private function loginAttemptKey(string $identity): string
    {
        return 'login-attempts:' . mb_strtolower(trim($identity));
    }

    private function loginCooldownKey(string $identity): string
    {
        return 'login-cooldown:' . mb_strtolower(trim($identity));
    }

    private function registerLoginFailure(string $identity): array
    {
        $attemptKey = $this->loginAttemptKey($identity);
        $cooldownKey = $this->loginCooldownKey($identity);

        $attempts = (int) Cache::get($attemptKey, 0);
        $attempts++;

        if ($attempts >= 5) {
            Cache::forget($attemptKey);
            Cache::put($cooldownKey, now()->addMinutes(5)->timestamp, now()->addMinutes(5));

            return [
                'locked' => true,
                'cooldown_seconds' => 300,
                'message' => 'Terlalu banyak percobaan login. Coba lagi dalam 5 menit.',
            ];
        }

        Cache::put($attemptKey, $attempts, now()->addMinutes(5));

        return [
            'locked' => false,
            'remaining_attempts' => 5 - $attempts,
            'message' => $attempts === 4
                ? 'Satu kali percobaan lagi sebelum cooldown 5 menit.'
                : 'Password anda salah.',
        ];
    }

    private function getLoginCooldownSeconds(string $identity): ?int
    {
        $expiresAt = Cache::get($this->loginCooldownKey($identity));

        if (! $expiresAt) {
            return null;
        }

        $remaining = (int) $expiresAt - now()->timestamp;

        if ($remaining <= 0) {
            Cache::forget($this->loginCooldownKey($identity));
            return null;
        }

        return $remaining;
    }

    private function clearLoginThrottle(string $identity): void
    {
        Cache::forget($this->loginAttemptKey($identity));
        Cache::forget($this->loginCooldownKey($identity));
    }

    private function formatCooldown(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;

        if ($minutes > 0) {
            return $minutes . ' menit ' . $secs . ' detik';
        }

        return $secs . ' detik';
    }

    private function normalizePhoneToChatId(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62' . ltrim($digits, '0');
        }

        return $digits . '@c.us';
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function otpResendThrottleKey(int $userId, string $channel): string
    {
        return 'password-reset-resend:' . $userId . ':' . $channel;
    }

    private function initializeOtpResendThrottle(User $user, string $channel): void
    {
        Cache::put(
            $this->otpResendThrottleKey($user->id, $channel),
            [
                'resend_count'   => 0,
                'cooldown_until'  => null,
            ],
            now()->addDay()
        );
    }

    private function getOtpResendCooldownSeconds(int $userId, string $channel): ?int
    {
        $state = Cache::get($this->otpResendThrottleKey($userId, $channel));

        if (! is_array($state) || empty($state['cooldown_until'])) {
            return null;
        }

        $remaining = (int) $state['cooldown_until'] - now()->timestamp;

        if ($remaining <= 0) {
            $state['cooldown_until'] = null;

            Cache::put(
                $this->otpResendThrottleKey($userId, $channel),
                $state,
                now()->addDay()
            );

            return null;
        }

        return $remaining;
    }

    private function registerOtpResend(User $user, string $channel): void
    {
        $key = $this->otpResendThrottleKey($user->id, $channel);

        $state = Cache::get($key, [
            'resend_count'   => 0,
            'cooldown_until' => null,
        ]);

        $resendCount = (int) ($state['resend_count'] ?? 0) + 1;

        $cooldownMinutes = $resendCount + 1;

        $state['resend_count'] = $resendCount;
        $state['cooldown_until'] = now()->addMinutes($cooldownMinutes)->timestamp;

        Cache::put($key, $state, now()->addDay());
    }

    private function logAuthenticationActivity(?int $userId, string $activity, string $description, mixed $oldData = null, mixed $newData = null): void
    {
        if (! $userId) {
            return;
        }

        user_activitie::create([
            'user_id'     => $userId,
            'module'      => 'Authentication',
            'activity'    => $activity,
            'description' => $description,
            'old_data'    => $oldData,
            'new_data'    => $newData,
        ]);
    }
}
