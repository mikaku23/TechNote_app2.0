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
    initializeActiveMenu();
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
        const target = parseInt(counter.textContent.replace(/,/g, ""));

        if (isNaN(target)) return;

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

    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${event.clientY - rect.top - size / 2}px`;
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
    document.body.style.opacity = "0";
    document.body.style.transform = "translateY(10px)";

    setTimeout(() => {
        document.body.style.transition = "all .5s ease";
        document.body.style.opacity = "1";
        document.body.style.transform = "translateY(0)";
    }, 50);
}

/* ==========================
   CARD MOTION
========================== */

function initializeCards() {
    const cards = document.querySelectorAll(".motion-card");

    cards.forEach((card) => {
        card.addEventListener("mousemove", handleCardMove);
        card.addEventListener("mouseleave", resetCard);
    });
}

function handleCardMove(event) {
    const card = event.currentTarget;
    const rect = card.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    card.classList.add("liquid");
    card.style.setProperty("--mouse-x", `${x}px`);
    card.style.setProperty("--mouse-y", `${y}px`);

    const centerX = rect.width / 2;
    const centerY = rect.height / 2;
    const rotateX = ((y - centerY) / centerY) * -4;
    const rotateY = ((x - centerX) / centerX) * 4;

    card.style.transform = `
        perspective(1000px)
        rotateX(${rotateX}deg)
        rotateY(${rotateY}deg)
        translateY(-4px)
    `;
}

function resetCard(event) {
    event.currentTarget.classList.remove("liquid");

    event.currentTarget.style.transform = `
        perspective(1000px)
        rotateX(0deg)
        rotateY(0deg)
        translateY(0px)
    `;
}

/* ==========================
   SEARCH
========================== */

function initializeSearch() {
    const searchInput = document.querySelector(".search-box input");

    if (!searchInput) return;

    searchInput.addEventListener("keyup", function () {
        const keyword = this.value.toLowerCase();

        const rows = document.querySelectorAll("tbody tr");

        rows.forEach((row) => {
            const text = row.textContent.toLowerCase();

            row.style.display = text.includes(keyword) ? "" : "none";
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

/* ==========================
   ACTIVE MENU
========================== */

function initializeActiveMenu() {
    const navItems = document.querySelectorAll(".nav-item");

    navItems.forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault();

            closeAllSidebarDropdowns();
            clearSidebarActive();

            this.classList.add("active");
        });
    });
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

function closeAllSidebarDropdowns() {
    document.querySelectorAll(".nav-group").forEach((group) => {
        group.classList.remove("open");
        group.classList.remove("popup-open");

        const button = group.querySelector(".nav-dropdown");
        if (button) {
            button.setAttribute("aria-expanded", "false");
        }

        const timer = sidebarPopupTimers.get(group);
        if (timer) {
            clearTimeout(timer);
            sidebarPopupTimers.delete(group);
        }
    });
}

function initializeSidebarDropdowns() {
    const dropdowns = document.querySelectorAll(".nav-dropdown");
    const dropdownItems = document.querySelectorAll(".dropdown-item");
    const main = document.querySelector(".main");
    const popupTimers = new Map();

    function isCollapsedMode() {
        return document.body.classList.contains("sidebar-collapsed");
    }

    function clearPopupTimer(group) {
        const timer = popupTimers.get(group);

        if (timer) {
            clearTimeout(timer);
            popupTimers.delete(group);
        }
    }

    function closePopupGroup(group) {
        clearPopupTimer(group);

        group.classList.remove("popup-open");

        const button = group.querySelector(".nav-dropdown");

        if (button) {
            button.setAttribute("aria-expanded", "false");
        }
    }

    function closeAllPopupGroups(exceptGroup = null) {
        document.querySelectorAll(".nav-group").forEach((group) => {
            if (group !== exceptGroup) {
                closePopupGroup(group);
            }
        });
    }

    function openPopupGroup(group) {
        if (!isCollapsedMode()) return;

        closeAllPopupGroups(group);

        group.classList.add("popup-open");

        const button = group.querySelector(".nav-dropdown");

        if (button) {
            button.setAttribute("aria-expanded", "true");
        }
    }

    function schedulePopupClose(group, delay = 5000) {
        clearPopupTimer(group);

        popupTimers.set(
            group,
            setTimeout(() => {
                closePopupGroup(group);
            }, delay),
        );
    }

    dropdowns.forEach((dropdown) => {
        dropdown.addEventListener("click", (e) => {
            const group = dropdown.closest(".nav-group");

            if (!group) return;

            const isOpen = group.classList.contains("open");
            const collapsed = isCollapsedMode();

            e.preventDefault();

            if (collapsed) {
                const isPopupOpen = group.classList.contains("popup-open");

                closeAllPopupGroups(group);

                if (!isPopupOpen) {
                    openPopupGroup(group);
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
        });
    });

    dropdownItems.forEach((item) => {
        item.addEventListener("click", (e) => {
            e.preventDefault();

            clearSidebarActive();

            item.classList.add("active");

            const parentGroup = item.closest(".nav-group");

            if (!parentGroup) return;

            parentGroup.classList.add("active");

            if (isCollapsedMode()) {
                parentGroup.classList.add("popup-open");
                schedulePopupClose(parentGroup, 5000);
            } else {
                parentGroup.classList.add("open");
            }
        });
    });

    if (main) {
        main.addEventListener("mouseenter", () => {
            if (isCollapsedMode()) {
                closeAllPopupGroups();
            }
        });
    }

    document.addEventListener("click", (e) => {
        if (isCollapsedMode() && !e.target.closest(".sidebar")) {
            closeAllPopupGroups();
        }
    });

    window.addEventListener("resize", () => {
        if (!isCollapsedMode()) {
            closeAllPopupGroups();
        }
    });

    document.querySelectorAll(".nav-group").forEach((group) => {
        group.addEventListener("mouseenter", () => {
            if (isCollapsedMode() && group.classList.contains("popup-open")) {
                clearPopupTimer(group);
            }
        });

        group.addEventListener("mouseleave", () => {
            if (isCollapsedMode() && group.classList.contains("popup-open")) {
                schedulePopupClose(group, 250);
            }
        });
    });
}
