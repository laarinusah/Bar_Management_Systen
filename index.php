<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| Expense Statistics
|--------------------------------------------------------------------------
*/

$totalExpenses = 0;
$totalRecords = 0;
$todayExpenses = 0;
$monthExpenses = 0;

/*
|--------------------------------------------------------------------------
| Total Expenses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_records,
        COALESCE(SUM(amount), 0) AS total_expenses
    FROM expenses
");

$expenseStats = $stmt->fetch();

$totalRecords = (int) ($expenseStats['total_records'] ?? 0);
$totalExpenses = (float) ($expenseStats['total_expenses'] ?? 0);


/*
|--------------------------------------------------------------------------
| Today's Expenses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM expenses
    WHERE expense_date = CURDATE()
");

$todayExpenses = (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Current Month Expenses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM expenses
    WHERE YEAR(expense_date) = YEAR(CURDATE())
      AND MONTH(expense_date) = MONTH(CURDATE())
");

$monthExpenses = (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Expense History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        description,
        amount,
        expense_date,
        created_at
    FROM expenses
    ORDER BY expense_date DESC, id DESC
");

$expenses = $stmt->fetchAll();

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
        Expenses - Bar Management System
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

        .expense-card {
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

        .action-button {
            white-space: nowrap;
        }

        .amount {
            font-weight: 600;
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

            .page-header .d-flex {
                align-items: flex-start !important;
                gap: 15px;
            }

            .page-header h2 {
                font-size: 22px;
            }

            .page-header p {
                font-size: 13px;
            }

            .page-header .btn {
                white-space: nowrap;
            }

            .stat-card .card-body {
                padding: 16px;
            }

            .stat-icon {
                font-size: 30px;
            }

            .stat-card h3 {
                font-size: 22px;
            }

            .card-header {
                padding: 14px;
            }

            .card-header h5 {
                font-size: 17px;
            }

            .table {
                min-width: 700px;
            }

        }


        @media (max-width: 576px) {

            .page-header .d-flex {
                flex-direction: column;
            }

            .page-header .btn {
                width: 100%;
            }

            .content {
                padding: 12px;
            }

            .stat-card h6 {
                font-size: 13px;
            }

            .stat-card h3 {
                font-size: 19px;
            }

            .stat-icon {
                font-size: 27px;
            }

            .table {
                font-size: 14px;
            }

            .action-button {
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

                    <i class="bi bi-cash-stack"></i>

                    Expenses

                </h2>

                <p class="text-muted mb-0">

                    Record and monitor business expenses.

                </p>

            </div>


            <?php if (
                $_SESSION['user_role'] === 'admin' ||
                $_SESSION['user_role'] === 'manager'
            ): ?>

                <a
                    href="add.php"
                    class="btn btn-success"
                >

                    <i class="bi bi-plus-circle me-1"></i>

                    Add Expense

                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- ======================================================
         CONTENT
    ======================================================= -->

    <div class="content">


        <!-- ==================================================
             EXPENSE STATISTICS
        =================================================== -->

        <div class="row g-3 mb-4">


            <!-- TOTAL EXPENSES -->

            <div class="col-6 col-lg-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h6 class="text-muted mb-2">
                                    Total Expenses
                                </h6>

                                <h3 class="mb-0">

                                    GHS
                                    <?= number_format($totalExpenses, 2) ?>

                                </h3>

                            </div>

                            <i class="bi bi-cash-stack stat-icon text-danger"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TOTAL RECORDS -->

            <div class="col-6 col-lg-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h6 class="text-muted mb-2">
                                    Expense Records
                                </h6>

                                <h3 class="mb-0">

                                    <?= $totalRecords ?>

                                </h3>

                            </div>

                            <i class="bi bi-receipt stat-icon text-primary"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TODAY -->

            <div class="col-6 col-lg-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h6 class="text-muted mb-2">
                                    Today's Expenses
                                </h6>

                                <h3 class="mb-0">

                                    GHS
                                    <?= number_format($todayExpenses, 2) ?>

                                </h3>

                            </div>

                            <i class="bi bi-calendar-day stat-icon text-warning"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- THIS MONTH -->

            <div class="col-6 col-lg-3">

                <div class="card stat-card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h6 class="text-muted mb-2">
                                    This Month
                                </h6>

                                <h3 class="mb-0">

                                    GHS
                                    <?= number_format($monthExpenses, 2) ?>

                                </h3>

                            </div>

                            <i class="bi bi-calendar-month stat-icon text-success"></i>

                        </div>

                    </div>

                </div>

            </div>


        </div>


        <!-- ==================================================
             EXPENSE HISTORY
        =================================================== -->

        <div class="card expense-card">


            <!-- CARD HEADER -->

            <div class="card-header bg-white">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">

                        <i class="bi bi-list-ul me-2"></i>

                        Expense History

                    </h5>

                    <span class="badge text-bg-secondary">

                        <?= $totalRecords ?> Records

                    </span>

                </div>

            </div>


            <!-- CARD BODY -->

            <div class="card-body p-0">


                <div class="table-responsive">


                    <table class="table table-hover table-bordered mb-0">


                        <thead class="table-dark">

                            <tr>

                                <th>#</th>

                                <th>Description</th>

                                <th>Amount</th>

                                <th>Expense Date</th>

                                <th>Recorded</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (count($expenses) > 0): ?>


                            <?php foreach ($expenses as $expense): ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?= (int) $expense['id'] ?>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $expense['description']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- AMOUNT -->

                                    <td>

                                        <span class="amount text-danger">

                                            GHS
                                            <?= number_format(
                                                (float) $expense['amount'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- EXPENSE DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $expense['expense_date']
                                        ) ?>

                                    </td>


                                    <!-- CREATED AT -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $expense['created_at']
                                        ) ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>


                                        <?php if (
                                            $_SESSION['user_role'] === 'admin'
                                        ): ?>


                                            <a
                                                href="delete.php?id=<?= (int) $expense['id'] ?>"
                                                class="btn btn-danger btn-sm action-button"
                                                onclick="return confirm('Are you sure you want to delete this expense?');"
                                            >

                                                <i class="bi bi-trash me-1"></i>

                                                Delete

                                            </a>


                                        <?php else: ?>


                                            <span class="text-muted">

                                                View Only

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center py-5"
                                >

                                    <i
                                        class="bi bi-receipt fs-1 text-muted"
                                    ></i>


                                    <p class="mt-3 mb-1">

                                        <strong>
                                            No expenses recorded yet.
                                        </strong>

                                    </p>


                                    <p class="text-muted mb-3">

                                        Start by adding your first expense.

                                    </p>


                                    <?php if (
                                        $_SESSION['user_role'] === 'admin' ||
                                        $_SESSION['user_role'] === 'manager'
                                    ): ?>


                                        <a
                                            href="add.php"
                                            class="btn btn-success"
                                        >

                                            <i class="bi bi-plus-circle me-1"></i>

                                            Add Expense

                                        </a>


                                    <?php endif; ?>


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