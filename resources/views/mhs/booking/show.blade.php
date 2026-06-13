<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div>
                <h2 class="tn-modal-title">
                    {{ $ticket->ticket_number }}
                </h2>
                <p class="tn-modal-subtitle">
                    Detail Booking
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="tn-modal-grid">
            <div class="tn-modal-group">
                <label>Software</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $ticket->penginstalan?->software?->name }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($ticket->status) }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Mulai</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($ticket->scheduled_start)->format('d M Y H:i') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Selesai</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($ticket->scheduled_end)->format('H:i') }}
                </div>
            </div>

            @if($ticket->qr_code)
            <div class="tn-modal-group tn-modal-full">
                <label>QR Ticket</label>

                <div class="qr-ticket-card">

                    <div class="qr-ticket-preview">
                        <img
                            src="{{ asset('storage/' . $ticket->qr_code) }}"
                            alt="QR {{ $ticket->ticket_number }}"
                            class="qr-ticket-image">
                    </div>

                    <div class="qr-ticket-content">
                        <div class="qr-ticket-number">
                            {{ $ticket->ticket_number }}
                        </div>

                        <div class="qr-ticket-desc">
                            Tunjukkan QR ini kepada teknisi saat menyerahkan atau mengambil laptop.
                        </div>

                        <div class="qr-ticket-info">
                            <span>Antrian #{{ $ticket->queue_number }}</span>
                            <span>{{ ucfirst($ticket->status) }}</span>
                        </div>
                    </div>

                </div>
            </div>
            @endif

            <div class="tn-modal-group tn-modal-full">
                <label>Timeline Status</label>
                <div class="glass p-3">
                    @forelse($ticket->statusLogs as $log)
                    <div style="margin-bottom:12px;">
                        <strong>{{ ucfirst($log->new_status) }}</strong>
                        <br>
                        <small>{{ $log->created_at->format('d M Y H:i') }}</small>
                    </div>
                    @empty
                    <p>Tidak ada log status.</p>
                    @endforelse
                </div>
            </div>

            @if($ticket->comments->count())
            <div class="tn-modal-group tn-modal-full">
                <label>Catatan</label>
                <div class="glass p-3">
                    @foreach($ticket->comments as $comment)
                    <div style="margin-bottom:12px;">
                        <strong>{{ $comment->user?->name ?? 'User' }}</strong>
                        <p style="margin:4px 0 0;">{{ $comment->comment }}</p>
                        <small>{{ $comment->created_at->format('d M Y H:i') }}</small>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>


    </div>
</div>