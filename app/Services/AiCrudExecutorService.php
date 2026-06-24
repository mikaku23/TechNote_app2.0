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
        'nonactive' => 'is_active',
        'nonaktif' => 'is_active',
        'inactive' => 'is_active',
        'deactivate' => 'is_active',
        'disable' => 'is_active',
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
        'contacts',
        'password_reset_otps',
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
        'contacts' => ['subject', 'message'],
        'password_reset_otps' => ['user_id', 'otp', 'expired_at'],
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
        'contacts.category' => ['question', 'suggestion', 'complaint'],
        'contacts.status' => ['pending', 'read', 'resolved'],
        'password_reset_otps.used_at' => null,
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
        'contacts.user_id' => 'users',
        'password_reset_otps.user_id' => 'users',
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
                    'read'   => $this->read($table, $entity, $payload),
                    'update' => $this->update($table, $entity, $payload),
                    'delete' => $this->delete($table, $entity, $payload),
                    'restore' => $this->restore($table, $entity, $payload),
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
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at'], $data['delete_at']);

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
        if (($enumCheck['ok'] ?? false) === false) {
            return $enumCheck;
        }

        $fkCheck = $this->validateForeignKeys($table, $data);
        if (($fkCheck['ok'] ?? false) === false) {
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

    protected function read(string $table, string $entity, array $payload): array
    {
        $target = $this->normalizeTarget($payload['target'] ?? []);
        $filters = $payload['filters'] ?? [];
        $limit = (int) ($filters['limit'] ?? 10);
        $includeDeleted = (bool) ($filters['include_deleted'] ?? false);
        $onlyDeleted = (bool) ($filters['only_deleted'] ?? false);

        $query = DB::table($table);
        $this->applySoftDeleteScope($query, $table, $includeDeleted, $onlyDeleted);

        if (! empty($target)) {
            $resolved = $this->resolveTargets($table, $target, $includeDeleted, $onlyDeleted);
            if (($resolved['ok'] ?? false) === false) {
                return $resolved;
            }

            if (($resolved['status'] ?? '') === 'ambiguous') {
                return $resolved;
            }

            return [
                'ok' => true,
                'status' => 'found',
                'message' => 'Data ditemukan.',
                'rows' => [$this->toArray($resolved['row'])],
                'count' => 1,
                'soft_deleted_count' => $this->softDeletedCount($table, $filters),
                'active_count' => 1,
            ];
        }

        $this->applyListFilters($query, $table, $filters);

        $rows = $query->orderBy('id')->limit(max(1, min($limit, 10)))->get($this->previewColumns($table))->toArray();
        $rows = $this->enrichRowsWithRelations($table, $rows);

        return [
            'ok' => true,
            'status' => 'success',
            'message' => ! empty($rows) ? 'Data ditemukan.' : 'Data tidak ditemukan.',
            'rows' => $rows,
            'count' => count($rows),
            'limit' => $limit,
            'soft_deleted_count' => $this->softDeletedCount($table, $filters),
            'active_count' => count($rows),
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

        if (($resolved['ok'] ?? false) === false) {
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

        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at'], $data['delete_at']);
        $data = $this->filterWritableColumns($table, $data, false);

        if (empty($data)) {
            return $this->specialUpdateFallback($table, $entity, $row, $payload);
        }

        $enumCheck = $this->validateEnumFields($table, $data);
        if (($enumCheck['ok'] ?? false) === false) {
            return $enumCheck;
        }

        $fkCheck = $this->validateForeignKeys($table, $data);
        if (($fkCheck['ok'] ?? false) === false) {
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

        if (empty($target)) {
            return [
                'ok' => false,
                'status' => 'target_missing',
                'message' => 'Target delete tidak boleh kosong.',
            ];
        }

        $resolved = $this->resolveTargets($table, $target, $includeDeleted, $onlyDeleted);

        if (($resolved['ok'] ?? false) === false) {
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

        $cascadeReport = [];
        $visited = [];
        $this->applyDeleteCascade($table, (int) $row->id, $cascadeReport, $visited);

        return [
            'ok' => true,
            'status' => 'success',
            'message' => 'Data ' . $this->entityLabel($entity) . ' berhasil dihapus.',
            'affected' => $cascadeReport['affected'] ?? 1,
            'id' => $row->id,
            'soft_deleted' => $this->hasSoftDeletes($table),
            'cascade' => $cascadeReport,
        ];
    }

    protected function restore(string $table, string $entity, array $payload): array
    {
        if (! $this->hasSoftDeletes($table)) {
            return [
                'ok' => false,
                'status' => 'restore_not_supported',
                'message' => 'Tabel ini tidak mendukung restore.',
            ];
        }

        $target = $this->normalizeTarget($payload['target'] ?? null);

        if (isset($payload['id']) && is_numeric($payload['id'])) {
            $target = ['id' => (int) $payload['id']];
        }

        if (empty($target)) {
            return [
                'ok' => false,
                'status' => 'target_missing',
                'message' => 'Target restore tidak boleh kosong.',
            ];
        }

        $resolved = $this->resolveTargets($table, $target, true, true);

        if (($resolved['ok'] ?? false) === false) {
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
                'message' => 'Target restore tidak ditemukan.',
            ];
        }

        $cascadeReport = [];
        $visited = [];
        $this->applyRestoreCascade($table, (int) $row->id, $cascadeReport, $visited);

        return [
            'ok' => true,
            'status' => 'success',
            'message' => 'Data ' . $this->entityLabel($entity) . ' berhasil dipulihkan.',
            'affected' => $cascadeReport['affected'] ?? 1,
            'id' => $row->id,
            'restored' => true,
            'cascade' => $cascadeReport,
        ];
    }


    protected function deleteRelationRules(): array
    {
        // Aturan ini disusun dari skema database + relasi logis yang dipakai aplikasi.
        // Arah cascade selalu parent -> child. Child yang dihapus tidak menghapus parent.
        return [
            'roles' => [
                ['table' => 'users', 'fk' => 'role_id', 'action' => 'cascade', 'source' => 'db'],
            ],
            'users' => [
                ['table' => 'tickets', 'fk' => 'user_id', 'action' => 'cascade', 'source' => 'db'],
                ['table' => 'password_reset_otps', 'fk' => 'user_id', 'action' => 'cascade', 'source' => 'db'],
                ['table' => 'notifications', 'fk' => 'user_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'ticket_comments', 'fk' => 'user_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'penginstalans', 'fk' => 'user_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'perbaikans', 'fk' => 'user_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'login_logs', 'fk' => 'user_id', 'action' => 'set_null', 'source' => 'db'],
                ['table' => 'user_activities', 'fk' => 'user_id', 'action' => 'set_null', 'source' => 'db'],
                ['table' => 'ai_logs', 'fk' => 'user_id', 'action' => 'set_null', 'source' => 'db'],
                ['table' => 'ai_tasks', 'fk' => 'user_id', 'action' => 'set_null', 'source' => 'db'],
                ['table' => 'contacts', 'fk' => 'user_id', 'action' => 'set_null', 'source' => 'db'],
            ],
            'tickets' => [
                ['table' => 'penginstalans', 'fk' => 'ticket_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'perbaikans', 'fk' => 'ticket_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'ticket_comments', 'fk' => 'ticket_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'ticket_status_logs', 'fk' => 'ticket_id', 'action' => 'cascade', 'source' => 'app'],
                ['table' => 'notifications', 'fk' => 'ticket_id', 'action' => 'set_null', 'source' => 'db'],
                ['table' => 'ai_recommendations', 'fk' => 'ticket_id', 'action' => 'set_null', 'source' => 'db'],
                ['table' => 'vercel_sync_logs', 'fk' => 'ticket_id', 'action' => 'set_null', 'source' => 'db'],
            ],
            'software' => [
                ['table' => 'penginstalans', 'fk' => 'software_id', 'action' => 'cascade', 'source' => 'app'],
            ],
            'ai_logs' => [
                ['table' => 'ai_action_logs', 'fk' => 'ai_log_id', 'action' => 'set_null', 'source' => 'db'],
            ],
        ];
    }

    protected function applyDeleteCascade(string $table, int $id, array &$report, array &$visited): array
    {
        $key = $table . ':' . $id;

        if (isset($visited[$key])) {
            return $report;
        }

        $visited[$key] = true;
        $report['affected'] = $report['affected'] ?? 0;
        $report['deleted'] = $report['deleted'] ?? [];
        $report['updated'] = $report['updated'] ?? [];
        $report['nullified'] = $report['nullified'] ?? [];
        $report['skipped'] = $report['skipped'] ?? [];

        foreach ($this->deleteRelationRules()[$table] ?? [] as $relation) {
            $childTable = $relation['table'];
            $fk = $relation['fk'];
            $action = $relation['action'] ?? 'cascade';

            if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, $fk)) {
                $report['skipped'][] = $childTable . '.' . $fk . ' (missing table/column)';
                continue;
            }

            if ($action === 'set_null') {
                $affected = DB::table($childTable)
                    ->where($fk, $id)
                    ->update([
                        $fk => null,
                        'updated_at' => now(),
                    ]);

                if ($affected > 0) {
                    $report['affected'] += $affected;
                    $report['nullified'][] = [
                        'table' => $childTable,
                        'fk' => $fk,
                        'parent_id' => $id,
                        'affected' => $affected,
                    ];
                }

                continue;
            }

            $childIds = DB::table($childTable)
                ->where($fk, $id)
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->all();

            foreach ($childIds as $childId) {
                $report = $this->applyDeleteCascade($childTable, $childId, $report, $visited);
            }
        }

        if ($this->hasSoftDeletes($table)) {
            $deletedColumn = $this->softDeleteColumn($table);
        $affected = DB::table($table)
                ->where('id', $id)
                ->update([
                    $deletedColumn => now(),
                    'updated_at' => now(),
                ]);

            if ($affected > 0) {
                $report['affected'] += $affected;
                $report['deleted'][] = [
                    'table' => $table,
                    'id' => $id,
                    'soft_deleted' => true,
                ];
            }

            return $report;
        }

        $affected = DB::table($table)
            ->where('id', $id)
            ->delete();

        if ($affected > 0) {
            $report['affected'] += $affected;
            $report['deleted'][] = [
                'table' => $table,
                'id' => $id,
                'soft_deleted' => false,
            ];
        }

        return $report;
    }

    protected function applyRestoreCascade(string $table, int $id, array &$report, array &$visited): array
    {
        $key = $table . ':' . $id;

        if (isset($visited[$key])) {
            return $report;
        }

        $visited[$key] = true;
        $report['affected'] = $report['affected'] ?? 0;
        $report['restored'] = $report['restored'] ?? [];
        $report['skipped'] = $report['skipped'] ?? [];

        foreach ($this->deleteRelationRules()[$table] ?? [] as $relation) {
            if (($relation['action'] ?? 'cascade') !== 'cascade') {
                continue;
            }

            $childTable = $relation['table'];
            $fk = $relation['fk'];

            if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, $fk)) {
                $report['skipped'][] = $childTable . '.' . $fk . ' (missing table/column)';
                continue;
            }

            $childIds = DB::table($childTable)
                ->where($fk, $id)
                ->whereNotNull($this->softDeleteColumn($table) ?? 'deleted_at')
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->all();

            foreach ($childIds as $childId) {
                $report = $this->applyRestoreCascade($childTable, $childId, $report, $visited);
            }
        }

        if ($this->hasSoftDeletes($table)) {
            $affected = DB::table($table)
                ->where('id', $id)
                ->whereNotNull($this->softDeleteColumn($table) ?? 'deleted_at')
                ->update([
                    $deletedColumn => null,
                    'updated_at' => now(),
                ]);

            if ($affected > 0) {
                $report['affected'] += $affected;
                $report['restored'][] = [
                    'table' => $table,
                    'id' => $id,
                ];
            }

            return $report;
        }

        return $report;
    }

    protected function specialUpdateFallback(string $table, string $entity, object $row, array $payload): array
    {
        $data = $this->normalizeData($payload['data'] ?? []);
        $statusLike = $data['is_active'] ?? $data['status'] ?? $data['sync_status'] ?? null;

        if ($table === 'users' && $statusLike !== null) {
            $roleId = DB::table('users')->where('id', $row->id)->value('role_id');

            if ($roleId && Schema::hasTable('roles') && Schema::hasColumn('roles', 'is_active')) {
                $affected = DB::table('roles')
                    ->where('id', (int) $roleId)
                    ->update([
                        'is_active' => $this->toBool($statusLike),
                        'updated_at' => now(),
                    ]);

                return [
                    'ok' => $affected > 0,
                    'status' => $affected > 0 ? 'success' : 'not_found',
                    'message' => $affected > 0
                        ? 'Role terkait user berhasil diperbarui sehingga status login ikut berubah.'
                        : 'Role terkait user tidak ditemukan.',
                    'affected' => $affected,
                    'id' => $row->id,
                    'redirected_table' => 'roles',
                    'redirected_id' => (int) $roleId,
                ];
            }
        }

        return [
            'ok' => false,
            'status' => 'empty_data',
            'message' => 'Data perubahan belum cukup jelas. Kirim field yang mau diubah.',
        ];
    }

    protected function applySoftDeleteScope($query, string $table, bool $includeSoftDeleted = false, bool $onlyTrashed = false)
    {
        if (! $this->hasSoftDeletes($table)) {
            return $query;
        }

        if ($onlyTrashed) {
            return $query->whereNotNull($this->softDeleteColumn($table) ?? 'deleted_at');
        }

        if (! $includeSoftDeleted) {
            return $query->whereNull($this->softDeleteColumn($table));
        }

        return $query;
    }

    protected function softDeletedCount(string $table, array $filters = []): int
    {
        if (! $this->hasSoftDeletes($table)) {
            return 0;
        }

        $query = DB::table($table);
        $this->applySoftDeleteScope($query, $table, false, true);
        $this->applyListFilters($query, $table, $filters);

        return (int) $query->count();
    }

    protected function applyListFilters($query, string $table, array $filters): void
    {
        if (($filters['today'] ?? false) === true && Schema::hasColumn($table, 'created_at')) {
            $query->whereDate('created_at', now()->toDateString());
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $this->applySearch($query, $table, (string) $filters['search']);
        }

        if (($filters['id'] ?? null) && is_numeric($filters['id'])) {
            $query->where('id', (int) $filters['id']);
        }
    }

    protected function resolveTargets(string $table, array $target, bool $includeSoftDeleted = false, bool $onlyTrashed = false): array
    {
        if (! Schema::hasTable($table)) {
            return [
                'ok' => false,
                'status' => 'invalid_table',
                'message' => 'Tabel tidak ditemukan.',
            ];
        }

        $query = DB::table($table);
        $this->applySoftDeleteScope($query, $table, $includeSoftDeleted, $onlyTrashed);

        if (isset($target['id']) && is_numeric($target['id'])) {
            $row = $query
                ->where('id', (int) $target['id'])
                ->first($this->previewColumns($table));

            if (! $row) {
                return [
                    'ok' => false,
                    'status' => 'not_found',
                    'message' => 'Data dengan ID tersebut tidak ditemukan.',
                ];
            }

            $row = $this->enrichRowWithRelations($table, $row);

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
                $query->where($key, (int) $value);
            } else {
                $query->where($key, 'like', '%' . $value . '%');
            }
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
            ->get($this->previewColumns($table))
            ->map(fn ($row) => $this->enrichRowWithRelations($table, $row));

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

    protected function applySearch($query, string $table, string $term): void
    {
        $term = trim($term);
        $columns = $this->searchableColumns($table);

        $query->where(function ($q) use ($columns, $term) {
            foreach ($columns as $i => $column) {
                if ($i === 0) {
                    $q->where($column, 'like', '%' . $term . '%');
                } else {
                    $q->orWhere($column, 'like', '%' . $term . '%');
                }
            }
        });
    }

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

    protected function previewColumns(string $table): array
    {
        $columns = Schema::getColumnListing($table);

        $preferred = [
            'id','role_id','user_id','ticket_id','software_id','changed_by','ai_log_id',
            'name','username','email','nim','nip','no_hp','ticket_number','type','status','priority',
            'title','message','description','developer','version','task_name','instruction','recommendation','reason',
            'item_name','item_location','damage_description','repair_action','repair_result','installation_result',
            'note','url','key','value','sync_status','response','rekap_date','comment','module','activity',
            'created_at','updated_at','deleted_at','delete_at','is_active','is_read','is_public','is_internal','category','otp','expired_at','used_at'
        ];

        $selected = array_values(array_intersect($preferred, $columns));

        return ! empty($selected) ? $selected : ['*'];
    }

    protected function filterWritableColumns(string $table, array $data, bool $isCreate): array
    {
        $columns = Schema::getColumnListing($table);
        $blocked = ['id', 'created_at', 'updated_at', 'deleted_at', 'delete_at', 'remember_token'];

        $allowed = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $blocked, true)) {
                continue;
            }

            $key = $this->normalizeKey($key);

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
            $key = $this->normalizeKey((string) $key);

            if (isset($this->fieldAliases[$key])) {
                $key = $this->fieldAliases[$key];
            }

            if (is_string($item)) {
                $item = trim($item);
            }

            if ($item === '') {
                continue;
            }

            $item = $this->normalizeScalar($item);
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
        $value = $this->normalizeScalar($value);

        if ($value === '') {
            return null;
        }

        $enumKey = $table . '.' . $column;
        $enumAllowed = $this->enumAllowedValues[$enumKey] ?? null;

        if (is_array($enumAllowed)) {
            $valueLower = mb_strtolower((string) $value);
            if (in_array($valueLower, $enumAllowed, true)) {
                return $valueLower;
            }
        }

        if (in_array($column, ['is_active', 'is_read', 'is_public', 'is_internal'], true)) {
            return $this->toBool($value);
        }

        if (in_array($column, ['estimated_minutes', 'queue_number', 'role_id', 'user_id', 'ticket_id', 'software_id', 'changed_by'], true)) {
            return is_numeric($value) ? (int) $value : $value;
        }

        return $value;
    }

    protected function normalizeScalar(mixed $value): mixed
    {
        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        if (is_string($value)) {
            $low = mb_strtolower(trim($value));

            if (in_array($low, ['nonactive', 'nonaktif', 'inactive', 'deactivate', 'disable', 'mati', 'off', 'false', '0'], true)) {
                return false;
            }

            if (in_array($low, ['active', 'aktif', 'enable', 'enabled', 'hidup', 'on', 'true', '1'], true)) {
                return true;
            }
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

            if (! $foreignTable || $value === null || $value === '') {
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

            $exists = DB::table($foreignTable)->where('id', (int) $value)->exists();

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


    protected function enrichRowsWithRelations(string $table, array $rows): array
    {
        return array_map(function ($row) use ($table) {
            return $this->enrichRowWithRelations($table, $row);
        }, $rows);
    }

    protected function enrichRowWithRelations(string $table, mixed $row): mixed
    {
        if (! is_array($row) && ! is_object($row)) {
            return $row;
        }

        $data = (array) $row;

        foreach ($this->foreignKeyMap as $mapKey => $foreignTable) {
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
            'contacts' => 'Contact',
            'password_reset_otps' => 'Password Reset OTP',
            default => ucfirst(str_replace('_', ' ', $entity)),
        };
    }

    protected function softDeleteColumn(string $table): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            return 'deleted_at';
        }

        if (Schema::hasColumn($table, 'delete_at')) {
            return 'delete_at';
        }

        return null;
    }

    protected function hasSoftDeletes(string $table): bool
    {
        return $this->softDeleteColumn($table) !== null;
    }

    protected function normalizeKey(string $key): string
    {
        $key = mb_strtolower(trim($key));
        return str_replace([' ', '-', '.'], '_', $key);
    }

    protected function toArray(mixed $row): array
    {
        return (array) $row;
    }
}
