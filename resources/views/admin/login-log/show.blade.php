<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Login Log Detail</h2>
                <p class="tn-modal-subtitle">Informasi sesi login pengguna</p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="glass tn-modal-info-box">
            <div class="tn-modal-info-row">
                <div class="glass tn-modal-icon-box">
                    <i data-lucide="shield-check"></i>
                </div>

                <div>
                    <h3>{{ $log->user?->name ?? 'User tidak ditemukan' }}</h3>
                    <p class="tn-modal-subtitle">{{ $log->user?->role?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        @php
        $hasCoord = !is_null($log->latitude) && !is_null($log->longitude);
        $coordText = $hasCoord
        ? number_format((float) $log->latitude, 8, '.', '') . ', ' . number_format((float) $log->longitude, 8, '.', '')
        : '-';
        @endphp

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

            @if($log->user?->nim)
            <div class="tn-modal-group">
                <label>NIM</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->user->nim }}
                </div>
            </div>
            @endif

            @if($log->user?->nip)
            <div class="tn-modal-group">
                <label>NIP</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->user->nip }}
                </div>
            </div>
            @endif

            <div class="tn-modal-group">
                <label>Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($log->status === 'online')
                    <span class="tn-badge tn-badge-success">Online</span>
                    @else
                    <span class="tn-badge tn-badge-warning">Offline</span>
                    @endif
                </div>
            </div>

            <div class="tn-modal-group">
                <label>IP Address</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->ip_address ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Login At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->login_at ? $log->login_at->format('d M Y H:i:s') : '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Logout At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $log->logout_at ? $log->logout_at->format('d M Y H:i:s') : 'Masih aktif' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Durasi Sesi</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($log->login_at && $log->logout_at)
                    {{ $log->login_at->diffForHumans($log->logout_at, true) }}
                    @else
                    Sesi aktif
                    @endif
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Latitude</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ !is_null($log->latitude) ? $log->latitude : '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Longitude</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ !is_null($log->longitude) ? $log->longitude : '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Koordinat</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $coordText }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Akurasi GPS</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ !is_null($log->accuracy_m) ? number_format((float) $log->accuracy_m, 2) . ' meter' : '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Jarak ke Titik Tumpuan</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ !is_null($log->distance_from_anchor_m) ? number_format((float) $log->distance_from_anchor_m, 2) . ' meter' : '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status Lokasi</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($log->location_status === 'inside')
                    <span class="tn-badge tn-badge-success">Dalam Radius</span>
                    @elseif($log->location_status === 'outside')
                    <span class="tn-badge tn-badge-warning">Di Luar Radius</span>
                    @else
                    <span class="tn-badge tn-badge-secondary">Tidak Diketahui</span>
                    @endif
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>User Agent</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $log->user_agent ?? '-' }}
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