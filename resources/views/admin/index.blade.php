@extends('template_admin.layout')

@section('title', 'Dashboard Admin')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/dash.css') }}">
<style>
    .tn-glass-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    background: rgba(15, 23, 42, 0.35);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 16px;

    color: #e2e8f0;
    padding: 10px 42px 10px 14px;
    min-width: 170px;

    font-size: 14px;
    font-weight: 600;
    line-height: 1.2;

    box-shadow:
        0 8px 24px rgba(2, 6, 23, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);

    cursor: pointer;
    transition: all 0.2s ease;
    background-image:
        linear-gradient(45deg, transparent 50%, rgba(226, 232, 240, 0.9) 50%),
        linear-gradient(135deg, rgba(226, 232, 240, 0.9) 50%, transparent 50%);
    background-position:
        calc(100% - 18px) 50%,
        calc(100% - 12px) 50%;
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
}

.tn-glass-select:hover {
    border-color: rgba(96, 165, 250, 0.45);
    background: rgba(15, 23, 42, 0.45);
    box-shadow:
        0 10px 28px rgba(2, 6, 23, 0.28),
        0 0 0 1px rgba(96, 165, 250, 0.12) inset;
}

.tn-glass-select:focus {
    outline: none;
    border-color: rgba(96, 165, 250, 0.7);
    box-shadow:
        0 0 0 3px rgba(96, 165, 250, 0.16),
        0 10px 28px rgba(2, 6, 23, 0.28);
}

.tn-glass-select option {
    background: #0f172a;
    color: #e2e8f0;
}
</style>
@endsection

@section('content')
@php
$dashboardData = [
    'ticketChart' => [
        'labels' => $ticketChartLabels ?? [],
        'installation' => $ticketInstallationChart ?? [],
        'repair' => $ticketRepairChart ?? [],
    ],
];
@endphp

<script>
    window.__TN_DASHBOARD_DATA = @json($dashboardData);
</script>

<div class="page-header">
    <div>
        <h1>Dashboard Admin</h1>
        <p>Liquid glass command center for TechNote App 2.0.</p>
    </div>

    <div class="dashboard-header-actions">
        <a href="{{ route('admin.ai.index') }}" class="btn-secondary" style="text-decoration:none;">
            <i data-lucide="bot"></i>
            <span>AI Dashboard</span>
        </a>
        <a href="{{ route('ticket.index') }}" class="btn-primary" style="text-decoration:none;">
            <i data-lucide="ticket"></i>
            <span>Tickets</span>
        </a>
    </div>
</div>

@if (session('success'))
<div class="tn-alert tn-alert-success">
    <strong>Success!</strong>
    <p>{{ session('success') }}</p>
</div>
@endif

