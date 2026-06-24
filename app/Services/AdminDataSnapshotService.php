<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminDataSnapshotService
{
    protected int $ttlSeconds = 60;

    protected array $entities = [
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

    protected function applyActiveScope($query, string $table): void
    {
        $column = $this->softDeleteColumn($table);

        if ($column) {
            $query->whereNull($column);
        }
    }

    public function overview(): array
    {
        return $this->remember('ai:admin:overview', function () {
            $today = now()->toDateString();

            return [
                'tickets' => [
                    'total' => (int) DB::table('tickets')->count(),
                    'waiting' => (int) DB::table('tickets')->where('status', 'waiting')->count(),
                    'diagnosis' => (int) DB::table('tickets')->where('status', 'diagnosis')->count(),
                    'processing' => (int) DB::table('tickets')->where('status', 'processing')->count(),
                    'testing' => (int) DB::table('tickets')->where('status', 'testing')->count(),
                    'completed' => (int) DB::table('tickets')->where('status', 'completed')->count(),
                    'failed' => (int) DB::table('tickets')->where('status', 'failed')->count(),
                    'cancelled' => (int) DB::table('tickets')->where('status', 'cancelled')->count(),
                ],
                'rekap_today' => [
                    'installation' => (int) DB::table('rekaps')->where('rekap_date', $today)->sum('total_installations'),
                    'repair' => (int) DB::table('rekaps')->where('rekap_date', $today)->sum('total_repairs'),
                    'completed' => (int) DB::table('rekaps')->where('rekap_date', $today)->sum('completed_tickets'),
                    'failed' => (int) DB::table('rekaps')->where('rekap_date', $today)->sum('failed_tickets'),
                    'pending' => (int) DB::table('rekaps')->where('rekap_date', $today)->sum('pending_tickets'),
                ],
                'counts' => [
                    'users' => (int) DB::table('users')->when($this->softDeleteColumn('users'), fn ($q, $c) => $q->whereNull($c))->count(),
                    'roles' => (int) DB::table('roles')->when($this->softDeleteColumn('roles'), fn ($q, $c) => $q->whereNull($c))->count(),
                    'software' => (int) DB::table('software')->when($this->softDeleteColumn('software'), fn ($q, $c) => $q->whereNull($c))->count(),
                    'trusted_websites' => (int) DB::table('trusted_websites')->where('is_active', 1)->count(),
                    'maintenances' => (int) DB::table('maintenances')->count(),
                    'ai_logs' => (int) DB::table('ai_logs')->count(),
                    'ai_tasks' => (int) DB::table('ai_tasks')->count(),
                    'ai_recommendations' => (int) DB::table('ai_recommendations')->count(),
                    'notifications' => (int) DB::table('notifications')->count(),
                    'user_activities' => (int) DB::table('user_activities')->count(),
                    'login_logs' => (int) DB::table('login_logs')->count(),
                ],
                'maintenance_active' => (bool) DB::table('maintenances')->where('is_active', 1)->exists(),
                'latest_tickets' => $this->latestTickets(5),
                'latest_ai_logs' => $this->latestAiLogs(5),
                'latest_tasks' => $this->latestAiTasks(5),
                'latest_recommendations' => $this->latestAiRecommendations(5),
                'latest_login_logs' => $this->latestLoginLogs(5),
                'latest_activities' => $this->latestActivities(5),
                'latest_notifications' => $this->latestNotifications(5),
            ];
        });
    }

    public function entity(string $entity, int $limit = 10, array $filters = []): array
    {
        $entity = mb_strtolower(trim($entity));

        $cacheKey = 'ai:admin:entity:' . $entity . ':' . $limit . ':' . hash('sha1', json_encode($filters, JSON_UNESCAPED_UNICODE));

        return $this->remember($cacheKey, function () use ($entity, $limit, $filters) {
            return [
                'entity' => $entity,
                'items' => $this->queryEntity($entity, $limit, $filters),
                'limit' => $limit,
                'filters' => $filters,
                'count' => $this->countEntity($entity, $filters),
            ];
        });
    }

    public function byId(string $entity, int $id): array
    {
        $entity = mb_strtolower(trim($entity));
        $query = DB::table($entity);
        $this->applyActiveScope($query, $entity);

        $row = $query
            ->where('id', $id)
            ->first();

        return [
            'entity' => $entity,
            'item' => $row ? $this->enrichRowWithRelations($entity, $row) : null,
        ];
    }

    public function search(string $entity, array $criteria, int $limit = 10): array
    {
        $entity = mb_strtolower(trim($entity));
        $query = DB::table($entity);
        $this->applyActiveScope($query, $entity);

        foreach ($criteria as $field => $value) {
            $field = mb_strtolower(trim((string) $field));
            if ($value === null || $value === '') {
                continue;
            }

            if (! Schema::hasColumn($entity, $field)) {
                continue;
            }

            if ($field === 'id' && is_numeric($value)) {
                $query->where('id', (int) $value);
            } elseif (is_numeric($value)) {
                $query->where($field, (string) $value);
            } else {
                $query->where($field, 'like', '%' . trim((string) $value) . '%');
            }
        }

        return [
            'entity' => $entity,
            'items' => $this->enrichRowsWithRelations(
                $entity,
                $query->orderBy('id')->limit($limit)->get($this->previewColumns($entity))->toArray()
            ),
        ];
    }

    public function today(string $entity, int $limit = 10): array
    {
        $entity = mb_strtolower(trim($entity));
        $query = DB::table($entity);
        $this->applyActiveScope($query, $entity);

        if ($entity === 'rekaps' && Schema::hasColumn($entity, 'rekap_date')) {
            $query->whereDate('rekap_date', now()->toDateString());
        } elseif (Schema::hasColumn($entity, 'created_at')) {
            $query->whereDate('created_at', now()->toDateString());
        }

        return [
            'entity' => $entity,
            'items' => $this->enrichRowsWithRelations(
                $entity,
                $query->orderBy('id')->limit($limit)->get($this->previewColumns($entity))->toArray()
            ),
        ];
    }

    public function refreshAll(): void
    {
        $this->forget('ai:admin:overview');

        foreach ($this->entities as $entity) {
            $this->forgetPrefix('ai:admin:entity:' . $entity . ':');
        }
    }

    public function availableEntities(): array
    {
        return $this->entities;
    }

    protected function queryEntity(string $entity, int $limit, array $filters): array
    {
        if (! in_array($entity, $this->entities, true) || ! Schema::hasTable($entity)) {
            return [];
        }

        $query = DB::table($entity);
        $this->applyActiveScope($query, $entity);

        if (($filters['today'] ?? false) === true) {
            if ($entity === 'rekaps' && Schema::hasColumn($entity, 'rekap_date')) {
                $query->whereDate('rekap_date', now()->toDateString());
            } elseif (Schema::hasColumn($entity, 'created_at')) {
                $query->whereDate('created_at', now()->toDateString());
            }
        }

        if (($filters['id'] ?? null) && is_numeric($filters['id'])) {
            $query->where('id', (int) $filters['id']);
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $this->applySearch($query, $entity, (string) $filters['search']);
        }

        return $this->enrichRowsWithRelations(
            $entity,
            $query
                ->orderBy('id')
                ->limit($limit)
                ->get($this->previewColumns($entity))
                ->toArray()
        );
    }

    protected function applySearch($query, string $entity, string $term): void
    {
        $term = trim($term);
        $columns = $this->searchableColumns($entity);

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

    protected function countEntity(string $entity, array $filters): int
    {
        if (! in_array($entity, $this->entities, true) || ! Schema::hasTable($entity)) {
            return 0;
        }

        $query = DB::table($entity);
        $this->applyActiveScope($query, $entity);

        if (($filters['today'] ?? false) === true) {
            if ($entity === 'rekaps' && Schema::hasColumn($entity, 'rekap_date')) {
                $query->whereDate('rekap_date', now()->toDateString());
            } elseif (Schema::hasColumn($entity, 'created_at')) {
                $query->whereDate('created_at', now()->toDateString());
            }
        }

        if (($filters['id'] ?? null) && is_numeric($filters['id'])) {
            $query->where('id', (int) $filters['id']);
        }

        return (int) $query->count();
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
            'created_at','updated_at','deleted_at','delete_at','is_active','is_read','is_public','is_internal'
        ];

        $selected = array_values(array_intersect($preferred, $columns));

        return ! empty($selected) ? $selected : ['*'];
    }

    protected function latestTickets(int $limit): array
    {
        return DB::table('tickets')
            ->when($this->softDeleteColumn('tickets'), fn ($q, $c) => $q->whereNull($c))
            ->orderByDesc('id')->limit($limit)->get($this->previewColumns('tickets'))->toArray();
    }

    protected function latestAiLogs(int $limit): array
    {
        return DB::table('ai_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->select('a.id','a.role','a.question','a.answer','a.action','a.source','a.created_at','u.name as user_name')
            ->get()
            ->toArray();
    }

    protected function latestAiTasks(int $limit): array
    {
        return DB::table('ai_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->orderByDesc('t.id')
            ->limit($limit)
            ->select('t.id','t.task_name','t.instruction','t.status','t.created_at','u.name as user_name')
            ->get()
            ->toArray();
    }

    protected function latestAiRecommendations(int $limit): array
    {
        return DB::table('ai_recommendations as r')
            ->leftJoin('tickets as t', 't.id', '=', 'r.ticket_id')
            ->orderByDesc('r.id')
            ->limit($limit)
            ->select('r.id','r.recommendation','r.reason','r.status','r.created_at','t.ticket_number')
            ->get()
            ->toArray();
    }

    protected function latestLoginLogs(int $limit): array
    {
        return DB::table('login_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->orderByDesc('l.id')
            ->limit($limit)
            ->select('l.id','u.name as user_name','l.status','l.ip_address','l.login_at','l.logout_at','l.location_status','l.created_at')
            ->get()
            ->toArray();
    }

    protected function latestActivities(int $limit): array
    {
        return DB::table('user_activities as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->select('a.id','u.name as user_name','a.module','a.activity','a.description','a.created_at')
            ->get()
            ->toArray();
    }

    protected function latestNotifications(int $limit): array
    {
        return DB::table('notifications as n')
            ->leftJoin('users as u', 'u.id', '=', 'n.user_id')
            ->orderByDesc('n.id')
            ->limit($limit)
            ->select('n.id','u.name as user_name','n.type','n.title','n.message','n.is_read','n.created_at')
            ->get()
            ->toArray();
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

    protected function remember(string $key, callable $callback): mixed
    {
        return Cache::remember($key, now()->addSeconds($this->ttlSeconds), function () use ($callback) {
            try {
                return $callback();
            } catch (Throwable $e) {
                logger()->warning('AI admin snapshot error', ['message' => $e->getMessage()]);
                return [];
            }
        });
    }

    protected function forget(string $key): void
    {
        Cache::forget($key);
    }

    protected function forgetPrefix(string $prefix): void
    {
        // no-op for driver compatibility
    }
}
