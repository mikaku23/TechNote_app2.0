<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    {{ $ticket->ticket_number }}
                </h2>

                <p class="tn-modal-subtitle">
                    Ticket Detail
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <div class="glass tn-modal-info-box">

            <div class="tn-modal-info-row">

                <div class="glass tn-modal-icon-box">
                    <i data-lucide="ticket" class="tn-modal-package-icon"></i>
                </div>

                <div>
                    <h3>{{ $ticket->ticket_number }}</h3>
                    <p class="tn-modal-subtitle">
                        {{ ucfirst($ticket->type) }} Ticket
                    </p>
                </div>

            </div>

        </div>

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
                <label>Public</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $ticket->is_public ? 'Public' : 'Private' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Estimated Finish</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($ticket->estimated_finish)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Completed At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($ticket->completed_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

        </div>

        <div class="glass" style="padding:20px;border-radius:24px;margin-top:4px;">

            <div class="tn-modal-header" style="margin-bottom:14px;">
                <div class="tn-modal-header-left">
                    <h3 class="tn-modal-title" style="font-size:20px;">Ticket Actions</h3>
                    <p class="tn-modal-subtitle">
                        Update ticket status or view activity logs.
                    </p>
                </div>

                <a
                    href="{{ route('ticket.logs.show', $ticket->id) }}"
                    class="btn-secondary"
                    style="text-decoration:none;">

                    <i data-lucide="history"></i>
                    Activity Logs

                </a>
            </div>

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Status Update</label>
                    <a
                        href="{{ route('ticket.edit', $ticket->id) }}"
                        class="btn-primary"
                        style="text-decoration:none;justify-content:center;">

                        <i data-lucide="pencil"></i>
                        Update Status

                    </a>
                </div>

            </div>

        </div>

        <div class="glass" style="padding:20px;border-radius:24px;margin-top:18px;">
            <h3 style="margin-bottom:14px;">Related Installation</h3>

            @if($ticket->penginstalan)
            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Software</label>
                    <div class="tn-modal-control tn-modal-readonly">
                        {{ $ticket->penginstalan->software->name ?? '-' }}
                    </div>
                </div>

                <div class="tn-modal-group">
                    <label>Installation Result</label>
                    <div class="tn-modal-control tn-modal-readonly">
                        {{ $ticket->penginstalan->installation_result ?? 'Pending' }}
                    </div>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Installation Note</label>
                    <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                        {{ $ticket->penginstalan->note ?? 'No note available.' }}
                    </div>
                </div>

            </div>
            @else
            <p style="color:var(--text-light);">No related installation data.</p>
            @endif
        </div>

        <div class="glass" style="padding:20px;border-radius:24px;margin-top:18px;">
            <h3 style="margin-bottom:14px;">Latest Timeline</h3>

            <div style="display:flex;flex-direction:column;gap:12px;">
                @forelse($ticket->statusLogs->take(5) as $log)
                <div class="glass" style="padding:14px;border-radius:16px;">
                    <strong>
                        {{ $log->old_status ?? 'Created' }} → {{ $log->new_status }}
                    </strong>
                    <br>
                    <small style="color:var(--text-light);">
                        {{ $log->created_at->format('d M Y H:i') }}
                        @if($log->changer)
                        • by {{ $log->changer->name }}
                        @endif
                    </small>
                    @if($log->note)
                    <p style="margin-top:8px;">{{ $log->note }}</p>
                    @endif
                </div>
                @empty
                <p style="color:var(--text-light);">No logs available.</p>
                @endforelse
            </div>
        </div>

        <div class="tn-modal-actions">
            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Close
            </button>
        </div>

    </div>

</div>