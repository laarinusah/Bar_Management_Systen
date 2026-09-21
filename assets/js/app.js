/* =========================================================
   BAR MANAGEMENT SYSTEM
   Main Application JavaScript
   ========================================================= */


/* ---------- Mobile Sidebar ---------- */

function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.toggle("show");
    }
}


/* ---------- Close Sidebar ---------- */

function closeSidebar() {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.remove("show");
    }
}


/* ---------- Close Sidebar After Clicking a Link ---------- */

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("sidebar");

    if (sidebar) {

        const sidebarLinks =
            sidebar.querySelectorAll("a");

        sidebarLinks.forEach(function (link) {

            link.addEventListener("click", function () {

                if (window.innerWidth <= 768) {
                    closeSidebar();
                }

            });

        });

    }

});


/* ---------- Confirm Delete ---------- */

function confirmDelete(message) {

    if (!message) {
        message =
            "Are you sure you want to delete this record?";
    }

    return confirm(message);
}


/* ---------- Automatically Hide Alerts ---------- */

document.addEventListener("DOMContentLoaded", function () {

    const alerts =
        document.querySelectorAll(".alert");

    alerts.forEach(function (alert) {

        setTimeout(function () {

            alert.style.transition =
                "opacity 0.5s ease";

            alert.style.opacity = "0";

            setTimeout(function () {

                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }

            }, 500);

        }, 5000);

    });

});


/* ---------- Prevent Double Form Submission ---------- */

document.addEventListener("DOMContentLoaded", function () {

    const forms =
        document.querySelectorAll("form");

    forms.forEach(function (form) {

        form.addEventListener("submit", function () {

            const submitButtons =
                form.querySelectorAll(
                    'button[type="submit"], input[type="submit"]'
                );

            submitButtons.forEach(function (button) {

                button.disabled = true;

                const originalText =
                    button.innerHTML;

                button.dataset.originalText =
                    originalText;

                if (button.tagName === "BUTTON") {
                    button.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
                }

            });

        });

    });

});


/* ---------- Format Currency ---------- */

function formatCurrency(amount) {

    amount = Number(amount) || 0;

    return new Intl.NumberFormat("en-GH", {
        style: "currency",
        currency: "GHS",
        minimumFractionDigits: 2
    }).format(amount);

}


/* ---------- Format Number ---------- */

function formatNumber(number) {

    number = Number(number) || 0;

    return new Intl.NumberFormat("en-GH")
        .format(number);

}


/* ---------- Confirm Logout ---------- */

function confirmLogout() {

    return confirm(
        "Are you sure you want to logout?"
    );

}


/* ---------- Prevent Negative Numbers ---------- */

document.addEventListener("DOMContentLoaded", function () {

    const numberInputs =
        document.querySelectorAll(
            'input[type="number"]'
        );

    numberInputs.forEach(function (input) {

        input.addEventListener("input", function () {

            if (this.value < 0) {
                this.value = 0;
            }

        });

    });

});


/* ---------- Mobile Menu Resize Handling ---------- */

window.addEventListener("resize", function () {

    const sidebar =
        document.getElementById("sidebar");

    if (!sidebar) {
        return;
    }

    if (window.innerWidth > 768) {
        sidebar.classList.remove("show");
    }

});


/* ---------- Console Message ---------- */

console.log(
    "Bar Management System JavaScript loaded successfully."
);