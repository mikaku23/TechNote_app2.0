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

    const themeBtn =
        document.getElementById("themeToggle");

    const savedTheme =
        localStorage.getItem("technote-theme");

    if (savedTheme === "dark") {

        document.body.classList.add("dark");

        updateThemeIcon();

    }

    if (!themeBtn) return;

    themeBtn.addEventListener("click", () => {

        document.body.classList.toggle("dark");

        const isDark =
            document.body.classList.contains("dark");

        localStorage.setItem(
            "technote-theme",
            isDark ? "dark" : "light"
        );

        updateThemeIcon();

    });

}

function updateThemeIcon() {

    const icon =
        document.querySelector(
            "#themeToggle i"
        );

    if (!icon) return;

    const isDark =
        document.body.classList.contains("dark");

    icon.setAttribute(
        "data-lucide",
        isDark ? "sun" : "moon"
    );

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

    const counters =
        document.querySelectorAll(
            ".stat-card h2"
        );

    counters.forEach(counter => {

        const target =
            parseInt(
                counter.textContent.replace(
                    /,/g,
                    ""
                )
            );

        if (isNaN(target)) return;

        animateCounter(
            counter,
            target
        );

    });

}

function animateCounter(
    element,
    target
) {

    let current = 0;

    const duration = 1200;

    const increment =
        target / 60;

    const timer =
        setInterval(() => {

            current += increment;

            if (current >= target) {

                current = target;

                clearInterval(timer);

            }

            element.textContent =
                Math.floor(current)
                .toLocaleString();

        }, duration / 60);

}

/* ==========================
   BUTTON RIPPLE
========================== */

function initializeButtons() {

    const buttons =
        document.querySelectorAll(
            ".btn-primary, .btn-secondary, .icon-btn"
        );

    buttons.forEach(button => {

        button.addEventListener(
            "click",
            createRipple
        );

    });

}

function createRipple(event) {

    const button =
        event.currentTarget;

    const ripple =
        document.createElement("span");

    const rect =
        button.getBoundingClientRect();

    const size =
        Math.max(
            rect.width,
            rect.height
        );

    ripple.style.width =
        ripple.style.height =
        `${size}px`;

    ripple.style.left =
        `${event.clientX - rect.left - size / 2}px`;

    ripple.style.top =
        `${event.clientY - rect.top - size / 2}px`;

    ripple.classList.add(
        "ripple"
    );

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

    document.body.style.transform =
        "translateY(10px)";

    setTimeout(() => {

        document.body.style.transition =
            "all .5s ease";

        document.body.style.opacity = "1";

        document.body.style.transform =
            "translateY(0)";

    }, 50);

}

/* ==========================
   CARD MOTION
========================== */

function initializeCards() {

const cards = document.querySelectorAll(".motion-card");

    cards.forEach(card => {

        card.addEventListener(
            "mousemove",
            handleCardMove
        );

        card.addEventListener(
            "mouseleave",
            resetCard
        );

    });

}

function handleCardMove(event) {

    const card =
        event.currentTarget;

    const rect =
        card.getBoundingClientRect();

    const x =
        event.clientX - rect.left;

    const y =
        event.clientY - rect.top;

        card.classList.add("liquid");

        card.style.setProperty("--mouse-x", `${x}px`);

        card.style.setProperty("--mouse-y", `${y}px`);

    const centerX =
        rect.width / 2;

    const centerY =
        rect.height / 2;

    const rotateX =
        ((y - centerY) / centerY) * -4;

    const rotateY =
        ((x - centerX) / centerX) * 4;

    card.style.transform =
        `
        perspective(1000px)
        rotateX(${rotateX}deg)
        rotateY(${rotateY}deg)
        translateY(-4px)
        `;

}

function resetCard(event) {

    event.currentTarget.classList.remove("liquid");

    event.currentTarget.style.transform =
        `
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

    const searchInput =
        document.querySelector(
            ".search-box input"
        );

    if (!searchInput) return;

    searchInput.addEventListener(
        "keyup",
        function () {

            const keyword =
                this.value
                    .toLowerCase();

            const rows =
                document.querySelectorAll(
                    "tbody tr"
                );

            rows.forEach(row => {

                const text =
                    row.textContent
                        .toLowerCase();

                row.style.display =
                    text.includes(keyword)
                    ? ""
                    : "none";

            });

        }
    );

}

/* ==========================
   TOAST
========================== */

function showToast(
    message,
    type = "success"
) {

    const toast =
        document.createElement(
            "div"
        );

    toast.className =
        `toast ${type}`;

    toast.innerHTML =
        message;

    document.body.appendChild(
        toast
    );

    setTimeout(() => {

        toast.classList.add(
            "show"
        );

    }, 100);

    setTimeout(() => {

        toast.classList.remove(
            "show"
        );

        setTimeout(() => {

            toast.remove();

        }, 300);

    }, 3000);

}

/* ==========================
   DEMO TOAST
========================== */



/* ==========================
   ACTIVE MENU
========================== */

const navItems =
    document.querySelectorAll(
        ".nav-item"
    );

navItems.forEach(item => {

    item.addEventListener(
        "click",
        function () {

            navItems.forEach(i =>
                i.classList.remove(
                    "active"
                )
            );

            this.classList.add(
                "active"
            );

        }
    );

});