<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiCrudExecutorService
{
    protected array $fieldAliases = [
        'nama' => 'name',
        'deskripsi' => 'description',
        'pengembang' => 'developer',
        'versi' => 'version',
        'estimasi_menit' => 'estimated_minutes',
        'menit_estimasi' => 'estimated_minutes',
        'judul' => 'title',
        'pesan' => 'message',
        'komentar' => 'comment',
        'aktif' => 'is_active',
        'status_baca' => 'is_read',
    ];

    protected array $allowedTables = [
        'users',
        'roles',
        'software',
        'tickets',
        'penginstalans',
        'perbaikans',
        'trusted_websites',
        'login_logs',
        'user_activities',
        'ai_logs',
        'ai_tasks',
        'ai_recommendations',
        'notifications',
        'maintenances',
        'system_settings',
        'rekaps',
        'vercel_sync_logs',
        'ticket_status_logs',
        'ticket_comments',
    ];

    protected array $searchableColumns = [
        'users' => ['id', 'name', 'username', 'email', 'nim', 'nip', 'no_hp'],
        'roles' => ['id', 'name', 'description'],
        'software' => ['id', 'name', 'developer', 'version', 'description'],
        'tickets' => ['id', 'ticket_number', 'type', 'status', 'priority'],
        'penginstalans' => ['id', 'installation_result', 'note'],
        'perbaikans' => ['id', 'item_name', 'item_location', 'damage_description', 'repair_action', 'repair_result', 'note'],
        'trusted_websites' => ['id', 'name', 'url', 'description'],
        'login_logs' => ['id', 'status', 'ip_address', 'user_agent', 'location_status'],
        'user_activities' => ['id', 'module', 'activity', 'description'],
        'ai_logs' => ['id', 'role', 'question', 'answer', 'action', 'source'],
        'ai_tasks' => ['id', 'task_name', 'instruction', 'status'],
        'ai_recommendations' => ['id', 'recommendation', 'reason', 'status'],
        'notifications' => ['id', 'type', 'title', 'message', 'is_read'],
        'maintenances' => ['id', 'message', 'is_active'],
        'system_settings' => ['id', 'key', 'value', 'description'],
        'rekaps' => ['id', 'rekap_date'],
        'vercel_sync_logs' => ['id', 'sync_status', 'response'],
        'ticket_status_logs' => ['id', 'old_status', 'new_status', 'note'],
        'ticket_comments' => ['id', 'comment'],
    ];

    protected array $requiredCreateFields = [
        'users' => ['role_id', 'name', 'username', 'no_hp', 'password'],
        'roles' => ['name'],
        'software' => ['name'],
        'tickets' => ['ticket_number', 'qr_token', 'type', 'user_id'],
        'penginstalans' => ['ticket_id', 'user_id', 'software_id'],
        'perbaikans' => ['ticket_id', 'user_id', 'item_name', 'damage_description'],
        'trusted_websites' => ['name', 'url'],
        'login_logs' => ['status'],
        'user_activities' => ['module', 'activity'],
        'ai_logs' => ['role', 'question', 'answer', 'source'],
        'ai_tasks' => ['task_name', 'instruction', 'status'],
        'ai_recommendations' => ['recommendation', 'reason'],
        'notifications' => ['user_id', 'type', 'title', 'message'],
        'maintenances' => [],
        'system_settings' => ['key', 'value'],
        'rekaps' => ['rekap_date'],
        'vercel_sync_logs' => ['sync_status'],
        'ticket_status_logs' => ['ticket_id', 'new_status'],
        'ticket_comments' => ['ticket_id', 'user_id', 'comment'],
    ];

    protected array $enumAllowedValues = [
        'tickets.type' => ['installation', 'repair'],
        'tickets.status' => ['waiting', 'diagnosis', 'processing', 'testing', 'completed', 'failed', 'cancelled'],
        'tickets.priority' => ['normal', 'high', 'urgent'],
        'penginstalans.installation_result' => ['success', 'failed'],
        'perbaikans.repair_result' => ['success', 'failed', 'unrepairable'],
        'ai_recommendations.status' => ['pending', 'accepted', 'ignored'],
        'ai_tasks.status' => ['pending', 'running', 'completed', 'failed'],
        'notifications.type' => ['system', 'whatsapp', 'ai'],
        'login_logs.status' => ['online', 'offline'],
        'vercel_sync_logs.sync_status' => ['pending', 'success', 'failed'],
        'users.email' => null,
        'maintenances.is_active' => null,
        'system_settings.key' => null,
        'ticket_status_logs.old_status' => null,
        'ticket_status_logs.new_status' => null,
    ];

    protected array $foreignKeyMap = [
        'users.role_id' => 'roles',
        'tickets.user_id' => 'users',
        'penginstalans.ticket_id' => 'tickets',
        'penginstalans.user_id' => 'users',
        'penginstalans.software_id' => 'software',
        'perbaikans.ticket_id' => 'tickets',
        'perbaikans.user_id' => 'users',
        'ai_logs.user_id' => 'users',
        'ai_tasks.user_id' => 'users',
        'ai_recommendations.ticket_id' => 'tickets',
        'notifications.user_id' => 'users',
        'notifications.ticket_id' => 'tickets',
        'login_logs.user_id' => 'users',
        'user_activities.user_id' => 'users',
        'ticket_status_logs.ticket_id' => 'tickets',
        'ticket_status_logs.changed_by' => 'users',
        'ticket_comments.ticket_id' => 'tickets',
        'ticket_comments.user_id' => 'users',
        'vercel_sync_logs.ticket_id' => 'tickets',
    ];

    public function execute(string $operation, string $entity, array $payload): array
    {
        $operation = mb_strtolower(trim($operation));
        $entity = mb_strtolower(trim($entity));
        $table = $this->resolveTable($entity);

        if (! $table) {
            return [
                'ok' => false,
                'status' => 'invalid_entity',
                'message' => 'Entity tidak diizinkan.',
            ];
        }

        try {
            return DB::transaction(function () use ($operation, $table, $entity, $payload) {
                return match ($operation) {
                    'create' => $this->create($table, $entity, $payload),
                    'update' => $this->update($table, $entity, $payload),
                    'delete' => $this->delete($table, $entity, $payload),
                    default  => [
                        'ok' => false,
                        'status' => 'invalid_operation',
                        'message' => 'Operation tidak valid untuk CRUD.',
                    ],
                };
            });
        } catch (Throwable $e) {
            logger()->error('AI CRUD execution failed', [
                'entity' => $entity,
                'table' => $table,
                'operation' => $operation,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 'error',
                'message' => 'Gagal mengeksekusi CRUD.',
            ];
        }
    }

    protected function create(string $table, string $entity, array $payload): array
    {
        $data = $this->normalizeData($payload['data'] ?? $payload);

        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $data = $this->filterWritableColumns($table, $data, true);

        $required = $this->requiredCreateFields[$table] ?? [];
        $missing = $this->missingRequiredFields($data, $required);

        if (! empty($missing)) {
            return [
                'ok' => false,
                'status' => 'missing_fields',
                'message' => 'Field wajib belum lengkap: ' . implode(', ', $missing),
                'missing_fields' => $missing,
            ];
        }

        $enumCheck = $this->validateEnumFields($table, $data);
        if ($enumCheck['ok'] === false) {
            return $enumCheck;
        }

        $fkCheck = $this->validateForeignKeys($table, $data);
        if ($fkCheck['ok'] === false) {
            return $fkCheck;
        }

        $data = $this->stampForInsert($table, $data);

        $id = DB::table($table)->insertGetId($data);

        return [
            'ok' => true,
            'status' => 'success',
            'message' => 'Data ' . $this->entityLabel($entity) . ' berhasil dibuat.',
            'id' => $id,
        ];
    }

    protected function update(string $table, string $entity, array $payload): array
    {
        $data = $this->normalizeData($payload['data'] ?? []);
        $target = $this->normalizeTarget($payload['target'] ?? null);

        if (isset($payload['id']) && is_numeric($payload['id'])) {
            $target = ['id' => (int) $payload['id']];
        }

        $resolved = $this->resolveTargets($table, $target);

        if ($resolved['ok'] === false) {
            return $resolved;
        }

        if (($resolved['status'] ?? '') === 'ambiguous') {
            return $resolved;
        }

        $row = $resolved['row'] ?? null;

        if (! $row || ! isset($row->id)) {
            return [
                'ok' => false,
                'status' => 'target_missing',
                'message' => 'Target update tidak ditemukan.',
            ];
        }

        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $data = $this->filterWritableColumns($table, $data, false);

        if (empty($data)) {
            return [
                'ok' => false,
                'status' => 'empty_data',
                'message' => 'Data update kosong.',
            ];
        }

        $enumCheck = $this->validateEnumFields($table, $data);
        if ($enumCheck['ok'] === false) {
            return $enumCheck;
        }

        $fkCheck = $this->validateForeignKeys($table, $data);
        if ($fkCheck['ok'] === false) {
            return $fkCheck;
        }

        $data = $this->stampForUpdate($table, $data);

        $affected = DB::table($table)
            ->where('id', $row->id)
            ->update($data);

        return [
            'ok' => $affected > 0,
            'status' => $affected > 0 ? 'success' : 'not_found',
            'message' => $affected > 0
                ? 'Data ' . $this->entityLabel($entity) . ' berhasil diubah.'
                : 'Data target ditemukan, tetapi tidak ada perubahan data.',
            'affected' => $affected,
            'id' => $row->id,
        ];
    }

    protected function delete(string $table, string $entity, array $payload): array
    {
        $target = $this->normalizeTarget($payload['target'] ?? null);

        if (isset($payload['id']) && is_numeric($payload['id'])) {
            $target = ['id' => (int) $payload['id']];
        }

        $resolved = $this->resolveTargets($table, $target);

        if ($resolved['ok'] === false) {
            return $resolved;
        }

        if (($resolved['status'] ?? '') === 'ambiguous') {
            return $resolved;
        }

        $row = $resolved['row'] ?? null;

        if (! $row || ! isset($row->id)) {
            return [
                'ok' => false,
                'status' => 'target_missing',
                'message' => 'Target delete tidak ditemukan.',
            ];
        }

        if ($this->hasSoftDeletes($table)) {
            $deleted = DB::table($table)
                ->where('id', $row->id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            return [
                'ok' => $deleted > 0,
                'status' => $deleted > 0 ? 'success' : 'not_found',
                'message' => $deleted > 0
                    ? 'Data ' . $this->entityLabel($entity) . ' berhasil dihapus.'
                    : 'Data target tidak ditemukan.',
                'affected' => $deleted,
                'id' => $row->id,
                'soft_deleted' => true,
            ];
        }

        $deleted = DB::table($table)
            ->where('id', $row->id)
            ->delete();

        return [
            'ok' => $deleted > 0,
            'status' => $deleted > 0 ? 'success' : 'not_found',
            'message' => $deleted > 0
                ? 'Data ' . $this->entityLabel($entity) . ' berhasil dihapus.'
                : 'Data target tidak ditemukan.',
            'affected' => $deleted,
            'id' => $row->id,
            'soft_deleted' => false,
        ];
    }

    protected function resolveTargets(string $table, array $target): array
    {
        if (isset($target['id']) && is_numeric($target['id'])) {
            $row = DB::table($table)
                ->where('id', (int) $target['id'])
                ->first($this->previewColumns($table));

            if (! $row) {
                return [
                    'ok' => false,
                    'status' => 'not_found',
                    'message' => 'Data dengan ID tersebut tidak ditemukan.',
                ];
            }

            return [
                'ok' => true,
                'status' => 'found',
                'row' => $row,
                'rows' => [$row],
                'count' => 1,
            ];
        }

        $target = $this->normalizeTarget($target);

        if (empty($target)) {
            return [
                'ok' => false,
                'status' => 'target_missing',
                'message' => 'Target data belum jelas. Kirim ID atau nama/detail pembeda.',
            ];
        }

        $query = DB::table($table);
        $columns = Schema::getColumnListing($table);

        $applied = false;

        foreach ($target as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (! in_array($key, $columns, true)) {
                continue;
            }

            $applied = true;

            if ($key === 'id' && is_numeric($value)) {
                $query->where('id', (int) $value);
                continue;
            }

            if (is_numeric($value)) {
                $query->where($key, $value);
                continue;
            }

            $query->where($key, 'like', '%' . $value . '%');
        }

        if (! $applied) {
            return [
                'ok' => false,
                'status' => 'target_missing',
                'message' => 'Target data belum bisa dicocokkan.',
            ];
        }

        $rows = $query
            ->orderBy('id')
            ->limit(10)
            ->get($this->previewColumns($table));

        if ($rows->isEmpty()) {
            return [
                'ok' => false,
                'status' => 'not_found',
                'message' => 'Data target tidak ditemukan.',
            ];
        }

        if ($rows->count() > 1) {
            return [
                'ok' => false,
                'status' => 'ambiguous',
                'message' => 'Lebih dari satu data cocok.',
                'matches' => $rows->toArray(),
                'count' => $rows->count(),
            ];
        }

        return [
            'ok' => true,
            'status' => 'found',
            'row' => $rows->first(),
            'rows' => $rows->toArray(),
            'count' => 1,
        ];
    }

    protected function previewColumns(string $table): array
    {
        $columns = Schema::getColumnListing($table);

        $preferred = [
            'id',
            'name',
            'username',
            'email',
            'nim',
            'nip',
            'no_hp',
            'ticket_number',
            'type',
            'status',
            'priority',
            'title',
            'message',
            'description',
            'developer',
            'version',
            'task_name',
            'instruction',
            'recommendation',
            'reason',
            'item_name',
            'item_location',
            'damage_description',
            'repair_action',
            'repair_result',
            'installation_result',
            'note',
            'url',
            'key',
            'value',
            'sync_status',
            'response',
            'rekap_date',
            'comment',
            'module',
            'activity',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $selected = array_values(array_intersect($preferred, $columns));

        return ! empty($selected) ? $selected : ['*'];
    }

    protected function filterWritableColumns(string $table, array $data, bool $isCreate): array
    {
        $columns = Schema::getColumnListing($table);

        $blocked = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
        ];

        $allowed = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $blocked, true)) {
                continue;
            }

            if (isset($this->fieldAliases[$key])) {
                $key = $this->fieldAliases[$key];
            }

            if (in_array($key, $blocked, true)) {
                continue;
            }

            if (in_array($key, $columns, true)) {
                $allowed[$key] = $this->normalizeValue($table, $key, $value);
            }
        }

        if ($isCreate && Schema::hasColumn($table, 'created_at') && ! array_key_exists('created_at', $allowed)) {
            $allowed['created_at'] = now();
        }

        if (Schema::hasColumn($table, 'updated_at') && ! array_key_exists('updated_at', $allowed)) {
            $allowed['updated_at'] = now();
        }

        return $allowed;
    }

    protected function stampForInsert(string $table, array $data): array
    {
        if (Schema::hasColumn($table, 'created_at') && ! array_key_exists('created_at', $data)) {
            $data['created_at'] = now();
        }

        if (Schema::hasColumn($table, 'updated_at') && ! array_key_exists('updated_at', $data)) {
            $data['updated_at'] = now();
        }

        return $data;
    }

    protected function stampForUpdate(string $table, array $data): array
    {
        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = now();
        }

        return $data;
    }

    protected function normalizeData(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $key => $item) {
            $key = is_string($key) ? mb_strtolower(trim($key)) : $key;

            if (isset($this->fieldAliases[$key])) {
                $key = $this->fieldAliases[$key];
            }

            if (is_string($item)) {
                $item = trim($item);
            }

            if ($item === '') {
                continue;
            }

            if (is_string($item) && is_numeric($item)) {
                $item = str_contains($item, '.') ? (float) $item : (int) $item;
            }

            $out[$key] = $item;
        }

        return $out;
    }

    protected function normalizeTarget(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value !== '') {
                return ['name' => $value];
            }

            return [];
        }

        if (! is_array($value)) {
            return [];
        }

        return $this->normalizeData($value);
    }

    protected function normalizeValue(string $table, string $column, mixed $value): mixed
    {
        $value = is_string($value) ? trim($value) : $value;

        if (is_string($value) && $value === '') {
            return null;
        }

        $enumKey = $table . '.' . $column;
        $enumAllowed = $this->enumAllowedValues[$enumKey] ?? null;

        if (is_array($enumAllowed)) {
            $valueLower = mb_strtolower((string) $value);

            if (! in_array($valueLower, $enumAllowed, true)) {
                return $value;
            }

            return $valueLower;
        }

        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        if (in_array($column, ['is_active', 'is_read', 'is_public', 'is_internal'], true)) {
            return $this->toBool($value);
        }

        if (in_array($column, ['estimated_minutes', 'queue_number', 'role_id', 'user_id', 'ticket_id', 'software_id', 'changed_by'], true)) {
            return is_numeric($value) ? (int) $value : $value;
        }

        return $value;
    }

    protected function validateEnumFields(string $table, array $data): array
    {
        foreach ($data as $column => $value) {
            $enumKey = $table . '.' . $column;
            $allowed = $this->enumAllowedValues[$enumKey] ?? null;

            if (! is_array($allowed)) {
                continue;
            }

            $valueLower = mb_strtolower((string) $value);

            if (! in_array($valueLower, $allowed, true)) {
                return [
                    'ok' => false,
                    'status' => 'invalid_enum',
                    'message' => "Nilai field {$column} tidak valid.",
                    'invalid_field' => $column,
                    'allowed_values' => $allowed,
                ];
            }
        }

        return ['ok' => true];
    }

    protected function validateForeignKeys(string $table, array $data): array
    {
        foreach ($data as $column => $value) {
            $mapKey = $table . '.' . $column;
            $foreignTable = $this->foreignKeyMap[$mapKey] ?? null;

            if (! $foreignTable) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (! is_numeric($value)) {
                return [
                    'ok' => false,
                    'status' => 'invalid_foreign_key',
                    'message' => "Field {$column} harus berupa ID numerik.",
                    'invalid_field' => $column,
                ];
            }

            $exists = DB::table($foreignTable)
                ->where('id', (int) $value)
                ->exists();

            if (! $exists) {
                return [
                    'ok' => false,
                    'status' => 'foreign_key_not_found',
                    'message' => "Relasi {$column} tidak ditemukan.",
                    'invalid_field' => $column,
                    'reference_table' => $foreignTable,
                    'reference_id' => (int) $value,
                ];
            }
        }

        return ['ok' => true];
    }

    protected function missingRequiredFields(array $data, array $required): array
    {
        $missing = [];

        foreach ($required as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $value = mb_strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'on', 'aktif', 'enabled'], true);
    }

    protected function resolveTable(string $entity): ?string
    {
        $entity = mb_strtolower(trim($entity));

        return in_array($entity, $this->allowedTables, true) ? $entity : null;
    }

    protected function entityLabel(string $entity): string
    {
        return match ($entity) {
            'users' => 'User',
            'roles' => 'Role',
            'software' => 'Software',
            'tickets' => 'Ticket',
            'penginstalans' => 'Penginstalan',
            'perbaikans' => 'Perbaikan',
            'trusted_websites' => 'Trusted Website',
            'login_logs' => 'Login Log',
            'user_activities' => 'User Activity',
            'ai_logs' => 'AI Log',
            'ai_tasks' => 'AI Task',
            'ai_recommendations' => 'AI Recommendation',
            'notifications' => 'Notification',
            'maintenances' => 'Maintenance',
            'system_settings' => 'System Setting',
            'rekaps' => 'Rekap',
            'vercel_sync_logs' => 'Vercel Sync Log',
            'ticket_status_logs' => 'Ticket Status Log',
            'ticket_comments' => 'Ticket Comment',
            default => ucfirst(str_replace('_', ' ', $entity)),
        };
    }

    protected function hasSoftDeletes(string $table): bool
    {
        return in_array($table, [
            'users',
            'roles',
            'software',
            'tickets',
            'penginstalans',
            'perbaikans',
            'ticket_comments',
        ], true);
    }
}
