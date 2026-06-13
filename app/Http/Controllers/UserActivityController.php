<?php

namespace App\Http\Controllers;

use App\Models\user_activitie;
use App\Models\UserActivity;
use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = user_activitie::with('user.role')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('module', 'like', "%{$search}%")
                    ->orWhere('activity', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('activity')) {
            $query->where('activity', $request->activity);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->paginate(10)->withQueryString();

        $totalLogs = user_activitie::count();
        $createCount = user_activitie::where('activity', 'create')->count();
        $updateCount = user_activitie::where('activity', 'update')->count();
        $deleteCount = user_activitie::where('activity', 'delete')->count();

        return view('admin.user-activity.index', [
            'menu' => 'user-activity',
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'createCount' => $createCount,
            'updateCount' => $updateCount,
            'deleteCount' => $deleteCount,
        ]);
    }

    public function show($id)
    {
        $log = user_activitie::with('user.role')->findOrFail($id);

        return view('admin.user-activity.show', [
            'menu' => 'user-activity',
            'log' => $log,
        ]);
    }
}
