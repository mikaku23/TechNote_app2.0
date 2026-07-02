<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DosenPerbaikanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $activeTicket = Ticket::with([
            'perbaikan',
            'statusLogs',
            'comments.user',
        ])
            ->where('user_id', $user->id)
            ->where('type', 'repair')
            ->whereIn('status', ['waiting', 'diagnosis', 'processing', 'testing'])
            ->orderByRaw("FIELD(status, 'processing', 'testing', 'diagnosis', 'waiting')")
            ->orderBy('estimated_finish')
            ->orderBy('created_at')
            ->first();

        $latestTicket = Ticket::with([
            'perbaikan',
            'statusLogs',
            'comments.user',
        ])
            ->where('user_id', $user->id)
            ->where('type', 'repair')
            ->latest()
            ->first();

        $now = now();
        $monthStart = $now->copy()->startOfMonth()->startOfDay();
        $monthEnd = $now->copy()->endOfMonth()->endOfDay();

        $periodStartDay = intdiv($now->day - 1, 7) * 7 + 1;
        $periodStart = $now->copy()->startOfMonth()->addDays($periodStartDay - 1)->startOfDay();
        $periodEnd = $periodStart->copy()->addDays(6)->endOfDay();

        if ($periodEnd->gt($monthEnd)) {
            $periodEnd = $monthEnd->copy();
        }

        $periodRangeText = 'Periode ' .
            $periodStart->translatedFormat('d') . ' - ' .
            $periodEnd->translatedFormat('d F Y');

        $periodTickets = Ticket::with(['perbaikan'])
            ->where('user_id', $user->id)
            ->where('type', 'repair')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn($item) => $item->created_at->toDateString())
            ->map(fn($items) => $items->sortByDesc('created_at')->first());

        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $weekDays = collect();
        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $weekDays->push([
                'date' => $date->copy(),
                'day' => $dayNames[$date->dayOfWeekIso],
                'ticket' => $periodTickets->get($date->toDateString()),
            ]);
        }

        $totalRepairs = Ticket::where('user_id', $user->id)
            ->where('type', 'repair')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $activeRepairs = Ticket::where('user_id', $user->id)
            ->where('type', 'repair')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereIn('status', ['waiting', 'diagnosis', 'processing', 'testing'])
            ->count();

        $completedRepairs = Ticket::where('user_id', $user->id)
            ->where('type', 'repair')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->count();

        $failedRepairs = Ticket::where('user_id', $user->id)
            ->where('type', 'repair')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereIn('status', ['failed', 'unrepairable'])
            ->count();

        $notice = null;

        if ($latestTicket && $latestTicket->estimated_finish) {
            $now = now();

            if (in_array($latestTicket->status, ['completed', 'failed'])) {
                $notice = [
                    'type' => $latestTicket->status === 'completed' ? 'success' : 'danger',
                    'title' => $latestTicket->status === 'completed' ? 'Perbaikan selesai' : 'Perbaikan gagal',
                    'message' => $latestTicket->status === 'completed'
                        ? 'Barang sudah selesai diperbaiki. Silakan ambil di ruang teknisi.'
                        : 'Barang tidak dapat diselesaikan. Silakan ambil di ruang teknisi.',
                    'expires_at' => now()->addMinutes(5)->timestamp,
                ];
            } elseif ($now->lt($latestTicket->estimated_finish)) {
                $notice = [
                    'type' => 'info',
                    'title' => 'Perbaikan sedang diproses',
                    'message' => 'Estimasi selesai pada ' . $latestTicket->estimated_finish->format('d M Y H:i') . '.',
                    'expires_at' => now()->addMinutes(5)->timestamp,
                ];
            } elseif ($now->gte($latestTicket->estimated_finish)) {
                $notice = [
                    'type' => $latestTicket->status === 'failed' ? 'danger' : 'success',
                    'title' => 'Status perbaikan terbaru',
                    'message' => $latestTicket->status === 'failed'
                        ? 'Barang tidak dapat diperbaiki. Silakan ambil di ruang teknisi.'
                        : 'Perbaikan selesai. Silakan ambil barang di ruang teknisi.',
                    'expires_at' => now()->addMinutes(5)->timestamp,
                ];
            }
        }

        return view('dsn.index', [
            'menu' => 'dosen',
            'activeTicket' => $activeTicket,
            'latestTicket' => $latestTicket,
            'weekDays' => $weekDays,
            'periodRangeText' => $periodRangeText,
            'totalRepairs' => $totalRepairs,
            'activeRepairs' => $activeRepairs,
            'completedRepairs' => $completedRepairs,
            'failedRepairs' => $failedRepairs,
            'notice' => $notice,
        ]);
    }

    public function show(Ticket $ticket)
    {
        abort_unless(
            $ticket->user_id === Auth::id() && $ticket->type === 'repair',
            403
        );

        $ticket->load([
            'perbaikan',
            'statusLogs',
            'comments.user',
        ]);

        return view('dosen.perbaikan.show', [
            'menu' => 'dosen',
            'ticket' => $ticket
            ]);
    }
}
