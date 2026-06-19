<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\user_activitie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    private const MAINTENANCE_ACTIVE_KEY = 'technote:maintenance:active';
    private const MAINTENANCE_STARTED_KEY = 'technote:maintenance:started';
    private const MAINTENANCE_UNTIL_KEY   = 'technote:maintenance:until';

    private const SYSTEM_MODES_KEY = 'technote:system:modes';

    public function maintenanceIndex()
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $this->syncMaintenanceState();

        return view('admin.setting.maintenance.index', [
            'menu'      => 'maintenance',
            'title'     => 'Maintenance Mode',
            'isActive'  => (bool) Cache::get(self::MAINTENANCE_ACTIVE_KEY, false),
            'startedAt' => Cache::get(self::MAINTENANCE_STARTED_KEY),
            'endsAt'    => Cache::get(self::MAINTENANCE_UNTIL_KEY),
        ]);
    }

    public function maintenanceStart(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $data = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $this->activateMaintenance((int) $data['minutes']);

        user_activitie::create([
            'user_id'     => Auth::id(),
            'module'      => 'Setting',
            'activity'    => 'maintenance start',
            'description' => 'mengaktifkan maintenance mode.',
            'old_data'    => null,
            'new_data'    => json_encode([
                'minutes' => (int) $data['minutes'],
                'ends_at'  => Cache::get(self::MAINTENANCE_UNTIL_KEY),
            ]),
        ]);

        return back()->with('success', 'Maintenance berhasil diaktifkan.');
    }

    public function maintenanceStop()
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $this->deactivateMaintenance();

        user_activitie::create([
            'user_id'     => Auth::id(),
            'module'      => 'Setting',
            'activity'    => 'maintenance stop',
            'description' => 'menonaktifkan maintenance mode.',
            'old_data'    => null,
            'new_data'    => null,
        ]);

        return back()->with('success', 'Maintenance berhasil dimatikan.');
    }

    public function systemIndex()
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $modes = array_merge($this->defaultSystemModes(), Cache::get(self::SYSTEM_MODES_KEY, []));

        $items = [
            [
                'key'         => 'forgot_password',
                'title'       => 'Mode Lupa Password',
                'description' => 'Menonaktifkan menu lupa password di login sementara waktu.',
                'icon'        => 'key-round',
                'enabled'     => (bool) ($modes['forgot_password'] ?? true),
            ],
            [
                'key'         => 'student_booking',
                'title'       => 'Mode Booking Mahasiswa',
                'description' => 'Seluruh mahasiswa tidak bisa membuat booking penginstalan sementara.',
                'icon'        => 'calendar-x-2',
                'enabled'     => (bool) ($modes['student_booking'] ?? true),
            ],
            [
                'key'         => 'anti_ai_mode',
                'title'       => 'Anti AI Mode',
                'description' => 'AI tetap bisa membaca dan menganalisis data, tetapi tidak boleh create/update/delete.',
                'icon'        => 'shield-ban',
                'enabled'     => (bool) ($modes['anti_ai_mode'] ?? false),
            ],
            [
                'key'         => 'otp_whatsapp',
                'title'       => 'Mode OTP WhatsApp',
                'description' => 'Menonaktifkan pengiriman OTP WhatsApp sementara untuk testing atau keadaan darurat.',
                'icon'        => 'message-circle-more',
                'enabled'     => (bool) ($modes['otp_whatsapp'] ?? true),
            ],
            [
                'key'         => 'otp_email',
                'title'       => 'Mode OTP Email',
                'description' => 'Menonaktifkan pengiriman OTP via email sementara.',
                'icon'        => 'mail',
                'enabled'     => (bool) ($modes['otp_email'] ?? true),
            ],
            [
                'key'         => 'security_question',
                'title'       => 'Mode Pertanyaan Keamanan',
                'description' => 'Menonaktifkan reset password via pertanyaan keamanan sementara.',
                'icon'        => 'shield-question',
                'enabled'     => (bool) ($modes['security_question'] ?? true),
            ],
        ];

        return view('admin.setting.sistem.index', [
            'menu'  => 'system',
            'title' => 'System Settings',
            'items' => $items,
        ]);
    }

    public function systemToggle(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $data = $request->validate([
            'mode'  => ['required', 'in:forgot_password,student_booking,anti_ai_mode,otp_whatsapp,otp_email,security_question'],
            'value' => ['required', 'boolean'],
        ]);

        $modes = array_merge($this->defaultSystemModes(), Cache::get(self::SYSTEM_MODES_KEY, []));
        $old   = $modes[$data['mode']] ?? null;

        $modes[$data['mode']] = (bool) $data['value'];
        Cache::forever(self::SYSTEM_MODES_KEY, $modes);

        user_activitie::create([
            'user_id'     => Auth::id(),
            'module'      => 'Setting',
            'activity'    => 'system toggle',
            'description' => 'mengubah mode sistem.',
            'old_data'    => json_encode([
                'mode'  => $data['mode'],
                'value' => $old,
            ]),
            'new_data'    => json_encode([
                'mode'  => $data['mode'],
                'value' => (bool) $data['value'],
            ]),
        ]);

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }

    private function defaultSystemModes(): array
    {
        return [
            'forgot_password'   => true,
            'student_booking'   => true,
            'anti_ai_mode'      => false,
            'otp_whatsapp'      => true,
            'otp_email'         => true,
            'security_question' => true,
        ];
    }

    private function activateMaintenance(int $minutes): void
    {
        $endsAt = now()->addMinutes($minutes)->timestamp;

        Cache::forever(self::MAINTENANCE_ACTIVE_KEY, true);
        Cache::forever(self::MAINTENANCE_STARTED_KEY, now()->timestamp);
        Cache::forever(self::MAINTENANCE_UNTIL_KEY, $endsAt);

        Role::whereIn('name', ['Mahasiswa', 'Dosen'])->update([
            'is_active' => false,
        ]);
    }

    private function deactivateMaintenance(): void
    {
        Cache::forget(self::MAINTENANCE_ACTIVE_KEY);
        Cache::forget(self::MAINTENANCE_STARTED_KEY);
        Cache::forget(self::MAINTENANCE_UNTIL_KEY);

        Role::whereIn('name', ['Mahasiswa', 'Dosen'])->update([
            'is_active' => true,
        ]);
    }

    private function syncMaintenanceState(): void
    {
        $isActive = (bool) Cache::get(self::MAINTENANCE_ACTIVE_KEY, false);
        $endsAt   = (int) Cache::get(self::MAINTENANCE_UNTIL_KEY, 0);

        if (! $isActive) {
            Role::whereIn('name', ['Mahasiswa', 'Dosen'])->update([
                'is_active' => true,
            ]);
            return;
        }

        if ($endsAt > 0 && now()->timestamp >= $endsAt) {
            $this->deactivateMaintenance();
            return;
        }

        Role::whereIn('name', ['Mahasiswa', 'Dosen'])->update([
            'is_active' => false,
        ]);
    }
}
