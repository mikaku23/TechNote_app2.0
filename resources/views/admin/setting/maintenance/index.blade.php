@extends('template_admin.layout')

@section('title', 'Maintenance Mode')

@section('css')
<style>
    .maintenance-grid {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        gap: 20px;
    }

    .maintenance-card {
        padding: 22px;
        border-radius: 24px;
    }

    .maintenance-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        width: fit-content;
    }

    .status-on {
        background: rgba(245, 158, 11, .14);
        color: #f59e0b;
    }

    .status-off {
        background: rgba(34, 197, 94, .14);
        color: #22c55e;
    }

    .countdown-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-top: 16px;
    }

    .countdown-box {
        padding: 14px 10px;
        border-radius: 18px;
        text-align: center;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .countdown-box strong {
        display: block;
        font-size: 28px;
        line-height: 1;
        margin-bottom: 6px;
    }

    .role-state-list {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }

    .role-state-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .pill {
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .pill-danger {
        background: rgba(239, 68, 68, .14);
        color: #fca5a5;
    }

    .pill-success {
        background: rgba(34, 197, 94, .14);
        color: #86efac;
    }

    @media (max-width: 992px) {
        .maintenance-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .countdown-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>Maintenance Mode</h1>
        <p>Atur maintenance sementara untuk TechNoteApp 2.0.</p>
    </div>
</div>

@if(session('success'))
<div class="tn-alert tn-alert-success">
    <strong>Success!</strong>
    <p>{{ session('success') }}</p>
</div>
@endif

<div class="maintenance-grid">
    <div class="glass card maintenance-card motion-card">
        <div class="maintenance-status {{ $isActive ? 'status-on' : 'status-off' }}">
            <i data-lucide="{{ $isActive ? 'alert-triangle' : 'check-circle-2' }}"></i>
            {{ $isActive ? 'Maintenance Aktif' : 'Maintenance Nonaktif' }}
        </div>

        <h2 style="margin-top:14px;">Mode Maintenance Sistem</h2>
        <p style="margin-top:8px;color:var(--text-light);line-height:1.7;">
            Saat mode ini aktif, role <strong>Mahasiswa</strong> dan <strong>Dosen</strong> tidak bisa login.
            Status role mereka juga otomatis berubah menjadi <strong>Deactivate</strong>.
        </p>

        @if(! $isActive)
        <form method="POST" action="{{ route('setting.maintenance.start') }}" style="margin-top:18px;">
            @csrf

            <div class="form-group">
                <label for="minutes">Durasi maintenance (menit)</label>
                <input
                    type="number"
                    id="minutes"
                    name="minutes"
                    class="form-control"
                    min="1"
                    max="1440"
                    value="{{ old('minutes', 30) }}"
                    required>
            </div>

            <button
                type="submit"
                class="btn-primary"
                data-tn-confirm
                data-tn-type="warning"
                data-tn-title="Aktifkan maintenance?"
                data-tn-message="Mahasiswa dan Dosen akan diblokir login sampai waktu maintenance selesai."
                data-tn-proceed-text="Aktifkan">
                <i data-lucide="shield-alert"></i>
                Aktifkan Maintenance
            </button>
        </form>
        @else
        <div class="glass card" style="padding:18px;margin-top:18px;">
            <div style="display:flex;gap:12px;justify-content:space-between;flex-wrap:wrap;">
                <div>
                    <strong>Dimulai pada</strong>
                    <p style="margin:4px 0 0;">
                        {{ $startedAt ? \Carbon\Carbon::createFromTimestamp($startedAt)->format('d M Y H:i:s') : '-' }}
                    </p>
                </div>
                <div>
                    <strong>Berakhir pada</strong>
                    <p style="margin:4px 0 0;">
                        {{ $endsAt ? \Carbon\Carbon::createFromTimestamp($endsAt)->format('d M Y H:i:s') : '-' }}
                    </p>
                </div>
            </div>

            <div id="maintenanceCountdown" data-end-at="{{ $endsAt ?? '' }}" class="countdown-grid">
                <div class="countdown-box">
                    <strong id="cdDays">00</strong>
                    <span>Hari</span>
                </div>
                <div class="countdown-box">
                    <strong id="cdHours">00</strong>
                    <span>Jam</span>
                </div>
                <div class="countdown-box">
                    <strong id="cdMinutes">00</strong>
                    <span>Menit</span>
                </div>
                <div class="countdown-box">
                    <strong id="cdSeconds">00</strong>
                    <span>Detik</span>
                </div>
            </div>

            <div style="margin-top:16px;">
                <form method="POST" action="{{ route('setting.maintenance.stop') }}">
                    @csrf

                    <button
                        type="submit"
                        class="btn-secondary"
                        data-tn-confirm
                        data-tn-type="danger"
                        data-tn-title="Matikan maintenance?"
                        data-tn-message="Role Mahasiswa dan Dosen akan diaktifkan kembali."
                        data-tn-proceed-text="Matikan">
                        <i data-lucide="power"></i>
                        Matikan Maintenance
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <div class="glass card maintenance-card">
        <h3>Efek Maintenance</h3>

        <div class="role-state-list">
            <div class="role-state-row">
                <span><i data-lucide="graduation-cap"></i> Mahasiswa</span>
                <span class="pill {{ $isActive ? 'pill-danger' : 'pill-success' }}">
                    {{ $isActive ? 'Deactivate' : 'Activate' }}
                </span>
            </div>

            <div class="role-state-row">
                <span><i data-lucide="briefcase-business"></i> Dosen</span>
                <span class="pill {{ $isActive ? 'pill-danger' : 'pill-success' }}">
                    {{ $isActive ? 'Deactivate' : 'Activate' }}
                </span>
            </div>
        </div>

        <hr style="margin:18px 0;">

        <p style="margin:0;color:var(--text-light);line-height:1.7;">
            Saat timer habis, status akan dikembalikan ke <strong>Activate</strong>.
        </p>
    </div>
</div>

@if($isActive && $endsAt)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const box = document.getElementById('maintenanceCountdown');
        if (!box) return;

        const endAt = parseInt(box.dataset.endAt, 10) * 1000;

        const elDays = document.getElementById('cdDays');
        const elHours = document.getElementById('cdHours');
        const elMinutes = document.getElementById('cdMinutes');
        const elSeconds = document.getElementById('cdSeconds');

        const pad = (n) => String(n).padStart(2, '0');

        function render() {
            const diff = endAt - Date.now();

            if (diff <= 0) {
                elDays.textContent = '00';
                elHours.textContent = '00';
                elMinutes.textContent = '00';
                elSeconds.textContent = '00';

                setTimeout(() => location.reload(), 1000);
                return;
            }

            const totalSeconds = Math.floor(diff / 1000);
            const days = Math.floor(totalSeconds / 86400);
            const hours = Math.floor((totalSeconds % 86400) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            elDays.textContent = pad(days);
            elHours.textContent = pad(hours);
            elMinutes.textContent = pad(minutes);
            elSeconds.textContent = pad(seconds);
        }

        render();
        setInterval(render, 1000);
    });
</script>
@endif
@endsection