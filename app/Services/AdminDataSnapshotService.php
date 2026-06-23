<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
    ];

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
                    'users' => (int) DB::table('users')->whereNull('deleted_at')->count(),
                    'roles' => (int) DB::table('roles')->whereNull('deleted_at')->count(),
                    'software' => (int) DB::table('software')->whereNull('deleted_at')->count(),
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
            return match ($entity) {
                'users' => $this->users($limit, $filters),
                'roles' => $this->roles($limit),
                'software' => $this->software($limit),
                'tickets' => $this->tickets($limit, $filters),
                'penginstalans' => $this->penginstalans($limit),
                'perbaikans' => $this->perbaikans($limit),
                'trusted_websites' => $this->trustedWebsites($limit),
                'login_logs' => $this->loginLogs($limit),
                'user_activities' => $this->activities($limit),
                'ai_logs' => $this->aiLogs($limit),
                'ai_tasks' => $this->aiTasks($limit),
                'ai_recommendations' => $this->aiRecommendations($limit),
                'notifications' => $this->notifications($limit),
                'maintenances' => $this->maintenances($limit),
                'system_settings' => $this->systemSettings($limit),
                'rekaps' => $this->rekaps($limit),
                'vercel_sync_logs' => $this->vercelSyncLogs($limit),
                default => [
                    'entity' => $entity,
                    'items' => [],
                    'note' => 'entity tidak dikenali',
                ],
            };
        });
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

    protected function remember(string $key, callable $callback): mixed
    {
        return Cache::remember($key, now()->addSeconds($this->ttlSeconds), function () use ($callback) {
            try {
                return $callback();
            } catch (Throwable $e) {
                logger()->warning('AI admin snapshot error', [
                    'message' => $e->getMessage(),
                ]);

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
        // Aman untuk cache driver umum: tidak menghapus massal lewat pattern jika tidak didukung.
        // Fungsi ini sengaja dibiarkan sebagai no-op kompatibel.
    }

    protected function latestTickets(int $limit): array
    {
        return DB::table('tickets')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'ticket_number',
                'type',
                'status',
                'priority',
                'booking_date',
                'session',
                'queue_number',
                'scheduled_start',
                'scheduled_end',
                'created_at',
            ])
            ->toArray();
    }

    protected function latestAiLogs(int $limit): array
    {
        return DB::table('ai_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->select(
                'a.id',
                'a.role',
                'a.question',
                'a.answer',
                'a.action',
                'a.source',
                'a.created_at',
                'u.name as user_name'
            )
            ->get()
            ->toArray();
    }

    protected function latestAiTasks(int $limit): array
    {
        return DB::table('ai_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->orderByDesc('t.id')
            ->limit($limit)
            ->select(
                't.id',
                't.task_name',
                't.instruction',
                't.status',
                't.created_at',
                'u.name as user_name'
            )
            ->get()
            ->toArray();
    }

    protected function latestAiRecommendations(int $limit): array
    {
        return DB::table('ai_recommendations as r')
            ->leftJoin('tickets as t', 't.id', '=', 'r.ticket_id')
            ->orderByDesc('r.id')
            ->limit($limit)
            ->select(
                'r.id',
                'r.recommendation',
                'r.reason',
                'r.status',
                'r.created_at',
                't.ticket_number'
            )
            ->get()
            ->toArray();
    }

    protected function latestLoginLogs(int $limit): array
    {
        return DB::table('login_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->orderByDesc('l.id')
            ->limit($limit)
            ->select(
                'l.id',
                'u.name as user_name',
                'l.status',
                'l.ip_address',
                'l.login_at',
                'l.logout_at',
                'l.location_status',
                'l.created_at'
            )
            ->get()
            ->toArray();
    }

    protected function latestActivities(int $limit): array
    {
        return DB::table('user_activities as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->select(
                'a.id',
                'u.name as user_name',
                'a.module',
                'a.activity',
                'a.description',
                'a.created_at'
            )
            ->get()
            ->toArray();
    }

    protected function latestNotifications(int $limit): array
    {
        return DB::table('notifications as n')
            ->leftJoin('users as u', 'u.id', '=', 'n.user_id')
            ->leftJoin('tickets as t', 't.id', '=', 'n.ticket_id')
            ->orderByDesc('n.id')
            ->limit($limit)
            ->select(
                'n.id',
                'u.name as user_name',
                't.ticket_number',
                'n.type',
                'n.title',
                'n.message',
                'n.is_read',
                'n.created_at'
            )
            ->get()
            ->toArray();
    }

    protected function users(int $limit, array $filters = []): array
    {
        $q = DB::table('users as u')
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->orderByDesc('u.id')
            ->limit($limit)
            ->select(
                'u.id',
                'u.name',
                'u.username',
                'u.email',
                'u.nim',
                'u.nip',
                'u.no_hp',
                'u.last_login_at',
                'u.created_at',
                'r.name as role_name',
                'r.description as role_description'
            );

        if (! empty($filters['role'])) {
            $q->where('r.name', $filters['role']);
        }

        return [
            'entity' => 'users',
            'items' => $q->get()->toArray(),
        ];
    }

    protected function roles(int $limit): array
    {
        return [
            'entity' => 'roles',
            'items' => DB::table('roles')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'name', 'description', 'is_active', 'created_at'])
                ->toArray(),
        ];
    }

    protected function software(int $limit): array
    {
        return [
            'entity' => 'software',
            'items' => DB::table('software')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'name', 'developer', 'version', 'estimated_minutes', 'description', 'created_at'])
                ->toArray(),
        ];
    }

    protected function tickets(int $limit, array $filters = []): array
    {
        $q = DB::table('tickets')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'id',
                'ticket_number',
                'type',
                'status',
                'priority',
                'booking_date',
                'session',
                'queue_number',
                'scheduled_start',
                'scheduled_end',
                'estimated_finish',
                'completed_at',
                'created_at',
            ]);

        return [
            'entity' => 'tickets',
            'items' => $q->toArray(),
        ];
    }

    protected function penginstalans(int $limit): array
    {
        return [
            'entity' => 'penginstalans',
            'items' => DB::table('penginstalans as p')
                ->leftJoin('tickets as t', 't.id', '=', 'p.ticket_id')
                ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
                ->leftJoin('software as s', 's.id', '=', 'p.software_id')
                ->orderByDesc('p.id')
                ->limit($limit)
                ->select(
                    'p.id',
                    'p.installation_result',
                    'p.note',
                    'p.created_at',
                    't.ticket_number',
                    't.status as ticket_status',
                    'u.name as user_name',
                    's.name as software_name',
                    's.version as software_version'
                )
                ->get()
                ->toArray(),
        ];
    }

    protected function perbaikans(int $limit): array
    {
        return [
            'entity' => 'perbaikans',
            'items' => DB::table('perbaikans as p')
                ->leftJoin('tickets as t', 't.id', '=', 'p.ticket_id')
                ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
                ->orderByDesc('p.id')
                ->limit($limit)
                ->select(
                    'p.id',
                    'p.item_name',
                    'p.item_location',
                    'p.damage_description',
                    'p.repair_action',
                    'p.repair_result',
                    'p.note',
                    'p.created_at',
                    't.ticket_number',
                    't.status as ticket_status',
                    'u.name as user_name'
                )
                ->get()
                ->toArray(),
        ];
    }

    protected function trustedWebsites(int $limit): array
    {
        return [
            'entity' => 'trusted_websites',
            'items' => DB::table('trusted_websites')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'name', 'url', 'description', 'is_active', 'created_at'])
                ->toArray(),
        ];
    }

    protected function loginLogs(int $limit): array
    {
        return [
            'entity' => 'login_logs',
            'items' => DB::table('login_logs as l')
                ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
                ->orderByDesc('l.id')
                ->limit($limit)
                ->select(
                    'l.id',
                    'u.name as user_name',
                    'l.status',
                    'l.ip_address',
                    'l.login_at',
                    'l.logout_at',
                    'l.location_status',
                    'l.created_at'
                )
                ->get()
                ->toArray(),
        ];
    }

    protected function activities(int $limit): array
    {
        return [
            'entity' => 'user_activities',
            'items' => DB::table('user_activities as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->orderByDesc('a.id')
                ->limit($limit)
                ->select('a.id', 'u.name as user_name', 'a.module', 'a.activity', 'a.description', 'a.created_at')
                ->get()
                ->toArray(),
        ];
    }

    protected function aiLogs(int $limit): array
    {
        return [
            'entity' => 'ai_logs',
            'items' => DB::table('ai_logs as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->orderByDesc('a.id')
                ->limit($limit)
                ->select('a.id', 'u.name as user_name', 'a.role', 'a.question', 'a.answer', 'a.action', 'a.source', 'a.created_at')
                ->get()
                ->toArray(),
        ];
    }

    protected function aiTasks(int $limit): array
    {
        return [
            'entity' => 'ai_tasks',
            'items' => DB::table('ai_tasks as t')
                ->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->orderByDesc('t.id')
                ->limit($limit)
                ->select('t.id', 'u.name as user_name', 't.task_name', 't.instruction', 't.status', 't.created_at')
                ->get()
                ->toArray(),
        ];
    }

    protected function aiRecommendations(int $limit): array
    {
        return [
            'entity' => 'ai_recommendations',
            'items' => DB::table('ai_recommendations as r')
                ->leftJoin('tickets as t', 't.id', '=', 'r.ticket_id')
                ->orderByDesc('r.id')
                ->limit($limit)
                ->select('r.id', 't.ticket_number', 'r.recommendation', 'r.reason', 'r.status', 'r.created_at')
                ->get()
                ->toArray(),
        ];
    }

    protected function notifications(int $limit): array
    {
        return [
            'entity' => 'notifications',
            'items' => DB::table('notifications as n')
                ->leftJoin('users as u', 'u.id', '=', 'n.user_id')
                ->leftJoin('tickets as t', 't.id', '=', 'n.ticket_id')
                ->orderByDesc('n.id')
                ->limit($limit)
                ->select('n.id', 'u.name as user_name', 't.ticket_number', 'n.type', 'n.title', 'n.message', 'n.is_read', 'n.created_at')
                ->get()
                ->toArray(),
        ];
    }

    protected function maintenances(int $limit): array
    {
        return [
            'entity' => 'maintenances',
            'items' => DB::table('maintenances')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'is_active', 'message', 'started_at', 'ended_at', 'created_at'])
                ->toArray(),
        ];
    }

    protected function systemSettings(int $limit): array
    {
        return [
            'entity' => 'system_settings',
            'items' => DB::table('system_settings')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'key', 'value', 'description', 'created_at'])
                ->toArray(),
        ];
    }

    protected function rekaps(int $limit): array
    {
        return [
            'entity' => 'rekaps',
            'items' => DB::table('rekaps')
                ->orderByDesc('rekap_date')
                ->limit($limit)
                ->get([
                    'id',
                    'rekap_date',
                    'total_installations',
                    'total_repairs',
                    'completed_tickets',
                    'failed_tickets',
                    'pending_tickets',
                    'created_at',
                ])
                ->toArray(),
        ];
    }

    protected function vercelSyncLogs(int $limit): array
    {
        return [
            'entity' => 'vercel_sync_logs',
            'items' => DB::table('vercel_sync_logs as v')
                ->leftJoin('tickets as t', 't.id', '=', 'v.ticket_id')
                ->orderByDesc('v.id')
                ->limit($limit)
                ->select('v.id', 't.ticket_number', 'v.sync_status', 'v.response', 'v.synced_at', 'v.created_at')
                ->get()
                ->toArray(),
        ];
    }
}
