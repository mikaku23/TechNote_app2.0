document.addEventListener("DOMContentLoaded", () => {
    const data = window.__TN_DASHBOARD_DATA || {};
    const chartCanvas = document.getElementById("ticketTrendChart");
    const modeSelect = document.getElementById("ticketTrendMode");
    const subtitleEl = document.getElementById("ticketTrendSubtitle");
    const rangeLabelEl = document.getElementById("ticketTrendRangeLabel");

    if (!chartCanvas) {
        console.error("ticketTrendChart canvas tidak ditemukan");
        return;
    }

    if (typeof window.Chart === "undefined") {
        console.error("Chart.js belum dimuat");
        return;
    }

    const labels = Array.isArray(data.ticketChart?.labels)
        ? data.ticketChart.labels
        : [];
    const installationData = Array.isArray(data.ticketChart?.installation)
        ? data.ticketChart.installation
        : [];
    const repairData = Array.isArray(data.ticketChart?.repair)
        ? data.ticketChart.repair
        : [];

    console.log("TN Dashboard data:", data);
    console.log("TN Labels:", labels);
    console.log("TN Installation:", installationData);
    console.log("TN Repair:", repairData);

    const today = new Date();
    const currentDay = today.getDate();
    const currentMonthName = new Intl.DateTimeFormat("id-ID", {
        month: "long",
        year: "numeric",
    }).format(today);

    const totalDaysInMonth =
        labels.length ||
        new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();

    const getWeekRange = (day) => {
        const start = Math.floor((day - 1) / 7) * 7 + 1;
        const end = Math.min(start + 6, totalDaysInMonth);
        return { start, end };
    };

    const buildWeekView = () => {
        const { start, end } = getWeekRange(currentDay);

        return {
            labels: labels.slice(start - 1, end),
            installation: installationData.slice(start - 1, end),
            repair: repairData.slice(start - 1, end),
            subtitle: `Menampilkan data tanggal ${start}-${end} bulan ${currentMonthName}.`,
            rangeLabel: `Minggu ${start}-${end}`,
        };
    };

    const buildMonthView = () => {
        return {
            labels,
            installation: installationData,
            repair: repairData,
            subtitle: `Menampilkan semua data tanggal 1-${totalDaysInMonth} bulan ${currentMonthName}.`,
            rangeLabel: `Bulan ini (${totalDaysInMonth} hari)`,
        };
    };

    const ctx = chartCanvas.getContext("2d");
    let chartInstance = null;

    const renderChart = (mode = "week") => {
        const view = mode === "month" ? buildMonthView() : buildWeekView();

        if (subtitleEl) subtitleEl.textContent = view.subtitle;
        if (rangeLabelEl) {
            rangeLabelEl.innerHTML = `<i data-lucide="bar-chart-3"></i> ${view.rangeLabel}`;
        }

        if (chartInstance) {
            chartInstance.destroy();
        }

        chartInstance = new Chart(ctx, {
            type: "line",
            data: {
                labels: view.labels,
                datasets: [
                    {
                        label: "Penginstalan",
                        data: view.installation,
                        borderColor: "#60a5fa",
                        backgroundColor: "rgba(96, 165, 250, 0.15)",
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 3,
                    },
                    {
                        label: "Perbaikan",
                        data: view.repair,
                        borderColor: "#f59e0b",
                        backgroundColor: "rgba(245, 158, 11, 0.15)",
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false,
                },
                plugins: {
                    legend: {
                        labels: {
                            color: "#cbd5e1",
                            usePointStyle: true,
                            pointStyle: "circle",
                        },
                    },
                    tooltip: {
                        backgroundColor: "rgba(15, 23, 42, 0.96)",
                        titleColor: "#fff",
                        bodyColor: "#e2e8f0",
                        borderColor: "rgba(255,255,255,.08)",
                        borderWidth: 1,
                    },
                },
                scales: {
                    x: {
                        ticks: { color: "#94a3b8" },
                        grid: { color: "rgba(148,163,184,.12)" },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: "#94a3b8", precision: 0 },
                        grid: { color: "rgba(148,163,184,.12)" },
                    },
                },
            },
        });
    };

    renderChart(modeSelect ? modeSelect.value : "week");

    if (modeSelect) {
        modeSelect.addEventListener("change", () => {
            renderChart(modeSelect.value);
        });
    }
});
