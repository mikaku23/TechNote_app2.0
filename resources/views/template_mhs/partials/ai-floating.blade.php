@php
$authUser = auth()->user();
$roleName = strtolower((string) data_get($authUser, 'role_name', ''));
if ($roleName === '') {
$roleName = strtolower((string) data_get($authUser, 'role.name', 'mahasiswa'));
}

if (!in_array($roleName, ['mahasiswa', 'dosen'], true)) {
return;
}

$chatRoute = $roleName === 'dosen' ? 'dosen.ai.chat' : 'mahasiswa.ai.chat';
@endphp

<div
    id="tn-ai-root"
    data-mode="floating"
    data-storage="technote_ai_user_{{ auth()->id() }}_{{ $roleName }}"
    data-endpoint="{{ route($chatRoute) }}">
    <div id="floating-chat" aria-hidden="false">
        <button id="chat-toggle" aria-label="Buka chat" title="Chatbot">
            <img src="{{ asset('assets/images/chatbot.png') }}" alt="chatbot" class="chat-logo">
        </button>
    </div>

    <div id="chat-popup" class="chat-popup" role="dialog" aria-label="Chatbot layanan akademik" aria-hidden="true">
        <div class="chat-header">
            <div class="chat-title">
                <img src="{{ asset('assets/images/chatbot.png') }}" alt="chatbot" class="chat-logo">
                <span>{{ $roleName === 'dosen' ? 'AI Dosen' : 'AI Mahasiswa' }}</span>
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