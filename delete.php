<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin']);

$error = '';

/*
|--------------------------------------------------------------------------
| Get Expense ID
|--------------------------------------------------------------------------
*/

$expenseId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$expenseId) {

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Check Expense
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        description,
        amount,
        expense_date
    FROM expenses
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$expenseId]);

$expense = $stmt->fetch();

if (!$expense) {

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Expense
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        DELETE FROM expenses
        WHERE id = ?
    ");

    $stmt->execute([$expenseId]);


    header('Location: index.php?deleted=1');
    exit;


} catch (PDOException $e) {

    $error = 'Unable to delete expense.';

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
        Delete Expense - Bar Management System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">


<?php

require_once '../includes/sidebar.php';

?>


<div class="main-content">


    <div class="container-fluid p-4">


        <div class="alert alert-danger">

            <h4>

                <i class="bi bi-exclamation-triangle"></i>

                Unable to Delete Expense

            </h4>

            <p class="mb-3">

                <?= htmlspecialchars($error) ?>

            </p>

            <a
                href="index.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Expenses

            </a>

        </div>


    </div>

</div>


</body>

</html>