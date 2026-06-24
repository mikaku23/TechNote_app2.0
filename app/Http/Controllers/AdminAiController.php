<?php

namespace App\Http\Controllers;

use App\Services\AiTraceService;
use App\Services\LogicAIforAdmin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdminAiController extends Controller
{
    public function index()
    {
        $trustedWebsites = DB::table('trusted_websites')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $antiMode = DB::table('system_settings')
            ->where('key', 'ai_admin_anti_mode')
            ->value('value') ?? '0';

        $permission = DB::table('system_settings')
            ->where('key', 'ai_admin_permission')
            ->value('value') ?? '0';

        $stats = [
            'tickets_total'      => DB::table('tickets')->count(),
            'tickets_waiting'    => DB::table('tickets')->where('status', 'waiting')->count(),
            'tickets_processing' => DB::table('tickets')->where('status', 'processing')->count(),
            'tickets_completed'  => DB::table('tickets')->where('status', 'completed')->count(),
            'tickets_failed'     => DB::table('tickets')->where('status', 'failed')->count(),
            'maintenance_active' => DB::table('maintenances')->where('is_active', 1)->exists(),
            'ai_logs_total'      => DB::table('ai_logs')->count(),
            'ai_tasks_total'     => DB::table('ai_tasks')->count(),
            'ai_rekom_total'     => DB::table('ai_recommendations')->count(),
        ];

        $recentLogs = DB::table('ai_logs')
            ->leftJoin('users', 'users.id', '=', 'ai_logs.user_id')
            ->select('ai_logs.*', 'users.name as user_name', 'users.username as user_username')
            ->orderByDesc('ai_logs.id')
            ->limit(8)
            ->get();

        return view('admin.ai.index', [
            'menu'            => 'ai',
            'trustedWebsites' => $trustedWebsites,
            'antiMode'        => $antiMode,
            'permission'      => $permission,
            'stats'           => $stats,
            'recentLogs'      => $recentLogs,
        ]);
    }

    public function chat(Request $request, LogicAIforAdmin $ai, AiTraceService $trace)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $question = trim($data['question']);

        $taskId = null;
        try {
            if (method_exists($trace, 'recordTask')) {
                $taskId = $trace->recordTask(
                    $user,
                    'AI: ' . \Illuminate\Support\Str::limit($question, 50, ''),
                    $question,
                    'processing'
                );
            }
        } catch (Throwable $e) {
            logger()->warning('AI trace recordTask failed', [
                'message' => $e->getMessage(),
            ]);
        }

        $result = $ai->handle(
            user: $user,
            question: $question,
            context: [
                'ticket_id' => $request->input('ticket_id'),
                'module'    => 'admin_ai',
                'task_id'   => $taskId,
            ]
        );

        $result = is_array($result) ? $result : [];
        $reply = trim((string) ($result['reply'] ?? ''));

        if ($reply === '') {
            $action = (string) ($result['action'] ?? '');

            $reply = in_array($action, ['greeting', 'bot_query'], true)
                ? 'Halo, ada yang bisa dibantu?'
                : 'Maaf, saya belum dapat memproses permintaan tersebut.';

            $result['reply'] = $reply;
            $result['action'] = $action !== '' ? $action : 'error';
            $result['source'] = $result['source'] ?? 'fallback';
            $result['confidence'] = $result['confidence'] ?? 0.25;
            $result['blocked'] = $result['blocked'] ?? false;
            $result['needs_confirmation'] = $result['needs_confirmation'] ?? false;
        }

        try {
            if ($taskId && method_exists($trace, 'finishTask')) {
                $trace->finishTask(
                    $taskId,
                    $reply,
                    ($result['ok'] ?? true) ? 'completed' : 'failed'
                );
            }
        } catch (Throwable $e) {
            logger()->warning('AI trace finishTask failed', [
                'message' => $e->getMessage(),
            ]);
        }

        try {
            if (method_exists($trace, 'recordAiResult')) {
                $trace->recordAiResult($user, $question, $result);
            }
        } catch (Throwable $e) {
            logger()->warning('AI trace recordAiResult failed', [
                'message' => $e->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with('ai_result', $result);
    }

    public function toggleAntiAi(Request $request)
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'ai_admin_anti_mode'],
            [
                'value'       => $request->boolean('enabled') ? '1' : '0',
                'description' => 'Anti AI Mode untuk AI Admin',
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        return back()->with('success', 'Anti AI Mode berhasil diperbarui.');
    }

    public function logs()
    {
        $rawLogs = DB::table('ai_logs')
            ->leftJoin('users', 'users.id', '=', 'ai_logs.user_id')
            ->select(
                'ai_logs.*',
                'users.name as user_name',
                'users.username as user_username'
            )
            ->orderByDesc('ai_logs.created_at')
            ->orderByDesc('ai_logs.id')
            ->get();

        $grouped = $rawLogs
            ->groupBy(function ($log) {
                $userId = $log->user_id ?? 0;
                $source = $log->source ?? '-';
                return $userId . '|' . $source;
            })
            ->map(function ($items) {
                $items = $items->values();
                $first = $items->last();
                $last  = $items->first();

                return [
                    'group_key'     => md5(($first->user_id ?? 0) . '|' . ($first->source ?? '-')),
                    'user_id'       => $first->user_id,
                    'user_name'     => $first->user_name ?? 'System',
                    'user_username' => $first->user_username ?? '-',
                    'source'        => $first->source ?? '-',
                    'count'         => $items->count(),
                    'first_at'      => $last->created_at,
                    'last_at'       => $first->created_at,
                    'items'         => $items->map(function ($log) {
                        return [
                            'id'         => $log->id,
                            'action'     => $log->action ?? '-',
                            'question'   => $log->question ?? '-',
                            'reply'      => $log->reply ?? ($log->answer ?? '-'),
                            'confidence' => $log->confidence ?? null,
                            'created_at'  => $log->created_at,
                            'source'     => $log->source ?? '-',
                        ];
                    })->values()->all(),
                ];
            })
            ->values();

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pagedItems = $grouped->slice(($page - 1) * $perPage, $perPage)->values();

        $logs = new LengthAwarePaginator(
            $pagedItems,
            $grouped->count(),
            $perPage,
            $page,
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );

        $totalLogs = $grouped->count();
        $todayLogs = $grouped->filter(function ($group) {
            return Carbon::parse($group['last_at'])->isToday();
        })->count();
        $uniqueUsers = $grouped->pluck('user_id')->filter()->unique()->count();

        return view('admin.ai.log', [
            'menu'        => 'logs',
            'logs'        => $logs,
            'totalLogs'   => $totalLogs,
            'todayLogs'   => $todayLogs,
            'uniqueUsers' => $uniqueUsers,
        ]);
    }

    public function tasks()
    {
        $tasks = DB::table('ai_tasks')
            ->leftJoin('users', 'users.id', '=', 'ai_tasks.user_id')
            ->select('ai_tasks.*', 'users.name as user_name')
            ->orderByDesc('ai_tasks.id')
            ->paginate(20);

        return view('admin.ai.tasks', [
            'menu'  => 'tasks',
            'tasks' => $tasks,
        ]);
    }

    public function recommendations()
    {
        $recommendations = DB::table('ai_recommendations')
            ->leftJoin('tickets', 'tickets.id', '=', 'ai_recommendations.ticket_id')
            ->select('ai_recommendations.*', 'tickets.ticket_number')
            ->orderByDesc('ai_recommendations.id')
            ->paginate(20);

        return view('admin.ai.rekom', [
            'menu'            => 'rekom',
            'recommendations' => $recommendations,
        ]);
    }
}
