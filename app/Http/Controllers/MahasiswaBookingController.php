<?php

namespace App\Http\Controllers;

use App\Models\Software;
use App\Models\ticket;
use App\Services\InstallationBookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MahasiswaBookingController extends Controller
{
    public function __construct(private InstallationBookingService $bookingService) {}

    private function getSessionAvailabilityFlags(): array
    {
        $now = now();

        $morningAcceptUntil = Carbon::parse($now->format('Y-m-d') . ' 10:00');
        $afternoonAcceptUntil = Carbon::parse($now->format('Y-m-d') . ' 20:00');

        return [
            'morningAvailable'     => $now->lt($morningAcceptUntil),
            'afternoonAvailable'    => $now->lt($afternoonAcceptUntil),
            'bookingClosedToday'    => $now->gte($afternoonAcceptUntil),
            'morningAcceptUntil'   => $morningAcceptUntil,
            'afternoonAcceptUntil'  => $afternoonAcceptUntil,
        ];
    }

    private function syncTodayQueue(): void
    {
        $this->bookingService->syncSessionState(Carbon::today());
    }

    private function syncTicketQueue(ticket $ticket): void
    {
        if ($ticket->booking_date && $ticket->session) {
            $this->bookingService->syncSessionState(
                Carbon::parse($ticket->booking_date),
                $ticket->session
            );
        }
    }

    public function index()
    {
        $this->syncTodayQueue();

        $user = Auth::user();

        $activeTicket = ticket::with([
            'penginstalan.software',
            'statusLogs',
            'comments.user',
        ])
            ->where('user_id', $user->id)
            ->where('type', 'installation')
            ->whereIn('status', ['waiting', 'processing'])
            ->orderByRaw("FIELD(status, 'processing', 'waiting')")
            ->orderBy('scheduled_start')
            ->orderBy('created_at')
            ->first();

        $latestTicket = ticket::with([
            'penginstalan.software',
            'statusLogs',
            'comments.user',
        ])
            ->where('user_id', $user->id)
            ->where('type', 'installation')
            ->latest()
            ->first();

        $histories = ticket::with(['penginstalan.software'])
            ->where('user_id', $user->id)
            ->where('type', 'installation')
            ->latest()
            ->take(10)
            ->get();

        $totalBookings = ticket::where('user_id', $user->id)
            ->where('type', 'installation')
            ->count();

        $activeBookings = ticket::where('user_id', $user->id)
            ->where('type', 'installation')
            ->whereIn('status', ['waiting', 'processing'])
            ->count();

        $completedBookings = ticket::where('user_id', $user->id)
            ->where('type', 'installation')
            ->where('status', 'completed')
            ->count();

        $failedBookings = ticket::where('user_id', $user->id)
            ->where('type', 'installation')
            ->where('status', 'failed')
            ->count();

        $notice = null;

        if ($latestTicket && $latestTicket->scheduled_start && $latestTicket->scheduled_end) {
            $now = now();

            if (in_array($latestTicket->status, ['completed', 'failed'])) {
                $notice = [
                    'type' => $latestTicket->status === 'completed' ? 'success' : 'danger',
                    'message' => $latestTicket->status === 'completed'
                        ? 'Penginstalan selesai, silakan ambil laptop kamu di ruang teknisi. Terima kasih.'
                        : 'Penginstalan gagal, silakan ambil laptop kamu di ruang teknisi. Terima kasih.',
                    'expires_at' => now()->addMinutes(5)->timestamp,
                ];
            } elseif ($now->lt($latestTicket->scheduled_start)) {
                $pickupTime = $latestTicket->scheduled_start->copy()->subMinutes(10);

                $notice = [
                    'type' => 'info',
                    'message' => 'Datang ke ruang teknisi pada jam ' . $pickupTime->format('H:i') . ' untuk menyerahkan laptop.',
                    'expires_at' => null,
                ];
            } elseif ($now->between($latestTicket->scheduled_start, $latestTicket->scheduled_end)) {
                $pickupTime = $latestTicket->scheduled_end->copy();

                $notice = [
                    'type' => 'warning',
                    'message' => 'Datang ke ruang teknisi pada jam ' . $pickupTime->format('H:i') . ' untuk mengambil laptop.',
                    'expires_at' => null,
                ];
            } elseif ($now->gte($latestTicket->scheduled_end)) {
                $notice = [
                    'type' => $latestTicket->status === 'failed' ? 'danger' : 'success',
                    'message' => $latestTicket->status === 'failed'
                        ? 'Penginstalan gagal, silakan ambil laptop kamu di ruang teknisi. Terima kasih.'
                        : 'Penginstalan selesai, silakan ambil laptop kamu di ruang teknisi. Terima kasih.',
                    'expires_at' => now()->addMinutes(5)->timestamp,
                ];
            }
        }

        $morningTickets = ticket::with('penginstalan.software')
            ->where('type', 'installation')
            ->whereDate('booking_date', now())
            ->where('session', 'morning')
            ->whereNull('deleted_at')
            ->get();

        $afternoonTickets = ticket::with('penginstalan.software')
            ->where('type', 'installation')
            ->whereDate('booking_date', now())
            ->where('session', 'afternoon')
            ->whereNull('deleted_at')
            ->get();

        $morningUsedMinutes = 0;
        foreach ($morningTickets as $ticket) {
            $duration = $ticket->penginstalan?->software?->estimated_minutes ?? 30;
            $morningUsedMinutes += ($duration + 5);
        }

        $afternoonUsedMinutes = 0;
        foreach ($afternoonTickets as $ticket) {
            $duration = $ticket->penginstalan?->software?->estimated_minutes ?? 30;
            $afternoonUsedMinutes += ($duration + 5);
        }

        $morningProgress = min(round(($morningUsedMinutes / 180) * 100), 100);
        $afternoonProgress = min(round(($afternoonUsedMinutes / 180) * 100), 100);

        $morningQueueCount = $morningTickets->count();
        $afternoonQueueCount = $afternoonTickets->count();

        $morningAvailable = $morningUsedMinutes < 180;
        $afternoonAvailable = $afternoonUsedMinutes < 180;

        $sessionFlags = $this->getSessionAvailabilityFlags();

        return view('mhs.booking.index', [
            'menu' => 'booking',
            'activeTicket' => $activeTicket,
            'latestTicket' => $latestTicket,
            'histories' => $histories,

            'totalBookings' => $totalBookings,
            'activeBookings' => $activeBookings,
            'completedBookings' => $completedBookings,
            'failedBookings' => $failedBookings,

            'morningUsedMinutes' => $morningUsedMinutes,
            'afternoonUsedMinutes' => $afternoonUsedMinutes,

            'morningQueueCount' => $morningQueueCount,
            'afternoonQueueCount' => $afternoonQueueCount,

            'morningAvailable' => $morningAvailable,
            'afternoonAvailable' => $afternoonAvailable,

            'morningProgress' => $morningProgress,
            'afternoonProgress' => $afternoonProgress,

            'notice' => $notice,
            'bookingClosedToday' => $sessionFlags['bookingClosedToday'],
        ]);
    }

    public function create()
    {
        $softwares = Software::orderBy('name')->get();
        $sessionFlags = $this->getSessionAvailabilityFlags();

        return view('mhs.booking.create', [
            'menu' => 'booking',
            'softwares' => $softwares,
            'morningAvailable' => $sessionFlags['morningAvailable'],
            'afternoonAvailable' => $sessionFlags['afternoonAvailable'],
            'bookingClosedToday' => $sessionFlags['bookingClosedToday'],
        ]);
    }

    public function edit(ticket $ticket)
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        $this->syncTicketQueue($ticket);
        $ticket->refresh();

        abort_if(in_array($ticket->status, ['completed', 'failed', 'cancelled']), 403);
        abort_if($ticket->status !== 'waiting', 403);

        $ticket->load(['penginstalan.software', 'comments']);

        $softwares = Software::orderBy('name')->get();

        return view('mhs.booking.edit', [
            'menu' => 'booking',
            'ticket' => $ticket,
            'softwares' => $softwares,
        ]);
    }

    public function show(ticket $ticket)
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        $this->syncTicketQueue($ticket);
        $ticket->refresh();

        $ticket->load([
            'user',
            'penginstalan.software',
            'statusLogs.changer',
            'comments.user',
        ]);

        return view('mhs.booking.show', [
            'menu' => 'booking',
            'ticket' => $ticket,
            'canModify' => $ticket->status === 'waiting',
        ]);
    }

    public function checkAvailability(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'booking_date' => ['required', 'date'],
            'session'      => ['required', 'in:morning,afternoon'],
            'software_id'  => ['required', 'exists:software,id'],
        ])->validate();

        $date = Carbon::parse($validated['booking_date']);

        if ($date->isToday()) {
            $this->bookingService->syncSessionState($date, $validated['session']);
        }

        $availability = $this->bookingService->checkAvailability(
            $date,
            $validated['session'],
            (int) $validated['software_id']
        );

        return response()->json([
            'available'    => $availability['available'],
            'queue_number' => $availability['queue_number'],
            'next_start'   => $availability['next_start']->format('H:i'),
            'next_end'     => $availability['next_end']->format('H:i'),
            'message'      => $availability['available']
                ? 'Slot tersedia.'
                : 'Booking penuh atau sudah melewati batas waktu booking.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'session'      => ['required', 'in:morning,afternoon'],
            'software_id'  => ['required', 'exists:software,id'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ])->validate();

        try {
            $date = Carbon::parse($validated['booking_date']);

            if ($date->isToday()) {
                $this->bookingService->syncSessionState($date, $validated['session']);
            }

            $this->bookingService->createBooking([
                'user_id'      => Auth::id(),
                'booking_date' => $validated['booking_date'],
                'session'      => $validated['session'],
                'software_id'  => (int) $validated['software_id'],
                'note'         => $validated['note'] ?? null,
                'changed_by'   => Auth::id(),
            ]);
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['session' => $e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('mahasiswa.booking.index')
            ->with('success', 'Booking berhasil dibuat. Ticket telah digenerate otomatis.');
    }

    public function update(Request $request, ticket $ticket)
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        $this->syncTicketQueue($ticket);
        $ticket->refresh();

        abort_if(in_array($ticket->status, ['completed', 'failed', 'cancelled']), 403);
        abort_if($ticket->status !== 'waiting', 403);

        $validated = Validator::make($request->all(), [
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'session'      => ['required', 'in:morning,afternoon'],
            'software_id'  => ['required', 'exists:software,id'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $this->bookingService->updateBooking($ticket, [
            'booking_date' => $validated['booking_date'],
            'session'      => $validated['session'],
            'software_id'  => (int) $validated['software_id'],
            'note'         => $validated['note'] ?? null,
            'changed_by'   => Auth::id(),
        ]);

        return redirect()
            ->route('mahasiswa.booking.show', $ticket->id)
            ->with('success', 'Booking berhasil diperbarui.');
    }

    public function destroy(ticket $ticket)
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        $this->syncTicketQueue($ticket);
        $ticket->refresh();

        abort_if(in_array($ticket->status, ['completed', 'failed', 'cancelled']), 403);

        if ($ticket->status !== 'waiting') {
            return back()->withErrors([
                'booking_date' => 'Booking tidak bisa dibatalkan karena sudah masuk proses pengerjaan.',
            ]);
        }

        $this->bookingService->cancelBooking($ticket, Auth::id());

        return redirect()
            ->route('mahasiswa.booking.index')
            ->with('success', 'Booking berhasil dibatalkan.');
    }
}
