<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\ticket_status_log;
use App\Models\User;
use App\Services\TicketFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(private TicketFlowService $ticketFlowService) {}

    public function index()
    {
        $tickets = Ticket::with('user')
            ->latest()
            ->paginate(10);

        return view('admin.ticket.mode1.index', [
            'menu' => 'ticket',
            'title' => 'Ticket Management',
            'tickets' => $tickets,
        ]);
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

        return redirect()
            ->route('ticket.show', $ticket->id)
            ->with('success', 'Ticket status updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()
            ->route('ticket.index')
            ->with('success', 'Ticket moved to recycle bin.');
    }
}
