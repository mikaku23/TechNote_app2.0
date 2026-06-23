@extends('template_admin.layout')

@section('title', 'TechNote AI Admin')

@section('css')
<style>
  

    .ai-page {
        height: calc(100vh - 24px);
        padding: 24px;
        overflow: hidden;
        color: var(--ai-text);
        background:
            radial-gradient(circle at top, rgba(76, 29, 149, .18), transparent 34%),
            radial-gradient(circle at left, rgba(59, 130, 246, .10), transparent 30%),
            linear-gradient(180deg, var(--ai-bg) 0%, var(--ai-bg-2) 100%);
    }

    .glass {
        background: var(--ai-card);
        border: 1px solid var(--ai-border);
        box-shadow: 0 18px 60px rgba(0, 0, 0, .30);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
    }

    .ai-shell {
        width: min(1180px, 100%);
        margin: 0 auto;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-height: 0;
    }

    .ai-hero {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        padding: 18px 20px;
        border-radius: 26px;
        flex-shrink: 0;
    }

    .ai-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #c4b5fd;
        background: rgba(168, 85, 247, .10);
        border: 1px solid rgba(168, 85, 247, .18);
    }

    .ai-title {
        margin: 10px 0 8px;
        font-size: clamp(28px, 4vw, 44px);
        line-height: 1.05;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .ai-subtitle {
        margin: 0;
        max-width: 760px;
        color: var(--ai-muted);
        font-size: 14px;
        line-height: 1.7;
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
        white-space: nowrap;
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

    .ai-layout {
        flex: 1;
        min-height: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 18px;
    }

    .ai-chat-panel {
        min-height: 0;
        border-radius: 26px;
        display: flex;
        flex-direction: column;
        padding: 18px;
    }

    .ai-chat-top {
        padding: 4px 4px 14px;
        border-bottom: 1px solid var(--ai-line);
        margin-bottom: 14px;
        flex-shrink: 0;
    }

    .ai-chat-top h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 650;
        letter-spacing: -.01em;
    }

    .ai-chat-top p {
        margin: 8px 0 0;
        color: var(--ai-muted);
        font-size: 13px;
        line-height: 1.65;
    }

    .ai-chat-box {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 6px 4px 14px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        scroll-behavior: smooth;
    }

    .ai-chat-box::-webkit-scrollbar {
        width: 8px;
    }

    .ai-chat-box::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .12);
        border-radius: 999px;
    }

    .bubble {
        max-width: 92%;
        border-radius: 22px;
        padding: 15px 16px;
        border: 1px solid var(--ai-border);
        word-break: break-word;
    }

    .bubble.system {
        align-self: center;
        max-width: 100%;
        background: rgba(255, 255, 255, .03);
    }

    .bubble.user {
        align-self: flex-end;
        background: rgba(255, 255, 255, .05);
    }

    .bubble.ai {
        align-self: flex-start;
        background: rgba(34, 197, 94, .08);
        border-color: rgba(34, 197, 94, .14);
    }

    .bubble.ai.thinking {
        background: rgba(59, 130, 246, .08);
        border-color: rgba(96, 165, 250, .16);
    }

    .bubble-label {
        font-size: 11px;
        color: rgba(148, 163, 184, .9);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-weight: 700;
    }

    .bubble-text {
        white-space: pre-line;
        color: #e5e7eb;
        line-height: 1.7;
        font-size: 14px;
    }

    .bubble-meta {
        margin-top: 10px;
        font-size: 12px;
        color: rgba(148, 163, 184, .82);
    }

    .ai-form {
        flex-shrink: 0;
        margin-top: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ai-input-shell {
        border-radius: 26px;
        border: 1px solid var(--ai-border);
        background: rgba(10, 10, 14, .72);
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
        line-height: 1.6;
        padding: 4px 2px 10px;
    }

    .ai-textarea::placeholder {
        color: rgba(148, 163, 184, .72);
    }

    .ai-send-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-top: 10px;
        border-top: 1px solid rgba(255, 255, 255, .06);
    }

    .ai-hint {
        font-size: 12px;
        color: rgba(148, 163, 184, .72);
    }

    .btn-send {
        border: none;
        cursor: pointer;
        border-radius: 999px;
        padding: 11px 18px;
        background: linear-gradient(135deg, #e5e7eb, #ffffff);
        color: #050816;
        font-weight: 800;
        box-shadow: 0 10px 30px rgba(255, 255, 255, .08);
        transition: transform .18s ease, opacity .18s ease;
        white-space: nowrap;
    }

    .btn-send:hover {
        transform: translateY(-1px);
        opacity: .97;
    }

    .btn-send:disabled {
        cursor: not-allowed;
    }

    .ai-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .ai-chip {
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(255, 255, 255, .08);
        color: rgba(226, 232, 240, .86);
        font-size: 12px;
        cursor: pointer;
        transition: all .18s ease;
    }

    .ai-chip:hover {
        background: rgba(255, 255, 255, .08);
        color: #fff;
        transform: translateY(-1px);
    }

    .bubble-thinking {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .process-steps {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 2px;
    }

    .process-step {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .06);
        opacity: .72;
        transition: all .18s ease;
    }

    .process-step.active {
        opacity: 1;
        background: rgba(59, 130, 246, .09);
        border-color: rgba(96, 165, 250, .18);
    }

    .process-step.done {
        opacity: .92;
        background: rgba(34, 197, 94, .08);
        border-color: rgba(34, 197, 94, .14);
    }

    .step-dot {
        width: 24px;
        height: 24px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 11px;
        font-weight: 800;
        color: rgba(226, 232, 240, .92);
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .process-step.active .step-dot {
        background: rgba(59, 130, 246, .18);
        border-color: rgba(96, 165, 250, .18);
        color: #93c5fd;
    }

    .process-step.done .step-dot {
        background: rgba(34, 197, 94, .16);
        border-color: rgba(34, 197, 94, .18);
        color: #86efac;
    }

    .process-copy strong {
        display: block;
        font-size: 13px;
        margin-bottom: 3px;
    }

    .process-copy p {
        margin: 0;
        font-size: 12px;
        color: rgba(226, 232, 240, .68);
        line-height: 1.5;
    }

    .thinking-status {
        margin-top: 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, .07);
        background: rgba(255, 255, 255, .03);
    }

    .thinking-status strong {
        font-size: 13px;
    }

    .thinking-status span {
        font-size: 12px;
        color: rgba(226, 232, 240, .70);
    }

    .pulse {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #93c5fd;
        box-shadow: 0 0 0 0 rgba(147, 197, 253, .5);
        animation: pulse 1.4s infinite;
        flex-shrink: 0;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(147, 197, 253, .45);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(147, 197, 253, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(147, 197, 253, 0);
        }
    }

    .is-hidden {
        display: none !important;
    }

    .processing-finished {
        color: #bbf7d0 !important;
    }

    @media (max-width: 1100px) {
        .ai-page {
            height: auto;
            overflow: auto;
        }

        .ai-shell {
            height: auto;
        }

        .ai-layout {
            grid-template-columns: 1fr;
        }

        .ai-chat-panel {
            min-height: 72vh;
        }
    }

    @media (max-width: 768px) {
        .ai-page {
            padding: 14px;
        }

        .ai-hero {
            padding: 16px;
            border-radius: 22px;
            flex-direction: column;
        }

        .ai-chat-panel {
            border-radius: 22px;
            padding: 14px;
            min-height: 72vh;
        }

        .bubble {
            max-width: 100%;
        }

        .ai-send-row {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-send {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="ai-page">
    <div class="ai-shell">


        <div class="ai-layout">
            <div class="ai-chat-panel glass">
                <div class="ai-chat-top">
                    <h2>Chat</h2>
                    <p>Enter untuk kirim. Shift + Enter untuk baris baru. Area chat akan scroll sendiri tanpa menggeser halaman utama.</p>
                </div>

                <div id="chatBox" class="ai-chat-box">
                    <div class="bubble system">
                        <div class="bubble-label">Sistem</div>
                        <div class="bubble-text">
                            Tanyakan ticket, rekap, user, software, perbaikan, penginstalan, maintenance, login log, atau minta analisis bottleneck.
                        </div>
                    </div>

                    @if(session('ai_result'))
                    <div class="bubble ai">
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
                            placeholder="Ask zapa a question..."
                            class="ai-textarea"></textarea>

                        <div class="ai-send-row">
                            <div class="ai-hint">Enter untuk kirim, Shift + Enter untuk baris baru</div>
                            <button type="submit" class="btn-send" id="sendBtn">
                                <span id="sendBtnText">Send</span>
                            </button>
                        </div>
                    </div>

                    <div class="ai-actions">
                        <button type="button" class="ai-chip quick" data-q="ringkas tiket hari ini">Ringkas tiket hari ini</button>
                        <button type="button" class="ai-chip quick" data-q="analisis bottleneck penginstalan">Analisis bottleneck</button>
                        <button type="button" class="ai-chip quick" data-q="rekomendasikan prioritas ticket urgent">Prioritas urgent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    (function() {
        const form = document.getElementById('aiForm');
        const textarea = document.getElementById('aiQuestion');
        const chatBox = document.getElementById('chatBox');
        const token = form.querySelector('input[name="_token"]').value;
        const sendBtn = document.getElementById('sendBtn');
        const sendBtnText = document.getElementById('sendBtnText');

        let isSubmitting = false;
        let activeRunId = 0;
        let thinkingBubble = null;
        let loadingBubble = null;
        let processTimer = [];
        let processState = {
            1: null,
            2: null,
            3: null,
            4: null,
        };

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function scrollChatToBottom() {
            requestAnimationFrame(() => {
                chatBox.scrollTop = chatBox.scrollHeight;
            });
        }

        function addBubble(role, text, meta = '') {
            const wrap = document.createElement('div');
            wrap.className = 'bubble ' + (role === 'user' ? 'user' : 'ai');

            wrap.innerHTML = `
            <div class="bubble-label">${role === 'user' ? 'Admin' : 'AI'}</div>
            <div class="bubble-text">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
            ${meta ? `<div class="bubble-meta">${escapeHtml(meta)}</div>` : ''}
        `;

            chatBox.appendChild(wrap);
            scrollChatToBottom();
        }

        function createThinkingBubble() {
            const wrap = document.createElement('div');
            wrap.className = 'bubble ai thinking';
            wrap.id = 'thinkingBubble';

            wrap.innerHTML = `
            <div class="bubble-label">AI</div>
            <div class="bubble-thinking">
                <div class="bubble-text">Sedang berpikir...</div>

                <div class="process-steps">
                    <div class="process-step" data-step="1">
                        <span class="step-dot">1</span>
                        <div class="process-copy">
                            <strong>Parse intent</strong>
                            <p>Mendeteksi maksud pertanyaan admin.</p>
                        </div>
                    </div>

                    <div class="process-step" data-step="2">
                        <span class="step-dot">2</span>
                        <div class="process-copy">
                            <strong>Query database</strong>
                            <p>Mengambil data dari tabel internal sesuai konteks.</p>
                        </div>
                    </div>

                    <div class="process-step" data-step="3">
                        <span class="step-dot">3</span>
                        <div class="process-copy">
                            <strong>Generate response</strong>
                            <p>Menyusun jawaban ringkas dalam format aman.</p>
                        </div>
                    </div>

                    <div class="process-step" data-step="4">
                        <span class="step-dot">4</span>
                        <div class="process-copy">
                            <strong>Save log</strong>
                            <p>Menyimpan hasil ke ai_logs dan ai_action_logs.</p>
                        </div>
                    </div>
                </div>

                <div class="thinking-status" id="thinkingStatus">
                    <strong>Status</strong>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span id="thinkingStatusText">Menunggu proses...</span>
                        <span class="pulse"></span>
                    </div>
                </div>
            </div>
        `;

            chatBox.appendChild(wrap);
            thinkingBubble = wrap;
            processState = {
                1: wrap.querySelector('[data-step="1"]'),
                2: wrap.querySelector('[data-step="2"]'),
                3: wrap.querySelector('[data-step="3"]'),
                4: wrap.querySelector('[data-step="4"]'),
            };

            scrollChatToBottom();
        }

        function removeThinkingBubble() {
            if (thinkingBubble) {
                thinkingBubble.remove();
                thinkingBubble = null;
            }
        }

        function addLoadingBubble() {
            const loading = document.createElement('div');
            loading.className = 'bubble ai is-hidden';
            loading.id = 'aiLoadingBubble';
            loading.innerHTML = `
            <div class="bubble-label">AI</div>
            <div class="bubble-text">Sedang memproses...</div>
        `;
            chatBox.appendChild(loading);
            loadingBubble = loading;
        }

        function removeLoadingBubble() {
            if (loadingBubble) {
                loadingBubble.remove();
                loadingBubble = null;
            }
        }

        function setProcessState(step, mode) {
            const item = processState[step];
            if (!item) return;

            item.classList.remove('active', 'done');
            if (mode === 'active') item.classList.add('active');
            if (mode === 'done') item.classList.add('done');
        }

        function resetThinkingVisual() {
            if (!thinkingBubble) return;

            [1, 2, 3, 4].forEach((step) => {
                if (processState[step]) {
                    processState[step].classList.remove('active', 'done');
                }
            });

            const statusText = thinkingBubble.querySelector('#thinkingStatusText');
            const statusBox = thinkingBubble.querySelector('#thinkingStatus');
            if (statusText) statusText.textContent = 'Menunggu proses...';
            if (statusBox) statusBox.classList.remove('processing-finished');
        }

        function markFinishedThinking() {
            if (!thinkingBubble) return;

            const statusText = thinkingBubble.querySelector('#thinkingStatusText');
            const statusBox = thinkingBubble.querySelector('#thinkingStatus');
            if (statusText) statusText.textContent = 'Proses selesai';
            if (statusBox) statusBox.classList.add('processing-finished');
        }

        function setBusyState(busy) {
            isSubmitting = busy;
            sendBtn.disabled = busy;
            textarea.disabled = busy;
            sendBtn.style.opacity = busy ? '.7' : '1';
            sendBtnText.textContent = busy ? 'Processing...' : 'Send';
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
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

        function clearTimers() {
            processTimer.forEach(t => clearTimeout(t));
            processTimer = [];
        }

        function startThinkingTimeline(runId) {
            clearTimers();
            removeThinkingBubble();
            createThinkingBubble();
            resetThinkingVisual();
            scrollChatToBottom();

            processTimer.push(setTimeout(() => {
                if (runId !== activeRunId || !thinkingBubble) return;
                setProcessState(1, 'active');
                const statusText = thinkingBubble.querySelector('#thinkingStatusText');
                if (statusText) statusText.textContent = 'Mendeteksi maksud pertanyaan...';
            }, 250));

            processTimer.push(setTimeout(() => {
                if (runId !== activeRunId || !thinkingBubble) return;
                setProcessState(1, 'done');
                setProcessState(2, 'active');
                const statusText = thinkingBubble.querySelector('#thinkingStatusText');
                if (statusText) statusText.textContent = 'Mengambil data dari database...';
            }, 900));

            processTimer.push(setTimeout(() => {
                if (runId !== activeRunId || !thinkingBubble) return;
                setProcessState(2, 'done');
                setProcessState(3, 'active');
                const statusText = thinkingBubble.querySelector('#thinkingStatusText');
                if (statusText) statusText.textContent = 'Menyusun jawaban...';
            }, 1550));

            processTimer.push(setTimeout(() => {
                if (runId !== activeRunId || !thinkingBubble) return;
                setProcessState(3, 'done');
                setProcessState(4, 'active');
                const statusText = thinkingBubble.querySelector('#thinkingStatusText');
                if (statusText) statusText.textContent = 'Menyimpan log aktivitas...';
            }, 2200));
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (isSubmitting) return;

            const question = textarea.value.trim();
            if (!question) return;

            activeRunId += 1;
            const runId = activeRunId;

            addBubble('user', question);
            textarea.value = '';
            autoResize();

            setBusyState(true);
            addLoadingBubble();
            startThinkingTimeline(runId);

            try {
                const response = await fetch(form.action, {
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

                const data = await response.json();

                if (runId !== activeRunId) return;

                clearTimers();
                removeLoadingBubble();

                if (thinkingBubble) {
                    markFinishedThinking();
                    setProcessState(4, 'done');
                }

                await sleep(500);

                if (runId !== activeRunId) return;

                removeThinkingBubble();

                const meta = [
                    data.source ? `source: ${data.source}` : '',
                    data.action ? `action: ${data.action}` : '',
                    typeof data.confidence !== 'undefined' ? `confidence: ${data.confidence}` : ''
                ].filter(Boolean).join(' · ');

                addBubble('ai', data.reply || 'Tidak ada respons.', meta);
            } catch (err) {
                if (runId !== activeRunId) return;

                clearTimers();
                removeLoadingBubble();
                removeThinkingBubble();
                addBubble('ai', 'Gagal menghubungi server AI.');
            } finally {
                if (runId === activeRunId) {
                    setBusyState(false);
                }
            }
        });

        autoResize();
    })();
</script>
@endsection