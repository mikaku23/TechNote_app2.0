<?php

namespace App\Http\Controllers;

use App\Models\login_log;
use App\Models\LoginLog;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        $query = login_log::with(['user.role'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('login_at', $request->date);
        }

        $logs = $query->paginate(10)->withQueryString();

        $totalLogs = login_log::count();
        $onlineCount = login_log::where('status', 'online')->count();
        $offlineCount = login_log::where('status', 'offline')->count();

        return view('admin.login-log.index', [
            'menu' => 'login-log',
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'onlineCount' => $onlineCount,
            'offlineCount' => $offlineCount,
        ]);
    }

    public function show($id)
    {
        $log = login_log::with(['user.role'])->findOrFail($id);

        return view('admin.login-log.show', [
            'menu' => 'login-log',
            'log' => $log,
        ]);
    }
}
