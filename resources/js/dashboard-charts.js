// Grafik dashboard (Chart.js, lazy chunk): chart.js hanya terunduh saat
// fungsi ini dipanggil pertama kali — bundle utama tetap ramping.
// Dipanggil dari blade dashboard dengan payload dari DashboardStats.
window.grafikDashboard = async function (payload) {
    const Chart = (await import("chart.js/auto")).default;

    // Hancurkan grafik lama saat dashboard dirender ulang (navigasi Turbo)
    if (window.__dashCharts) window.__dashCharts.forEach((c) => c.destroy());
    window.__dashCharts = [];

    const tick = "#94a3b8";

    if (payload.burung && document.getElementById("grafik-fase")) {
        window.__dashCharts.push(
            new Chart(document.getElementById("grafik-fase"), {
                type: "doughnut",
                data: {
                    labels: ["Dewasa", "Baby"],
                    datasets: [
                        {
                            data: [
                                payload.burung.dewasa,
                                payload.burung.anakan,
                            ],
                            backgroundColor: ["#3b82f6", "#f59e0b"],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "65%",
                    plugins: {
                        legend: { position: "bottom", labels: { color: tick } },
                    },
                },
            }),
        );
    }

    if (payload.burung && document.getElementById("grafik-spesies")) {
        const spesies = Object.entries(payload.burung.spesies || {});
        if (payload.burung.spesiesLainnya > 0)
            spesies.push(["Lainnya", payload.burung.spesiesLainnya]);
        const palet = [
            "#E62C37",
            "#f59e0b",
            "#22c55e",
            "#3b82f6",
            "#a855f7",
            "#14b8a6",
            "#64748b",
        ];
        window.__dashCharts.push(
            new Chart(document.getElementById("grafik-spesies"), {
                type: "doughnut",
                data: {
                    labels: spesies.map(([l]) => l),
                    datasets: [
                        {
                            data: spesies.map(([, v]) => v),
                            backgroundColor: palet.slice(0, spesies.length),
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "65%",
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: {
                                color: tick,
                                boxWidth: 10,
                                font: { size: 10 },
                            },
                        },
                    },
                },
            }),
        );
    }
};
