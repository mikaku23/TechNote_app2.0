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
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1>Booking Penginstalan</h1>
        <p>Kelola jadwal instalasi software.</p>
    </div>

    @if($bookingClosedToday)
    <button
        type="button"
        class="btn-primary"
        data-tn-blocked
        data-tn-only-cancel="true"
        data-tn-type="warning"
        data-tn-title="Sesi telah berakhir"
        data-tn-message="Sesi telah berakhir, silahkan booking besok">

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
            <button
                class="btn-secondary open-modal"
                data-url="{{ route('mahasiswa.booking.edit',$activeTicket->id) }}">
                <i data-lucide="pencil"></i>
            </button>

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
            @else
            <button
                type="button"
                class="btn-secondary"
                data-tn-blocked
                data-tn-only-cancel="true"
                data-tn-type="warning"
                data-tn-title="Tidak bisa edit"
                data-tn-message="Booking tidak bisa diedit atau dibatalkan karena sudah masuk proses pengerjaan">
                <i data-lucide="pencil"></i>
            </button>
            @endif
        </div>
    </div>
    @endif

    <div class="glass table-card booking-table-card">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Software</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($histories as $ticket)
                <tr>
                    <td>{{ $ticket->ticket_number }}</td>
                    <td>{{ $ticket->penginstalan?->software?->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($ticket->booking_date)->format('d M Y') }}</td>
                    <td>
                        <span class="badge badge-primary">
                            {{ ucfirst($ticket->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="ticket-actions">
                            <button
                                class="btn-secondary open-modal"
                                data-url="{{ route('mahasiswa.booking.show',$ticket->id) }}">
                                <i data-lucide="eye"></i>
                            </button>

                            @if(!in_array($ticket->status, ['completed','failed','cancelled']) && $ticket->status === 'waiting')
                            <button
                                class="btn-secondary open-modal"
                                data-url="{{ route('mahasiswa.booking.edit',$ticket->id) }}">
                                <i data-lucide="pencil"></i>
                            </button>

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
                                    Cancel Booking
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
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            Belum ada booking.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
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