<?php

$currentPage = basename($_SERVER['PHP_SELF']);
$currentFolder = basename(dirname($_SERVER['PHP_SELF']));

?>
<style>

    body {
        margin: 0;
        padding: 0;
        padding-left: 250px;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100vh;
        background: #212529;
        color: white;
        z-index: 1000;
        overflow-y: auto;
    }

    .sidebar-brand {
        padding: 20px 15px;
        text-align: center;
        border-bottom: 1px solid #495057;
    }

    .sidebar-brand h4 {
        margin: 0;
        font-size: 20px;
    }

    .sidebar-user {
        padding: 15px;
        border-bottom: 1px solid #495057;
        text-align: center;
    }

    .sidebar-user small {
        color: #adb5bd;
    }

    .sidebar-menu {
        padding: 15px 10px;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        margin-bottom: 5px;
        color: #dee2e6;
        text-decoration: none;
        border-radius: 6px;
        transition: 0.2s;
    }

    .sidebar-menu a:hover {
        background: #343a40;
        color: white;
    }

    .sidebar-menu a.active {
        background: #0d6efd;
        color: white;
    }

    .sidebar-menu i {
        font-size: 18px;
        width: 22px;
    }

    .menu-title {
        color: #6c757d;
        font-size: 11px;
        text-transform: uppercase;
        padding: 15px 15px 8px;
        font-weight: bold;
    }

    .logout-link {
        color: #ff6b6b !important;
    }

    .logout-link:hover {
        background: #3b2929 !important;
    }


    /* Mobile */

    @media (max-width: 768px) {

        body {
            padding-left: 0;
        }

        .sidebar {
            width: 250px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .mobile-navbar {
            display: flex !important;
        }

    }

    .mobile-navbar {
        display: none;
        height: 60px;
        background: #212529;
        color: white;
        align-items: center;
        padding: 0 15px;
    }

    .mobile-navbar button {
        background: transparent;
        border: none;
        color: white;
        font-size: 25px;
    }

</style>


<!-- Mobile Top Bar -->

<div class="mobile-navbar">

    <button
        type="button"
        onclick="toggleSidebar()"
    >

        <i class="bi bi-list"></i>

    </button>

    <strong class="ms-3">
        Bar Management System
    </strong>

</div>


<!-- Sidebar -->

<aside class="sidebar" id="sidebar">


    <!-- Brand -->

    <div class="sidebar-brand">

        <div style="font-size: 35px;">
            🏪
        </div>

        <h4>
            Bar Management
        </h4>

        <small class="text-secondary">
            Management System
        </small>

    </div>


    <!-- User -->

    <div class="sidebar-user">

        <strong>
            <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
        </strong>

        <br>

        <small>
            <?= htmlspecialchars(
                ucfirst($_SESSION['user_role'] ?? 'User')
            ) ?>
        </small>

    </div>


    <!-- Menu -->

    <div class="sidebar-menu">


        <div class="menu-title">
            Main Menu
        </div>


        <!-- Dashboard -->

        <a
            href="../admin/dashboard.php"
            class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
        >

            <i class="bi bi-speedometer2"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- Products -->

        <a
            href="../products/index.php"
            class="<?= $currentFolder === 'products' ? 'active' : '' ?>"
        >

            <i class="bi bi-box-seam"></i>

            <span>
                Products
            </span>

        </a>


        <!-- Inventory -->

        <a
            href="../inventory/index.php"
            class="<?= $currentFolder === 'inventory' ? 'active' : '' ?>"
        >

            <i class="bi bi-boxes"></i>

            <span>
                Inventory
            </span>

        </a>


        <!-- Sales -->

        <a
            href="../sales/index.php"
            class="<?= $currentFolder === 'sales' ? 'active' : '' ?>"
        >

            <i class="bi bi-cart-check"></i>

            <span>
                Sales / POS
            </span>

        </a>


        <!-- Expenses -->

        <a
            href="../expenses/index.php"
            class="<?= $currentFolder === 'expenses' ? 'active' : '' ?>"
        >

            <i class="bi bi-cash-stack"></i>

            <span>
                Expenses
            </span>

        </a>


        <div class="menu-title">
            Reports
        </div>


        <!-- Sales Report -->

        <a href="../reports/sales.php">

            <i class="bi bi-bar-chart"></i>

            <span>
                Sales Report
            </span>

        </a>


        <!-- Expense Report -->

        <a href="../reports/expenses.php">

            <i class="bi bi-receipt"></i>

            <span>
                Expense Report
            </span>

        </a>


        <!-- Profit Report -->

        <a href="../reports/profit.php">

            <i class="bi bi-graph-up-arrow"></i>

            <span>
                Profit Report
            </span>

        </a>


        <?php if (
            isset($_SESSION['user_role']) &&
            $_SESSION['user_role'] === 'admin'
        ): ?>

            <div class="menu-title">
                Administration
            </div>


            <!-- Users -->

            <a
                href="../admin/users.php"
                class="<?= $currentPage === 'users.php' ? 'active' : '' ?>"
            >

                <i class="bi bi-people"></i>

                <span>
                    Users
                </span>

            </a>

        <?php endif; ?>


        <div class="menu-title">
            Account
        </div>


        <!-- Logout -->

        <a
            href="../logout.php"
            class="logout-link"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>


    </div>

</aside>


<script>

function toggleSidebar() {

    const sidebar = document.getElementById('sidebar');

    sidebar.classList.toggle('show');

}

</script>
