(function () {
    document.addEventListener("DOMContentLoaded", () => {
        const root = document.getElementById("tn-ai-root");
        if (!root) return;

        const storageKey = root.dataset.storage || "technote_ai_user";
        const endpoint = root.dataset.endpoint || "";
        const mode = root.dataset.mode || "floating";

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

        if (
            !floating ||
            !toggleBtn ||
            !chatPopup ||
            !closeBtn ||
            !chatForm ||
            !chatInput ||
            !chatMessages ||
            !sendBtn ||
            !endpoint
        ) {
            return;
        }

        let state = loadState();
        let busy = false;
        let activeRunId = 0;

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
            return { messages: [], draft: "", openedOnce: false };
        }

        function saveState() {
            state.draft = chatInput ? chatInput.value : state.draft;
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

        function scrollBottom() {
            requestAnimationFrame(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
        }

        function renderMessage(role, text) {
            const node = document.createElement("div");
            node.className =
                role === "user" ? "msg user enter" : "msg bot enter";
            node.textContent = text;
            chatMessages.appendChild(node);
            scrollBottom();
        }

        function renderAllMessages() {
            chatMessages.innerHTML = "";

            if (state.messages.length === 0) {
                renderMessage(
                    "bot",
                    "Halo — chat ini khusus untuk bantuan akun milikmu sendiri dan trusted website aktif. Ketik pertanyaan tentang profil, login, instalasi, atau data website tepercaya.",
                );
                return;
            }

            state.messages.forEach((item) =>
                renderMessage(item.role, item.text),
            );
        }

        function persistMessage(role, text) {
            state.messages.push({ role, text, at: Date.now() });
            saveState();
        }

        function setBusy(v) {
            busy = v;
            sendBtn.disabled = v;
            chatInput.disabled = v;
            sendBtn.style.opacity = v ? ".7" : "1";
        }

        function createTypingIndicator() {
            const wrapper = document.createElement("div");
            wrapper.className = "typing-wrapper";

            const t = document.createElement("div");
            t.className = "typing";
            t.setAttribute("aria-hidden", "true");

            const dots = document.createElement("span");
            dots.className = "dots";

            for (let i = 0; i < 3; i++) {
                const dot = document.createElement("span");
                dot.className = "dot";
                dots.appendChild(dot);
            }

            t.appendChild(dots);
            wrapper.appendChild(t);
            chatMessages.appendChild(wrapper);

            requestAnimationFrame(() => t.classList.add("show"));
            scrollBottom();
            return t;
        }

        function removeTypingIndicator(node) {
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

        function getPopupRectHiddenSafe() {
            chatPopup.style.visibility = "hidden";
            chatPopup.style.display = "block";
            const rect = chatPopup.getBoundingClientRect();
            chatPopup.style.display = "";
            chatPopup.style.visibility = "";
            return rect;
        }

        async function openChat() {
            floating.style.display = "";
            await new Promise((r) => requestAnimationFrame(r));

            const btnRect = floating.getBoundingClientRect();
            const popupRect = getPopupRectHiddenSafe();

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
                    popupRect.left - (stretchW - popupRect.width) / 2 + "px";
                island.style.top =
                    popupRect.top - (stretchH - popupRect.height) / 2 + "px";
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

            setTimeout(() => chatInput.focus(), 16);
        }

        async function closeChat() {
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
            chatInput.blur();
        }

        async function sendQuestion(question) {
            if (busy) return;
            const text = String(question || "").trim();
            if (!text) return;

            activeRunId += 1;
            const runId = activeRunId;

            renderMessage("user", text);
            persistMessage("user", text);

            chatInput.value = "";
            state.draft = "";
            saveState();

            setBusy(true);
            const typingNode = createTypingIndicator();

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

                await removeTypingIndicator(typingNode);

                const reply =
                    data?.reply || data?.message || "Tidak ada jawaban.";
                if (!res.ok) {
                    renderMessage("bot", reply);
                    persistMessage("bot", reply);
                    return;
                }

                renderMessage("bot", reply);
                persistMessage("bot", reply);
            } catch (err) {
                if (runId !== activeRunId) return;
                await removeTypingIndicator(typingNode);
                const fallback = "Terjadi kesalahan saat menghubungi AI.";
                renderMessage("bot", fallback);
                persistMessage("bot", fallback);
                console.error(err);
            } finally {
                if (runId === activeRunId) setBusy(false);
            }
        }

        function hydrateDraft() {
            if (state.draft) {
                chatInput.value = state.draft;
            }
        }

        toggleBtn.addEventListener("click", () => {
            const isOpen = chatPopup.classList.contains("ready");
            if (!isOpen) openChat();
            else closeChat();
        });

        closeBtn.addEventListener("click", closeChat);

        sendBtn.addEventListener("click", () => sendQuestion(chatInput.value));
        chatForm.addEventListener("submit", (e) => {
            e.preventDefault();
            sendQuestion(chatInput.value);
        });

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

        renderAllMessages();
        hydrateDraft();

        window.addEventListener("beforeunload", saveState);
    });
})();
