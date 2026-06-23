<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/card.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/popupconfirmation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/table.css') }}">

    <style>
        .table-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }


        .btn-icon {
            width: 38px;
            height: 38px;

            border-radius: 12px;
            border: none;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;

            transition: .25s ease;

            backdrop-filter: blur(10px);
        }


        .btn-icon svg {
            width: 18px;
            height: 18px;
        }



        /* detail */
        .btn-icon.secondary {
            background: rgba(255, 255, 255, .08);
            color: #fff;
        }



        /* selesai */
        .btn-icon.success {
            background: rgba(34, 197, 94, .18);
            color: #22c55e;
        }



        /* gagal */
        .btn-icon.danger {
            background: rgba(239, 68, 68, .18);
            color: #ef4444;
        }



        .btn-icon:hover:not(.disabled-action) {
            transform: translateY(-2px);
        }



        /* tiket sudah final */
        .disabled-action {

            opacity: .35;

            filter: grayscale(1);

            cursor: default !important;

            pointer-events: none;

        }
    </style>

    @yield('css')
</head>

<body class="admin-panel">

    <!-- Floating Background -->
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- Sidebar -->
    @include('template_admin.sidebar')


    <!-- Main -->
    <main class="main">

        <!-- Navbar -->
        @include('template_admin.nav')

        <!-- Dashboard -->
        <section class="dashboard">
            @yield('content')

        </section>

        <!-- Footer -->
        @include('template_admin.footer')


    </main>

    <div id="modalContainer"></div>

    <div class="tn-confirm-overlay" id="tnConfirmOverlay" aria-hidden="true">
        <div class="glass card tn-confirm-card" role="dialog" aria-modal="true" aria-labelledby="tnConfirmTitle">

            <div class="tn-confirm-header">
                <div class="tn-confirm-icon-wrap" id="tnConfirmIconWrap">
                    <i data-lucide="alert-triangle" id="tnConfirmIcon"></i>
                </div>

                <button type="button" class="icon-btn tn-confirm-close" id="tnConfirmClose">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="tn-confirm-body">
                <h2 class="tn-confirm-title" id="tnConfirmTitle">Confirmation</h2>
                <p class="tn-confirm-message" id="tnConfirmMessage">
                    Are you sure you want to continue?
                </p>
            </div>

            <div class="tn-confirm-actions">
                <button type="button" class="btn-secondary" id="tnConfirmCancel">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button type="button" class="btn-primary" id="tnConfirmProceed">
                    <i data-lucide="check"></i>
                    Continue
                </button>
            </div>

        </div>
    </div>

    @yield('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const notificationButton = document.getElementById('notificationButton');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const closeNotificationPanel = document.getElementById('closeNotificationPanel');
            const unreadDot = document.querySelector('.notification-dot');
            const notificationList = document.querySelector('.notification-list');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const markAllReadUrl = "{{ route('notifications.readAll') }}";

            if (notificationButton && notificationDropdown) {
                notificationButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('is-open');
                });
            }

            async function markAllAsRead() {
                try {
                    const response = await fetch(markAllReadUrl, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Gagal menandai notifikasi sebagai sudah dibaca.');
                    }

                    return true;
                } catch (error) {
                    console.error(error);
                    return false;
                }
            }

            if (closeNotificationPanel && notificationDropdown) {
                closeNotificationPanel.addEventListener('click', async (e) => {
                    e.stopPropagation();

                    const success = await markAllAsRead();

                    if (success) {
                        notificationDropdown.classList.remove('is-open');

                        window.location.reload();
                    }
                });
            }

            document.addEventListener('click', (e) => {
                if (!notificationDropdown || !notificationButton) return;

                const clickedInside =
                    notificationDropdown.contains(e.target) ||
                    notificationButton.contains(e.target);

                if (!clickedInside) {
                    notificationDropdown.classList.remove('is-open');
                }
            });
        });
    </script>

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
                            question: question
                        })
                    });

                    const data = await res.json();

                    loading.remove();

                    const meta = [
                        data.source ? `source: ${data.source}` : '',
                        data.action ? `action: ${data.action}` : '',
                        typeof data.confidence !== 'undefined' ? `confidence: ${data.confidence}` : '',
                        data.blocked ? 'blocked by anti-ai mode' : '',
                    ].filter(Boolean).join(' · ');

                    bubble('ai', data.reply ?? 'Tidak ada jawaban.', meta);
                } catch (err) {
                    loading.remove();
                    bubble('ai', 'Terjadi error saat menghubungi AI.');
                }
            });
        })();
    </script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

</body>

</html>