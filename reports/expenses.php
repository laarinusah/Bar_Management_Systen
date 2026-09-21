<?php

require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole(['admin', 'manager']);

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

$error = '';

if (!empty($startDate) && !empty($endDate)) {

    if ($startDate > $endDate) {
        $temp = $startDate;
        $startDate = $endDate;
        $endDate = $temp;
    }

}

/*
|--------------------------------------------------------------------------
| Total Expenses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(amount), 0) AS total_expenses,
        COUNT(*) AS expense_count
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
");

$stmt->execute([
    $startDate,
    $endDate
]);

$summary = $stmt->fetch();

$totalExpenses = (float) ($summary['total_expenses'] ?? 0);
$expenseCount  = (int) ($summary['expense_count'] ?? 0);

$averageExpense = $expenseCount > 0
    ? $totalExpenses / $expenseCount
    : 0;

/*
|--------------------------------------------------------------------------
| Expense History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        description,
        amount,
        expense_date,
        created_at
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
    ORDER BY expense_date DESC, id DESC
");

$stmt->execute([
    $startDate,
    $endDate
]);

$expenses = $stmt->fetchAll();

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

    <title>Expense Report - Bar Management System</title>

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

        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
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

        .report-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .table th {
            white-space: nowrap;
        }

        .money {
            font-weight: 600;
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

            .table-responsive {
                font-size: 14px;
            }

        }

    </style>

</head>

<body>

<?php require_once '../includes/sidebar.php'; ?>

<div class="page-content">

    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="page-title mb-1">
                <i class="bi bi-receipt"></i>
                Expense Report
            </h2>

            <p class="text-muted mb-0">
                View and analyse business expenses.
            </p>

        </div>

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

        <!-- TOTAL EXPENSES -->

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


        <!-- NUMBER OF EXPENSES -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-1">
                                Number of Expenses
                            </p>

                            <h4 class="mb-0">
                                <?= number_format($expenseCount) ?>
                            </h4>

                        </div>

                        <div class="stat-icon bg-warning-subtle text-warning">

                            <i class="bi bi-list-check"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- AVERAGE EXPENSE -->

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-1">
                                Average Expense
                            </p>

                            <h4 class="mb-0">
                                <?= formatMoney($averageExpense) ?>
                            </h4>

                        </div>

                        <div class="stat-icon bg-info-subtle text-info">

                            <i class="bi bi-calculator"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- EXPENSE HISTORY -->

    <div class="card report-card">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0">

                <i class="bi bi-table"></i>

                Expense History

            </h5>

            <small class="text-muted">

                <?= htmlspecialchars($startDate) ?>

                to

                <?= htmlspecialchars($endDate) ?>

            </small>

        </div>


        <div class="card-body p-0">

            <?php if (count($expenses) > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="table-light">

                            <tr>

                                <th>#</th>

                                <th>Description</th>

                                <th>Amount</th>

                                <th>Expense Date</th>

                                <th>Recorded</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($expenses as $index => $expense): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $expense['description']
                                            ) ?>
                                        </strong>

                                    </td>

                                    <td class="money">

                                        <?= formatMoney(
                                            $expense['amount']
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $expense['expense_date']
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $expense['created_at']
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>


                        <tfoot class="table-light">

                            <tr>

                                <th colspan="2">
                                    Total
                                </th>

                                <th class="money">

                                    <?= formatMoney(
                                        $totalExpenses
                                    ) ?>

                                </th>

                                <th colspan="2"></th>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            <?php else: ?>

                <div class="text-center py-5">

                    <i
                        class="bi bi-receipt text-muted"
                        style="font-size: 50px;"
                    ></i>

                    <h5 class="mt-3">
                        No expenses found
                    </h5>

                    <p class="text-muted">
                        There are no expenses recorded
                        for the selected date range.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>