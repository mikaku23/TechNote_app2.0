@extends('template_dsn.layout')

@section('title','Data Perbaikan')

@section('css')
<style>
    .repair-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .repair-notice {
        position: relative;
        overflow: hidden;
        padding: 18px 20px 18px 58px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, .10);
        backdrop-filter: blur(18px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .14);
    }

    .repair-notice::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(180deg, rgba(99, 102, 241, .95), rgba(59, 130, 246, .95));
    }

    .repair-notice-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(255, 255, 255, .08);
    }

    .repair-notice-content {
        position: relative;
        z-index: 1;
    }

    .repair-table-card table tbody tr+tr {
        border-top: 1px solid rgba(255, 255, 255, .06);
    }

    .repair-table-card td,
    .repair-table-card th {
        padding-top: 18px;
        padding-bottom: 18px;
    }

    .repair-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .repair-actions form {
        margin: 0;
    }

    .empty-state {
        text-align: center;
        padding: 38px 18px;
        color: var(--text-light);
    }

    .weekly-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .weekly-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 860px;
    }

    .weekly-table th,
    .weekly-table td {
        padding: 16px 14px;
        vertical-align: middle;
    }

    .day-cell strong {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .status-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid transparent;
    }

    .status-waiting {
        background: rgba(245, 158, 11, .12);
        color: #f59e0b;
        border-color: rgba(245, 158, 11, .18);
    }

    .status-diagnosis {
        background: rgba(168, 85, 247, .12);
        color: #a855f7;
        border-color: rgba(168, 85, 247, .18);
    }

    .status-processing {
        background: rgba(59, 130, 246, .12);
        color: #3b82f6;
        border-color: rgba(59, 130, 246, .18);
    }

    .status-testing {
        background: rgba(14, 165, 233, .12);
        color: #0ea5e9;
        border-color: rgba(14, 165, 233, .18);
    }

    .status-completed {
        background: rgba(34, 197, 94, .12);
        color: #22c55e;
        border-color: rgba(34, 197, 94, .18);
    }

    .status-failed,
    .status-unrepairable {
        background: rgba(239, 68, 68, .12);
        color: #ef4444;
        border-color: rgba(239, 68, 68, .18);
    }

    .status-cancelled {
        background: rgba(148, 163, 184, .12);
        color: #94a3b8;
        border-color: rgba(148, 163, 184, .18);
    }

    .status-empty {
        color: var(--text-light);
        opacity: .7;
    }

    .qr-mini-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qr-mini {
        width: 64px;
        height: 64px;
        object-fit: contain;
        display: block;
        border-radius: 12px;
        background: #fff;
        padding: 4px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .10);
    }

    .qr-mini-empty {
        font-size: 13px;
        color: var(--text-light);
        opacity: .75;
    }

    .repair-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    @media (max-width: 900px) {
        .repair-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 560px) {
        .repair-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1>Data Perbaikan</h1>
        <p>Riwayat perbaikan milik Anda berdasarkan periode mingguan dalam bulan berjalan.</p>
    </div>
</div>

@if(session('success'))
<div class="tn-alert tn-alert-success">
    {{ session('success') }}
</div>
@endif

<div class="repair-stack">

    @if($notice)
    <div
        id="repairNotice"
        class="tn-alert tn-alert-{{ $notice['type'] }} repair-notice"
        data-expires-at="{{ $notice['expires_at'] ?? '' }}">

        <div class="repair-notice-icon">
            @if($notice['type'] === 'success')
            <i data-lucide="check-circle-2"></i>
            @elseif($notice['type'] === 'danger')
            <i data-lucide="alert-triangle"></i>
            @elseif($notice['type'] === 'warning')
            <i data-lucide="clock-3"></i>
            @else
            <i data-lucide="info"></i>
            @endif
        </div>

        <div class="repair-notice-content">
            <strong style="display:block;margin-bottom:4px;">
                {{ $notice['title'] ?? 'Informasi' }}
            </strong>

            <p style="margin:0;">
                {{ $notice['message'] }}
            </p>
        </div>
    </div>
    @endif

    <div class="repair-summary-grid">
        <div class="glass card">
            <h3>Total Perbaikan</h3>
            <p style="font-size:28px;margin:8px 0 0;">{{ $totalRepairs }}</p>
        </div>

        <div class="glass card">
            <h3>Aktif</h3>
            <p style="font-size:28px;margin:8px 0 0;">{{ $activeRepairs }}</p>
        </div>

        <div class="glass card">
            <h3>Selesai</h3>
            <p style="font-size:28px;margin:8px 0 0;">{{ $completedRepairs }}</p>
        </div>

        <div class="glass card">
            <h3>Gagal / Tidak Bisa Diperbaiki</h3>
            <p style="font-size:28px;margin:8px 0 0;">{{ $failedRepairs }}</p>
        </div>
    </div>

    @if($activeTicket)
    <div class="glass card">
        <h3>Perbaikan Aktif</h3>

        <div class="grid-2">
            <div>
                <strong>{{ $activeTicket->ticket_number }}</strong>
                <p>{{ $activeTicket->perbaikan?->item_name ?? '-' }}</p>
            </div>

            <div>
                <span class="badge badge-info">
                    {{ strtoupper($activeTicket->status) }}
                </span>
            </div>
        </div>

        <hr>

        <p>
            Lokasi:
            {{ $activeTicket->perbaikan?->item_location ?? '-' }}
        </p>

        <p>
            Estimasi selesai:
            {{ optional($activeTicket->estimated_finish)->format('d M Y H:i') ?? '-' }}
        </p>

        <div class="repair-actions">
            <button
                class="btn-secondary open-modal"
                data-url="{{ route('dosen.perbaikan.show', $activeTicket->id) }}">
                <i data-lucide="eye"></i>
            </button>
        </div>
    </div>
    @endif

    <div class="glass table-card repair-table-card">
        <div style="padding: 18px 18px 0;">
            <h3 style="margin-bottom: 6px;">Riwayat Periode</h3>
            <p style="margin: 0; color: var(--text-light);">
                {{ $periodRangeText }}
            </p>
        </div>

        <div class="weekly-table-wrap">
            <table class="weekly-table">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Barang</th>
                        <th>Kerusakan</th>
                        <th>Status</th>
                        <th>QR</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($weekDays as $row)
                    @php
                    $ticket = $row['ticket'];
                    $status = $ticket?->status;
                    @endphp

                    <tr>
                        <td>
                            <div class="day-cell">
                                <strong>{{ $row['day'] }}</strong>
                            </div>
                        </td>

                        <td>
                            {{ $ticket?->perbaikan?->item_name ?? '-' }}
                        </td>

                        <td>
                            {{ \Illuminate\Support\Str::limit($ticket?->perbaikan?->damage_description ?? '-', 45) }}
                        </td>

                        <td>
                            @if($status)
                            <span
                                class="status-icon status-{{ $status }}"
                                title="{{ ucfirst($status) }}">
                                @if($status === 'waiting')
                                <i data-lucide="clock-3"></i>
                                @elseif($status === 'diagnosis')
                                <i data-lucide="search"></i>
                                @elseif($status === 'processing')
                                <i data-lucide="loader-2"></i>
                                @elseif($status === 'testing')
                                <i data-lucide="flask-conical"></i>
                                @elseif($status === 'completed')
                                <i data-lucide="check-circle-2"></i>
                                @elseif(in_array($status, ['failed', 'unrepairable']))
                                <i data-lucide="x-circle"></i>
                                @elseif($status === 'cancelled')
                                <i data-lucide="ban"></i>
                                @else
                                <i data-lucide="help-circle"></i>
                                @endif
                            </span>
                            @else
                            <span class="status-empty">-</span>
                            @endif
                        </td>

                        <td>
                            @if($ticket?->qr_code)
                            <div class="qr-mini-wrap">
                                <img
                                    src="{{ asset('storage/' . $ticket->qr_code) }}"
                                    alt="QR {{ $ticket->ticket_number }}"
                                    class="qr-mini">
                            </div>
                            @else
                            <span class="qr-mini-empty">-</span>
                            @endif
                        </td>

                        <td>
                            <div class="repair-actions">
                                @if($ticket)
                                <button
                                    class="btn-secondary open-modal"
                                    data-url="{{ route('dosen.show', $ticket->id) }}">
                                    <i data-lucide="eye"></i>
                                </button>
                                @else
                                <span class="status-empty">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                Belum ada data perbaikan pada periode ini.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalContainer"></div>

@if($notice)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notice = document.getElementById('repairNotice');
        if (!notice) return;

        const expiresAt = notice.dataset.expiresAt ? parseInt(notice.dataset.expiresAt, 10) : null;
        if (!expiresAt) return;

        const delay = expiresAt * 1000 - Date.now();

        if (delay <= 0) {
            notice.remove();
            return;
        }

        setTimeout(() => {
            notice.remove();
        }, delay);
    });
</script>
@endif

@endsection