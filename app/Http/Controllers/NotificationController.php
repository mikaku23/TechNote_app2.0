<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type');

        $query = Notification::with([
            'ticket.user.role',
            'ticket.penginstalan.software',
            'ticket.perbaikan',
        ])
            ->when($type && in_array($type, ['system', 'ai', 'whatsapp'], true), function ($q) use ($type) {
                $q->where('type', $type);
            });

        if ($user->role?->name === 'Admin') {
            // Admin melihat seluruh notifikasi
            // tidak dibatasi user_id
        } else {
            // Selain admin hanya melihat notifikasi miliknya / ticket miliknya
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('ticket', function ($ticketQuery) use ($user) {
                        $ticketQuery->where('user_id', $user->id);
                    });
            });
        }

        $logs = $query->latest()->paginate(10)->withQueryString();

        $baseCountQuery = Notification::query();
        if ($user->role?->name !== 'Admin') {
            $baseCountQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('ticket', function ($ticketQuery) use ($user) {
                        $ticketQuery->where('user_id', $user->id);
                    });
            });
        }

        $totalLogs = (clone $baseCountQuery)->count();
        $systemCount = (clone $baseCountQuery)->where('type', 'system')->count();
        $aiCount = (clone $baseCountQuery)->where('type', 'ai')->count();
        $whatsappCount = (clone $baseCountQuery)->where('type', 'whatsapp')->count();
        $unreadCount = (clone $baseCountQuery)->where('is_read', false)->count();

        return view('admin.notifications.index', [
            'menu' => 'notifications',
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'systemCount' => $systemCount,
            'aiCount' => $aiCount,
            'whatsappCount' => $whatsappCount,
            'unreadCount' => $unreadCount,
            'type' => $type,
        ]);
    }

    public function show(Notification $notification)
    {
        $this->authorizeAccess($notification);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        $notification->load([
            'ticket.user.role',
            'ticket.penginstalan.software',
            'ticket.perbaikan',
        ]);

        return view('admin.notifications.show', [
            'menu' => 'notifications',
            'notification' => $notification,
        ]);
    }

    public function markRead(Notification $notification)
    {
        $this->authorizeAccess($notification);

        $notification->update(['is_read' => true]);

        return back();
    }

    public function markAllRead()
    {
        $user = Auth::user();

        $query = Notification::query();

        if ($user->role?->name !== 'Admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('ticket', function ($ticketQuery) use ($user) {
                        $ticketQuery->where('user_id', $user->id);
                    });
            });
        }

        $query->where('is_read', false)->update(['is_read' => true]);

        return back();
    }

    private function authorizeAccess(Notification $notification): void
    {
        $user = Auth::user();

        if ($user->role?->name === 'Admin') {
            return;
        }

        abort_unless(
            $notification->user_id === $user->id || $notification->ticket?->user_id === $user->id,
            403
        );
    }
}