<div class="dashboard-hero">
    <div class="glass hero-card">
        <div class="hero-top">
            <div>
                <span class="tag success">
                    <i data-lucide="sparkles"></i>
                    Admin overview
                </span>
                <div class="hero-title">Selamat datang, {{ $adminName }}</div>
                <div class="hero-subtitle">
                    Ringkasan operasional TechNote App 2.0: status pengguna, tiket, sesi kerja, maintenance, dan aktivitas terbaru dalam satu panel.
                </div>
                <div class="hero-badges">
                    <span class="hero-chip">
                        <i data-lucide="user-round"></i>
                        {{ $adminUsername }}
                    </span>
                    <span class="hero-chip">
                        <i data-lucide="shield-check"></i>
                        {{ $adminRole }}
                    </span>
                    <span class="hero-chip">
                        <i data-lucide="clock-3"></i>
                        {{ $serverTime }}
                    </span>
                    <span class="hero-chip">
                        <i data-lucide="calendar-days"></i>
                        {{ $todayLabel }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="glass profile-card">
        <div class="profile-row">
            <div class="profile-avatar">{{ $adminInitial }}</div>
            <div class="profile-meta">
                <h3>{{ $adminName }}</h3>
                <p>{{ $adminEmail }}</p>
                <span>
                    <i data-lucide="activity"></i>
                    {{ $onlineCount }} online sekarang
                </span>
            </div>
        </div>

        <div class="empty-state">
            Dashboard ini menampilkan snapshot data live dari login log, user activity, AI logs, ticket session, dan maintenance.
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="metric-grid">
        <div class="glass metric-card">
            <div class="metric-top">
                <div>
                    <p class="metric-label">Online Users</p>
                    <h2 class="metric-value">{{ number_format($onlineCount) }}</h2>
                    <p class="metric-sub">Pengguna yang sedang aktif login.</p>
                </div>
                <div class="metric-icon success"><i data-lucide="wifi"></i></div>
            </div>
        </div>

        <div class="glass metric-card">
            <div class="metric-top">
                <div>
                    <p class="metric-label">Offline Users</p>
                    <h2 class="metric-value">{{ number_format($offlineCount) }}</h2>
                    <p class="metric-sub">Pengguna yang terakhir tercatat logout.</p>
                </div>
                <div class="metric-icon warning"><i data-lucide="power"></i></div>
            </div>
        </div>

        <div class="glass metric-card">
            <div class="metric-top">
                <div>
                    <p class="metric-label">Roles</p>
                    <h2 class="metric-value">{{ number_format($roleCount) }}</h2>
                    <p class="metric-sub">Total role pada sistem akses.</p>
                </div>
                <div class="metric-icon purple"><i data-lucide="shield"></i></div>
            </div>
        </div>

        <div class="glass metric-card">
            <div class="metric-top">
                <div>
                    <p class="metric-label">Software</p>
                    <h2 class="metric-value">{{ number_format($softwareCount) }}</h2>
                    <p class="metric-sub">Master aplikasi yang terdaftar.</p>
                </div>
                <div class="metric-icon primary"><i data-lucide="package"></i></div>
            </div>
        </div>
    </div>

    <div class="status-grid">
        <div class="glass status-card">
            <div>
                <span class="status-pill slate"><i data-lucide="layers-3"></i>Total Ticket</span>
                <h3>Queue overview</h3>
                <p class="count">{{ number_format($ticketTotal) }}</p>
                <p class="desc">Seluruh ticket yang tercatat di sistem.</p>
            </div>
        </div>

        <div class="glass status-card">
            <div>
                <span class="status-pill warning"><i data-lucide="hourglass"></i>Waiting</span>
                <h3>Menunggu proses</h3>
                <p class="count">{{ number_format($ticketWaiting) }}</p>
                <p class="desc">Ticket yang belum dikerjakan.</p>
            </div>
        </div>

        <div class="glass status-card">
            <div>
                <span class="status-pill primary"><i data-lucide="cog"></i>Processing</span>
                <h3>Sedang berjalan</h3>
                <p class="count">{{ number_format($ticketProcessing) }}</p>
                <p class="desc">Ticket yang sedang diproses teknisi.</p>
            </div>
        </div>

        <div class="glass status-card">
            <div>
                <span class="status-pill success"><i data-lucide="check-circle-2"></i>Completed</span>
                <h3>Selesai</h3>
                <p class="count">{{ number_format($ticketCompleted) }}</p>
                <p class="desc">Ticket yang sudah ditutup.</p>
            </div>
        </div>

        <div class="glass status-card">
            <div>
                <span class="status-pill danger"><i data-lucide="x-circle"></i>Failed</span>
                <h3>Gagal</h3>
                <p class="count">{{ number_format($ticketFailed) }}</p>
                <p class="desc">Ticket yang ditandai gagal.</p>
            </div>
        </div>
    </div>

    <div class="system-grid">
        <div class="glass system-card">
            <div class="system-head">
                <div>
                    <span class="tag {{ $maintenanceActive ? 'warning' : 'success' }}">
                        <i data-lucide="{{ $maintenanceActive ? 'shield-alert' : 'shield-check' }}"></i>
                        Maintenance
                    </span>
                    <p>{{ $maintenanceActive ? 'Mode maintenance sedang aktif.' : 'Mode maintenance nonaktif.' }}</p>
                </div>
                <div class="system-icon {{ $maintenanceActive ? 'warning' : 'success' }}">
                    <i data-lucide="{{ $maintenanceActive ? 'shield-alert' : 'shield-check' }}"></i>
                </div>
            </div>
            <div class="actions">
                <a href="{{ route('setting.maintenance.index') }}" class="btn-secondary" style="text-decoration:none;">
                    <i data-lucide="arrow-right"></i>
                    <span>Buka Maintenance</span>
                </a>
            </div>
        </div>

        <div class="glass system-card">
            <div class="system-head">
                <div>
                    <span class="tag {{ $antiAiMode ? 'danger' : 'success' }}">
                        <i data-lucide="bot"></i>
                        Anti AI Mode
                    </span>
                    <p>{{ $antiAiMode ? 'AI create/update/delete dibatasi.' : 'AI mode normal dan aktif.' }}</p>
                </div>
                <div class="system-icon {{ $antiAiMode ? 'danger' : 'success' }}">
                    <i data-lucide="bot"></i>
                </div>
            </div>
            <div class="actions">
                <a href="{{ route('admin.ai.index') }}" class="btn-secondary" style="text-decoration:none;">
                    <i data-lucide="message-square"></i>
                    <span>AI Console</span>
                </a>
            </div>
        </div>

        <div class="glass system-card">
            <div class="system-head">
                <div>
                    <span class="tag slate">
                        <i data-lucide="activity-square"></i>
                        System pulse
                    </span>
                    <p>
                        {{ $todayTicketCount }} ticket dibuat hari ini, {{ $todayUserActivityCount }} aktivitas user, dan {{ $todayAiLogCount }} log AI.
                    </p>
                </div>
                <div class="system-icon primary">
                    <i data-lucide="activity-square"></i>
                </div>
            </div>
            <div class="actions">
                <a href="{{ route('ticket.index') }}" class="btn-primary" style="text-decoration:none;">
                    <i data-lucide="ticket"></i>
                    <span>Kelola Ticket</span>
                </a>
            </div>
        </div>
    </div>

    <div class="glass chart-card motion-card">
        <div class="chart-header">
    <div>
        <h3 class="chart-title">Ticket Trend</h3>
        <p class="chart-subtitle" id="ticketTrendSubtitle">
            Menampilkan data minggu berjalan.
        </p>
    </div>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <div class="hero-chip" id="ticketTrendRangeLabel">
            <i data-lucide="bar-chart-3"></i>
            Minggu berjalan
        </div>

       <select id="ticketTrendMode" class="tn-glass-select">
    <option value="week">Mingguan</option>
    <option value="month">Bulanan</option>
</select>
    </div>
</div>

        <div class="chart-wrapper">
            <canvas id="ticketTrendChart"></canvas>
        </div>
    </div>

    <div class="session-grid">
        @forelse($sessionCards as $session)
        @php
        $status = $session['status'] ?? 'neutral';
        $statusClass = match ($status) {
        'active' => 'session-time',
        'upcoming' => 'session-capacity',
        'ended' => 'session-muted',
        default => 'session-muted',
        };

        $progressClass = match ($session['alert_level'] ?? 'neutral') {
        'danger' => 'session-danger',
        'warning' => 'session-warning',
        'time' => 'session-time',
        'capacity' => 'session-capacity',
        default => 'session-muted',
        };
        @endphp

        <div class="glass session-card {{ $status === 'ended' ? 'is-disabled' : '' }}">
            <div class="session-head">
                <div>
                    <span class="tag {{ $status === 'active' ? 'success' : ($status === 'upcoming' ? 'warning' : 'slate') }}">
                        <i data-lucide="timer"></i>
                        {{ $session['badge'] }}
                    </span>
                    <h3>{{ $session['label'] }}</h3>
                    <p>{{ $session['range'] }}</p>
                </div>

                <span class="status-pill {{ $status === 'active' ? 'success' : ($status === 'upcoming' ? 'warning' : 'slate') }}">
                    {{ strtoupper($status) }}
                </span>
            </div>

            <div>
                <div class="session-meta">
                    <span>{{ $session['time_label'] }}</span>
                    <span>{{ $session['time_remaining_label'] }}</span>
                </div>
                <div class="session-line {{ $statusClass }}" style="--progress-width: {{ (int) ($session['time_progress_percent'] ?? 0) }}%;">
                    <div class="session-fill"></div>
                </div>
            </div>

            <div>
                <div class="session-meta">
                    <span>{{ $session['capacity_label'] }}</span>
                    <span>{{ $session['capacity_remaining_label'] }}</span>
                </div>
                <div class="session-line {{ $progressClass }}" style="--progress-width: {{ (int) ($session['booking_progress_percent'] ?? 0) }}%;">
                    <div class="session-fill"></div>
                </div>
            </div>

            <div class="empty-state">
                Ticket aktif: {{ $session['ticket_count'] }} • Batas booking: {{ $session['accept_until'] }}
            </div>
        </div>
        @empty
        <div class="glass session-card">
            Tidak ada data sesi ticket.
        </div>
        @endforelse
    </div>

    <div class="lists-grid">
        <div class="glass mini-card motion-card">
            <div class="mini-head">
                <div>
                    <h3>Recent User Activity</h3>
                    <p>3 aktivitas terbaru dari sistem.</p>
                </div>
                <a href="{{ route('user-activity.index') }}" class="btn-secondary" style="text-decoration:none;">
                    <i data-lucide="arrow-right"></i>
                    <span>Lihat</span>
                </a>
            </div>

            <div class="mini-list">
                @forelse($recentUserActivities as $item)
                <div class="mini-item">
                    <div class="mini-icon"><i data-lucide="history"></i></div>
                    <div class="mini-content">
                        <strong>{{ $item->user_name ?? 'System' }}</strong>
                        <p>{{ $item->action ?? '-' }}</p>
                        <div class="mini-meta">
                            <span>{{ $item->user_username ?? '-' }}</span>
                            <span>•</span>
                            <span>{{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-state">Belum ada user activity.</div>
                @endforelse
            </div>
        </div>

        <div class="glass mini-card motion-card">
            <div class="mini-head">
                <div>
                    <h3>Recent Login Logs</h3>
                    <p>3 log login/logout terbaru.</p>
                </div>
                <a href="{{ route('login-log.index') }}" class="btn-secondary" style="text-decoration:none;">
                    <i data-lucide="arrow-right"></i>
                    <span>Lihat</span>
                </a>
            </div>

            <div class="mini-list">
                @forelse($recentLoginLogs as $log)
                <div class="mini-item">
                    <div class="mini-icon"><i data-lucide="{{ $log->status === 'online' ? 'wifi' : 'power' }}"></i></div>
                    <div class="mini-content">
                        <strong>{{ $log->user_name ?? 'Unknown User' }}</strong>
                        <p>{{ $log->ip_address ?? '-' }} • {{ ucfirst($log->status ?? '-') }}</p>
                        <div class="mini-meta">
                            <span>{{ $log->user_username ?? '-' }}</span>
                            <span>•</span>
                            <span>{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-state">Belum ada login log.</div>
                @endforelse
            </div>
        </div>

        <div class="glass mini-card motion-card">
            <div class="mini-head">
                <div>
                    <h3>Recent AI Logs</h3>
                    <p>3 interaksi AI terbaru.</p>
                </div>
                <a href="{{ route('ai.log') }}" class="btn-secondary" style="text-decoration:none;">
                    <i data-lucide="arrow-right"></i>
                    <span>Lihat</span>
                </a>
            </div>

            <div class="mini-list">
                @forelse($recentAiLogs as $log)
                <div class="mini-item">
                    <div class="mini-icon"><i data-lucide="bot"></i></div>
                    <div class="mini-content">
                        <strong>{{ $log->user_name ?? 'System' }}</strong>
                        <p>{{ \Illuminate\Support\Str::limit($log->question ?? '-', 100) }}</p>
                        <div class="mini-meta">
                            <span>{{ $log->source ?? '-' }}</span>
                            <span>•</span>
                            <span>{{ $log->action ?? '-' }}</span>
                            <span>•</span>
                            <span>{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-state">Belum ada AI log.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="quick-grid">
        <a href="{{ route('ticket.index') }}" class="glass quick-card">
            <div class="metric-icon primary"><i data-lucide="ticket"></i></div>
            <div>
                <h4>Ticket Management</h4>
                <p>Masuk ke halaman ticket untuk melihat queue, session, dan status ticket.</p>
            </div>
        </a>

        <a href="{{ route('user.index') }}" class="glass quick-card">
            <div class="metric-icon success"><i data-lucide="users"></i></div>
            <div>
                <h4>User Management</h4>
                <p>Kelola user, role, dan data akses dari satu tempat.</p>
            </div>
        </a>

        <a href="{{ route('software.index') }}" class="glass quick-card">
            <div class="metric-icon purple"><i data-lucide="package"></i></div>
            <div>
                <h4>Software Master</h4>
                <p>Lihat data software yang sering dipakai untuk penginstalan.</p>
            </div>
        </a>

        <a href="{{ route('setting.maintenance.index') }}" class="glass quick-card">
            <div class="metric-icon warning"><i data-lucide="shield-alert"></i></div>
            <div>
                <h4>Maintenance Center</h4>
                <p>Atur mode maintenance dan kontrol akses sistem.</p>
            </div>
        </a>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/dash.js') }}"></script>
@endsection