@extends('template_admin.layout')

@section('title', 'AI Log')

@section('content')
<div class="page-header">
    <div>
        <h1>AI Log</h1>
        <p>Monitor aktivitas chat, analisis, dan aksi AI admin.</p>
    </div>
</div>

<div class="stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Total Grup</p>
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
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">Grup Hari Ini</p>
                <h2 style="margin:0;color:#22c55e;font-size:28px;">{{ number_format($todayLogs) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(34,197,94,.14);color:#86efac;">
                <i data-lucide="activity"></i>
            </div>
        </div>
    </div>

    <div class="glass" style="padding:18px 20px;border-radius:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <p style="margin:0 0 8px;color:#94a3b8;font-size:13px;">User Terlibat</p>
                <h2 style="margin:0;color:#f97316;font-size:28px;">{{ number_format($uniqueUsers) }}</h2>
            </div>
            <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(249,115,22,.14);color:#fdba74;">
                <i data-lucide="users"></i>
            </div>
        </div>
    </div>
</div>

<div class="glass table-card motion-card">
    <div class="table-toolbar">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" id="AiLogSearch" placeholder="Search log...">
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Source</th>
                <th>Jumlah</th>
                <th>Waktu</th>
                <th width="120">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($logs as $log)
            <tr class="log-row">
                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="glass" style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="user"></i>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:2px;">
                            <strong>{{ $log['user_name'] ?? 'System' }}</strong>
                            <small style="color:#94a3b8;">
                                {{ $log['user_username'] ?? '-' }}
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    <strong>{{ $log['source'] ?? '-' }}</strong>
                </td>

                <td>
                    <span class="tn-badge tn-badge-success">{{ $log['count'] ?? 1 }} data</span>
                </td>

                <td>
                    {{ $log['first_at'] ? \Carbon\Carbon::parse($log['first_at'])->format('d M Y H:i') : '-' }}
                    <br>
                    <small style="color:#94a3b8;">
                        s/d {{ $log['last_at'] ? \Carbon\Carbon::parse($log['last_at'])->format('d M Y H:i') : '-' }}
                    </small>
                </td>

                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-group='@json($log)'>
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:40px;">
                    No AI log available.
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

@section('js')
<script>
    (function() {
        const search = document.getElementById('AiLogSearch');
        const rows = document.querySelectorAll('.log-row');

        search?.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
            });
        });

        document.querySelectorAll('.open-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                const group = JSON.parse(btn.dataset.group || '{}');
                const items = group.items || [];

                const detailRows = items.map((item, index) => `
                    <div style="padding:12px 14px;border-radius:14px;background:rgba(2,6,23,.55);border:1px solid rgba(255,255,255,.06);margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
                            <strong style="color:#f8fafc;">Data #${index + 1}</strong>
                            <small style="color:#94a3b8;">${item.created_at ?? '-'}</small>
                        </div>
                        <div style="color:#93c5fd;margin-bottom:6px;"><strong>Question:</strong> ${escapeHtml(item.question ?? '-')}</div>
                        <div style="color:#e2e8f0;margin-bottom:6px;"><strong>Reply:</strong> ${escapeHtml(item.reply ?? '-')}</div>
                        <div style="color:#94a3b8;">
                            <strong>Confidence:</strong> ${item.confidence ?? '-'}
                        </div>
                    </div>
                `).join('');

                const modalContainer = document.getElementById('modalContainer');
                modalContainer.innerHTML = `
                    <div class="tn-modal-overlay">
                        <div class="glass card tn-modal-card">
                            <div class="tn-modal-header">
                                <div class="tn-modal-header-left">
                                    <h2 class="tn-modal-title">AI Log Detail</h2>
                                    <p class="tn-modal-subtitle">Ringkasan log berdekatan yang digabung</p>
                                </div>

                                <button type="button" class="icon-btn close-modal">
                                    <i data-lucide="x"></i>
                                </button>
                            </div>

                            <div class="glass tn-modal-info-box">
                                <div class="tn-modal-info-row">
                                    <div class="glass tn-modal-icon-box">
                                        <i data-lucide="bot"></i>
                                    </div>

                                    <div>
                                        <h3>${escapeHtml(group.user_name || 'System')}</h3>
                                        <p class="tn-modal-subtitle">${escapeHtml(group.action || '-')} · ${escapeHtml(group.source || '-')}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="tn-modal-grid">
                                <div class="tn-modal-group">
                                    <label>User</label>
                                    <div class="tn-modal-control tn-modal-readonly">
                                        ${escapeHtml(group.user_name || 'System')}
                                    </div>
                                </div>

                                <div class="tn-modal-group">
                                    <label>Username</label>
                                    <div class="tn-modal-control tn-modal-readonly">
                                        ${escapeHtml(group.user_username || '-')}
                                    </div>
                                </div>

                                <div class="tn-modal-group">
                                    <label>Action</label>
                                    <div class="tn-modal-control tn-modal-readonly">
                                        ${escapeHtml(group.action || '-')}
                                    </div>
                                </div>

                                <div class="tn-modal-group">
                                    <label>Source</label>
                                    <div class="tn-modal-control tn-modal-readonly">
                                        ${escapeHtml(group.source || '-')}
                                    </div>
                                </div>

                                <div class="tn-modal-group">
                                    <label>Jumlah Data</label>
                                    <div class="tn-modal-control tn-modal-readonly">
                                        ${group.count || 1} data
                                    </div>
                                </div>

                                <div class="tn-modal-group">
                                    <label>Waktu Awal</label>
                                    <div class="tn-modal-control tn-modal-readonly">
                                        ${escapeHtml(group.first_at || '-')}
                                    </div>
                                </div>

                                <div class="tn-modal-group">
                                    <label>Waktu Akhir</label>
                                    <div class="tn-modal-control tn-modal-readonly">
                                        ${escapeHtml(group.last_at || '-')}
                                    </div>
                                </div>

                                <div class="tn-modal-group tn-modal-full">
                                    <label>Detail Grup</label>
                                    <div style="margin-top:10px;">
                                        ${detailRows || '<div style="color:#94a3b8;">Tidak ada detail.</div>'}
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
                `;

                bindCloseModal();
                if (window.lucide) lucide.createIcons();
            });
        });

        function bindCloseModal() {
            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('modalContainer').innerHTML = '';
                });
            });

            document.querySelector('.tn-modal-overlay')?.addEventListener('click', (e) => {
                if (e.target.classList.contains('tn-modal-overlay')) {
                    document.getElementById('modalContainer').innerHTML = '';
                }
            });
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    })();
</script>
@endsection