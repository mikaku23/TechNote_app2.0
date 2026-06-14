<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Notification Detail</h2>
                <p class="tn-modal-subtitle">Detail notifikasi sistem, AI, atau WhatsApp.</p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        @php
        $ticket = $notification->ticket;
        $user = $ticket?->user;
        $status = $ticket?->status ?? '-';

        $statusClass = match($status) {
        'completed' => 'tn-badge-success',
        'failed' => 'tn-badge-danger',
        'cancelled' => 'tn-badge-secondary',
        'processing' => 'tn-badge-warning',
        'diagnosis' => 'tn-badge-info',
        'testing' => 'tn-badge-primary',
        default => 'tn-badge-secondary',
        };

        $typeClass = match($notification->type) {
        'system' => 'tn-badge-success',
        'ai' => 'tn-badge-warning',
        'whatsapp' => 'tn-badge-primary',
        default => 'tn-badge-secondary',
        };
        @endphp

        <div class="glass tn-modal-info-box">
            <div class="tn-modal-info-row">
                <div class="glass tn-modal-icon-box">
                    <i data-lucide="bell"></i>
                </div>

                <div>
                    <h3>{{ $notification->title }}</h3>
                    <p class="tn-modal-subtitle">
                        <span class="tn-badge {{ $typeClass }}">{{ ucfirst($notification->type) }}</span>
                        · {{ $notification->created_at?->format('d M Y H:i:s') ?? '-' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="tn-modal-grid">
            <div class="tn-modal-group">
                <label>Type</label>
                <div class="tn-modal-control tn-modal-readonly">{{ ucfirst($notification->type) }}</div>
            </div>

            <div class="tn-modal-group">
                <label>Ticket Number</label>
                <div class="tn-modal-control tn-modal-readonly">{{ $ticket?->ticket_number ?? '-' }}</div>
            </div>

            <div class="tn-modal-group">
                <label>User</label>
                <div class="tn-modal-control tn-modal-readonly">{{ $user?->name ?? '-' }}</div>
            </div>

            <div class="tn-modal-group">
                <label>Username</label>
                <div class="tn-modal-control tn-modal-readonly">{{ $user?->username ?? '-' }}</div>
            </div>

            <div class="tn-modal-group">
                <label>Software / Item</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ data_get($ticket, 'penginstalan.software.name') ?? data_get($ticket, 'perbaikan.item_name') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status Ticket</label>
                <div class="tn-modal-control tn-modal-readonly">
                    <span class="tn-badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Message</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $notification->message }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Read Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $notification->is_read ? 'Read' : 'Unread' }}
                </div>
            </div>
        </div>

        <div class="tn-modal-actions">
            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary">
                    <i data-lucide="check"></i>
                    Mark as Read
                </button>
            </form>

            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Close
            </button>
        </div>
    </div>
</div>