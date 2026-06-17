@extends('template_admin.layout')
@section('title', 'Ticket Management')
@section('css')
<style>
    .session-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .session-card {
        padding: 18px;
        border-radius: 22px;
        min-height: 160px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        box-shadow:
            0 12px 30px rgba(0, 0, 0, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .session-card.is-active {
        opacity: 1;
    }

    .session-card.is-disabled {
        opacity: 0.45;
        filter: saturate(0.25);
        cursor: not-allowed;
    }

    .session-progress {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.10);
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.12);
    }

    .session-progress-bar {
        width: var(--progress-width);
        height: 100%;
        border-radius: 999px;
        transition: width 0.4s ease;
    }

    /* aktif, aman */
    .session-progress-time .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(34, 197, 94, 0.70),
                rgba(16, 185, 129, 0.45));
        box-shadow:
            0 0 10px rgba(34, 197, 94, 0.22),
            0 0 18px rgba(16, 185, 129, 0.14);
    }

    /* kapasitas aman */
    .session-progress-capacity .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(59, 130, 246, 0.70),
                rgba(96, 165, 250, 0.45));
        box-shadow:
            0 0 10px rgba(59, 130, 246, 0.22),
            0 0 18px rgba(96, 165, 250, 0.14);
    }

    /* kapasitas mulai penuh */
    .session-progress-warning .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(245, 158, 11, 0.72),
                rgba(251, 191, 36, 0.46));
        box-shadow:
            0 0 10px rgba(245, 158, 11, 0.20),
            0 0 18px rgba(251, 191, 36, 0.12);
    }

    /* kapasitas hampir habis */
    .session-progress-danger .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(239, 68, 68, 0.72),
                rgba(248, 113, 113, 0.46));
        box-shadow:
            0 0 10px rgba(239, 68, 68, 0.20),
            0 0 18px rgba(248, 113, 113, 0.12);
    }

    /* non aktif, netral glass */
    .session-progress-neutral .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(148, 163, 184, 0.46),
                rgba(203, 213, 225, 0.28));
        box-shadow:
            0 0 8px rgba(148, 163, 184, 0.10),
            0 0 14px rgba(203, 213, 225, 0.06);
    }

    /* beku, abu-abu */
    .session-progress-muted .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(107, 114, 128, 0.58),
                rgba(156, 163, 175, 0.36));
        box-shadow:
            0 0 8px rgba(107, 114, 128, 0.10),
            0 0 14px rgba(156, 163, 175, 0.06);
    }

    .session-progress-warning .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(245, 158, 11, 0.72),
                rgba(251, 191, 36, 0.46));
        box-shadow:
            0 0 10px rgba(245, 158, 11, 0.20),
            0 0 18px rgba(251, 191, 36, 0.12);
    }

    .session-progress-danger .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(239, 68, 68, 0.72),
                rgba(248, 113, 113, 0.46));
        box-shadow:
            0 0 10px rgba(239, 68, 68, 0.20),
            0 0 18px rgba(248, 113, 113, 0.12);
    }

    .session-progress-neutral .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(148, 163, 184, 0.46),
                rgba(203, 213, 225, 0.28));
        box-shadow:
            0 0 8px rgba(148, 163, 184, 0.10),
            0 0 14px rgba(203, 213, 225, 0.06);
    }

    .session-progress-muted .session-progress-bar {
        background: linear-gradient(90deg,
                rgba(107, 114, 128, 0.58),
                rgba(156, 163, 175, 0.36));
        box-shadow:
            0 0 8px rgba(107, 114, 128, 0.10),
            0 0 14px rgba(156, 163, 175, 0.06);
    }
</style>
@endsection
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

<div class="session-grid">
    @forelse ($sessionCards as $session)
    @php
    $isFrozen = $session['status'] === 'ended';
    $isActive = $session['status'] === 'active';

    $timeClass = 'session-progress-neutral';
    $capacityClass = 'session-progress-neutral';

    if ($isFrozen) {
    $timeClass = 'session-progress-muted';
    $capacityClass = 'session-progress-muted';
    } elseif ($isActive) {
    if ($session['real_progress_percent'] >= 90) {
    $timeClass = 'session-progress-danger';
    } elseif ($session['real_progress_percent'] >= 70) {
    $timeClass = 'session-progress-warning';
    } else {
    $timeClass = 'session-progress-time';
    }

    if ($session['booking_progress_percent'] >= 90) {
    $capacityClass = 'session-progress-danger';
    } elseif ($session['booking_progress_percent'] >= 70) {
    $capacityClass = 'session-progress-warning';
    } else {
    $capacityClass = 'session-progress-capacity';
    }
    }
    @endphp

    <div class="glass session-card {{ $isFrozen ? 'is-disabled' : 'is-active' }}">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
            <div>
                <div style="font-size:12px;color:var(--text-light);">
                    {{ $session['label'] }}
                </div>
                <h3 style="margin:4px 0 0;">
                    {{ $session['range'] }}
                </h3>
            </div>

            @if ($session['status'] === 'active')
            <span class="badge success">Aktif</span>
            @elseif ($session['status'] === 'upcoming')
            <span class="badge warning">Menunggu</span>
            @else
            <span class="badge danger">Beku</span>
            @endif
        </div>

        <div style="margin-top:14px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-light);margin-bottom:6px;">
                <span>Waktu sesi terpakai: {{ $session['real_elapsed_human'] }}</span>
                <span>Sisa waktu sesi: {{ $session['real_remaining_human'] }}</span>
            </div>

            <div
                class="session-progress {{ $timeClass }}"
                style="--progress-width: {{ (int) $session['real_progress_percent'] }}%;">
                <div class="session-progress-bar"></div>
            </div>
        </div>

        <div style="margin-top:14px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-light);margin-bottom:6px;">
                <span>Slot ticket terpakai: {{ $session['booked_human'] }}</span>
                <span>Sisa kapasitas: {{ $session['capacity_remaining_human'] }}</span>
            </div>

            <div
                class="session-progress {{ $capacityClass }}"
                style="--progress-width: {{ (int) $session['booking_progress_percent'] }}%;">
                <div class="session-progress-bar"></div>
            </div>
        </div>

        <div style="margin-top:12px;font-size:12px;color:var(--text-light);">
            Batas booking: {{ $session['accept_until'] }} • Ticket aktif: {{ $session['ticket_count'] }}
        </div>
    </div>
    @empty
    <div class="glass session-card" style="grid-column:1/-1;">
        Tidak ada data sesi.
    </div>
    @endforelse
</div>

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
                <td colspan="7" style="text-align:center;padding:40px;">
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