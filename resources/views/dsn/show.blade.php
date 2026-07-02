<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div>
                <h2 class="tn-modal-title">
                    {{ $ticket->ticket_number }}
                </h2>
                <p class="tn-modal-subtitle">
                    Detail Perbaikan
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="tn-modal-grid">
            <div class="tn-modal-group">
                <label>Barang</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $ticket->perbaikan?->item_name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Lokasi Barang</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $ticket->perbaikan?->item_location ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($ticket->status) }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Prioritas</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($ticket->priority) }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Deskripsi Kerusakan</label>
                <div class="tn-modal-control tn-modal-readonly" style="min-height: 88px; white-space: pre-wrap;">
                    {{ $ticket->perbaikan?->damage_description ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Tindakan Perbaikan</label>
                <div class="tn-modal-control tn-modal-readonly" style="min-height: 88px; white-space: pre-wrap;">
                    {{ $ticket->perbaikan?->repair_action ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Hasil Perbaikan</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $ticket->perbaikan?->repair_result ? ucfirst($ticket->perbaikan->repair_result) : '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Estimasi Selesai</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($ticket->estimated_finish)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Selesai Pada</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($ticket->completed_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            @if($ticket->qr_code)
            <div class="tn-modal-group tn-modal-full">
                <label>QR Ticket</label>

                <div class="qr-ticket-card">
                    <div class="qr-ticket-image-wrap">
                        <img
                            src="{{ asset('storage/' . $ticket->qr_code) }}"
                            alt="QR {{ $ticket->ticket_number }}"
                            class="qr-ticket-image">
                    </div>

                    <div class="qr-ticket-footer">
                        {{ $ticket->ticket_number }}
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