<?php

namespace App\Http\Controllers;

use App\Models\ticket_status_log;
use App\Models\Ticket;
use App\Models\user_activitie;
use App\Models\User;
use App\Services\TicketFlowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(private TicketFlowService $ticketFlowService) {}

    private function getSessionConfig(string $session): array
    {
        return match ($session) {
            'morning' => [
                'start' => '08:00',
                'end' => '11:00',
                'accept_until' => '10:00',
            ],
            'afternoon' => [
                'start' => '14:00',
                'end' => '21:00',
                'accept_until' => '20:00',
            ],
            default => throw new \RuntimeException('Invalid session.'),
        };
    }

    public function index()
    {
        $tickets = Ticket::with('user')
            ->latest()
            ->paginate(10);

        $today = Carbon::today();

        $sessionCards = [
            $this->buildSessionCard($today, 'morning'),
            $this->buildSessionCard($today, 'afternoon'),
        ];

        return view('admin.ticket.mode1.index', [
            'menu' => 'ticket',
            'title' => 'Ticket Management',
            'tickets' => $tickets,
            'sessionCards' => $sessionCards,
        ]);
    }

    private function buildSessionCard(Carbon $date, string $session): array
    {
        $config = $this->getSessionConfig($session);

        $sessionStart = Carbon::parse($date->format('Y-m-d') . ' ' . $config['start']);
        $sessionEnd   = Carbon::parse($date->format('Y-m-d') . ' ' . $config['end']);
        $acceptUntil  = Carbon::parse($date->format('Y-m-d') . ' ' . $config['accept_until']);
        $now          = Carbon::now();

        $activeTickets = Ticket::with(['penginstalan.software'])
            ->where('type', 'installation')
            ->whereDate('booking_date', $date->toDateString())
            ->where('session', $session)
            ->whereIn('status', ['waiting', 'processing'])
            ->whereNull('deleted_at')
            ->orderBy('queue_number')
            ->orderBy('scheduled_start')
            ->orderBy('id')
            ->get();

        $totalSessionMinutes = $sessionStart->diffInMinutes($sessionEnd);

        $bookedMinutes = (int) $activeTickets->sum(function ($ticket) {
            $duration = (int) ($ticket->penginstalan?->software?->estimated_minutes ?? 0);
            return $duration + 5;
        });

        $realElapsedMinutes = 0;
        if ($date->isToday()) {
            if ($now->lessThanOrEqualTo($sessionStart)) {
                $realElapsedMinutes = 0;
            } elseif ($now->greaterThanOrEqualTo($sessionEnd)) {
                $realElapsedMinutes = $totalSessionMinutes;
            } else {
                $realElapsedMinutes = $sessionStart->diffInMinutes($now);
            }
        } elseif ($date->lessThan($now->copy()->startOfDay())) {
            $realElapsedMinutes = $totalSessionMinutes;
        }

        $realRemainingMinutes = max(0, $totalSessionMinutes - $realElapsedMinutes);

        $capacityRemainingMinutes = max(0, $totalSessionMinutes - $bookedMinutes);

        $realProgressPercent = $totalSessionMinutes > 0
            ? min(100, (int) round(($realElapsedMinutes / $totalSessionMinutes) * 100))
            : 0;

        $bookingProgressPercent = $totalSessionMinutes > 0
            ? min(100, (int) round(($bookedMinutes / $totalSessionMinutes) * 100))
            : 0;

        $status = 'upcoming';

        if ($date->isToday()) {
            if ($now->betweenIncluded($sessionStart, $sessionEnd)) {
                $status = 'active';
            } elseif ($now->greaterThan($sessionEnd)) {
                $status = 'ended';
            }
        } elseif ($date->lessThan($now->copy()->startOfDay())) {
            $status = 'ended';
        }

        return [
            'key'                     => $session,
            'label'                   => $session === 'morning' ? 'Sesi 1' : 'Sesi 2',
            'range'                   => $sessionStart->format('H:i') . ' - ' . $sessionEnd->format('H:i'),
            'accept_until'            => $acceptUntil->format('H:i'),
            'status'                  => $status,
            'is_disabled'             => $status !== 'active',

            'total_minutes'           => $totalSessionMinutes,

            'real_elapsed_minutes'    => $realElapsedMinutes,
            'real_elapsed_human'      => $this->humanizeMinutes($realElapsedMinutes),
            'real_remaining_minutes'  => $realRemainingMinutes,
            'real_remaining_human'    => $this->humanizeMinutes($realRemainingMinutes),
            'real_progress_percent'   => $realProgressPercent,

            'booked_minutes'          => $bookedMinutes,
            'booked_human'            => $this->humanizeMinutes($bookedMinutes),
            'capacity_remaining_minutes' => $capacityRemainingMinutes,
            'capacity_remaining_human'   => $this->humanizeMinutes($capacityRemainingMinutes),
            'booking_progress_percent'   => $bookingProgressPercent,

            'ticket_count'            => $activeTickets->count(),
        ];
    }

    private function humanizeMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        if ($hours <= 0) {
            return $mins . ' menit';
        }

        if ($mins <= 0) {
            return $hours . ' jam';
        }

        return $hours . ' jam ' . $mins . ' menit';
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'user',
            'penginstalan.software',
            'statusLogs.changer',
        ]);

        return view('admin.ticket.mode1.show', [
            'menu' => 'ticket',
            'title' => 'Ticket Detail',
            'ticket' => $ticket,
        ]);
    }

    public function edit(Ticket $ticket)
    {
        $ticket->load(['user']);

        $users = User::orderBy('name')->get();

        return view('admin.ticket.mode1.edit', [
            'menu' => 'ticket',
            'title' => 'Update Ticket Status',
            'ticket' => $ticket,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['installation', 'repair'])],
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', Rule::in([
                'waiting',
                'diagnosis',
                'processing',
                'testing',
                'completed',
                'failed',
            ])],
            'priority' => ['required', Rule::in(['normal', 'high', 'urgent'])],
            'estimated_finish' => ['nullable', 'date'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'type' => $validated['type'],
            'user_id' => $validated['user_id'],
            'priority' => $validated['priority'],
            'estimated_finish' => $validated['estimated_finish'] ?? null,
            'is_public' => $request->boolean('is_public', true),
        ]);

        if ($oldStatus !== $validated['status']) {
            $this->ticketFlowService->changeStatus(
                $ticket,
                $validated['status'],
                'Status updated from ticket edit form.',
                Auth::id()
            );
        } else {
            $ticket->update([
                'status' => $validated['status'],
            ]);
        }

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Ticket',
            'activity' => 'update',
            'description' => 'mengupdate data ticket.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('ticket.index')
            ->with('edit', 'Ticket updated successfully.');
    }

    public function logs()
    {
        $ticketLogs = ticket_status_log::with(['ticket.user', 'changer'])
            ->latest()
            ->paginate(15);

        return view('admin.ticket.mode2.index', [
            'menu' => 'ticket',
            'title' => 'Ticket Activity Logs',
            'ticketLogs' => $ticketLogs,
        ]);
    }

    public function showLogs(Ticket $ticket)
    {
        $ticket->load([
            'user',
            'statusLogs.changer',
            'penginstalan.software',
        ]);

        return view('admin.ticket.mode2.show', [
            'menu' => 'ticket',
            'title' => 'Ticket Activity Detail',
            'ticket' => $ticket,
        ]);
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                'waiting',
                'diagnosis',
                'processing',
                'testing',
                'completed',
                'failed',
            ])],
            'note' => ['nullable', 'string'],
        ]);

        $this->ticketFlowService->changeStatus(
            $ticket,
            $validated['status'],
            $validated['note'] ?? null,
            Auth::id()
        );

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Ticket',
            'activity' => 'update status',
            'description' => 'mengupdate status ticket.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('ticket.show', $ticket->id)
            ->with('success', 'Ticket status updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Ticket',
            'activity' => 'delete',
            'description' => 'menghapus data ticket.',
            'old_data' => null,
            'new_data' => null,
        ]);
        
        return redirect()
            ->route('ticket.index')
            ->with('success', 'Ticket moved to recycle bin.');
    }
}
