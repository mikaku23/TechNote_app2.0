@extends('template_admin.layout')

@section('title', 'Notification Log')

@section('content')
<div class="page-header">
    <div>
        <h1>Notification Log</h1>
        <p>Monitor system, AI, and WhatsApp notifications in one place.</p>
    </div>
</div>

<div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Total Log</p>
                <h2 style="margin:0;color:#f8fafc;font-size:28px;">{{ number_format($totalLogs) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(37,99,235,.14);color:#93c5fd;">
                <i data-lucide="bell"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">System</p>
                <h2 style="margin:0;color:#22c55e;font-size:28px;">{{ number_format($systemCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(34,197,94,.14);color:#86efac;">
                <i data-lucide="settings-2"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">AI</p>
                <h2 style="margin:0;color:#facc15;font-size:28px;">{{ number_format($aiCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(250,204,21,.14);color:#fde68a;">
                <i data-lucide="bot"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">WhatsApp</p>
                <h2 style="margin:0;color:#f97316;font-size:28px;">{{ number_format($whatsappCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(249,115,22,.14);color:#fdba74;">
                <i data-lucide="message-circle"></i>
            </div>
        </div>
    </div>
</div>

<div class="glass table-card motion-card">
    <div class="table-toolbar" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" id="NotificationSearch" placeholder="Search notification...">
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('notifications.index') }}" class="btn-secondary">All</a>
            <a href="{{ route('notifications.index', ['type' => 'system']) }}" class="btn-secondary">System</a>
            <a href="{{ route('notifications.index', ['type' => 'ai']) }}" class="btn-secondary">AI</a>
            <a href="{{ route('notifications.index', ['type' => 'whatsapp']) }}" class="btn-secondary">WhatsApp</a>

            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary">Mark All Read</button>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Ticket</th>
                <th>User</th>
                <th>Title</th>
                <th>Message</th>
                <th>Status</th>
                <th>Time</th>
                <th width="120">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($logs as $log)
            @php
            $ticket = $log->ticket;
            $ticketStatus = $ticket?->status ?? '-';

            $typeClass = match($log->type) {
            'system' => 'tn-badge-success',
            'ai' => 'tn-badge-warning',
            'whatsapp' => 'tn-badge-primary',
            default => 'tn-badge-secondary',
            };

            $statusClass = match($ticketStatus) {
            'completed' => 'tn-badge-success',
            'failed' => 'tn-badge-danger',
            'cancelled' => 'tn-badge-secondary',
            'processing' => 'tn-badge-warning',
            'diagnosis' => 'tn-badge-info',
            'testing' => 'tn-badge-primary',
            default => 'tn-badge-secondary',
            };
            @endphp

            <tr class="{{ $log->is_read ? '' : 'activity-row-unread' }}">
                <td>
                    <span class="tn-badge {{ $typeClass }}">{{ ucfirst($log->type) }}</span>
                </td>

                <td>
                    <strong>{{ $ticket?->ticket_number ?? '-' }}</strong>
                </td>

                <td>
                    <div style="display:flex;flex-direction:column;gap:2px;">
                        <strong>{{ $ticket?->user?->name ?? '-' }}</strong>
                        <small style="color:#94a3b8;">{{ $ticket?->user?->username ?? '-' }}</small>
                    </div>
                </td>

                <td>{{ $log->title }}</td>

                <td>{{ \Illuminate\Support\Str::limit($log->message, 60) }}</td>

                <td>
                    <span class="tn-badge {{ $statusClass }}">
                        {{ ucfirst($ticketStatus) }}
                    </span>
                </td>

                <td>{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}</td>

                <td>
                    <div class="table-actions">
                        <button type="button" class="btn-secondary open-modal" data-url="{{ route('notifications.show', $log->id) }}">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:40px;">
                    No notification log available.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrapper">
    {{ $logs->links() }}
</div>

<div id="modalContainer"></div>
@endsection