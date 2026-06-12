@extends('template_admin.layout')
@section('title', 'Ticket Management')
@section('content')

<div class="page-header">

    <div>
        <h1>Ticket Management</h1>
        <p>Manage installation and repair tickets.</p>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">

        <a
            href="{{ route('ticket.logs') }}"
            class="btn-secondary"
            style="text-decoration:none;">

            <i data-lucide="history"></i>
            <span>Activity Logs</span>

        </a>

    </div>

</div>

@if (session('success'))
<div class="tn-alert tn-alert-success">
    <strong>Success!</strong>
    <p>{{ session('success') }}</p>
</div>
@endif

@if (session('edit'))
<div class="tn-alert tn-alert-edit">
    <strong>Updated!</strong>
    <p>{{ session('edit') }}</p>
</div>
@endif

@if ($errors->any())
<div class="tn-alert tn-alert-error">
    <strong>Oops! Please correct the following errors:</strong>
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="glass table-card motion-card">

    <div class="table-toolbar">

        <div class="search-box">
            <i data-lucide="search"></i>
            <input
                type="text"
                id="ticketSearch"
                placeholder="Search ticket...">
        </div>

        <div class="table-footer-actions">
            <span style="color:var(--text-light)">
                Total: {{ $tickets->total() }} Ticket
            </span>
        </div>

    </div>

    <table>

        <thead>
            <tr>
                <th>Ticket Number</th>
                <th>Type</th>
                <th>User</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Public</th>
              
                <th width="170">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($tickets as $ticket)

            <tr class="ticket-row">

                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="ticket"></i>
                        </div>

                        <div>
                            <strong>{{ $ticket->ticket_number }}</strong>
                            <br>
                            <small style="color:var(--text-light);">
                                Ticket System
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    @if($ticket->type === 'installation')
                    <span class="badge success">Installation</span>
                    @else
                    <span class="badge warning">Repair</span>
                    @endif
                </td>

                <td>
                    {{ $ticket->user->name ?? '-' }}
                </td>

                <td>
                    {{ ucfirst($ticket->status) }}
                </td>

                <td>
                    @if($ticket->priority === 'urgent')
                    <span class="badge danger">Urgent</span>
                    @elseif($ticket->priority === 'high')
                    <span class="badge warning">High</span>
                    @else
                    <span class="badge success">Normal</span>
                    @endif
                </td>

                <td>
                    @if($ticket->is_public)
                    <span class="badge success">Public</span>
                    @else
                    <span class="badge danger">Private</span>
                    @endif
                </td>

             

                <td>
                    <div class="table-actions">

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('ticket.show', $ticket->id) }}">

                            <i data-lucide="eye"></i>

                        </button>

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('ticket.edit', $ticket->id) }}">

                            <i data-lucide="pencil"></i>

                        </button>

                    </div>
                </td>

            </tr>

            @empty

            <tr>
                <td colspan="8" style="text-align:center;padding:40px;">
                    No ticket available.
                </td>
            </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $tickets->links() }}
</div>

<div id="modalContainer"></div>

@endsection