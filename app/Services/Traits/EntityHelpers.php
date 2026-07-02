<?php

namespace App\Services\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

trait EntityHelpers
{
    /**
     * Daftar kolom yang bisa dicari untuk tiap entitas.
     */
    protected function searchableColumns(string $entity): array
    {
        return match ($entity) {
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
            'contacts' => ['id', 'subject', 'message', 'category', 'status'],
            'password_reset_otps' => ['id', 'otp', 'expired_at'],
            default => ['id'],
        };
    }

    /**
     * Kolom yang ditampilkan untuk preview.
     */
    protected function previewColumns(string $table): array
    {
        $columns = Schema::getColumnListing($table);

        $preferred = [
            'id',
            'role_id',
            'user_id',
            'ticket_id',
            'software_id',
            'changed_by',
            'ai_log_id',
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
            'delete_at',
            'is_active',
            'is_read',
            'is_public',
            'is_internal',
            'category',
            'otp',
            'expired_at',
            'used_at'
        ];

        $selected = array_values(array_intersect($preferred, $columns));
        return ! empty($selected) ? $selected : ['*'];
    }

    /**
     * Memperkaya baris dengan relasi (nama field asing).
     */
    protected function enrichRowsWithRelations(string $table, array $rows): array
    {
        return array_map(fn($row) => $this->enrichRowWithRelations($table, $row), $rows);
    }

    protected function enrichRowWithRelations(string $table, mixed $row): mixed
    {
        if (! is_array($row) && ! is_object($row)) {
            return $row;
        }

        $data = (array) $row;
        $foreignKeyMap = $this->getForeignKeyMap();

        foreach ($foreignKeyMap as $mapKey => $foreignTable) {
            [$sourceTable, $column] = explode('.', $mapKey, 2);

            if ($sourceTable !== $table) {
                continue;
            }

            $relatedId = $data[$column] ?? null;
            if (! is_numeric($relatedId) || ! Schema::hasTable($foreignTable)) {
                continue;
            }

            $related = DB::table($foreignTable)->where('id', (int) $relatedId)->first();
            if (! $related) {
                continue;
            }

            $relatedData = (array) $related;
            $alias = $this->relationAlias($column, $foreignTable);
            $display = $this->relationDisplayValue($foreignTable, $relatedData);

            if ($display !== null) {
                $data[$alias] = $display;
            }

            if ($foreignTable === 'roles') {
                $data['role_status'] = $this->boolText($relatedData['is_active'] ?? null);
            } elseif (isset($relatedData['is_active'])) {
                $data[$alias . '_status'] = $this->boolText($relatedData['is_active']);
            }
        }

        if (is_object($row)) {
            foreach ($data as $key => $value) {
                $row->{$key} = $value;
            }
            return $row;
        }

        return $data;
    }

    protected function relationAlias(string $column, string $foreignTable): string
    {
        return match ($foreignTable) {
            'roles' => 'role_name',
            'users' => 'user_name',
            'software' => 'software_name',
            'tickets' => 'ticket_number',
            default => (preg_replace('/_id$/', '', $column) ?: $column) . '_name',
        };
    }

    protected function relationDisplayValue(string $foreignTable, array $row): ?string
    {
        $preferred = match ($foreignTable) {
            'users' => ['name', 'username', 'email', 'nim', 'nip'],
            'roles' => ['name', 'description'],
            'software' => ['name', 'developer', 'version'],
            'tickets' => ['ticket_number', 'status', 'type'],
            'trusted_websites' => ['name', 'url'],
            default => ['name', 'title', 'ticket_number', 'subject', 'task_name', 'item_name', 'module', 'key', 'url', 'description'],
        };

        foreach ($preferred as $field) {
            $value = $row[$field] ?? null;
            if ($value !== null && $value !== '') {
                return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return null;
    }

    protected function boolText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'unknown';
        }
        return (bool) $value ? 'active' : 'inactive';
    }

    /**
     * Daftar foreign key antar tabel.
     * Harus di-override jika ada tambahan.
     */
    protected function getForeignKeyMap(): array
    {
        return [
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
            'contacts.user_id' => 'users',
            'password_reset_otps.user_id' => 'users',
        ];
    }
}
