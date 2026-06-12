@extends('template_admin.layout')
@section('title', 'Ticket Activity Logs')

@section('content')

<div class="page-header">

    <div>
        <h1>Ticket Activity Logs</h1>
        <p>Track status changes and ticket activity.</p>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">

        <a
            href="{{ route('ticket.index') }}"
            class="btn-secondary"
            style="text-decoration:none;">

            <i data-lucide="arrow-left"></i>
            <span>Back to Tickets</span>

        </a>

    </div>

</div>

<div class="glass table-card motion-card">

    <div class="table-toolbar">

        <div class="search-box">
            <i data-lucide="search"></i>
            <input
                type="text"
                id="ticketLogSearch"
                placeholder="Search log...">
        </div>

        <div class="table-footer-actions">
            <span style="color:var(--text-light)">
                Total: {{ $ticketLogs->total() }} Log
            </span>
        </div>

    </div>

    <table>

        <thead>
            <tr>
                <th>Ticket</th>
                <th>Old Status</th>
                <th>New Status</th>
                <th>Note</th>
                <th>Changed By</th>
                <th>Created At</th>
                <th width="120">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($ticketLogs as $log)

            <tr class="ticket-log-row">

                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="glass" style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="ticket"></i>
                        </div>
                        <div>
                            <strong>{{ $log->ticket->ticket_number ?? '-' }}</strong>
                            <br>
                            <small style="color:var(--text-light);">
                                {{ ucfirst($log->ticket->type ?? '-') }}
                            </small>
                        </div>
                    </div>
                </td>

                <td>{{ $log->old_status ?? '-' }}</td>

                <td>{{ $log->new_status }}</td>

                <td>{{ $log->note ?? '-' }}</td>

                <td>{{ $log->changer->name ?? 'System' }}</td>

                <td>{{ $log->created_at->format('d M Y H:i') }}</td>

                <td>
                    <div class="table-actions">

                        <a
                            type="button"
                            class="btn-secondary open-modal"
                            href="{{ route('ticket.logs.show', $log->ticket_id) }}">

                            <i data-lucide="eye"></i>

                        </a>
                    </div>
                </td>

            </tr>

            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;">
                    No ticket log available.
                </td>
            </tr>
            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $ticketLogs->links() }}
</div>

@endsection