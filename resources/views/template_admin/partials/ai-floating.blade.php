<div
    id="tn-ai-root"
    data-mode="floating"
    data-storage="technote_ai_admin_{{ auth()->id() }}"
    data-endpoint="{{ route('admin.ai.chat') }}">
    <div id="floating-chat" aria-hidden="false">
        <button id="chat-toggle" aria-label="Buka chat" title="Chatbot">
            <img src="{{ asset('assets/images/chatbot.png') }}" alt="chatbot" class="chat-logo">
        </button>
    </div>

    <div id="chat-popup" class="chat-popup" role="dialog" aria-label="Chatbot layanan teknisi" aria-hidden="true">
        <div class="chat-header">
            <div class="chat-title">
                <img src="{{ asset('assets/images/chatbot.png') }}" alt="chatbot" class="chat-logo">
                <span>Chatbot</span>
            </div>
            <button id="chat-close" aria-label="Tutup chat">&times;</button>
        </div>

        <div id="chat-messages" class="chat-messages" aria-live="polite"></div>

        <form id="chat-form" class="chat-form" onsubmit="return false;">
            <input id="chat-input" type="text" placeholder="Tulis pesan..." autocomplete="off" />
            <button id="chat-send" type="button">Kirim</button>
        </form>
    </div>
</div>