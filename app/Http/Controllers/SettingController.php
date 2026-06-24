<?php

namespace App\Http\Controllers;

use App\Models\user_activitie;
use App\Services\SystemControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function __construct(
        protected SystemControlService $systemControlService
    ) {}

    public function maintenanceIndex()
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $state = $this->systemControlService->syncMaintenanceState();

        return view('admin.setting.maintenance.index', [
            'menu'      => 'maintenance',
            'title'     => 'Maintenance Mode',
            'isActive'   => (bool) ($state['active'] ?? false),
            'startedAt'  => $state['started_at'] ?? null,
            'endsAt'     => $state['ends_at'] ?? null,
            'scope'      => $state['scope'] ?? ['type' => 'global', 'value' => null],
            'lockRoles'  => false,
        ]);
    }

    public function maintenanceStart(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $data = $request->validate([
            'minutes'     => ['nullable', 'integer', 'min:1', 'max:1440'],
            'scope_type'  => ['nullable', 'in:global,role,user_name,user_id'],
            'scope_value' => ['nullable', 'string', 'max:255'],
        ]);

        $minutes = (int) ($data['minutes'] ?? 30);
        $scopeType = $data['scope_type'] ?? 'global';
        $scopeValue = $data['scope_value'] ?? null;

        if ($scopeType === 'user_id' && $scopeValue !== null) {
            $scopeValue = (int) $scopeValue;
        }

        $state = $this->systemControlService->startMaintenance($minutes, [
            'type'  => $scopeType,
            'value' => $scopeValue,
        ]);

        user_activitie::create([
            'user_id'     => Auth::id(),
            'module'      => 'Setting',
            'activity'    => 'maintenance start',
            'description' => 'mengaktifkan maintenance mode.',
            'old_data'    => null,
            'new_data'    => json_encode([
                'minutes' => $minutes,
                'state'   => $state,
            ]),
        ]);

        return back()->with('success', 'Maintenance berhasil diaktifkan.');
    }

    public function maintenanceStop()
    {
        abort_unless(Auth::check() && Auth::user()->role?->name === 'Admin', 403);

        $this->systemControlService->stopMaintenance();

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

        $items = collect($this->systemControlService->getSystemSettingFeatures())
            ->map(function (array $item) {
                return [
                    'key'         => $item['key'],
                    'title'       => $item['title'],
                    'description' => $item['description'],
                    'icon'        => match ($item['key']) {
                        'forgot_password' => 'key-round',
                        'student_booking' => 'calendar-x-2',
                        'anti_ai_mode' => 'shield-ban',
                        'ai_admin_permission' => 'shield-check',
                        'otp_whatsapp' => 'message-circle-more',
                        'otp_email' => 'mail',
                        'security_question' => 'shield-question',
                        default => 'settings',
                    },
                    'enabled'     => (bool) ($item['enabled'] ?? false),
                    'status_text'  => (bool) ($item['enabled'] ?? false) ? 'aktif' : 'nonaktif',
                ];
            })
            ->all();

        $state = $this->systemControlService->syncMaintenanceState();

        return view('admin.setting.sistem.index', [
            'menu'  => 'system',
            'title' => 'System Settings',
            'items' => $items,
            'maintenanceState' => $state,
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

        $old = $this->systemControlService->getMode($mode);

        $this->systemControlService->setMode($mode, $value);

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
}
