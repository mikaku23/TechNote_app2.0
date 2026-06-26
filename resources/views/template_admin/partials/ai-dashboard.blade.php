<div
    id="tn-ai-root"
    data-mode="dashboard"
    data-storage="technote_ai_admin_{{ auth()->id() }}"
    data-endpoint="{{ route('admin.ai.chat') }}">
    <div class="ai-page">
        <div class="ai-shell">
            <div class="ai-layout">
                <div class="ai-chat-panel glass">
                    <div class="ai-chat-top">
                        <h2>Chat</h2>
                        <p>Enter untuk kirim. Shift + Enter untuk baris baru. Area chat akan scroll sendiri tanpa menggeser halaman utama.</p>
                    </div>

                    <div id="chatBox" class="ai-chat-box" aria-live="polite">
                        <div class="bubble system">
                            <div class="bubble-label">Sistem</div>
                            <div class="bubble-text">
                                Tanyakan ticket, rekap, user, software, perbaikan, penginstalan, maintenance, login log, atau minta analisis bottleneck.
                            </div>
                        </div>
                    </div>

                    <form id="aiForm" method="POST" action="{{ route('admin.ai.chat') }}" class="ai-form">
                        @csrf

                        <div class="ai-input-shell">
                            <textarea
                                name="question"
                                id="aiQuestion"
                                rows="1"
                                placeholder="Ask TechAI..."
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
</div>