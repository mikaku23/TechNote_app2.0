<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    {{ $perbaikan->ticket->ticket_number ?? 'Repair Detail' }}
                </h2>
                <p class="tn-modal-subtitle">
                    Repair Information
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="glass tn-modal-info-box">
            <div class="tn-modal-info-row">
                <div class="glass tn-modal-icon-box">
                    <i data-lucide="wrench" class="tn-modal-package-icon"></i>
                </div>

                <div>
                    <h3>{{ $perbaikan->item_name ?? 'No Item' }}</h3>
                    <p class="tn-modal-subtitle">
                        {{ $perbaikan->user->name ?? 'No User' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="tn-modal-grid">
            <div class="tn-modal-group">
                <label>Ticket Number</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $perbaikan->ticket->ticket_number ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Type</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($perbaikan->ticket->type ?? '-') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>User</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $perbaikan->user->name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Priority</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($perbaikan->ticket->priority ?? '-') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($perbaikan->ticket->status ?? '-') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Result</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($perbaikan->repair_result === 'success')
                    <span class="badge success">Success</span>
                    @elseif($perbaikan->repair_result === 'failed')
                    <span class="badge danger">Failed</span>
                    @elseif($perbaikan->repair_result === 'unrepairable')
                    <span class="badge danger">Unrepairable</span>
                    @else
                    <span class="badge warning">Pending</span>
                    @endif
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Item Name</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $perbaikan->item_name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Location</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $perbaikan->item_location ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Damage Description</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $perbaikan->damage_description ?? 'No damage description.' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Repair Action</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $perbaikan->repair_action ?? 'No repair action yet.' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Note</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $perbaikan->note ?? 'No note available.' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Created At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($perbaikan->created_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Updated At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($perbaikan->updated_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>
        </div>

        <div class="tn-modal-actions">
            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Close
            </button>

            <button
                type="button"
                class="btn-primary open-modal"
                data-url="{{ route('perbaikan.edit', $perbaikan->id) }}">
                <i data-lucide="pencil"></i>
                Edit Repair
            </button>
        </div>
    </div>
</div>