<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    {{ optional($rekap->rekap_date)->format('d M Y') ?? 'Rekap Detail' }}
                </h2>
                <p class="tn-modal-subtitle">
                    Daily summary overview
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="glass tn-modal-info-box">
            <div class="tn-modal-info-row">
                <div class="glass tn-modal-icon-box">
                    <i data-lucide="chart-column-big" class="tn-modal-package-icon"></i>
                </div>

                <div>
                    <h3>Daily Rekap</h3>
                    <p class="tn-modal-subtitle">
                        Ticket summary for the selected date
                    </p>
                </div>
            </div>
        </div>

        <div class="tn-modal-grid">

            <div class="tn-modal-group">
                <label>Date</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($rekap->rekap_date)->format('d M Y') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Total Installation</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $rekap->total_installations }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Total Repair</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $rekap->total_repairs }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Completed Tickets</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $rekap->completed_tickets }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Failed Tickets</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $rekap->failed_tickets }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Pending Tickets</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $rekap->pending_tickets }}
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
                data-url="{{ route('rekap.edit', $rekap->id) }}">
                <i data-lucide="pencil"></i>
                Edit Rekap
            </button>
        </div>
    </div>
</div>