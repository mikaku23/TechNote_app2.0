@extends('template_admin.layout')

@section('title', 'User Activity Log')

@section('content')
<div class="page-header">
    <div>
        <h1>User Activity Log</h1>
        <p>Monitor user update, delete, create, and other actions.</p>
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
                <i data-lucide="database"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Create</p>
                <h2 style="margin:0;color:#22c55e;font-size:28px;">{{ number_format($createCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(34,197,94,.14);color:#86efac;">
                <i data-lucide="plus-circle"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Update</p>
                <h2 style="margin:0;color:#facc15;font-size:28px;">{{ number_format($updateCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(250,204,21,.14);color:#fde68a;">
                <i data-lucide="pencil"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Delete</p>
                <h2 style="margin:0;color:#f97316;font-size:28px;">{{ number_format($deleteCount) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(249,115,22,.14);color:#fdba74;">
                <i data-lucide="trash-2"></i>
            </div>
        </div>
    </div>
</div>

<div class="glass table-card motion-card">
    <div class="table-toolbar">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input
                type="text"
                id="UserActivitySearch"
                placeholder="Search activity...">
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Module</th>
                <th>Activity</th>
                <th>Description</th>
                <th>Time</th>
                <th width="120">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($logs as $log)
            <tr class="activity-row">
                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="glass" style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="user"></i>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:2px;">
                            <strong>{{ $log->user->name ?? 'Unknown User' }}</strong>
                            <small style="color:#94a3b8;">{{ $log->user->username ?? '-' }}</small>
                        </div>
                    </div>
                </td>

                <td>
                    <strong>{{ $log->user->role->name ?? '-' }}</strong>
                </td>

                <td>
                    <span class="tn-badge tn-badge-secondary">{{ $log->module }}</span>
                </td>

                <td>
                    @php
                    $badgeClass = match($log->activity) {
                    'create' => 'tn-badge-success',
                    'update' => 'tn-badge-warning',
                    'delete' => 'tn-badge-danger',
                    default => 'tn-badge-secondary',
                    };
                    @endphp
                    <span class="tn-badge {{ $badgeClass }}">{{ ucfirst($log->activity) }}</span>
                </td>

                <td>
                    {{ \Illuminate\Support\Str::limit($log->description ?? '-', 50) }}
                </td>

                <td>
                    {{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}
                </td>

                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('user-activity.show', $log->id) }}">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;">
                    No activity log available.
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