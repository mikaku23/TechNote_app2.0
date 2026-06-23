<script>
    (function() {
        const form = document.getElementById('aiForm');
        const textarea = document.getElementById('aiQuestion');
        const chatBox = document.getElementById('chatBox');
        const token = form.querySelector('input[name="_token"]').value;
        const sendBtn = document.getElementById('sendBtn');
        const sendBtnText = document.getElementById('sendBtnText');

        const processItems = {
            1: document.querySelector('[data-step="1"]'),
            2: document.querySelector('[data-step="2"]'),
            3: document.querySelector('[data-step="3"]'),
            4: document.querySelector('[data-step="4"]'),
        };

        const thinkingText = document.getElementById('thinkingText');
        const thinkingPulse = document.getElementById('thinkingPulse');

        let isSubmitting = false;
        let activeRunId = 0;

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

        function addLoadingBubble() {
            const loading = document.createElement('div');
            loading.className = 'bubble ai';
            loading.id = 'aiLoadingBubble';
            loading.innerHTML = `
            <div class="bubble-label">AI</div>
            <div class="bubble-text">Sedang berpikir...</div>
        `;
            chatBox.appendChild(loading);
            scrollChatToBottom();
        }

        function removeLoadingBubble() {
            const loading = document.getElementById('aiLoadingBubble');
            if (loading) loading.remove();
        }

        function setProcessState(step, mode) {
            const item = processItems[step];
            if (!item) return;

            item.classList.remove('is-pending', 'is-active', 'is-done');

            if (mode === 'active') {
                item.classList.add('is-active');
            } else if (mode === 'done') {
                item.classList.add('is-done');
            } else {
                item.classList.add('is-pending');
            }
        }

        function resetProcess() {
            [1, 2, 3, 4].forEach((step) => {
                processItems[step].classList.remove('is-active', 'is-done');
                processItems[step].classList.add('is-pending');
            });

            thinkingText.textContent = 'Menunggu pertanyaan.';
            thinkingPulse.style.opacity = '1';
        }

        function setThinking(text) {
            thinkingText.textContent = text;
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

        async function runProcessingTimeline(runId) {
            resetProcess();

            setProcessState(1, 'active');
            setThinking('Mendeteksi maksud pertanyaan...');
            await sleep(500);
            if (runId !== activeRunId) return;

            setProcessState(1, 'done');
            setProcessState(2, 'active');
            setThinking('Mengambil data dari database...');
            await sleep(650);
            if (runId !== activeRunId) return;

            setProcessState(2, 'done');
            setProcessState(3, 'active');
            setThinking('Menyusun jawaban...');
            await sleep(650);
            if (runId !== activeRunId) return;

            setProcessState(3, 'done');
            setProcessState(4, 'active');
            setThinking('Menyimpan log aktivitas...');
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

            addLoadingBubble();
            setBusyState(true);

            try {
                const processingPromise = runProcessingTimeline(runId);

                const requestPromise = fetch(form.action, {
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

                const [_, response] = await Promise.all([processingPromise, requestPromise]);
                const data = await response.json();

                if (runId !== activeRunId) return;

                removeLoadingBubble();
                setProcessState(4, 'done');
                setThinking(data.blocked ?
                    'Aksi diblokir oleh mode sistem.' :
                    'Jawaban berhasil dibuat dan log tersimpan.'
                );

                const meta = [
                    data.source ? `source: ${data.source}` : '',
                    data.action ? `action: ${data.action}` : '',
                    typeof data.confidence !== 'undefined' ? `confidence: ${data.confidence}` : ''
                ].filter(Boolean).join(' · ');

                addBubble('ai', data.reply || 'Tidak ada respons.', meta);
                scrollChatToBottom();

                await sleep(900);
                if (runId !== activeRunId) return;

                resetProcess();
            } catch (err) {
                if (runId !== activeRunId) return;

                removeLoadingBubble();
                resetProcess();
                setThinking('Terjadi kesalahan saat memproses permintaan.');

                addBubble('ai', 'Gagal menghubungi server AI.');
            } finally {
                if (runId === activeRunId) {
                    setBusyState(false);
                }
            }
        });

        autoResize();
        resetProcess();
    })();
</script>