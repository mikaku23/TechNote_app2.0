<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemControlService
{
    private const MAINTENANCE_KEY = 'technote:maintenance:state';

    public function defaultModes(): array
    {
        return [
            'forgot_password'     => true,
            'student_booking'     => true,
            'anti_ai_mode'        => false,
            'ai_admin_permission' => false,
            'otp_whatsapp'        => true,
            'otp_email'           => true,
            'security_question'   => true,
        ];
    }

    public function getSystemSettingFeatures(): array
    {
        $modes = $this->loadModes();

        return [
            [
                'key'         => 'forgot_password',
                'title'       => 'Mode Lupa Password',
                'description' => 'Menonaktifkan menu lupa password di login sementara waktu.',
                'enabled'     => (bool) ($modes['forgot_password'] ?? true),
            ],
            [
                'key'         => 'student_booking',
                'title'       => 'Mode Booking Mahasiswa',
                'description' => 'Seluruh mahasiswa tidak bisa membuat booking penginstalan sementara.',
                'enabled'     => (bool) ($modes['student_booking'] ?? true),
            ],
            [
                'key'         => 'anti_ai_mode',
                'title'       => 'Anti AI Mode',
                'description' => 'Jika aktif, AI tetap boleh membaca tetapi seluruh CRUD diblok total.',
                'enabled'     => (bool) ($modes['anti_ai_mode'] ?? false),
            ],
            [
                'key'         => 'ai_admin_permission',
                'title'       => 'Permission AI Admin',
                'description' => 'Jika aktif, CRUD butuh izin atau approval. Jika nonaktif, CRUD langsung jalan tanpa pertanyaan.',
                'enabled'     => (bool) ($modes['ai_admin_permission'] ?? false),
            ],
            [
                'key'         => 'otp_whatsapp',
                'title'       => 'Mode OTP WhatsApp',
                'description' => 'Menonaktifkan pengiriman OTP WhatsApp sementara untuk testing atau keadaan darurat.',
                'enabled'     => (bool) ($modes['otp_whatsapp'] ?? true),
            ],
            [
                'key'         => 'otp_email',
                'title'       => 'Mode OTP Email',
                'description' => 'Menonaktifkan pengiriman OTP via email sementara.',
                'enabled'     => (bool) ($modes['otp_email'] ?? true),
            ],
            [
                'key'         => 'security_question',
                'title'       => 'Mode Pertanyaan Keamanan',
                'description' => 'Menonaktifkan reset password via pertanyaan keamanan sementara.',
                'enabled'     => (bool) ($modes['security_question'] ?? true),
            ],
        ];
    }

    public function getSystemSettingFeatureText(): string
    {
        $items = $this->getSystemSettingFeatures();

        $lines = [];
        foreach ($items as $item) {
            $status = ($item['enabled'] ?? false) ? 'aktif' : 'nonaktif';
            $lines[] = '- ' . $item['title'] . ' [' . $status . ']';
        }

        return "Fitur setting sistem yang bisa diubah:
" . implode("
", $lines);
    }

    public function defaultMaintenanceState(): array
    {
        return [
            'active'       => false,
            'started_at'   => null,
            'ends_at'      => null,
            'scope'        => [
                'type'  => 'global',   // global|role|user_name|user_id
                'value' => null,
            ],
        ];
    }

    public function getMaintenanceState(): array
    {
        $state = Cache::get(self::MAINTENANCE_KEY, $this->defaultMaintenanceState());

        if (($state['active'] ?? false) && !empty($state['ends_at']) && now()->timestamp >= (int) $state['ends_at']) {
            $this->stopMaintenance();
            return $this->defaultMaintenanceState();
        }

        return array_replace_recursive($this->defaultMaintenanceState(), is_array($state) ? $state : []);
    }

    public function isMaintenanceActive(): bool
    {
        return (bool) ($this->getMaintenanceState()['active'] ?? false);
    }

    public function startMaintenance(int $minutes = 30, array $scope = ['type' => 'global', 'value' => null]): array
    {
        $minutes = max(1, min($minutes, 1440));
        $scopeType = $scope['type'] ?? 'global';
        $scopeValue = $scope['value'] ?? null;

        $state = [
            'active'     => true,
            'started_at' => now()->timestamp,
            'ends_at'    => now()->addMinutes($minutes)->timestamp,
            'scope'      => [
                'type'  => $scopeType,
                'value' => $scopeValue,
            ],
        ];

        Cache::forever(self::MAINTENANCE_KEY, $state);

        return $state;
    }

    public function stopMaintenance(): void
    {
        $state = $this->getMaintenanceState();

        Cache::forget(self::MAINTENANCE_KEY);
    }

    public function syncMaintenanceState(): array
    {
        $state = $this->getMaintenanceState();

        if (!($state['active'] ?? false)) {
            return $state;
        }

        if (!empty($state['ends_at']) && now()->timestamp >= (int) $state['ends_at']) {
            $this->stopMaintenance();
            return $this->defaultMaintenanceState();
        }

        return $state;
    }

    public function maintenanceAffectsUser(User $user): bool
    {
        $state = $this->getMaintenanceState();

        if (!($state['active'] ?? false)) {
            return false;
        }

        $scopeType = $state['scope']['type'] ?? 'global';
        $scopeValue = $state['scope']['value'] ?? null;

        return match ($scopeType) {
            'global'    => in_array($user->role?->name, ['Mahasiswa', 'Dosen'], true),
            'role'      => mb_strtolower((string) $user->role?->name) === mb_strtolower((string) $scopeValue),
            'user_name' => mb_strtolower((string) $user->name) === mb_strtolower((string) $scopeValue),
            'user_id'   => (int) $user->id === (int) $scopeValue,
            default     => false,
        };
    }

    public function loadModes(): array
    {
        $modes = $this->defaultModes();

        $rows = DB::table('system_settings')
            ->whereIn('key', array_keys($modes))
            ->get(['key', 'value']);

        foreach ($rows as $row) {
            $modes[$row->key] = $this->castBool($row->value);
        }

        return $modes;
    }

    public function getMode(string $mode): bool
    {
        $modes = $this->loadModes();

        return (bool) ($modes[$mode] ?? ($this->defaultModes()[$mode] ?? false));
    }

    public function setMode(string $mode, bool $value): void
    {
        if (!array_key_exists($mode, $this->defaultModes())) {
            throw new \InvalidArgumentException("Mode tidak dikenal: {$mode}");
        }

        DB::table('system_settings')->updateOrInsert(
            ['key' => $mode],
            [
                'value'      => $value ? '1' : '0',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function toggleMode(string $mode): bool
    {
        $next = ! $this->getMode($mode);
        $this->setMode($mode, $next);

        return $next;
    }

    public function parseCommand(string $text): array
    {
        $text = mb_strtolower(trim($text));

        if ($text === '') {
            return ['action' => 'unknown'];
        }

        if (
            preg_match('/\b(list|daftar|tampilkan|show)\b.*\b(fitur setting sistem|setting sistem|system setting)\b/u', $text)
            || preg_match('/\b(list fitur setting sistem|daftar fitur setting sistem)\b/u', $text)
        ) {
            return ['action' => 'list_features'];
        }

        $isMaintenance = str_contains($text, 'maintenance') || str_contains($text, 'pemeliharaan');
        if ($isMaintenance) {
            if (preg_match('/\b(nonaktifkan|matikan|hentikan|stop|berhenti)\b/u', $text)) {
                return ['action' => 'maintenance_stop'];
            }

            if (preg_match('/\b(aktifkan|nyalakan|hidupkan|start)\b/u', $text)) {
                $minutes = 30;

                if (preg_match('/(\d+)\s*(menit|min|minutes?)\b/u', $text, $m)) {
                    $minutes = (int) $m[1];
                } elseif (preg_match('/(\d+)\s*(jam|hour|hours?)\b/u', $text, $m)) {
                    $minutes = ((int) $m[1]) * 60;
                }

                $scope = ['type' => 'global', 'value' => null];

                if (preg_match('/\b(user\s+dengan\s+role|untuk\s+role|role)\s+([a-z0-9_\-\s]+)\b/u', $text, $m)) {
                    $role = $this->normalizeScopeValue($m[2]);

                    if (mb_strtolower($role) === 'dosen') {
                        $scope = ['type' => 'role', 'value' => 'Dosen'];
                    } elseif (mb_strtolower($role) === 'mahasiswa') {
                        $scope = ['type' => 'role', 'value' => 'Mahasiswa'];
                    } else {
                        $scope = ['type' => 'role', 'value' => mb_convert_case($role, MB_CASE_TITLE, 'UTF-8')];
                    }
                } elseif (preg_match('/\b(user\s+id|id)\s+(\d+)\b/u', $text, $m)) {
                    $scope = ['type' => 'user_id', 'value' => (int) $m[2]];
                } elseif (preg_match('/\b(user\s+dengan\s+nama|nama)\s+([a-z0-9_\-\s]+)\b/u', $text, $m)) {
                    $scope = ['type' => 'user_name', 'value' => $this->normalizeScopeValue($m[2])];
                }

                return [
                    'action'  => 'maintenance_start',
                    'minutes' => $minutes,
                    'scope'   => $scope,
                ];
            }
        }

        $mode = $this->detectModeKey($text);
        if ($mode) {
            if (preg_match('/\b(nonaktifkan|matikan|hentikan|stop|berhenti)\b/u', $text)) {
                return ['action' => 'mode_set', 'mode' => $mode, 'value' => false];
            }

            if (preg_match('/\b(aktifkan|nyalakan|hidupkan|enable|on)\b/u', $text)) {
                return ['action' => 'mode_set', 'mode' => $mode, 'value' => true];
            }

            if (preg_match('/\b(toggle|balik|switch)\b/u', $text)) {
                return ['action' => 'mode_toggle', 'mode' => $mode];
            }
        }

        return ['action' => 'unknown'];
    }

    public function executeCommand(string $text): array
    {
        $parsed = $this->parseCommand($text);

        return match ($parsed['action'] ?? 'unknown') {
            'list_features' => [
                'ok' => true,
                'type' => 'list_features',
                'message' => $this->getSystemSettingFeatureText(),
                'items' => $this->getSystemSettingFeatures(),
            ],
            'maintenance_start' => [
                'ok' => true,
                'type' => 'maintenance_start',
                'state' => $this->startMaintenance(
                    (int) ($parsed['minutes'] ?? 30),
                    (array) ($parsed['scope'] ?? ['type' => 'global', 'value' => null])
                ),
                'message' => $this->formatMaintenanceStarted(
                    (int) ($parsed['minutes'] ?? 30),
                    (array) ($parsed['scope'] ?? ['type' => 'global', 'value' => null])
                ),
            ],
            'maintenance_stop' => $this->executeMaintenanceStop(),
            'mode_set' => [
                'ok' => true,
                'type' => 'mode_set',
                'mode' => $parsed['mode'],
                'value' => (bool) ($parsed['value'] ?? false),
                'message' => $this->executeModeSet((string) $parsed['mode'], (bool) ($parsed['value'] ?? false)),
            ],
            'mode_toggle' => [
                'ok' => true,
                'type' => 'mode_toggle',
                'mode' => $parsed['mode'],
                'value' => $this->toggleMode((string) $parsed['mode']),
                'message' => $this->formatModeMessage((string) $parsed['mode'], $this->getMode((string) $parsed['mode'])),
            ],
            default => [
                'ok' => false,
                'type' => 'unknown',
                'message' => 'Perintah sistem tidak dikenali.',
            ],
        };
    }

    public function executeMaintenanceStop(): array
    {
        $this->stopMaintenance();

        return [
            'ok' => true,
            'type' => 'maintenance_stop',
            'message' => 'Maintenance berhasil dimatikan.',
        ];
    }

    public function executeModeSet(string $mode, bool $value): string
    {
        $this->setMode($mode, $value);

        return $this->formatModeMessage($mode, $value);
    }

    public function formatModeMessage(string $mode, bool $value): string
    {
        $label = $this->modeLabel($mode);
        $stateText = $value ? 'diaktifkan' : 'dinonaktifkan';

        return "{$label} berhasil {$stateText}.";
    }

    public function formatMaintenanceStarted(int $minutes, array $scope): string
    {
        $scopeText = $this->formatScope($scope);

        return "Maintenance berhasil diaktifkan selama {$minutes} menit{$scopeText}.";
    }

    public function formatScope(array $scope): string
    {
        $type = $scope['type'] ?? 'global';
        $value = $scope['value'] ?? null;

        return match ($type) {
            'global'    => ' untuk role Mahasiswa dan Dosen',
            'role'      => ' untuk role ' . $value,
            'user_name' => ' untuk user bernama ' . $value,
            'user_id'   => ' untuk user ID ' . $value,
            default     => '',
        };
    }

    public function modeLabel(string $mode): string
    {
        return match ($mode) {
            'forgot_password' => 'Mode Lupa Password',
            'student_booking' => 'Mode Booking Mahasiswa',
            'anti_ai_mode' => 'Anti AI Mode',
            'ai_admin_permission' => 'Permission AI Admin',
            'otp_whatsapp' => 'Mode OTP WhatsApp',
            'otp_email' => 'Mode OTP Email',
            'security_question' => 'Mode Pertanyaan Keamanan',
            default => $mode,
        };
    }

    public function isRecognizedMode(string $mode): bool
    {
        return array_key_exists($mode, $this->defaultModes());
    }

    public function isUserBlockedByMaintenance(User $user): bool
    {
        return $this->maintenanceAffectsUser($user);
    }

    private function detectModeKey(string $text): ?string
    {
        foreach (array_keys($this->defaultModes()) as $mode) {
            $human = str_replace('_', ' ', $mode);

            if (str_contains($text, $mode) || str_contains($text, $human)) {
                return $mode;
            }
        }

        return null;
    }

    private function normalizeScopeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/\b(aja|saja|dong|deh|nih|itu|nya)\b/u', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private function castBool(mixed $value): bool
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
}
