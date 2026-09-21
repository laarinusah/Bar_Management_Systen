<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin', 'manager']);

/*
|--------------------------------------------------------------------------
| Date Filters
|--------------------------------------------------------------------------
*/

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');


/*
|--------------------------------------------------------------------------
| Validate Dates
|--------------------------------------------------------------------------
*/

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-d');
}


/*
|--------------------------------------------------------------------------
| Make Sure Start Date Is Not After End Date
|--------------------------------------------------------------------------
*/

if ($startDate > $endDate) {

    $temporaryDate = $startDate;
    $startDate = $endDate;
    $endDate = $temporaryDate;
}


/*
|--------------------------------------------------------------------------
| Total Sales
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(total_amount), 0) AS total_sales,
        COUNT(*) AS total_transactions
    FROM sales
    WHERE DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$salesSummary = $stmt->fetch();


$totalSales = (float) ($salesSummary['total_sales'] ?? 0);
$totalTransactions = (int) ($salesSummary['total_transactions'] ?? 0);


/*
|--------------------------------------------------------------------------
| Average Sale
|--------------------------------------------------------------------------
*/

$averageSale = 0;

if ($totalTransactions > 0) {
    $averageSale = $totalSales / $totalTransactions;
}


/*
|--------------------------------------------------------------------------
| Sales By Payment Method
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        payment_method,
        COUNT(*) AS transactions,
        COALESCE(SUM(total_amount), 0) AS amount
    FROM sales
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY amount DESC
");

$stmt->execute([
    $startDate,
    $endDate
]);

$paymentMethods = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Sales History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        sales.id,
        sales.total_amount,
        sales.payment_method,
        sales.created_at,
        users.full_name
    FROM sales
    LEFT JOIN users
        ON sales.user_id = users.id
    WHERE DATE(sales.created_at) BETWEEN ? AND ?
    ORDER BY sales.id DESC
");

$stmt->execute([
    $startDate,
    $endDate
]);

$sales = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Format Currency
|--------------------------------------------------------------------------
*/

