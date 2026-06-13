@extends('template_admin.layout')

@section('title', 'Login Log')

@section('content')
<div class="page-header">
    <div>
        <h1>Login Log</h1>
        <p>Monitor user login and logout activity.</p>
    </div>
</div>

<div class="stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Total Log</p>
                <h2 style="margin:0;color:#f8fafc;font-size:28px;">{{ number_format($totalLogs) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(37,99,235,.14);color:#93c5fd;">
                <i data-lucide="database"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Total Online</p>
                <h2 style="margin:0;color:#22c55e;font-size:28px;">{{ number_format($onlineCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(34,197,94,.14);color:#86efac;">
                <i data-lucide="wifi"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Total Offline</p>
                <h2 style="margin:0;color:#f97316;font-size:28px;">{{ number_format($offlineCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(249,115,22,.14);color:#fdba74;">
                <i data-lucide="power"></i>
            </div>
        </div>
    </div>
</div>

@if (session('success'))
<div class="tn-alert tn-alert-success">
    <strong>Success!</strong>
    <p>{{ session('success') }}</p>
</div>
@endif

@if ($errors->any())
<div class="tn-alert tn-alert-error">
    <strong>Oops! Please correct the following errors:</strong>
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="glass table-card motion-card">
    <div class="table-toolbar">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input
                type="text"
                id="LoginLogSearch"
                placeholder="Search log...">
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>IP Address</th>
                <th>Status</th>
                
                <th>Session</th>
                <th width="120">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($logs as $log)
            <tr class="log-row">
                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="user"></i>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:2px;">
                            <strong>{{ $log->user->name ?? 'Unknown User' }}</strong>
                            <small style="color:#94a3b8;">
                                {{ $log->user->username ?? '-' }}
                                @if($log->user?->nim)
                                | NIM: {{ $log->user->nim }}
                                @elseif($log->user?->nip)
                                | NIP: {{ $log->user->nip }}
                                @endif
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    <strong>{{ $log->user->role->name ?? '-' }}</strong>
                </td>

                <td>
                    <code style="background:rgba(2,6,23,.55);padding:6px 10px;border-radius:10px;color:#93c5fd;">
                        {{ $log->ip_address ?? '-' }}
                    </code>
                </td>

                <td>
                    @if($log->status === 'online')
                    <span class="tn-badge tn-badge-success">Online</span>
                    @else
                    <span class="tn-badge tn-badge-warning">Offline</span>
                    @endif
                </td>

                <td>
                    @if($log->login_at && $log->logout_at)
                    {{ $log->login_at->diffForHumans($log->logout_at, true) }}
                    @elseif($log->login_at && !$log->logout_at)
                    Still active
                    @else
                    -
                    @endif
                </td>

                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('login-log.show', $log->id) }}">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:40px;">
                    No login log available.
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