/* ==========================
   TECHNOTE 2.0
   APPLE INSPIRED JS
========================== */

document.addEventListener("DOMContentLoaded", () => {
    initializeIcons();
    initializeTheme();
    initializeSidebar();
    initializeProfileMenu();
    initializeNotifications();
    initializeCounters();
    initializeButtons();
    initializePageAnimation();
    initializeCards();
    initializeSearch();
    initializeSidebarDropdowns();
    initializeSoftwareSearch();
    initializeUserSearch();
    initializeRoleSearch();
    initializeTrustedWebsiteSearch();
    initializeModalLoader();
    initializeModalClose();
    initializeSidebarState();
    initializeGlobalConfirmModal();
    initializeSearchPenginstalan();
    initializeSearchTicketLog();
    initializeSearchTicket();
    initializeSearchPerbaikan();
    initializeSearchRekap();
    initializeSearchLog();
    initializeSearchUserLog();
});

/* ==========================
   LUCIDE ICONS
========================== */

function initializeIcons() {
    if (typeof lucide !== "undefined") {
        lucide.createIcons();
    }
}

/* ==========================
   THEME
========================== */

function initializeTheme() {
    const themeBtn = document.getElementById("themeToggle");
    const savedTheme = localStorage.getItem("technote-theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        updateThemeIcon();
    }

    if (!themeBtn) return;

    themeBtn.addEventListener("click", () => {
        document.body.classList.toggle("dark");

        const isDark = document.body.classList.contains("dark");

        localStorage.setItem("technote-theme", isDark ? "dark" : "light");

        updateThemeIcon();
    });
}

function updateThemeIcon() {
    const icon = document.querySelector("#themeToggle i");

    if (!icon) return;

    const isDark = document.body.classList.contains("dark");

    icon.setAttribute("data-lucide", isDark ? "sun" : "moon");

    lucide.createIcons();
}

/* ==========================
   SIDEBAR
========================== */

function initializeSidebar() {
    const button = document.getElementById("sidebarToggle");

    if (!button) return;

    button.addEventListener("click", () => {
        document.body.classList.toggle("sidebar-collapsed");
    });
}

/* ==========================
   PROFILE MENU
========================== */

function initializeProfileMenu() {
    const button = document.getElementById("profileMenuButton");
    const menu = document.getElementById("profileDropdown");

    if (!button || !menu) return;

    button.addEventListener("click", (e) => {
        e.stopPropagation();

        menu.classList.toggle("show");

        document
            .getElementById("notificationDropdown")
            ?.classList.remove("show");
    });

    document.addEventListener("click", () => {
        menu.classList.remove("show");
    });
}

/* ==========================
   NOTIFICATIONS
========================== */

function initializeNotifications() {
    const button = document.getElementById("notificationButton");
    const menu = document.getElementById("notificationDropdown");

    if (!button || !menu) return;

    button.addEventListener("click", (e) => {
        e.stopPropagation();

        menu.classList.toggle("show");

        document.getElementById("profileDropdown")?.classList.remove("show");
    });

    document.addEventListener("click", () => {
        menu.classList.remove("show");
    });
}

/* ==========================
   COUNTER ANIMATION
========================== */

function initializeCounters() {
    const counters = document.querySelectorAll(".stat-card h2");

    counters.forEach((counter) => {
        const target = parseInt(counter.textContent.replace(/,/g, ""), 10);

        if (Number.isNaN(target)) return;

        animateCounter(counter, target);
    });
}

function animateCounter(element, target) {
    let current = 0;
    const duration = 1200;
    const increment = target / 60;

    const timer = setInterval(() => {
        current += increment;

        if (current >= target) {
            current = target;
            clearInterval(timer);
        }

        element.textContent = Math.floor(current).toLocaleString();
    }, duration / 60);
}

/* ==========================
   BUTTON RIPPLE
========================== */

function initializeButtons() {
    const buttons = document.querySelectorAll(
        ".btn-primary, .btn-secondary, .icon-btn",
    );

    buttons.forEach((button) => {
        button.addEventListener("click", createRipple);
    });
}