function formatMoney(float $amount): string
{
    return 'GHS ' . number_format($amount, 2);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Sales Report - Bar Management System
    </title>


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


    <style>

        body {
            background: #f5f6fa;
            overflow-x: hidden;
        }


        .main-content {
            min-height: 100vh;
        }


        .page-header {
            background: #ffffff;
            padding: 20px 25px;
            border-bottom: 1px solid #ddd;
        }


        .content {
            padding: 25px;
        }


        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
            height: 100%;
        }


        .stat-card .card-body {
            padding: 20px;
        }


        .stat-icon {
            font-size: 38px;
        }


        .report-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
        }


        .table th {
            white-space: nowrap;
        }


        .table td {
            vertical-align: middle;
        }


        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }


        .table {
            min-width: 700px;
        }


        .filter-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
        }


        .form-control {
            min-height: 44px;
        }


        .btn {
            min-height: 44px;
        }


        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 768px) {

            .page-header {
                padding: 15px;
            }


            .content {
                padding: 15px;
            }


            .page-header h2 {
                font-size: 22px;
            }


            .page-header p {
                font-size: 13px;
            }


            .stat-card .card-body {
                padding: 16px;
            }


            .stat-icon {
                font-size: 30px;
            }


            .stat-card h3 {
                font-size: 23px;
            }


            .card-header {
                padding: 14px;
            }


            .card-header h5 {
                font-size: 17px;
            }

        }


        @media (max-width: 576px) {

            .content {
                padding: 12px;
            }


            .page-header .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px;
            }


            .page-header .btn {
                width: 100%;
            }


            .stat-card h6 {
                font-size: 13px;
            }


            .stat-card h3 {
                font-size: 20px;
            }


            .stat-icon {
                font-size: 26px;
            }


            .filter-buttons {
                flex-direction: column;
            }


            .filter-buttons .btn {
                width: 100%;
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


    <!-- ======================================================
         PAGE HEADER
    ======================================================= -->

    <div class="page-header">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-bar-chart-line"></i>

                    Sales Report

                </h2>

                <p class="text-muted mb-0">

                    View and analyze sales performance.

                </p>

            </div>


            <a
                href="../sales/index.php"
                class="btn btn-primary"
            >

                <i class="bi bi-cart-check me-1"></i>

                Go to POS

            </a>

        </div>

    </div>


    <!-- ======================================================
         CONTENT
    ======================================================= -->

    <div class="content">


        <!-- ==================================================
             DATE FILTER
        =================================================== -->

        <div class="card filter-card mb-4">


            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-calendar-range me-2"></i>

                    Report Period

                </h5>

            </div>


            <div class="card-body">


                <form method="GET">

                    <div class="row g-3 align-items-end">


                        <!-- START DATE -->

                        <div class="col-md-4">

                            <label
                                for="start_date"
                                class="form-label fw-semibold"
                            >

                                Start Date

                            </label>

                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                class="form-control"
                                value="<?= htmlspecialchars($startDate) ?>"
                                required
                            >

                        </div>


                        <!-- END DATE -->

                        <div class="col-md-4">

                            <label
                                for="end_date"
                                class="form-label fw-semibold"
                            >

                                End Date

                            </label>

                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                class="form-control"
                                value="<?= htmlspecialchars($endDate) ?>"
                                required
                            >

                        </div>


                        <!-- BUTTONS -->

                        <div class="col-md-4">

                            <div class="d-flex gap-2 filter-buttons">

                                <button
                                    type="submit"
                                    class="btn btn-primary flex-grow-1"
                                >

                                    <i class="bi bi-search me-1"></i>

                                    Generate Report

                                </button>


                                <a
                                    href="sales.php"
                                    class="btn btn-secondary"
                                >

                                    <i class="bi bi-arrow-clockwise"></i>

                                </a>

                            </div>

                        </div>


                    </div>

                </form>


            </div>

        </div>


        <!-- ==================================================
             SALES STATISTICS
        =================================================== -->

        <div class="row g-3 mb-4">


            <!-- TOTAL SALES -->

            <div class="col-6 col-lg-4">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h6 class="text-muted mb-2">

                                    Total Sales

                                </h6>

                                <h3 class="mb-0">

                                    <?= formatMoney($totalSales) ?>

                                </h3>

                            </div>


                            <i class="bi bi-cash-stack stat-icon text-success"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TRANSACTIONS -->

            <div class="col-6 col-lg-4">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h6 class="text-muted mb-2">

                                    Transactions

                                </h6>

                                <h3 class="mb-0">

                                    <?= $totalTransactions ?>

                                </h3>

                            </div>


                            <i class="bi bi-receipt stat-icon text-primary"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- AVERAGE SALE -->

            <div class="col-12 col-lg-4">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h6 class="text-muted mb-2">

                                    Average Sale

                                </h6>

                                <h3 class="mb-0">

                                    <?= formatMoney($averageSale) ?>

                                </h3>

                            </div>


                            <i class="bi bi-graph-up stat-icon text-warning"></i>

                        </div>

                    </div>

                </div>

            </div>


        </div>


        <!-- ==================================================
             SALES BY PAYMENT METHOD
        =================================================== -->

        <div class="card report-card mb-4">


            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-credit-card me-2"></i>

                    Sales by Payment Method

                </h5>

            </div>


            <div class="card-body p-0">


                <div class="table-responsive">

                    <table class="table table-hover table-bordered mb-0">


                        <thead class="table-dark">

                            <tr>

                                <th>#</th>

                                <th>Payment Method</th>

                                <th>Transactions</th>

                                <th>Total Amount</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (count($paymentMethods) > 0): ?>


                            <?php foreach ($paymentMethods as $index => $payment): ?>


                                <tr>

                                    <td>

                                        <?= $index + 1 ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $payment['payment_method']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= (int) $payment['transactions'] ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= formatMoney(
                                                (float) $payment['amount']
                                            ) ?>

                                        </strong>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="4"
                                    class="text-center py-5 text-muted"
                                >

                                    <i class="bi bi-bar-chart fs-1 d-block mb-2"></i>

                                    No sales found for the selected period.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


            </div>

        </div>


        <!-- ==================================================
             SALES HISTORY
        =================================================== -->

        <div class="card report-card">


            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-clock-history me-2"></i>

                    Sales History

                </h5>

            </div>


            <div class="card-body p-0">


                <div class="table-responsive">

                    <table class="table table-hover table-bordered mb-0">


                        <thead class="table-dark">

                            <tr>

                                <th>#</th>

                                <th>Receipt ID</th>

                                <th>Cashier</th>

                                <th>Payment Method</th>

                                <th>Total Amount</th>

                                <th>Date & Time</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (count($sales) > 0): ?>


                            <?php foreach ($sales as $sale): ?>


                                <tr>


                                    <td>

                                        <?= $sale['id'] ?>

                                    </td>


                                    <td>

                                        <strong>

                                            #<?= $sale['id'] ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $sale['full_name'] ?? 'Unknown'
                                        ) ?>

                                    </td>


                                    <td>

                                        <span class="badge text-bg-primary">

                                            <?= htmlspecialchars(
                                                $sale['payment_method']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= formatMoney(
                                                (float) $sale['total_amount']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $sale['created_at']
                                        ) ?>

                                    </td>


                                    <td>

                                        <a
                                            href="../sales/receipt.php?id=<?= (int) $sale['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >

                                            <i class="bi bi-receipt me-1"></i>

                                            Receipt

                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center py-5 text-muted"
                                >

                                    <i class="bi bi-receipt fs-1 d-block mb-2"></i>

                                    No sales transactions found.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


            </div>

        </div>


    </div>

</div>


<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>