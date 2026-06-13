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
        @if($ticket->qr_code)
        <div class="glass" style="padding-bottom:400px;border-radius:24px;">

            <div style="text-align:center;">

                <h3 style="margin-bottom:6px;">
                    Ticket QR Code
                </h3>

                <p class="tn-modal-subtitle" style="margin-bottom:18px;">
                    Scan this QR code to verify ticket information.
                </p>

                <div class="glass"
                    style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                padding:18px;
                border-radius:24px;
                background:rgba(255,255,255,.08);
                backdrop-filter:blur(20px);
            ">

                    <img
                        src="{{ asset('storage/' . $ticket->qr_code) }}"
                        alt="QR Code"
                        style="
                    width:220px;
                    height:220px;
                    object-fit:contain;
                    border-radius:16px;
                ">
                </div>

                <div style="margin-top:16px;">

                    <div class="tn-modal-control tn-modal-readonly"
                        style="
                    max-width:320px;
                    margin:auto;
                    text-align:center;
                ">
                        {{ $ticket->ticket_number }}
                    </div>

                </div>

            </div>

        </div>
        @endif
        <div class="tn-modal-actions">
            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Close
            </button>
        </div>

    </div>

</div>