function createRipple(event) {
    const button = event.currentTarget;
    const ripple = document.createElement("span");
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);

    const clientX =
        typeof event.clientX === "number"
            ? event.clientX
            : rect.left + rect.width / 2;
    const clientY =
        typeof event.clientY === "number"
            ? event.clientY
            : rect.top + rect.height / 2;

    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${clientX - rect.left - size / 2}px`;
    ripple.style.top = `${clientY - rect.top - size / 2}px`;
    ripple.classList.add("ripple");

    button.appendChild(ripple);

    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/* ==========================
   PAGE ANIMATION
========================== */

function initializePageAnimation() {
    // Jangan transform body, karena itu membuat fixed modal ikut "nempel" ke halaman.
    document.body.style.opacity = "0";

    requestAnimationFrame(() => {
        document.body.style.transition = "opacity .5s ease";
        document.body.style.opacity = "1";
    });
}

/* ==========================
   CARD MOTION
========================== */

const cardRafMap = new WeakMap();

function initializeCards() {
    if (document.body.classList.contains("admin-panel")) {
        return;
    }

    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    if (reduceMotion) return;

    const cards = document.querySelectorAll(".motion-card");

    cards.forEach((card) => {
        card.addEventListener("pointermove", handleCardMove, { passive: true });
        card.addEventListener("pointerleave", resetCard);
        card.addEventListener("pointerenter", () => {
            card.classList.add("liquid");
        });
    });
}

function handleCardMove(event) {
    const card = event.currentTarget;
    const rect = card.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    const prevFrame = cardRafMap.get(card);
    if (prevFrame) cancelAnimationFrame(prevFrame);

    const frame = requestAnimationFrame(() => {
        card.classList.add("liquid");
        card.style.setProperty("--mouse-x", `${x}px`);
        card.style.setProperty("--mouse-y", `${y}px`);

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        const rotateX = ((y - centerY) / centerY) * -3;
        const rotateY = ((x - centerX) / centerX) * 3;

        card.style.transform = `
            perspective(1200px)
            rotateX(${rotateX}deg)
            rotateY(${rotateY}deg)
            translate3d(0, -5px, 0)
        `;
    });

    cardRafMap.set(card, frame);
}

function resetCard(event) {
    const card = event.currentTarget;

    const prevFrame = cardRafMap.get(card);
    if (prevFrame) cancelAnimationFrame(prevFrame);
    cardRafMap.delete(card);

    card.classList.remove("liquid");
    card.style.transform = `
        perspective(1200px)
        rotateX(0deg)
        rotateY(0deg)
        translate3d(0, 0, 0)
    `;
}

/* ==========================
   SEARCH
========================== */

function initializeSearch() {
    const searchInput = document.querySelector(".search-box input");

    if (!searchInput) return;

    const rows = Array.from(document.querySelectorAll("tbody tr"));
    let searchFrame = 0;

    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(keyword) ? "" : "none";
            });
        });
    });
}

/* ==========================
   TOAST
========================== */

function showToast(message, type = "success") {
    const toast = document.createElement("div");

    toast.className = `toast ${type}`;
    toast.innerHTML = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add("show");
    }, 100);

    setTimeout(() => {
        toast.classList.remove("show");

        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

function clearSidebarActive() {
    document.querySelectorAll(".nav-item").forEach((item) => {
        item.classList.remove("active");
    });

    document.querySelectorAll(".nav-group").forEach((group) => {
        group.classList.remove("active");
    });

    document.querySelectorAll(".dropdown-item").forEach((item) => {
        item.classList.remove("active");
    });
}

function setActiveGroup(group) {
    document.querySelectorAll(".nav-group").forEach((g) => {
        g.classList.remove("active");
    });

    document.querySelectorAll(".nav-item").forEach((item) => {
        item.classList.remove("active");
    });

    group.classList.add("active");
}

function setActiveChild(item) {
    document.querySelectorAll(".dropdown-item").forEach((i) => {
        i.classList.remove("active");
    });

    item.classList.add("active");
}

/* ==========================
   SIDEBAR DROPDOWN / POPUP
========================== */

const sidebarPopupTimers = new Map();

function clearSidebarPopupTimer(group) {
    const timer = sidebarPopupTimers.get(group);

    if (timer) {
        clearTimeout(timer);
        sidebarPopupTimers.delete(group);
    }
}

function closeSidebarPopupGroup(group) {
    clearSidebarPopupTimer(group);

    group.classList.remove("popup-open");

    const button = group.querySelector(".nav-dropdown");
    if (button) {
        button.setAttribute("aria-expanded", "false");
    }
}

function closeAllSidebarDropdowns() {
    document.querySelectorAll(".nav-group").forEach((group) => {
        group.classList.remove("open");
        group.classList.remove("popup-open");
        clearSidebarPopupTimer(group);

        const button = group.querySelector(".nav-dropdown");
        if (button) {
            button.setAttribute("aria-expanded", "false");
        }
    });
}

function initializeSidebarDropdowns() {
    const sidebarNav = document.querySelector(".sidebar-nav");
    const main = document.querySelector(".main");

    if (!sidebarNav) return;

    const isCollapsedMode = () =>
        document.body.classList.contains("sidebar-collapsed");

    const openSidebarPopupGroup = (group) => {
        if (!isCollapsedMode()) return;

        closeAllSidebarDropdowns();
        group.classList.add("popup-open");

        const button = group.querySelector(".nav-dropdown");
        if (button) {
            button.setAttribute("aria-expanded", "true");
        }
    };

    const schedulePopupClose = (group, delay = 5000) => {
        clearSidebarPopupTimer(group);

        const timer = setTimeout(() => {
            closeSidebarPopupGroup(group);
        }, delay);

        sidebarPopupTimers.set(group, timer);
    };

    sidebarNav.addEventListener("click", (e) => {
        const dropdown = e.target.closest(".nav-dropdown");
        const item = e.target.closest(".dropdown-item");

        if (dropdown) {
            const group = dropdown.closest(".nav-group");

            if (!group) return;

            const isOpen = group.classList.contains("open");
            const collapsed = isCollapsedMode();

            e.preventDefault();

            if (collapsed) {
                const isPopupOpen = group.classList.contains("popup-open");

                closeAllSidebarDropdowns();

                if (!isPopupOpen) {
                    openSidebarPopupGroup(group);
                }

                dropdown.setAttribute("aria-expanded", String(!isPopupOpen));
                return;
            }

            document.querySelectorAll(".nav-group").forEach((g) => {
                if (g !== group) {
                    g.classList.remove("open");
                }
            });

            group.classList.toggle("open");
            setActiveGroup(group);
            dropdown.setAttribute("aria-expanded", String(!isOpen));
            return;
        }

        if (item) {
            const href = item.getAttribute("href");

            // jika menuju route Laravel,
            // biarkan Blade yang menentukan active
            if (
                href &&
                href !== "#" &&
                href !== "" &&
                !href.startsWith("javascript")
            ) {
                return;
            }

            e.preventDefault();

            const parentGroup = item.closest(".nav-group");

            if (!parentGroup) return;

            parentGroup.classList.add("active");

            if (isCollapsedMode()) {
                parentGroup.classList.add("popup-open");
                schedulePopupClose(parentGroup, 5000);
            } else {
                parentGroup.classList.add("open");
            }
        }
    });

    document.querySelectorAll(".nav-group").forEach((group) => {
        group.addEventListener("mouseenter", () => {
            if (isCollapsedMode() && group.classList.contains("popup-open")) {
                clearSidebarPopupTimer(group);
            }
        });

        group.addEventListener("mouseleave", () => {
            if (isCollapsedMode() && group.classList.contains("popup-open")) {
                schedulePopupClose(group, 250);
            }
        });
    });

    if (main) {
        main.addEventListener("mouseenter", () => {
            if (isCollapsedMode()) {
                closeAllSidebarDropdowns();
            }
        });
    }

    document.addEventListener("click", (e) => {
        if (isCollapsedMode() && !e.target.closest(".sidebar")) {
            closeAllSidebarDropdowns();
        }
    });

    window.addEventListener("resize", () => {
        if (!isCollapsedMode()) {
            closeAllSidebarDropdowns();
        }
    });
}

function initializeSidebarState() {
    document.querySelectorAll(".dropdown-item.active").forEach((item) => {
        const group = item.closest(".nav-group");

        if (!group) return;

        group.classList.add("active");
        group.classList.add("open");

        const button = group.querySelector(".nav-dropdown");

        if (button) {
            button.setAttribute("aria-expanded", "true");
        }
    });
}

function initializeSoftwareSearch() {
    const input = document.getElementById("softwareSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll("tbody tr"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}

function initializeUserSearch() {
    const input = document.getElementById("userSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll("tbody tr"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}

function initializeTrustedWebsiteSearch() {
    const input = document.getElementById("trustedWebsiteSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll("tbody tr"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}
function initializeRoleSearch() {
    const input = document.getElementById("roleSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll("tbody tr"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}

/* ==========================
   MODAL LOADER
========================== */

function initializeModalLoader() {
    document.addEventListener("click", async function (e) {
        const button = e.target.closest(".open-modal");
        if (!button) return;

        const url = button.dataset.url;
        if (!url) return;

        try {
            const response = await fetch(url);
            const html = await response.text();

            const container = document.getElementById("modalContainer");
            if (!container) return;

            container.innerHTML = html;

            document.body.classList.add("overflow-hidden", "modal-open");

            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        } catch (error) {
            console.error(error);
        }
    });
}

function initializeModalClose() {
    document.addEventListener("click", function (e) {
        const container = document.getElementById("modalContainer");
        if (!container) return;

        const overlay = e.target.closest(".tn-modal-overlay");
        const closeBtn = e.target.closest(".close-modal");

        if (overlay && e.target === overlay) {
            container.innerHTML = "";
            document.body.classList.remove("overflow-hidden", "modal-open");
            return;
        }

        if (closeBtn) {
            container.innerHTML = "";
            document.body.classList.remove("overflow-hidden", "modal-open");
        }
    });
}

let tnConfirmForm = null;
let tnConfirmCallback = null;

function initializeGlobalConfirmModal() {
    const overlay = document.getElementById("tnConfirmOverlay");
    const titleEl = document.getElementById("tnConfirmTitle");
    const messageEl = document.getElementById("tnConfirmMessage");
    const iconWrap = document.getElementById("tnConfirmIconWrap");
    const iconEl = document.getElementById("tnConfirmIcon");
    const proceedBtn = document.getElementById("tnConfirmProceed");
    const cancelBtn = document.getElementById("tnConfirmCancel");
    const closeBtn = document.getElementById("tnConfirmClose");

    if (
        !overlay ||
        !titleEl ||
        !messageEl ||
        !proceedBtn ||
        !cancelBtn ||
        !closeBtn
    ) {
        return;
    }

    function openConfirm(options = {}) {
        const {
            title = "Confirmation",
            message = "Are you sure you want to continue?",
            type = "warning",
            proceedText = "Continue",
            form = null,
            callback = null,
            showProceed = true,
        } = options;

        titleEl.textContent = title;
        messageEl.textContent = message;
        proceedBtn.innerHTML = `<i data-lucide="check"></i>${proceedText}`;
        proceedBtn.style.display = showProceed ? "" : "none";

        overlay.classList.add("show");
        overlay.setAttribute("aria-hidden", "false");
        document.body.classList.add("overflow-hidden", "modal-open");

        overlay.classList.remove(
            "tn-confirm-type-warning",
            "tn-confirm-type-danger",
            "tn-confirm-type-success",
        );
        overlay.classList.add(`tn-confirm-type-${type}`);

        tnConfirmForm = form;
        tnConfirmCallback = callback || null;

        if (iconWrap && iconEl) {
            let iconName = "alert-triangle";
            if (type === "danger") iconName = "trash-2";
            if (type === "success") iconName = "check-circle-2";
            iconEl.setAttribute("data-lucide", iconName);
            lucide?.createIcons();
        }
    }

    function closeConfirm() {
        overlay.classList.remove("show");
        overlay.setAttribute("aria-hidden", "true");
        document.body.classList.remove("overflow-hidden", "modal-open");
        tnConfirmForm = null;
        tnConfirmCallback = null;
        proceedBtn.style.display = "";
    }

    function proceedConfirm() {
        if (tnConfirmForm) {
            tnConfirmForm.submit();
        } else if (typeof tnConfirmCallback === "function") {
            tnConfirmCallback();
        }
        closeConfirm();
    }

    document.addEventListener("click", (e) => {
        const confirmTarget = e.target.closest("[data-tn-confirm]");
        const blockedTarget = e.target.closest("[data-tn-blocked]");

        if (blockedTarget) {
            e.preventDefault();

            const onlyCancel =
                blockedTarget.dataset.tnOnlyCancel === "true" ||
                blockedTarget.hasAttribute("data-tn-only-cancel");

            openConfirm({
                title: blockedTarget.dataset.tnTitle || "Action blocked",
                message:
                    blockedTarget.dataset.tnMessage ||
                    "This action is currently not allowed.",
                type: blockedTarget.dataset.tnType || "warning",
                proceedText:
                    blockedTarget.dataset.tnProceedText || "Understood",
                callback: onlyCancel ? null : () => {},
                showProceed: !onlyCancel,
            });

            return;
        }

        if (!confirmTarget) return;

        const form = confirmTarget.closest("form");
        const title = confirmTarget.dataset.tnTitle || "Confirm action";
        const message =
            confirmTarget.dataset.tnMessage ||
            "Are you sure you want to continue?";
        const type = confirmTarget.dataset.tnType || "warning";
        const proceedText = confirmTarget.dataset.tnProceedText || "Continue";

        e.preventDefault();

        const url = confirmTarget.dataset.tnUrl || null;

        let callback = null;
        if (url) {
            callback = async () => {
                try {
                    const response = await fetch(url);
                    const html = await response.text();
                    const container = document.getElementById("modalContainer");
                    if (container) {
                        container.innerHTML = html;
                        lucide?.createIcons();
                    }
                } catch (err) {
                    console.error("Failed to load URL for confirm:", err);
                }
            };
        }

        openConfirm({
            title,
            message,
            type,
            proceedText,
            form,
            callback,
        });
    });

    proceedBtn.addEventListener("click", proceedConfirm);
    cancelBtn.addEventListener("click", closeConfirm);
    closeBtn.addEventListener("click", closeConfirm);

    overlay.addEventListener("click", (e) => {
        if (e.target === overlay) closeConfirm();
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && overlay.classList.contains("show")) {
            closeConfirm();
        }
    });
}

function initializeSearchPenginstalan() {
    const input = document.getElementById("penginstalanSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll(".penginstalan-row"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}

function initializeSearchLog() {
    const input = document.getElementById("LoginLogSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll(".log-row"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}
function initializeSearchPerbaikan() {
    const input = document.getElementById("perbaikanSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll(".perbaikan-row"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}
function initializeSearchUserLog() {
    const input = document.getElementById("UserActivitySearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll(".activity-row"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}

function initializeSearchRekap() {
    const input = document.getElementById("rekapSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll(".rekap-row"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}
function initializeSearchTicketLog() {
    const input = document.getElementById("ticketLogSearch");

    if (!input) return;

    const countEl = document.querySelector(".table-footer-actions span");
    let searchFrame = 0;

    const filterRows = (keyword) => {
        const rows = Array.from(
            document.querySelectorAll("tbody tr.ticket-log-row"),
        );
        let visibleCount = 0;

        rows.forEach((row) => {
            const content = row.innerText.toLowerCase();
            const isVisible = keyword === "" || content.includes(keyword);

            row.style.display = isVisible ? "table-row" : "none";

            if (isVisible) {
                visibleCount++;
            }
        });

        if (countEl) {
            countEl.textContent = `Total: ${visibleCount} Log`;
        }
    };

    input.addEventListener("input", function () {
        const keyword = this.value.trim().toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            filterRows(keyword);
        });
    });
}
function initializeSearchTicket() {
    const input = document.getElementById("ticketSearch");

    if (!input) return;

    const rows = Array.from(document.querySelectorAll(".ticket-row"));
    let searchFrame = 0;

    input.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();

        cancelAnimationFrame(searchFrame);

        searchFrame = requestAnimationFrame(() => {
            rows.forEach((row) => {
                row.style.display = row.textContent
                    .toLowerCase()
                    .includes(keyword)
                    ? ""
                    : "none";
            });
        });
    });
}

/* ==========================
   TECHNOTE 2.0 BUTTON LIQUID GLASS UPGRADE
========================== */

function initializeButtons() {
    const buttons = document.querySelectorAll(
        ".btn-primary, .btn-secondary, .icon-btn",
    );

    ensureTechnoteGlassFilter();

    buttons.forEach((button) => {
        prepareTechnoteButton(button);
    });
}

function ensureTechnoteGlassFilter() {
    if (document.getElementById("container-glass")) return;

    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("aria-hidden", "true");
    svg.setAttribute("focusable", "false");
    svg.setAttribute(
        "style",
        "position:absolute;width:0;height:0;overflow:hidden;pointer-events:none;",
    );

    svg.innerHTML = `
        <defs>
            <filter
                id="container-glass"
                x="0%"
                y="0%"
                width="100%"
                height="100%"
                colorInterpolationFilters="sRGB"
            >
                <feTurbulence
                    type="fractalNoise"
                    baseFrequency="0.05 0.05"
                    numOctaves="1"
                    seed="1"
                    result="turbulence"
                />
                <feGaussianBlur in="turbulence" stdDeviation="2" result="blurredNoise" />
                <feDisplacementMap
                    in="SourceGraphic"
                    in2="blurredNoise"
                    scale="70"
                    xChannelSelector="R"
                    yChannelSelector="B"
                    result="displaced"
                />
                <feGaussianBlur in="displaced" stdDeviation="4" result="finalBlur" />
                <feComposite in="finalBlur" in2="finalBlur" operator="over" />
            </filter>
        </defs>
    `;

    document.body.appendChild(svg);
}

function prepareTechnoteButton(button) {
    if (!button || button.dataset.tnLiquidReady === "1") return;
    button.dataset.tnLiquidReady = "1";

    button.classList.add("button-liquid-base");
    button.style.setProperty("--btn-x", "50%");
    button.style.setProperty("--btn-y", "35%");

    if (!button.querySelector(".tn-liquid-surface")) {
        const surface = document.createElement("span");
        surface.className = "tn-liquid-surface";
        surface.setAttribute("aria-hidden", "true");
        button.prepend(surface);
    }

    const handlePointerMove = (event) => {
        const rect = button.getBoundingClientRect();
        const x =
            typeof event.clientX === "number"
                ? event.clientX - rect.left
                : rect.width / 2;
        const y =
            typeof event.clientY === "number"
                ? event.clientY - rect.top
                : rect.height / 2;

        button.style.setProperty(
            "--btn-x",
            `${Math.max(0, Math.min(rect.width, x))}px`,
        );
        button.style.setProperty(
            "--btn-y",
            `${Math.max(0, Math.min(rect.height, y))}px`,
        );
    };

    const activateButton = () => {
        button.classList.add("button-liquid-hover");
    };

    const deactivateButton = () => {
        button.classList.remove("button-liquid-hover", "button-liquid-pressed");
        button.style.setProperty("--btn-x", "50%");
        button.style.setProperty("--btn-y", "35%");
    };

    button.addEventListener("pointerenter", activateButton);
    button.addEventListener("pointerleave", deactivateButton);
    button.addEventListener("pointercancel", deactivateButton);
    button.addEventListener("blur", deactivateButton);
    button.addEventListener("pointermove", handlePointerMove, {
        passive: true,
    });

    button.addEventListener("pointerdown", (event) => {
        if (event.button !== undefined && event.button !== 0) return;

        button.classList.add("button-liquid-pressed");
        handlePointerMove(event);
        createRipple(button, event);
    });

    button.addEventListener("pointerup", () => {
        button.classList.remove("button-liquid-pressed");
    });

    button.addEventListener("click", (event) => {
        if (event.detail === 0) {
            createRipple(button, event);
        }
    });

    button.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
            button.classList.add("button-liquid-pressed");
            createRipple(button, event);
            window.setTimeout(() => {
                button.classList.remove("button-liquid-pressed");
            }, 120);
        }
    });
}

function createRipple(button, event) {
    if (!button) return;

    const rect = button.getBoundingClientRect();
    const ripple = document.createElement("span");
    ripple.className = "tn-button-ripple";

    const clientX =
        typeof event?.clientX === "number"
            ? event.clientX
            : rect.left + rect.width / 2;
    const clientY =
        typeof event?.clientY === "number"
            ? event.clientY
            : rect.top + rect.height / 2;

    const size = Math.max(rect.width, rect.height);

    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${clientX - rect.left - size / 2}px`;
    ripple.style.top = `${clientY - rect.top - size / 2}px`;

    button.appendChild(ripple);

    window.setTimeout(() => {
        ripple.remove();
    }, 640);
}
