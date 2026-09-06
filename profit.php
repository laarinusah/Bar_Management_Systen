<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin', 'manager']);

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

if ($startDate > $endDate) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

/*
|--------------------------------------------------------------------------
| Total Sales
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) AS total_sales
    FROM sales
    WHERE DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$totalSales = (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Total Expenses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_expenses
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$totalExpenses = (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Profit Calculation
|--------------------------------------------------------------------------
*/

$netProfit = $totalSales - $totalExpenses;

$profitMargin = $totalSales > 0
    ? ($netProfit / $totalSales) * 100
    : 0;


/*
|--------------------------------------------------------------------------
| Transaction Count
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sales
    WHERE DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$totalTransactions = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function formatMoney($amount)
{
    return 'GHS ' . number_format((float) $amount, 2);
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
        Profit Report - Bar Management System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f8f9fa;
        }

        .page-content {
            padding: 25px;
        }

        .report-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;
        }

        .profit-value {
            font-size: 28px;
            font-weight: 700;
        }

        .profit-positive {
            color: #198754;
        }

        .profit-negative {
            color: #dc3545;
        }

        .summary-table th {
            width: 60%;
        }

        @media (max-width: 768px) {

            .page-content {
                padding: 15px;
            }

            .page-title {
                font-size: 22px;
            }

            .filter-button {
                width: 100%;
                margin-top: 10px;
            }

            .profit-value {
                font-size: 24px;
            }

        }

    </style>

</head>

<body>

<?php require_once '../includes/sidebar.php'; ?>

<div class="page-content">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        <h2 class="page-title mb-1">

            <i class="bi bi-graph-up-arrow"></i>

            Profit Report

        </h2>

        <p class="text-muted mb-0">

            Analyse sales, expenses and net profit.

        </p>

    </div>


    <!-- DATE FILTER -->

    <div class="card report-card mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3 align-items-end"
            >

                <div class="col-md-4">

                    <label class="form-label">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="<?= htmlspecialchars($startDate) ?>"
                        required
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="<?= htmlspecialchars($endDate) ?>"
                        required
                    >

                </div>


                <div class="col-md-4">

                    <button
                        type="submit"
                        class="btn btn-primary filter-button"
                    >

                        <i class="bi bi-funnel"></i>

                        Filter Report

                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- SUMMARY CARDS -->

    <div class="row g-4 mb-4">

        <!-- SALES -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-1">
                                Total Sales
                            </p>

                            <h4 class="mb-0">
                                <?= formatMoney($totalSales) ?>
                            </h4>

                        </div>

                        <div class="stat-icon bg-primary-subtle text-primary">

                            <i class="bi bi-cart-check"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- EXPENSES -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-1">
                                Total Expenses
                            </p>

                            <h4 class="mb-0">
                                <?= formatMoney($totalExpenses) ?>
                            </h4>

                        </div>

                        <div class="stat-icon bg-danger-subtle text-danger">

                            <i class="bi bi-cash-stack"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- NET PROFIT -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-1">
                                Net Profit
                            </p>

                            <h4 class="mb-0 profit-value
                                <?= $netProfit >= 0
                                    ? 'profit-positive'
                                    : 'profit-negative' ?>"
                            >

                                <?= formatMoney($netProfit) ?>

                            </h4>

                        </div>

                        <div class="stat-icon
                            <?= $netProfit >= 0
                                ? 'bg-success-subtle text-success'
                                : 'bg-danger-subtle text-danger' ?>"
                        >

                            <i class="bi
                                <?= $netProfit >= 0
                                    ? 'bi-graph-up-arrow'
                                    : 'bi-graph-down-arrow' ?>"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- PROFIT SUMMARY -->

    <div class="row g-4">

        <div class="col-lg-8">

            <div class="card report-card">

                <div class="card-header bg-white py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-bar-chart-line"></i>

                        Financial Summary

                    </h5>

                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table summary-table">

                            <tbody>

                                <tr>

                                    <th>
                                        Total Sales
                                    </th>

                                    <td class="text-end">
                                        <?= formatMoney($totalSales) ?>
                                    </td>

                                </tr>


                                <tr>

                                    <th>
                                        Total Expenses
                                    </th>

                                    <td class="text-end text-danger">
                                        <?= formatMoney($totalExpenses) ?>
                                    </td>

                                </tr>


                                <tr class="table-light">

                                    <th>
                                        Net Profit
                                    </th>

                                    <td class="text-end fw-bold
                                        <?= $netProfit >= 0
                                            ? 'text-success'
                                            : 'text-danger' ?>"
                                    >

                                        <?= formatMoney($netProfit) ?>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>


        <!-- PROFIT INFORMATION -->

        <div class="col-lg-4">

            <div class="card report-card">

                <div class="card-header bg-white py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-info-circle"></i>

                        Report Summary

                    </h5>

                </div>


                <div class="card-body">

                    <div class="mb-3">

                        <small class="text-muted">
                            Reporting Period
                        </small>

                        <div class="fw-bold">

                            <?= htmlspecialchars($startDate) ?>

                            to

                            <?= htmlspecialchars($endDate) ?>

                        </div>

                    </div>


                    <div class="mb-3">

                        <small class="text-muted">
                            Sales Transactions
                        </small>

                        <div class="fw-bold">

                            <?= number_format($totalTransactions) ?>

                        </div>

                    </div>


                    <div>

                        <small class="text-muted">
                            Profit Margin
                        </small>

                        <div class="fw-bold fs-4
                            <?= $netProfit >= 0
                                ? 'text-success'
                                : 'text-danger' ?>"
                        >

                            <?= number_format(
                                $profitMargin,
                                2
                            ) ?>%

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>