<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    {{ $penginstalan->ticket->ticket_number ?? 'Installation Detail' }}
                </h2>

                <p class="tn-modal-subtitle">
                    Installation Information
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <div class="glass tn-modal-info-box">

            <div class="tn-modal-info-row">

                <div class="glass tn-modal-icon-box">
                    <i data-lucide="package" class="tn-modal-package-icon"></i>
                </div>

                <div>
                    <h3>{{ $penginstalan->software->name ?? 'No Software' }}</h3>
                    <p class="tn-modal-subtitle">
                        {{ $penginstalan->user->name ?? 'No User' }}
                    </p>
                </div>

            </div>

        </div>

        <div class="tn-modal-grid">

            <div class="tn-modal-group">
                <label>Ticket Number</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $penginstalan->ticket->ticket_number ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Type</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($penginstalan->ticket->type ?? '-') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>User</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $penginstalan->user->name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Software</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $penginstalan->software->name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($penginstalan->ticket->status ?? '-') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Priority</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($penginstalan->ticket->priority ?? '-') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Result</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($penginstalan->installation_result === 'success')
                    <span class="badge success">Success</span>
                    @elseif($penginstalan->installation_result === 'failed')
                    <span class="badge danger">Failed</span>
                    @else
                    <span class="badge warning">Pending</span>
                    @endif
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Public</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $penginstalan->ticket->is_public ? 'Public' : 'Private' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Created At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($penginstalan->created_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Updated At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($penginstalan->updated_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Note</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $penginstalan->note ?? 'No note available.' }}
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
                data-url="{{ route('penginstalan.edit', $penginstalan->id) }}">
                <i data-lucide="pencil"></i>
                Edit Installation
            </button>
        </div>

    </div>

</div>