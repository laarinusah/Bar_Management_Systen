<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin', 'manager']);

$error = '';
$success = '';

$description = '';
$amount = '';
$expenseDate = date('Y-m-d');


/*
|--------------------------------------------------------------------------
| Process Expense
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $description = trim($_POST['description'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($description === '') {

        $error = 'Expense description is required.';

    } elseif ($amount === '' || !is_numeric($amount) || $amount <= 0) {

        $error = 'Please enter a valid expense amount greater than zero.';

    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate)
    ) {

        $error = 'Please enter a valid expense date.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Insert Expense
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO expenses
                (
                    description,
                    amount,
                    expense_date
                )
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $description,
                $amount,
                $expenseDate
            ]);


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $success = 'Expense recorded successfully.';

            /*
            |--------------------------------------------------------------------------
            | Clear Form
            |--------------------------------------------------------------------------
            */

            $description = '';
            $amount = '';
            $expenseDate = date('Y-m-d');


        } catch (PDOException $e) {

            $error = 'Unable to record expense: ' . $e->getMessage();

        }

    }

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
        Add Expense - Bar Management System
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

        .expense-card {
            max-width: 850px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
        }

        .form-label {
            font-weight: 600;
        }

        .form-control,
        .form-select {
            min-height: 46px;
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

            .expense-card {
                width: 100%;
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

            .card-body {
                padding: 18px !important;
            }

            .form-control,
            .form-select {
                min-height: 48px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
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

                    <i class="bi bi-plus-circle"></i>

                    Add Expense

                </h2>

                <p class="text-muted mb-0">

                    Record a new business expense.

                </p>

            </div>


            <a
                href="index.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Expenses

            </a>

        </div>

    </div>


    <!-- ======================================================
         CONTENT
    ======================================================= -->

    <div class="content">


        <div class="card expense-card">


            <!-- CARD HEADER -->

            <div class="card-header bg-danger text-white">

                <h5 class="mb-0">

                    <i class="bi bi-cash-stack me-2"></i>

                    Expense Information

                </h5>

            </div>


            <!-- CARD BODY -->

            <div class="card-body p-4">


                <!-- ERROR -->

                <?php if ($error !== ''): ?>

                    <div class="alert alert-danger">

                        <i class="bi bi-exclamation-triangle me-1"></i>

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <!-- SUCCESS -->

                <?php if ($success !== ''): ?>

                    <div class="alert alert-success">

                        <i class="bi bi-check-circle me-1"></i>

                        <?= htmlspecialchars($success) ?>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <form method="POST">


                    <!-- DESCRIPTION -->

                    <div class="mb-4">

                        <label
                            for="description"
                            class="form-label"
                        >

                            Expense Description

                        </label>

                        <input
                            type="text"
                            name="description"
                            id="description"
                            class="form-control"
                            value="<?= htmlspecialchars($description) ?>"
                            placeholder="Example: Electricity bill"
                            maxlength="255"
                            required
                        >

                        <div class="form-text">

                            Enter a clear description of the expense.

                        </div>

                    </div>


                    <!-- AMOUNT -->

                    <div class="mb-4">

                        <label
                            for="amount"
                            class="form-label"
                        >

                            Amount (GHS)

                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                GHS
                            </span>

                            <input
                                type="number"
                                name="amount"
                                id="amount"
                                class="form-control"
                                value="<?= htmlspecialchars($amount) ?>"
                                placeholder="0.00"
                                step="0.01"
                                min="0.01"
                                required
                            >

                        </div>

                    </div>


                    <!-- EXPENSE DATE -->

                    <div class="mb-4">

                        <label
                            for="expense_date"
                            class="form-label"
                        >

                            Expense Date

                        </label>

                        <input
                            type="date"
                            name="expense_date"
                            id="expense_date"
                            class="form-control"
                            value="<?= htmlspecialchars($expenseDate) ?>"
                            required
                        >

                    </div>


                    <hr>


                    <!-- BUTTONS -->

                    <div class="d-flex gap-2 form-actions">


                        <button
                            type="submit"
                            class="btn btn-success"
                        >

                            <i class="bi bi-save me-1"></i>

                            Save Expense

                        </button>


                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >

                            <i class="bi bi-x-circle me-1"></i>

                            Cancel

                        </a>


                    </div>


                </form>


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