(function () {
    document.addEventListener("DOMContentLoaded", () => {
        const root = document.getElementById("tn-ai-root");
        if (!root) return;

        const mode = root.dataset.mode || "floating";
        const storageKey = root.dataset.storage || "technote_ai_admin";
        const endpoint = root.dataset.endpoint || "";

        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";

        const floating = document.getElementById("floating-chat");
        const toggleBtn = document.getElementById("chat-toggle");
        const chatPopup = document.getElementById("chat-popup");
        const closeBtn = document.getElementById("chat-close");
        const chatForm = document.getElementById("chat-form");
        const chatInput = document.getElementById("chat-input");
        const chatMessages = document.getElementById("chat-messages");
        const sendBtn = document.getElementById("chat-send");

        const aiForm = document.getElementById("aiForm");
        const chatBox = document.getElementById("chatBox");
        const aiQuestion = document.getElementById("aiQuestion");
        const sendBtnText = document.getElementById("sendBtnText");

        const quickBtns = document.querySelectorAll(".quick");

        let state = loadState();
        let activeContainer = mode === "dashboard" ? chatBox : chatMessages;
        let activeInput = mode === "dashboard" ? aiQuestion : chatInput;
        let busy = false;
        let activeRunId = 0;

        let thinkingBubble = null;
        let thinkingStatusText = null;
        let thinkingSteps = {};
        let stageTimers = [];
        let stage2StartedAt = 0;
        let autoFollow = true;

        const MIN_STAGE_1 = 350;
        const MIN_STAGE_2 = 700;
        const MIN_STAGE_3 = 700;
        const MIN_STAGE_4 = 350;

        function loadState() {
            try {
                const parsed = JSON.parse(localStorage.getItem(storageKey));
                if (parsed && typeof parsed === "object") {
                    return {
                        messages: Array.isArray(parsed.messages)
                            ? parsed.messages
                            : [],
                        draft:
                            typeof parsed.draft === "string"
                                ? parsed.draft
                                : "",
                        openedOnce: !!parsed.openedOnce,
                    };
                }
            } catch (e) {}
            return {
                messages: [],
                draft: "",
                openedOnce: false,
            };
        }

        function saveState() {
            state.draft = activeInput ? activeInput.value : state.draft;
            localStorage.setItem(storageKey, JSON.stringify(state));
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function isNearBottom(el) {
            return el.scrollHeight - el.scrollTop - el.clientHeight < 80;
        }

        function scrollBottom() {
            if (!activeContainer || !autoFollow) return;
            requestAnimationFrame(() => {
                activeContainer.scrollTop = activeContainer.scrollHeight;
            });
        }

        function renderMessage(target, role, text, meta = "") {
            if (!target) return;

            const node = document.createElement("div");

            if (mode === "dashboard") {
                node.className = role === "user" ? "bubble user" : "bubble ai";
                node.innerHTML = `
                    <div class="bubble-label">${role === "user" ? "Admin" : "AI"}</div>
                    <div class="bubble-text">${escapeHtml(text).replace(/\n/g, "<br>")}</div>
                    ${meta ? `<div class="bubble-meta">${escapeHtml(meta)}</div>` : ""}
                `;
            } else {
                node.className =
                    role === "user" ? "msg user enter" : "msg bot enter";
                node.textContent = text;
            }

            target.appendChild(node);
            scrollBottom();
        }

        function renderAllMessages() {
            if (!activeContainer) return;

            const initialSystemText =
                mode === "dashboard"
                    ? "Tanyakan ticket, rekap, user, software, perbaikan, penginstalan, maintenance, login log, atau minta analisis bottleneck."
                    : "Halo — chat ini khusus untuk bantuan aplikasi layanan teknisi. Ketik pertanyaan tentang penginstalan, perbaikan, rekap, atau contact.";

            activeContainer.innerHTML = "";

            if (state.messages.length === 0) {
                if (mode === "dashboard") {
                    const sys = document.createElement("div");
                    sys.className = "bubble system";
                    sys.innerHTML = `
                        <div class="bubble-label">Sistem</div>
                        <div class="bubble-text">${escapeHtml(initialSystemText).replace(/\n/g, "<br>")}</div>
                    `;
                    activeContainer.appendChild(sys);
                } else {
                    renderMessage(activeContainer, "bot", initialSystemText);
                }
                scrollBottom();
                return;
            }

            state.messages.forEach((item) => {
                renderMessage(
                    activeContainer,
                    item.role,
                    item.text,
                    item.meta || "",
                );
            });

            scrollBottom();
        }

        function persistMessage(role, text, meta = "") {
            state.messages.push({
                role,
                text,
                meta,
                at: Date.now(),
            });
            saveState();
        }

        function setBusy(v) {
            busy = v;

            if (mode === "dashboard") {
                if (sendBtn) sendBtn.disabled = v;
                if (aiQuestion) aiQuestion.disabled = v;
                if (sendBtnText)
                    sendBtnText.textContent = v ? "Processing..." : "Send";
                if (sendBtn) sendBtn.style.opacity = v ? ".7" : "1";
            } else {
                if (sendBtn) sendBtn.disabled = v;
            }
        }

        function autoResizeTextarea() {
            if (!aiQuestion) return;
            aiQuestion.style.height = "auto";
            aiQuestion.style.height =
                Math.min(aiQuestion.scrollHeight, 220) + "px";
        }

        function buildThinkingBubble() {
            if (!chatBox) return;

            const el = document.createElement("div");
            el.className = "bubble ai thinking";
            el.innerHTML = `
                <div class="bubble-label">AI</div>
                <div class="bubble-thinking">
                    <div class="bubble-text">Sedang memproses...</div>

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

                    <div class="thinking-status">
                        <strong>Status</strong>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span id="thinkingStatusText">Menunggu proses...</span>
                            <span class="pulse"></span>
                        </div>
                    </div>
                </div>
            `;

            chatBox.appendChild(el);
            thinkingBubble = el;
            thinkingStatusText = el.querySelector("#thinkingStatusText");
            thinkingSteps = {
                1: el.querySelector('[data-step="1"]'),
                2: el.querySelector('[data-step="2"]'),
                3: el.querySelector('[data-step="3"]'),
                4: el.querySelector('[data-step="4"]'),
            };

            scrollBottom();
        }

        function setStep(step, modeState) {
            const item = thinkingSteps[step];
            if (!item) return;

            item.classList.remove("active", "done");
            if (modeState === "active") item.classList.add("active");
            if (modeState === "done") item.classList.add("done");

            scrollBottom();
        }

        function clearStageTimers() {
            stageTimers.forEach((t) => clearTimeout(t));
            stageTimers = [];
        }

        function removeThinkingBubble() {
            if (thinkingBubble) {
                thinkingBubble.remove();
                thinkingBubble = null;
                thinkingStatusText = null;
                thinkingSteps = {};
                scrollBottom();
            }
        }

        function startProgressTimeline(runId) {
            clearStageTimers();
            removeThinkingBubble();
            buildThinkingBubble();

            stageTimers.push(
                setTimeout(() => {
                    if (runId !== activeRunId || !thinkingBubble) return;
                    setStep(1, "active");
                    if (thinkingStatusText) {
                        thinkingStatusText.textContent = "Mendeteksi intent...";
                        scrollBottom();
                    }
                }, 120),
            );

            stageTimers.push(
                setTimeout(() => {
                    if (runId !== activeRunId || !thinkingBubble) return;
                    setStep(1, "done");
                    setStep(2, "active");
                    stage2StartedAt = performance.now();
                    if (thinkingStatusText) {
                        thinkingStatusText.textContent =
                            "Mengambil data dari database...";
                        scrollBottom();
                    }
                }, MIN_STAGE_1),
            );
        }

        function finishProgressTimeline(runId) {
            return new Promise((resolve) => {
                if (runId !== activeRunId || !thinkingBubble) {
                    resolve();
                    return;
                }

                const finishStage2 = () => {
                    if (runId !== activeRunId || !thinkingBubble) {
                        resolve();
                        return;
                    }

                    setStep(2, "done");
                    setStep(3, "active");
                    if (thinkingStatusText) {
                        thinkingStatusText.textContent = "Menyusun jawaban...";
                        scrollBottom();
                    }

                    stageTimers.push(
                        setTimeout(() => {
                            if (runId !== activeRunId || !thinkingBubble) {
                                resolve();
                                return;
                            }

                            setStep(3, "done");
                            setStep(4, "active");
                            if (thinkingStatusText) {
                                thinkingStatusText.textContent =
                                    "Menyimpan log...";
                                scrollBottom();
                            }

                            stageTimers.push(
                                setTimeout(() => {
                                    if (
                                        runId !== activeRunId ||
                                        !thinkingBubble
                                    ) {
                                        resolve();
                                        return;
                                    }

                                    setStep(4, "done");
                                    if (thinkingStatusText) {
                                        thinkingStatusText.textContent =
                                            "Selesai";
                                        scrollBottom();
                                    }
                                    resolve();
                                }, MIN_STAGE_4),
                            );
                        }, MIN_STAGE_3),
                    );
                };

                const waitStage2 = Math.max(
                    0,
                    MIN_STAGE_2 - (performance.now() - stage2StartedAt),
                );
                stageTimers.push(setTimeout(finishStage2, waitStage2));
            });
        }

        function createFloatingTypingIndicator() {
            if (!chatMessages) return null;

            const wrapper = document.createElement("div");
            wrapper.className = "typing-wrapper";

            const typing = document.createElement("div");
            typing.className = "typing";
            typing.setAttribute("aria-hidden", "true");

            const dots = document.createElement("span");
            dots.className = "dots";

            for (let i = 0; i < 3; i++) {
                const dot = document.createElement("span");
                dot.className = "dot";
                dots.appendChild(dot);
            }

            typing.appendChild(dots);
            wrapper.appendChild(typing);
            chatMessages.appendChild(wrapper);

            requestAnimationFrame(() => typing.classList.add("show"));
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return typing;
        }

        function removeFloatingTypingIndicator(node) {
            return new Promise((resolve) => {
                if (!node) return resolve();

                node.classList.add("fade-out");
                setTimeout(() => {
                    const parent = node.parentElement;
                    if (parent) parent.remove();
                    resolve();
                }, 280);
            });
        }

        async function sendQuestion(question) {
            if (busy || !question) return;

            const text = question.trim();
            if (!text) return;

            activeRunId += 1;
            const runId = activeRunId;

            renderMessage(activeContainer, "user", text);
            persistMessage("user", text);

            if (activeInput) {
                activeInput.value = "";
                state.draft = "";
                saveState();
            }

            setBusy(true);

            let typingNode = null;

            if (mode === "dashboard") {
                startProgressTimeline(runId);
            } else {
                typingNode = createFloatingTypingIndicator();
            }

            try {
                const res = await fetch(endpoint, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                        "Content-Type":
                            "application/x-www-form-urlencoded; charset=UTF-8",
                    },
                    body: new URLSearchParams({ question: text }),
                });

                let data = null;
                try {
                    data = await res.json();
                } catch (e) {
                    data = null;
                }

                if (runId !== activeRunId) return;

                if (mode === "dashboard") {
                    await finishProgressTimeline(runId);
                    if (runId !== activeRunId) return;
                    removeThinkingBubble();
                } else {
                    await removeFloatingTypingIndicator(typingNode);
                }

                const reply = data?.reply || "Tidak ada jawaban.";
                const meta = [
                    data?.source ? `source: ${data.source}` : "",
                    data?.action ? `action: ${data.action}` : "",
                    typeof data?.confidence !== "undefined"
                        ? `confidence: ${data.confidence}`
                        : "",
                    data?.blocked ? "blocked by anti-ai mode" : "",
                ]
                    .filter(Boolean)
                    .join(" · ");

                renderMessage(activeContainer, "ai", reply, meta);
                persistMessage("bot", reply, meta);
            } catch (err) {
                if (runId !== activeRunId) return;

                if (mode === "dashboard") {
                    clearStageTimers();
                    removeThinkingBubble();
                } else {
                    await removeFloatingTypingIndicator(typingNode);
                }

                const fallback = "Terjadi kesalahan saat menghubungi AI.";
                renderMessage(activeContainer, "ai", fallback);
                persistMessage("bot", fallback);
                console.error(err);
            } finally {
                if (runId === activeRunId) {
                    setBusy(false);
                    clearStageTimers();
                }
            }
        }

        function awaitTransitionEnd(el, timeout = 700) {
            return new Promise((resolve) => {
                let done = false;

                const onEnd = (e) => {
                    if (e.target !== el) return;
                    if (done) return;
                    done = true;
                    el.removeEventListener("transitionend", onEnd);
                    resolve(true);
                };

                el.addEventListener("transitionend", onEnd);

                setTimeout(() => {
                    if (done) return;
                    done = true;
                    el.removeEventListener("transitionend", onEnd);
                    resolve(false);
                }, timeout);
            });
        }

        function awaitAnimationEnd(el, timeout = 500) {
            return new Promise((resolve) => {
                let done = false;

                const onEnd = (e) => {
                    if (e.target !== el) return;
                    if (done) return;
                    done = true;
                    el.removeEventListener("animationend", onEnd);
                    resolve(true);
                };

                el.addEventListener("animationend", onEnd);

                setTimeout(() => {
                    if (done) return;
                    done = true;
                    el.removeEventListener("animationend", onEnd);
                    resolve(false);
                }, timeout);
            });
        }

        function getTargetRect() {
            if (mode === "dashboard") {
                const panel = document.querySelector(".ai-chat-panel");
                if (panel) return panel.getBoundingClientRect();
                return root.getBoundingClientRect();
            }

            const popup = document.getElementById("chat-popup");
            if (popup) return popup.getBoundingClientRect();
            return null;
        }

        function getSourceRect() {
            if (mode === "dashboard") {
                const panel = document.querySelector(".ai-chat-panel");
                if (panel) return panel.getBoundingClientRect();
                return root.getBoundingClientRect();
            }

            const btn = document.getElementById("floating-chat");
            if (btn) return btn.getBoundingClientRect();
            return null;
        }

        async function playEnterTransitionFromSession() {
            const raw = sessionStorage.getItem("tn_ai_transition_rect");
            if (!raw) return;

            let data = null;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                sessionStorage.removeItem("tn_ai_transition_rect");
                return;
            }

            const targetRect = getTargetRect();
            if (!targetRect) {
                sessionStorage.removeItem("tn_ai_transition_rect");
                return;
            }

            const island = document.createElement("div");
            island.className = "island-transition";
            island.style.left = data.left + "px";
            island.style.top = data.top + "px";
            island.style.width = data.width + "px";
            island.style.height = data.height + "px";
            island.style.borderRadius = data.borderRadius || "999px";
            island.style.opacity = "1";
            document.body.appendChild(island);

            if (mode === "dashboard") {
                root.style.visibility = "hidden";
            } else {
                const popup = document.getElementById("chat-popup");
                if (popup) popup.style.visibility = "hidden";
                if (floating) floating.classList.add("sucked");
            }

            requestAnimationFrame(() => {
                island.style.transition = [
                    "left .34s cubic-bezier(.2,1,.3,1)",
                    "top .34s cubic-bezier(.2,1,.3,1)",
                    "width .34s cubic-bezier(.2,1,.3,1)",
                    "height .34s cubic-bezier(.2,1,.3,1)",
                    "border-radius .24s cubic-bezier(.2,.9,.2,1)",
                    "transform .34s cubic-bezier(.2,1,.3,1)",
                    "box-shadow .34s ease",
                ].join(", ");

                island.style.left = targetRect.left + "px";
                island.style.top = targetRect.top + "px";
                island.style.width = targetRect.width + "px";
                island.style.height = targetRect.height + "px";

                if (mode === "dashboard") {
                    island.style.borderRadius = "26px";
                    island.style.boxShadow = "0 40px 110px rgba(2,6,23,0.48)";
                } else {
                    island.style.borderRadius =
                        Math.max(targetRect.width, targetRect.height) + "px";
                    island.style.boxShadow = "0 16px 40px rgba(2,6,23,0.26)";
                }
            });

            await awaitTransitionEnd(island, 420);

            island.remove();
            sessionStorage.removeItem("tn_ai_transition_rect");

            if (mode === "dashboard") {
                root.style.visibility = "";
            } else {
                if (floating) {
                    floating.classList.remove("sucked");
                    floating.style.display = "";
                }

                const popup = document.getElementById("chat-popup");
                if (popup) popup.style.visibility = "";
            }
        }

        function storeCurrentRectBeforeNavigate() {
            const rect = getSourceRect();
            if (!rect) return;

            const sourceEl =
                mode === "dashboard"
                    ? document.querySelector(".ai-chat-panel") || root
                    : document.getElementById("floating-chat") || root;

            sessionStorage.setItem(
                "tn_ai_transition_rect",
                JSON.stringify({
                    left: rect.left,
                    top: rect.top,
                    width: rect.width,
                    height: rect.height,
                    borderRadius:
                        getComputedStyle(sourceEl).borderRadius || "999px",
                }),
            );
        }

        function setupNavTransitionHooks() {
            document.addEventListener("click", (e) => {
                const link = e.target.closest("a[href]");
                if (!link) return;

                const href = link.getAttribute("href") || "";
                if (
                    href === "#" ||
                    href.startsWith("javascript:") ||
                    href.startsWith("mailto:") ||
                    href.startsWith("tel:")
                ) {
                    return;
                }

                const url = new URL(link.href, window.location.origin);
                if (url.origin !== window.location.origin) return;

                const isLogout = /logout/i.test(url.pathname);
                const isInternalNav = url.pathname !== window.location.pathname;
                if (!isInternalNav) return;

                if (isLogout) {
                    localStorage.removeItem(storageKey);
                    sessionStorage.removeItem("tn_ai_transition_rect");
                    return;
                }

                if (!url.pathname.startsWith("/admin/")) return;

                storeCurrentRectBeforeNavigate();

                if (mode === "floating" && floating) {
                    floating.classList.add("sucked");
                }

                if (mode === "dashboard") {
                    const panel = document.querySelector(".ai-chat-panel");
                    if (panel) panel.style.transform = "scale(.985)";
                }

                saveState();
            });
        }

        function setupFloatingHandlers() {
            if (mode !== "floating") return;

            if (toggleBtn && chatPopup) {
                toggleBtn.addEventListener("click", () => {
                    const isOpen = chatPopup.classList.contains("ready");
                    if (!isOpen) openChat();
                    else closeChat();
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener("click", closeChat);
            }

            if (sendBtn) {
                sendBtn.addEventListener("click", () => {
                    sendQuestion(chatInput?.value || "");
                });
            }

            if (chatForm) {
                chatForm.addEventListener("submit", (e) => {
                    e.preventDefault();
                    sendQuestion(chatInput?.value || "");
                });
            }

            if (chatInput) {
                chatInput.addEventListener("keydown", (e) => {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        sendQuestion(chatInput.value);
                    }
                });

                chatInput.addEventListener("input", () => {
                    state.draft = chatInput.value;
                    saveState();
                });
            }
        }

        function setupDashboardHandlers() {
            if (mode !== "dashboard") return;

            if (aiQuestion) {
                aiQuestion.addEventListener("input", () => {
                    autoResizeTextarea();
                    state.draft = aiQuestion.value;
                    saveState();
                });

                aiQuestion.addEventListener("keydown", (e) => {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        aiForm?.requestSubmit();
                    }
                });
            }

            quickBtns.forEach((btn) => {
                btn.addEventListener("click", () => {
                    if (!aiQuestion) return;
                    aiQuestion.value = btn.dataset.q || "";
                    autoResizeTextarea();
                    aiQuestion.focus();
                    state.draft = aiQuestion.value;
                    saveState();
                });
            });

            if (aiForm) {
                aiForm.addEventListener("submit", async (e) => {
                    e.preventDefault();
                    if (busy) return;

                    const question = aiQuestion?.value || "";
                    await sendQuestion(question);
                });
            }

            if (chatBox) {
                chatBox.addEventListener("scroll", () => {
                    autoFollow =
                        chatBox.scrollHeight -
                            chatBox.scrollTop -
                            chatBox.clientHeight <
                        80;
                });
            }
        }

        function openChat() {
            if (!floating || !chatPopup) return;

            floating.style.display = "";
            requestAnimationFrame(async () => {
                const btnRect = floating.getBoundingClientRect();

                const island = document.createElement("div");
                island.className = "island-transition suction";
                island.style.left = btnRect.left + "px";
                island.style.top = btnRect.top + "px";
                island.style.width = btnRect.width + "px";
                island.style.height = btnRect.height + "px";
                island.style.borderRadius =
                    Math.max(btnRect.width, btnRect.height) + "px";
                island.style.opacity = "1";
                document.body.appendChild(island);

                floating.classList.add("sucked");

                chatPopup.style.visibility = "hidden";
                chatPopup.style.display = "block";
                const popupRect = chatPopup.getBoundingClientRect();
                chatPopup.style.display = "";
                chatPopup.style.visibility = "";

                const stretchW = popupRect.width * 1.12;
                const stretchH = popupRect.height * 0.92;

                island.style.transition = [
                    "left .32s cubic-bezier(.12,1,.25,1)",
                    "top .32s cubic-bezier(.12,1,.25,1)",
                    "width .32s cubic-bezier(.12,1,.25,1)",
                    "height .32s cubic-bezier(.12,1,.25,1)",
                    "border-radius .22s cubic-bezier(.2,.9,.2,1)",
                    "transform .32s cubic-bezier(.12,1,.25,1)",
                    "box-shadow .32s ease",
                ].join(", ");

                requestAnimationFrame(() => {
                    island.style.left =
                        popupRect.left -
                        (stretchW - popupRect.width) / 2 +
                        "px";
                    island.style.top =
                        popupRect.top -
                        (stretchH - popupRect.height) / 2 +
                        "px";
                    island.style.width = stretchW + "px";
                    island.style.height = stretchH + "px";
                    island.style.borderRadius = "18px";
                    island.style.transform = "scaleX(.92) scaleY(1.08)";
                    island.style.boxShadow = "0 40px 110px rgba(2,6,23,0.48)";
                });

                await awaitTransitionEnd(island, 420);

                island.style.transition = "all .11s cubic-bezier(.3,.9,.2,1)";

                requestAnimationFrame(() => {
                    island.style.left = popupRect.left + "px";
                    island.style.top = popupRect.top + "px";
                    island.style.width = popupRect.width + "px";
                    island.style.height = popupRect.height + "px";
                    island.style.borderRadius = "14px";
                    island.style.transform = "scale(1)";
                    island.style.filter = "none";
                });

                await awaitTransitionEnd(island, 160);

                island.remove();
                floating.style.display = "none";

                chatPopup.classList.add("ready");
                chatPopup.setAttribute("aria-hidden", "false");

                chatPopup.classList.add("popup-settle");
                chatPopup.addEventListener(
                    "animationend",
                    () => chatPopup.classList.remove("popup-settle"),
                    { once: true },
                );

                setTimeout(() => chatInput?.focus(), 16);
            });
        }

        async function closeChat() {
            if (!floating || !chatPopup) return;

            floating.style.display = "";
            await new Promise((r) => requestAnimationFrame(r));

            const btnRect = floating.getBoundingClientRect();
            const popupRect = chatPopup.getBoundingClientRect();

            const island = document.createElement("div");
            island.className = "island-transition";
            island.style.left = popupRect.left + "px";
            island.style.top = popupRect.top + "px";
            island.style.width = popupRect.width + "px";
            island.style.height = popupRect.height + "px";
            island.style.borderRadius = "14px";
            island.style.boxShadow = "0 24px 60px rgba(2,6,23,0.34)";
            document.body.appendChild(island);

            chatPopup.classList.remove("ready");
            chatPopup.setAttribute("aria-hidden", "true");

            island.style.transition = [
                "left .36s cubic-bezier(.25,.9,.32,1)",
                "top .36s cubic-bezier(.25,.9,.32,1)",
                "width .36s cubic-bezier(.25,.9,.32,1)",
                "height .36s cubic-bezier(.25,.9,.32,1)",
                "border-radius .26s cubic-bezier(.3,.9,.2,1)",
                "box-shadow .36s cubic-bezier(.25,.9,.32,1)",
            ].join(", ");

            requestAnimationFrame(() => {
                island.style.left = btnRect.left + "px";
                island.style.top = btnRect.top + "px";
                island.style.width = btnRect.width + "px";
                island.style.height = btnRect.height + "px";
                island.style.borderRadius =
                    Math.max(btnRect.width, btnRect.height) + "px";
                island.style.boxShadow = "0 16px 40px rgba(2,6,23,0.26)";
            });

            await awaitTransitionEnd(island, 200);

            island.classList.add("island-collapse-settle");
            await awaitAnimationEnd(island, 50);

            island.remove();
            floating.classList.remove("sucked");
            floating.style.display = "";
            chatInput?.blur();
        }

        function attachGlobalExitReset() {
            document.addEventListener("click", (e) => {
                const logout = e.target.closest(
                    "a[href*='logout'], button[data-logout], form[data-logout]",
                );
                if (!logout) return;

                localStorage.removeItem(storageKey);
                sessionStorage.removeItem("tn_ai_transition_rect");
            });
        }

        function hydrateDraft() {
            if (!activeInput) return;
            if (state.draft) {
                activeInput.value = state.draft;
                if (mode === "dashboard") autoResizeTextarea();
            }
        }

        function init() {
            renderAllMessages();
            hydrateDraft();

            if (mode === "floating") {
                setupFloatingHandlers();
            } else {
                setupDashboardHandlers();
            }

            setupNavTransitionHooks();
            attachGlobalExitReset();

            if (mode === "dashboard") {
                requestAnimationFrame(() => {
                    activeContainer = chatBox;
                    scrollBottom();
                });
            }

            playEnterTransitionFromSession();
            window.addEventListener("beforeunload", saveState);
        }

        init();
    });
})();
