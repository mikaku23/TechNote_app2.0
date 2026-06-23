<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\user_activitie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    private const MAINTENANCE_ACTIVE_KEY = 'technote:maintenance:active';
    private const MAINTENANCE_STARTED_KEY = 'technote:maintenance:started';
    private const MAINTENANCE_UNTIL_KEY   = 'technote:maintenance:until';

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

        $modes = $this->loadSystemModes();

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
                'description' => 'Jika aktif, read tetap bebas tetapi seluruh CRUD diblok total.',
                'icon'        => 'shield-ban',
                'enabled'     => (bool) ($modes['anti_ai_mode'] ?? false),
            ],
            [
                'key'         => 'ai_admin_permission',
                'title'       => 'Permission AI Admin',
                'description' => 'Jika aktif, CRUD butuh izin/approval. Jika nonaktif, CRUD langsung jalan tanpa pertanyaan. Anti AI Mode tetap mengalahkan semuanya.',
                'icon'        => 'shield-check',
                'enabled'     => (bool) ($modes['ai_admin_permission'] ?? false),
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
            'mode'  => ['required', 'in:forgot_password,student_booking,anti_ai_mode,ai_admin_permission,otp_whatsapp,otp_email,security_question'],
            'value' => ['required', 'boolean'],
        ]);

        $mode  = $data['mode'];
        $value = (bool) $data['value'];

        $old = $this->getSystemModeValue($mode);

        $this->saveSystemModeValue($mode, $value);

        user_activitie::create([
            'user_id'     => Auth::id(),
            'module'      => 'Setting',
            'activity'    => 'system toggle',
            'description' => 'mengubah mode sistem.',
            'old_data'    => json_encode([
                'mode'  => $mode,
                'value' => $old,
            ]),
            'new_data'    => json_encode([
                'mode'  => $mode,
                'value' => $value,
            ]),
        ]);

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }

    private function loadSystemModes(): array
    {
        $modes = $this->defaultSystemModes();

        $rows = DB::table('system_settings')
            ->whereIn('key', array_keys($modes))
            ->get(['key', 'value']);

        foreach ($rows as $row) {
            $modes[$row->key] = $this->castSettingValue($row->value);
        }

        return $modes;
    }

    private function getSystemModeValue(string $mode): bool
    {
        $row = DB::table('system_settings')
            ->where('key', $mode)
            ->value('value');

        if ($row === null) {
            return (bool) ($this->defaultSystemModes()[$mode] ?? false);
        }

        return $this->castSettingValue($row);
    }

    private function saveSystemModeValue(string $mode, bool $value): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => $mode],
            [
                'value'      => $value ? '1' : '0',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function castSettingValue(mixed $value): bool
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

    private function defaultSystemModes(): array
    {
        return [
            'forgot_password'      => true,
            'student_booking'      => true,
            'anti_ai_mode'         => false,
            'ai_admin_permission'  => false,
            'otp_whatsapp'         => true,
            'otp_email'            => true,
            'security_question'    => true,
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
