/* =========================================================
   MONITORING SYSTEM
   Dashboard JavaScript
   ========================================================= */

// =========================================================
// MOBILE SIDEBAR
// =========================================================

const menuToggle = document.getElementById("menuToggle");
const sidebar = document.getElementById("sidebar");

menuToggle.addEventListener("click", function () {
    sidebar.classList.toggle("open");
});


// =========================================================
// CURRENT DATE
// =========================================================

const currentDate = document.getElementById("currentDate");

const today = new Date();

const dateOptions = {
    year: "numeric",
    month: "long",
    day: "numeric"
};

currentDate.textContent = today.toLocaleDateString(
    "en-US",
    dateOptions
);


// =========================================================
// TASK PROGRESS CHART
// =========================================================

const progressCanvas = document.getElementById("progressChart");

new Chart(progressCanvas, {

    type: "line",

    data: {

        labels: [
            "Week 1",
            "Week 2",
            "Week 3",
            "Week 4",
            "Week 5",
            "Week 6"
        ],

        datasets: [

            {
                label: "Completed Tasks",

                data: [
                    18,
                    27,
                    35,
                    42,
                    48,
                    52
                ],

                borderColor: "#2563eb",

                backgroundColor:
                    "rgba(37, 99, 235, 0.08)",

                borderWidth: 2,

                fill: true,

                tension: 0.4,

                pointRadius: 3,

                pointHoverRadius: 5
            },

            {
                label: "Total Tasks",

                data: [
                    35,
                    48,
                    58,
                    67,
                    76,
                    86
                ],

                borderColor: "#d1d5db",

                borderWidth: 2,

                borderDash: [5, 5],

                fill: false,

                tension: 0.4,

                pointRadius: 2
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                position: "bottom",

                labels: {
                    usePointStyle: true,
                    boxWidth: 6,
                    font: {
                        size: 9
                    }
                }
            }

        },

        scales: {

            y: {

                beginAtZero: true,

                grid: {
                    color: "#f1f3f6"
                },

                ticks: {
                    font: {
                        size: 9
                    },

                    color: "#9ca3af"
                }

            },

            x: {

                grid: {
                    display: false
                },

                ticks: {
                    font: {
                        size: 9
                    },

                    color: "#9ca3af"
                }

            }

        }

    }

});


// =========================================================
// TASK STATUS DONUT
// =========================================================

const statusCanvas = document.getElementById("statusChart");

new Chart(statusCanvas, {

    type: "doughnut",

    data: {

        labels: [
            "Completed",
            "In Progress",
            "Pending",
            "Overdue"
        ],

        datasets: [

            {
                data: [
                    52,
                    21,
                    10,
                    3
                ],

                backgroundColor: [
                    "#16a34a",
                    "#2563eb",
                    "#f59e0b",
                    "#dc2626"
                ],

                borderWidth: 0,

                hoverOffset: 5
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        cutout: "76%",

        plugins: {

            legend: {
                display: false
            },

            tooltip: {
                enabled: true
            }

        }

    }

});


// =========================================================
// NAVIGATION DEMO
// =========================================================

const navigationItems =
    document.querySelectorAll(".nav-item");

navigationItems.forEach(function (item) {

    item.addEventListener("click", function (event) {

        if (item.getAttribute("href") === "#") {
            event.preventDefault();
        }

        navigationItems.forEach(function (nav) {
            nav.classList.remove("active");
        });

        item.classList.add("active");

    });

});


// =========================================================
// QUICK ACTION DEMO
// =========================================================

const quickActions =
    document.querySelectorAll(".quick-action");

quickActions.forEach(function (button) {

    button.addEventListener("click", function () {

        const action =
            button.querySelector("strong").textContent;

        alert(
            action +
            " module will be available in the full system."
        );

    });

});