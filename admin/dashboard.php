<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Total products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$totalProducts = (int) $stmt->fetchColumn();


// Today's sales
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM sales
    WHERE DATE(created_at) = CURDATE()
");

$todaySales = (float) $stmt->fetchColumn();


// Today's expenses
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM expenses
    WHERE expense_date = CURDATE()
");

$todayExpenses = (float) $stmt->fetchColumn();


// Today's profit
$todayProfit = $todaySales - $todayExpenses;


// Low-stock products
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE quantity <= minimum_stock
");

$lowStock = (int) $stmt->fetchColumn();


// Total products in stock
$stmt = $pdo->query("
    SELECT COALESCE(SUM(quantity), 0)
    FROM products
");

$totalStock = (int) $stmt->fetchColumn();


// Recent sales
$stmt = $pdo->query("
    SELECT
        sales.id,
        sales.total_amount,
        sales.payment_method,
        sales.created_at,
        users.full_name
    FROM sales
    LEFT JOIN users
        ON sales.user_id = users.id
    ORDER BY sales.id DESC
    LIMIT 5
");

$recentSales = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard - Bar Management System</title>


    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- Main Application CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        body {
            background: #f5f6fa;
        }

        .topbar {
            background: #ffffff;
            padding: 15px 25px;
            border-bottom: 1px solid #ddd;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
        }

        .stat-icon {
            font-size: 32px;
        }

        .content {
            padding: 25px;
        }

        .welcome-text {
            font-size: 14px;
        }

        .table th {
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .role-badge {
            font-size: 12px;
        }

        @media (max-width: 768px) {

            .topbar {
                padding: 12px 15px;
            }

            .content {
                padding: 15px;
            }

            .topbar h4 {
                font-size: 18px;
            }

        }

    </style>

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| Reusable Sidebar
|--------------------------------------------------------------------------
*/

require_once '../includes/sidebar.php';

?>


<!-- ==========================================================
     MAIN CONTENT
========================================================== -->

<div class="main-content">


    <!-- TOP BAR -->

    <div class="topbar d-flex justify-content-between align-items-center">

        <div>

            <h4 class="mb-0">
                Dashboard
            </h4>

            <small class="text-muted welcome-text">

                Welcome back,

                <strong>
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                </strong>

            </small>

        </div>


        <div>

            <span class="badge bg-primary role-badge">

                <?= htmlspecialchars(
                    ucfirst($_SESSION['user_role'] ?? 'User')
                ) ?>

            </span>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <!-- ==================================================
             STATISTICS CARDS
        ================================================== -->

        <div class="row g-4">


            <!-- TODAY'S SALES -->

            <div class="col-md-6 col-xl-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <h6 class="text-muted">
                                    Today's Sales
                                </h6>

                                <h3 class="mb-0">
                                    GH₵ <?= number_format($todaySales, 2) ?>
                                </h3>

                            </div>

                            <div class="stat-icon text-primary">

                                <i class="bi bi-cash-coin"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TODAY'S EXPENSES -->

            <div class="col-md-6 col-xl-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <h6 class="text-muted">
                                    Today's Expenses
                                </h6>

                                <h3 class="mb-0">
                                    GH₵ <?= number_format($todayExpenses, 2) ?>
                                </h3>

                            </div>

                            <div class="stat-icon text-danger">

                                <i class="bi bi-wallet2"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TODAY'S PROFIT -->

            <div class="col-md-6 col-xl-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <h6 class="text-muted">
                                    Today's Profit
                                </h6>

                                <h3 class="mb-0">
                                    GH₵ <?= number_format($todayProfit, 2) ?>
                                </h3>

                            </div>

                            <div class="stat-icon text-success">

                                <i class="bi bi-graph-up-arrow"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TOTAL PRODUCTS -->

            <div class="col-md-6 col-xl-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <h6 class="text-muted">
                                    Total Products
                                </h6>

                                <h3 class="mb-0">
                                    <?= $totalProducts ?>
                                </h3>

                            </div>

                            <div class="stat-icon text-warning">

                                <i class="bi bi-box-seam"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
             SECOND ROW
        ================================================== -->

        <div class="row g-4 mt-1">


            <!-- INVENTORY STATUS -->

            <div class="col-md-6">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <h5>

                            <i class="bi bi-boxes me-2"></i>

                            Inventory Status

                        </h5>

                        <hr>


                        <p>

                            Total items in stock:

                            <strong>
                                <?= $totalStock ?>
                            </strong>

                        </p>


                        <p>

                            Low-stock products:

                            <strong class="text-danger">

                                <?= $lowStock ?>

                            </strong>

                        </p>


                        <a
                            href="../inventory/index.php"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-boxes me-1"></i>

                            View Inventory

                        </a>

                    </div>

                </div>

            </div>


            <!-- QUICK ACTIONS -->

            <div class="col-md-6">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <h5>

                            <i class="bi bi-lightning-charge me-2"></i>

                            Quick Actions

                        </h5>

                        <hr>


                        <div class="d-flex gap-2 flex-wrap">


                            <!-- NEW SALE -->

                            <a
                                href="../sales/index.php"
                                class="btn btn-success"
                            >

                                <i class="bi bi-cart me-1"></i>

                                New Sale

                            </a>


                            <!-- ADD PRODUCT -->

                            <?php if (
                                isset($_SESSION['user_role']) &&
                                in_array(
                                    $_SESSION['user_role'],
                                    ['admin', 'manager'],
                                    true
                                )
                            ): ?>

                                <a
                                    href="../products/add.php"
                                    class="btn btn-primary"
                                >

                                    <i class="bi bi-plus-circle me-1"></i>

                                    Add Product

                                </a>

                            <?php endif; ?>


                            <!-- ADD EXPENSE -->

                            <a
                                href="../expenses/add.php"
                                class="btn btn-warning"
                            >

                                <i class="bi bi-cash me-1"></i>

                                Add Expense

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
             RECENT SALES
        ================================================== -->

        <div class="card stat-card mt-4">

            <div class="card-body">


                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">

                        <i class="bi bi-receipt me-2"></i>

                        Recent Sales

                    </h5>


                    <a
                        href="../sales/index.php"
                        class="btn btn-sm btn-primary"
                    >

                        View All

                    </a>

                </div>


                <hr>


                <div class="table-responsive">

                    <table class="table table-hover">

                        <thead>

                            <tr>

                                <th>
                                    Sale #
                                </th>

                                <th>
                                    Cashier
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($recentSales)): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >

                                    <i class="bi bi-receipt fs-3 d-block mb-2"></i>

                                    No sales recorded yet.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($recentSales as $sale): ?>

                                <tr>


                                    <!-- SALE NUMBER -->

                                    <td>

                                        <strong>

                                            #<?= (int) $sale['id'] ?>

                                        </strong>

                                    </td>


                                    <!-- CASHIER -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $sale['full_name'] ?? 'Unknown'
                                        ) ?>

                                    </td>


                                    <!-- AMOUNT -->

                                    <td>

                                        <strong>

                                            GH₵
                                            <?= number_format(
                                                (float) $sale['total_amount'],
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- PAYMENT METHOD -->

                                    <td>

                                        <span class="badge bg-light text-dark">

                                            <?= htmlspecialchars(
                                                $sale['payment_method']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $sale['created_at']
                                        ) ?>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    </div>

</div>


<!-- ==========================================================
     BOOTSTRAP JAVASCRIPT
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- Main Application JavaScript -->

<script src="../assets/js/app.js"></script>


</body>

</html>