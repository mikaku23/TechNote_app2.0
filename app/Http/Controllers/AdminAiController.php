<?php

namespace App\Http\Controllers;

use App\Services\LogicAIforAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'tickets_total'       => DB::table('tickets')->count(),
            'tickets_waiting'     => DB::table('tickets')->where('status', 'waiting')->count(),
            'tickets_processing'  => DB::table('tickets')->where('status', 'processing')->count(),
            'tickets_completed'   => DB::table('tickets')->where('status', 'completed')->count(),
            'tickets_failed'      => DB::table('tickets')->where('status', 'failed')->count(),
            'maintenance_active'  => DB::table('maintenances')->where('is_active', 1)->exists(),
            'ai_logs_total'       => DB::table('ai_logs')->count(),
            'ai_tasks_total'      => DB::table('ai_tasks')->count(),
            'ai_rekom_total'      => DB::table('ai_recommendations')->count(),
        ];

        $recentLogs = DB::table('ai_logs')
            ->leftJoin('users', 'users.id', '=', 'ai_logs.user_id')
            ->select('ai_logs.*', 'users.name as user_name')
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

    public function chat(Request $request, LogicAIforAdmin $ai)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:5000'],
        ]);

        $result = $ai->handle(
            user: $request->user(),
            question: $data['question'],
            context: [
                'ticket_id' => $request->input('ticket_id'),
                'module'    => 'admin_ai',
            ]
        );

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
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        return back()->with('success', 'Anti AI Mode berhasil diperbarui.');
    }

    public function logs()
    {
        $logs = DB::table('ai_logs')
            ->leftJoin('users', 'users.id', '=', 'ai_logs.user_id')
            ->select('ai_logs.*', 'users.name as user_name')
            ->orderByDesc('ai_logs.id')
            ->paginate(20);

        return view('admin.ai.log', [
            'menu' => 'logs',
            'logs' => $logs,
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
