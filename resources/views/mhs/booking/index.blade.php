@extends('template_mhs.layout')

@section('title','Booking Penginstalan')

@section('css')
<style>
    .booking-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .booking-notice {
        position: relative;
        overflow: hidden;
        padding: 18px 20px 18px 58px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, .10);
        backdrop-filter: blur(18px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .14);
    }

    .booking-notice::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(180deg, rgba(99, 102, 241, .95), rgba(59, 130, 246, .95));
    }

    .booking-notice::after {
        content: "";
        position: absolute;
        right: -40px;
        top: -40px;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .06);
        filter: blur(2px);
        pointer-events: none;
    }

    .booking-notice-icon {
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

    .booking-notice-content {
        position: relative;
        z-index: 1;
    }

    .booking-card {
        margin-bottom: 0;
    }

    .booking-table-card {
        margin-top: 0;
    }

    .booking-table-card table tbody tr+tr {
        border-top: 1px solid rgba(255, 255, 255, .06);
    }

    .booking-table-card td,
    .booking-table-card th {
        padding-top: 18px;
        padding-bottom: 18px;
    }

    .ticket-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .ticket-actions form {
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
        min-width: 820px;
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

    .status-processing {
        background: rgba(59, 130, 246, .12);
        color: #3b82f6;
        border-color: rgba(59, 130, 246, .18);
    }

    .status-completed {
        background: rgba(34, 197, 94, .12);
        color: #22c55e;
        border-color: rgba(34, 197, 94, .18);
    }

    .status-failed {
        background: rgba(239, 68, 68, .12);
        color: #ef4444;
        border-color: rgba(239, 68, 68, .18);
    }

    .status-empty,
    .action-empty {
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

    .qr-ticket-card {
        margin-top: 18px;
        padding: 16px;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, .10);
        background: rgba(255, 255, 255, .04);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .10);
    }

    .qr-ticket-header {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-bottom: 14px;
    }

    .qr-ticket-subtitle {
        font-size: 13px;
        color: var(--text-light);
        opacity: .85;
    }

    .qr-ticket-image-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 14px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .92);
    }

    .qr-ticket-image {
        width: 180px;
        height: 180px;
        object-fit: contain;
        display: block;
    }

    .qr-ticket-footer {
        margin-top: 12px;
        text-align: center;
        font-size: 13px;
        color: var(--text-light);
        word-break: break-word;
    }
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1>Booking Penginstalan</h1>
        <p>Kelola jadwal instalasi software.</p>
    </div>

    @if($bookingClosedToday || $todayBookingExists)
    <button
        type="button"
        class="btn-primary"
        data-tn-blocked
        data-tn-only-cancel="true"
        data-tn-type="warning"
        data-tn-title="Booking tidak tersedia"
        data-tn-message="{{ $todayBookingExists ? 'Hari ini kamu sudah membuat 1 booking. Silakan booking lagi besok.' : 'Sesi telah berakhir, silahkan booking besok' }}">
        <i data-lucide="plus"></i>
        <span>Buat Booking</span>
    </button>
    @else
    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('mahasiswa.booking.create') }}">
        <i data-lucide="plus"></i>
        <span>Buat Booking</span>
    </button>
    @endif
</div>

@if(session('success'))
<div class="tn-alert tn-alert-success">
    {{ session('success') }}
</div>
@endif

<div class="booking-stack">

    @if($notice)
    <div
        id="bookingNotice"
        class="tn-alert tn-alert-{{ $notice['type'] }} booking-notice"
        data-expires-at="{{ $notice['expires_at'] ?? '' }}">

        <div class="booking-notice-icon">
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

        <div class="booking-notice-content">
            <strong style="display:block;margin-bottom:4px;">
                {{ $notice['title'] ?? 'Informasi' }}
            </strong>

            <p style="margin:0;">
                {{ $notice['message'] }}
            </p>
        </div>
    </div>
    @endif

    @if($activeTicket)
    <div class="glass card booking-card">
        <h3>Booking Aktif</h3>

        <div class="grid-2">
            <div>
                <strong>{{ $activeTicket->ticket_number }}</strong>
                <p>{{ $activeTicket->penginstalan?->software?->name }}</p>
            </div>

            <div>
                <span class="badge badge-info">
                    {{ strtoupper($activeTicket->status) }}
                </span>
            </div>
        </div>

        <hr>

        <p>
            Jadwal:
            {{ optional($activeTicket->scheduled_start)->format('d M Y H:i') }}
            -
            {{ optional($activeTicket->scheduled_end)->format('H:i') }}
        </p>

     

        <div class="ticket-actions">
            <button
                class="btn-secondary open-modal"
                data-url="{{ route('mahasiswa.booking.show',$activeTicket->id) }}">
                <i data-lucide="eye"></i>
            </button>

            @if($activeTicket->status === 'waiting')
            <form action="{{ route('mahasiswa.booking.destroy', $activeTicket->id) }}" method="POST">
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="btn-secondary"
                    data-tn-confirm
                    data-tn-type="danger"
                    data-tn-title="Cancel booking?"
                    data-tn-message="Booking akan dibatalkan dan antrian akan dihitung ulang."
                    data-tn-proceed-text="Cancel">
                    Cancel Booking
                </button>
            </form>
            
            @endif
        </div>
    </div>
    @endif

    <div class="glass table-card booking-table-card">
        <div style="padding: 18px 18px 0;">
            <h3 style="margin-bottom: 6px;">Riwayat Mingguan</h3>
            <p style="margin: 0; color: var(--text-light);">
                {{ $weekRangeText }}
            </p>
        </div>

        <div class="weekly-table-wrap">
            <table class="weekly-table">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Software</th>
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
                            {{ $ticket?->penginstalan?->software?->name ?? '-' }}
                        </td>

                        <td>
                            @if($status)
                            <span
                                class="status-icon status-{{ $status }}"
                                title="{{ ucfirst($status) }}">
                                @if($status === 'waiting')
                                <i data-lucide="clock-3"></i>
                                @elseif($status === 'processing')
                                <i data-lucide="loader-2"></i>
                                @elseif($status === 'completed')
                                <i data-lucide="check-circle-2"></i>
                                @elseif($status === 'failed')
                                <i data-lucide="x-circle"></i>
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
                            <div class="ticket-actions">
                                @if($ticket)
                                <button
                                    class="btn-secondary open-modal"
                                    data-url="{{ route('mahasiswa.booking.show',$ticket->id) }}">
                                    <i data-lucide="eye"></i>
                                </button>

                                @if(!in_array($ticket->status, ['completed','failed','cancelled']) && $ticket->status === 'waiting')


                                <form action="{{ route('mahasiswa.booking.destroy', $ticket->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn-secondary"
                                        data-tn-confirm
                                        data-tn-type="danger"
                                        data-tn-title="Cancel booking?"
                                        data-tn-message="Booking akan dibatalkan dan antrian akan dihitung ulang."
                                        data-tn-proceed-text="Cancel">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                                @elseif($ticket->status === 'processing')
                                <button
                                    type="button"
                                    class="btn-secondary"
                                    data-tn-blocked
                                    data-tn-only-cancel="true"
                                    data-tn-type="warning"
                                    data-tn-title="Tidak bisa diubah"
                                    data-tn-message="Ticket sedang diproses, jadi edit dan cancel dimatikan sementara">
                                    <i data-lucide="pencil"></i>
                                </button>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                Belum ada data booking pada minggu ini.
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
        const notice = document.getElementById('bookingNotice');
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