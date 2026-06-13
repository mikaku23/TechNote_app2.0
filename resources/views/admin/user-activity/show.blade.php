<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Activity Log Detail</h2>
                <p class="tn-modal-subtitle">Informasi aktivitas pengguna</p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="glass tn-modal-info-box">
            <div class="tn-modal-info-row">
                <div class="glass tn-modal-icon-box">
                    <i data-lucide="activity"></i>
                </div>

                <div>
                    <h3>{{ $log->user?->name ?? 'User tidak ditemukan' }}</h3>
                    <p class="tn-modal-subtitle">{{ $log->module }} - {{ ucfirst($log->activity) }}</p>
                </div>
            </div>
        </div>

        <div class="tn-modal-grid">
            <div class="tn-modal-group">
                <label>Nama</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->user?->name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Username</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->user?->username ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Role</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->user?->role?->name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Module</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->module }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Activity</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ ucfirst($log->activity) }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Created At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->created_at ? $log->created_at->format('d M Y H:i:s') : '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Description</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $log->description ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Old Data</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    <pre style="margin:0;white-space:pre-wrap;">{{ json_encode($log->old_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>New Data</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    <pre style="margin:0;white-space:pre-wrap;">{{ json_encode($log->new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
                </div>
            </div>
        </div>

        <div class="tn-modal-actions">
            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Tutup
            </button>
        </div>
    </div>
</div>