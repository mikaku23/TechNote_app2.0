@extends('template_admin.layout')
@section('title', 'Ticket Activity Detail')

@section('content')

<div class="page-header">

    <div>
        <h1>{{ $ticket->ticket_number }}</h1>
        <p>Detailed activity history for this ticket.</p>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">

        <a
            href="{{ route('ticket.logs') }}"
            class="btn-secondary"
            style="text-decoration:none;">

            <i data-lucide="arrow-left"></i>
            <span>Back to Logs</span>

        </a>

    </div>

</div>

<div class="glass card motion-card" style="padding:24px;">

    <div class="tn-modal-grid">

        <div class="tn-modal-group">
            <label>Ticket Number</label>
            <div class="tn-modal-control tn-modal-readonly">
                {{ $ticket->ticket_number }}
            </div>
        </div>

        <div class="tn-modal-group">
            <label>Type</label>
            <div class="tn-modal-control tn-modal-readonly">
                {{ ucfirst($ticket->type) }}
            </div>
        </div>

        <div class="tn-modal-group">
            <label>User</label>
            <div class="tn-modal-control tn-modal-readonly">
                {{ $ticket->user->name ?? '-' }}
            </div>
        </div>

        <div class="tn-modal-group">
            <label>Status</label>
            <div class="tn-modal-control tn-modal-readonly">
                {{ ucfirst($ticket->status) }}
            </div>
        </div>

        <div class="tn-modal-group">
            <label>Priority</label>
            <div class="tn-modal-control tn-modal-readonly">
                {{ ucfirst($ticket->priority) }}
            </div>
        </div>

        <div class="tn-modal-group">
            <label>Estimated Finish</label>
            <div class="tn-modal-control tn-modal-readonly">
                {{ optional($ticket->estimated_finish)->format('d M Y H:i') ?? '-' }}
            </div>
        </div>

    </div>

</div>

<div class="glass table-card motion-card" style="margin-top:20px;">

    <div class="card-header">
        <h3>Status Timeline</h3>
    </div>

    <table>

        <thead>
            <tr>
                <th>Date</th>
                <th>Old Status</th>
                <th>New Status</th>
                <th>Changed By</th>
                <th>Note</th>
            </tr>
        </thead>

        <tbody>

            @forelse($ticket->statusLogs as $log)

            <tr>
                <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                <td>{{ $log->old_status ?? '-' }}</td>
                <td>{{ $log->new_status }}</td>
                <td>{{ $log->changer->name ?? 'System' }}</td>
                <td>{{ $log->note ?? '-' }}</td>
            </tr>

            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:40px;">
                    No activity found.
                </td>
            </tr>
            @endforelse

        </tbody>

    </table>

</div>

@endsection