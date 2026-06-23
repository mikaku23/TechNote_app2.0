@extends('template_admin.layout')

@section('title', 'TechNote AI Admin')

@section('css')
<style>
    .ai-page {
        min-height: 100vh;
        padding: 24px;
        background:
            radial-gradient(circle at top left, rgba(56, 189, 248, .18), transparent 28%),
            radial-gradient(circle at top right, rgba(168, 85, 247, .14), transparent 26%),
            linear-gradient(180deg, #020617 0%, #0f172a 100%);
        color: #f8fafc;
    }

    .glass {
        background: rgba(15, 23, 42, .55);
        border: 1px solid rgba(255, 255, 255, .10);
        box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
        backdrop-filter: blur(26px);
        -webkit-backdrop-filter: blur(26px);
    }

    .ai-header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        border-radius: 28px;
        padding: 22px;
        margin-bottom: 18px;
    }

    .ai-kicker {
        display: inline-flex;
        width: fit-content;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #93c5fd;
        background: rgba(59, 130, 246, .12);
        border: 1px solid rgba(96, 165, 250, .18);
    }

    .ai-title-wrap h1 {
        margin: 10px 0 8px;
        font-size: clamp(28px, 4vw, 42px);
        line-height: 1.05;
        font-weight: 700;
    }

    .ai-title-wrap p {
        margin: 0;
        color: rgba(226, 232, 240, .72);
        max-width: 760px;
        font-size: 15px;
        line-height: 1.6;
    }

    .ai-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 14px;
    }

    .ai-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .ai-badge.success {
        background: rgba(34, 197, 94, .10);
        border-color: rgba(34, 197, 94, .18);
        color: #bbf7d0;
    }

    .ai-badge.warning {
        background: rgba(251, 191, 36, .10);
        border-color: rgba(251, 191, 36, .18);
        color: #fde68a;
    }

    .ai-badge.danger {
        background: rgba(239, 68, 68, .10);
        border-color: rgba(239, 68, 68, .18);
        color: #fecaca;
    }

    .ai-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn-glass {
        color: #f8fafc;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .12);
        padding: 12px 16px;
        border-radius: 999px;
        backdrop-filter: blur(18px);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all .18s ease;
    }

    .btn-glass:hover {
        background: rgba(255, 255, 255, .12);
        transform: translateY(-1px);
        color: #fff;
    }

    .ai-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, .9fr);
        gap: 18px;
        align-items: start;
    }

    .ai-chat-panel,
    .ai-process-panel {
        border-radius: 28px;
    }

    .ai-chat-panel {
        padding: 18px;
        min-height: 760px;
        display: flex;
        flex-direction: column;
    }

    .ai-chat-top {
        padding: 6px 4px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
        margin-bottom: 14px;
    }

    .ai-chat-top h2,
    .ai-process-panel h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 650;
    }

    .ai-chat-top p,
    .ai-process-panel p {
        margin: 8px 0 0;
        color: rgba(226, 232, 240, .68);
        font-size: 14px;
        line-height: 1.6;
    }

    .ai-chat-box {
        flex: 1;
        overflow-y: auto;
        padding: 6px 4px 18px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        scroll-behavior: smooth;
    }

    .ai-bubble {
        border-radius: 22px;
        padding: 16px 18px;
        border: 1px solid rgba(255, 255, 255, .08);
        max-width: 92%;
    }

    .ai-bubble.system {
        background: rgba(255, 255, 255, .05);
    }

    .ai-bubble.ai {
        align-self: flex-start;
        background: rgba(34, 197, 94, .08);
        border-color: rgba(34, 197, 94, .14);
    }

    .bubble-label {
        font-size: 12px;
        color: rgba(148, 163, 184, .85);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .bubble-text {
        white-space: pre-line;
        color: #e2e8f0;
        line-height: 1.65;
        font-size: 14px;
    }

    .bubble-meta {
        margin-top: 10px;
        font-size: 12px;
        color: rgba(148, 163, 184, .78);
    }

    .ai-form {
        margin-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ai-input-shell {
        border-radius: 26px;
        border: 1px solid rgba(255, 255, 255, .10);
        background: rgba(2, 6, 23, .55);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04);
        padding: 14px;
    }

    .ai-textarea {
        width: 100%;
        min-height: 64px;
        max-height: 220px;
        resize: none;
        border: none;
        outline: none;
        background: transparent;
        color: #f8fafc;
        font-size: 15px;
        line-height: 1.55;
        padding: 4px 2px 10px;
    }

    .ai-textarea::placeholder {
        color: rgba(148, 163, 184, .75);
    }

    .ai-send-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-top: 8px;
        border-top: 1px solid rgba(255, 255, 255, .07);
    }

    .ai-input-hint {
        font-size: 12px;
        color: rgba(148, 163, 184, .72);
    }

    .btn-send {
        border: none;
        cursor: pointer;
        border-radius: 999px;
        padding: 12px 18px;
        background: linear-gradient(135deg, #e2e8f0, #ffffff);
        color: #020617;
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(255, 255, 255, .08);
        transition: all .18s ease;
    }

    .btn-send:hover {
        transform: translateY(-1px);
        opacity: .96;
    }

    .ai-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .ai-chip {
        padding: 11px 15px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .10);
        color: rgba(226, 232, 240, .85);
        font-size: 13px;
        cursor: pointer;
        transition: all .18s ease;
    }

    .ai-chip:hover {
        background: rgba(255, 255, 255, .08);
        color: #fff;
        transform: translateY(-1px);
    }

    .ai-process-panel {
        padding: 18px;
    }

    .process-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 14px;
    }

    .process-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 14px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .process-step {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .step-done {
        background: rgba(34, 197, 94, .16);
        color: #86efac;
    }

    .step-active {
        background: rgba(59, 130, 246, .16);
        color: #93c5fd;
    }

    .step-pending {
        background: rgba(255, 255, 255, .10);
        color: rgba(226, 232, 240, .85);
    }

    .process-item strong {
        display: block;
        font-size: 14px;
        margin-bottom: 3px;
    }

    .process-item p {
        margin: 0;
        font-size: 12px;
        color: rgba(226, 232, 240, .70);
        line-height: 1.55;
    }

    .ai-mini-list {
        margin-top: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 340px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .ai-mini-item {
        padding: 12px 14px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .ai-mini-item strong {
        display: block;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .ai-mini-item span,
    .ai-mini-item p,
    .ai-empty {
        color: rgba(226, 232, 240, .70);
        font-size: 12px;
        line-height: 1.55;
    }

    .section-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 14px;
        color: #93c5fd;
        text-decoration: none;
        font-size: 13px;
    }

    .section-link:hover {
        text-decoration: underline;
    }

    @media (max-width: 1100px) {
        .ai-grid {
            grid-template-columns: 1fr;
        }

        .ai-chat-panel {
            min-height: 680px;
        }
    }

    @media (max-width: 768px) {
        .ai-page {
            padding: 14px;
        }

        .ai-header {
            padding: 18px;
            border-radius: 22px;
            flex-direction: column;
        }

        .ai-chat-panel,
        .ai-process-panel {
            border-radius: 22px;
        }

        .ai-chat-panel {
            min-height: 620px;
            padding: 14px;
        }

        .ai-bubble {
            max-width: 100%;
        }

        .ai-send-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .btn-send {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="ai-page">
    <div class="ai-header glass">
        <div>
            <span class="ai-kicker">TechNote AI Admin</span>
            <div class="ai-title-wrap">
                <h1>TechNote AI Admin</h1>
                <p>AI agent untuk membaca, menganalisis, dan mengelola data admin. Halaman ini hanya untuk chat, sedangkan log, tasks, dan rekomendasi dipisah ke halaman lain.</p>
            </div>

            <div class="ai-badges">
                <span class="ai-badge {{ filter_var($antiMode, FILTER_VALIDATE_BOOL) ? 'danger' : 'success' }}">
                    Anti AI: {{ filter_var($antiMode, FILTER_VALIDATE_BOOL) ? 'ON' : 'OFF' }}
                </span>
                <span class="ai-badge {{ filter_var($permission, FILTER_VALIDATE_BOOL) ? 'success' : 'warning' }}">
                    Permission AI Admin: {{ filter_var($permission, FILTER_VALIDATE_BOOL) ? 'ON' : 'OFF' }}
                </span>
            </div>
        </div>

        <div class="ai-links">
            <a href="{{ route('setting.sistem.index') }}" class="btn-glass">
                <i data-lucide="toggle-right"></i> System Settings
            </a>
            <a href="{{ route('admin.ai.log') }}" class="btn-glass">
                <i data-lucide="file-text"></i> AI Log
            </a>
            <a href="{{ route('admin.ai.tasks') }}" class="btn-glass">
                <i data-lucide="list-checks"></i> AI Tasks
            </a>
            <a href="{{ route('admin.ai.rekom') }}" class="btn-glass">
                <i data-lucide="sparkles"></i> AI Rekom
            </a>
        </div>
    </div>

    <div class="ai-grid">
        <div class="ai-chat-panel glass">
            <div class="ai-chat-top">
                <h2>AI Chat</h2>
                <p>Tanyakan ticket, rekap, user, software, perbaikan, penginstalan, maintenance, login log, atau minta analisis bottleneck.</p>
            </div>

            <div id="chatBox" class="ai-chat-box">
                <div class="ai-bubble system">
                    <div class="bubble-label">Sistem</div>
                    <div class="bubble-text">
                        Tanyakan ticket, rekap, user, software, perbaikan, penginstalan, maintenance, login log, atau minta analisis bottleneck.
                    </div>
                </div>

                @if(session('ai_result'))
                <div class="ai-bubble ai">
                    <div class="bubble-label">AI</div>
                    <div class="bubble-text">{{ session('ai_result.reply') }}</div>
                    @if(!empty(session('ai_result.source')))
                    <div class="bubble-meta">
                        source: {{ session('ai_result.source') }}
                        @if(!empty(session('ai_result.action'))) · action: {{ session('ai_result.action') }} @endif
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <form id="aiForm" method="POST" action="{{ route('admin.ai.chat') }}" class="ai-form">
                @csrf

                <div class="ai-input-shell">
                    <textarea
                        name="question"
                        id="aiQuestion"
                        rows="1"
                        placeholder="Contoh: analisis ticket waiting yang paling banyak hari ini"
                        class="ai-textarea"></textarea>

                    <div class="ai-send-row">
                        <div class="ai-input-hint">
                            Enter untuk kirim, Shift + Enter untuk baris baru
                        </div>

                        <button type="submit" class="btn-send">
                            Kirim ke AI
                        </button>
                    </div>
                </div>

                <div class="ai-actions">
                    <button type="button" class="ai-chip quick" data-q="ringkas tiket hari ini">
                        Ringkas tiket hari ini
                    </button>
                    <button type="button" class="ai-chip quick" data-q="analisis bottleneck penginstalan">
                        Analisis bottleneck
                    </button>
                    <button type="button" class="ai-chip quick" data-q="rekomendasikan prioritas ticket urgent">
                        Prioritas urgent
                    </button>
                </div>
            </form>
        </div>

        <div class="ai-process-panel glass">
            <h2>Processing</h2>
            <p>Alur kerja chat AI admin yang terlihat di dashboard.</p>

            <div class="process-list">
                <div class="process-item">
                    <span class="process-step step-done">1</span>
                    <div>
                        <strong>Parse intent</strong>
                        <p>Mendeteksi maksud pertanyaan admin.</p>
                    </div>
                </div>

                <div class="process-item">
                    <span class="process-step step-active">2</span>
                    <div>
                        <strong>Query database</strong>
                        <p>Mengambil data dari tabel internal sesuai konteks.</p>
                    </div>
                </div>

                <div class="process-item">
                    <span class="process-step step-pending">3</span>
                    <div>
                        <strong>Generate response</strong>
                        <p>Menyusun jawaban ringkas dalam format aman.</p>
                    </div>
                </div>

                <div class="process-item">
                    <span class="process-step step-pending">4</span>
                    <div>
                        <strong>Save log</strong>
                        <p>Menyimpan hasil ke ai_logs dan ai_action_logs.</p>
                    </div>
                </div>
            </div>

            <div style="margin-top:18px;">
                <h2>Log terbaru</h2>
                <div class="ai-mini-list">
                    @forelse($recentLogs as $log)
                    <div class="ai-mini-item">
                        <strong>{{ $log->user_name ?? 'System' }} · {{ $log->action ?? '-' }}</strong>
                        <span>{{ $log->source ?? '-' }}</span>
                        <p>{{ \Illuminate\Support\Str::limit($log->question, 90) }}</p>
                    </div>
                    @empty
                    <div class="ai-empty">Belum ada log.</div>
                    @endforelse
                </div>

                <a href="{{ route('admin.ai.log') }}" class="section-link">Lihat semua log →</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    (function() {
        const form = document.getElementById('aiForm');
        const chatBox = document.getElementById('chatBox');
        const textarea = document.getElementById('aiQuestion');
        const token = form.querySelector('input[name="_token"]').value;

        function escapeHtml(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function bubble(role, text, meta = '') {
            const wrapper = document.createElement('div');
            wrapper.className = 'ai-bubble ' + (role === 'user' ? 'system' : 'ai');

            wrapper.innerHTML = `
            <div class="bubble-label">${role === 'user' ? 'Admin' : 'AI'}</div>
            <div class="bubble-text">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
            ${meta ? `<div class="bubble-meta">${escapeHtml(meta)}</div>` : ''}
        `;

            chatBox.appendChild(wrapper);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function autoResize() {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 220) + 'px';
        }

        document.querySelectorAll('.quick').forEach(btn => {
            btn.addEventListener('click', () => {
                textarea.value = btn.dataset.q || '';
                autoResize();
                textarea.focus();
            });
        });

        textarea.addEventListener('input', autoResize);

        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const question = textarea.value.trim();
            if (!question) return;

            bubble('user', question);
            textarea.value = '';
            autoResize();

            const loading = document.createElement('div');
            loading.className = 'ai-bubble ai';
            loading.innerHTML = `
            <div class="bubble-label">AI</div>
            <div class="bubble-text">Sedang berpikir...</div>
        `;
            chatBox.appendChild(loading);
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams({
                        question
                    })
                });

                const data = await res.json();
                loading.remove();

                const meta = [
                    data.source ? `source: ${data.source}` : '',
                    data.action ? `action: ${data.action}` : '',
                    typeof data.confidence !== 'undefined' ? `confidence: ${data.confidence}` : ''
                ].filter(Boolean).join(' · ');

                bubble('ai', data.reply || 'Tidak ada respons.', meta);
            } catch (err) {
                loading.remove();
                bubble('ai', 'Gagal menghubungi server AI.');
            }
        });

        autoResize();
    })();
</script>
@endsection