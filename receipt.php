<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();


/*
|--------------------------------------------------------------------------
| Get Sale ID
|--------------------------------------------------------------------------
*/

$saleId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$saleId || $saleId <= 0) {

    http_response_code(400);

    die("Invalid sale ID.");
}


/*
|--------------------------------------------------------------------------
| Get Current User
|--------------------------------------------------------------------------
*/

$userRole = $_SESSION['user_role'] ?? '';
$userId   = (int) ($_SESSION['user_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| Get Sale
|--------------------------------------------------------------------------
|
| Admin and Manager:
|   Can view any sale receipt.
|
| Cashier:
|   Can only view receipts for sales they recorded.
|
|--------------------------------------------------------------------------
*/

if ($userRole === 'admin' || $userRole === 'manager') {

    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.total_amount,
            s.payment_method,
            s.created_at,
            u.full_name
        FROM sales s
        LEFT JOIN users u
            ON s.user_id = u.id
        WHERE s.id = ?
    ");

    $stmt->execute([
        $saleId
    ]);

} else {

    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.total_amount,
            s.payment_method,
            s.created_at,
            u.full_name
        FROM sales s
        LEFT JOIN users u
            ON s.user_id = u.id
        WHERE s.id = ?
        AND s.user_id = ?
    ");

    $stmt->execute([
        $saleId,
        $userId
    ]);
}


$sale = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Sale Not Found
|--------------------------------------------------------------------------
*/

if (!$sale) {

    http_response_code(404);

    die("
        <!DOCTYPE html>
        <html lang='en'>

        <head>

            <meta charset='UTF-8'>

            <meta
                name='viewport'
                content='width=device-width, initial-scale=1.0'
            >

            <title>Sale Not Found</title>

            <link
                href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
                rel='stylesheet'
            >

        </head>

        <body class='bg-light'>

            <div class='container py-5'>

                <div class='card shadow-sm mx-auto'
                     style='max-width: 500px;'>

                    <div class='card-body text-center p-5'>

                        <div class='display-4 mb-3'>
                            🔒
                        </div>

                        <h3 class='mb-3'>
                            Sale Not Found
                        </h3>

                        <p class='text-muted'>
                            The requested sale does not exist,
                            or you do not have permission to view it.
                        </p>

                        <a
                            href='index.php'
                            class='btn btn-primary'
                        >
                            Back to Sales
                        </a>

                    </div>

                </div>

            </div>

        </body>

        </html>
    ");
}


/*
|--------------------------------------------------------------------------
| Get Sale Items
|--------------------------------------------------------------------------
*/

$stmtItems = $pdo->prepare("
    SELECT
        si.id,
        si.quantity,
        si.price,
        si.subtotal,
        p.product_name
    FROM sale_items si
    LEFT JOIN products p
        ON si.product_id = p.id
    WHERE si.sale_id = ?
    ORDER BY si.id ASC
");

$stmtItems->execute([
    $saleId
]);

$items = $stmtItems->fetchAll();


/*
|--------------------------------------------------------------------------
| Currency Formatter
|--------------------------------------------------------------------------
*/

function formatCurrency($amount)
{
    return 'GH₵ ' . number_format(
        (float) $amount,
        2
    );
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
        Receipt #<?= htmlspecialchars($sale['id']) ?>
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        body {
            background: #f8f9fa;
        }


        .receipt-container {

            max-width: 700px;

            margin: 30px auto;

            background: #ffffff;

            padding: 30px;

            border-radius: 10px;

            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.08);
        }


        .receipt-header {

            text-align: center;

            margin-bottom: 25px;
        }


        .receipt-header h2 {

            margin-bottom: 5px;

            font-weight: 700;
        }


        .receipt-header p {

            margin-bottom: 3px;

            color: #6c757d;
        }


        .receipt-info {

            border-top: 1px dashed #adb5bd;

            border-bottom: 1px dashed #adb5bd;

            padding: 15px 0;

            margin-bottom: 20px;
        }


        .receipt-info-row {

            display: flex;

            justify-content: space-between;

            margin-bottom: 7px;
        }


        .receipt-info-row:last-child {

            margin-bottom: 0;
        }


        .receipt-items th {

            white-space: nowrap;
        }


        .receipt-total {

            font-size: 22px;

            font-weight: 700;
        }


        .receipt-footer {

            text-align: center;

            margin-top: 30px;

            padding-top: 20px;

            border-top: 1px dashed #adb5bd;

            color: #6c757d;
        }


        .receipt-actions {

            max-width: 700px;

            margin: 0 auto 30px;

            display: flex;

            gap: 10px;

            justify-content: center;
        }


        @media print {

            body {

                background: #ffffff;
            }


            .receipt-container {

                box-shadow: none;

                margin: 0;

                max-width: 100%;

                padding: 10px;
            }


            .receipt-actions {

                display: none !important;
            }


            .no-print {

                display: none !important;
            }

        }


        @media (max-width: 576px) {

            .receipt-container {

                margin: 10px;

                padding: 20px;
            }


            .receipt-actions {

                margin: 10px;

                flex-direction: column;
            }


            .receipt-actions .btn {

                width: 100%;
            }


            .receipt-info-row {

                font-size: 14px;
            }

        }

    </style>

</head>


<body>


<div class="receipt-container">


    <!-- Receipt Header -->

    <div class="receipt-header">

        <div
            style="font-size: 40px;"
        >
            🏪
        </div>

        <h2>
            Bar Management System
        </h2>

        <p>
            Sales Receipt
        </p>

        <p>
            Receipt #<?= htmlspecialchars($sale['id']) ?>
        </p>

    </div>


    <!-- Sale Information -->

    <div class="receipt-info">

        <div class="receipt-info-row">

            <span>
                <strong>Date:</strong>
            </span>

            <span>
                <?= htmlspecialchars(
                    date(
                        'd M Y, h:i A',
                        strtotime($sale['created_at'])
                    )
                ) ?>
            </span>

        </div>


        <div class="receipt-info-row">

            <span>
                <strong>Cashier:</strong>
            </span>

            <span>
                <?= htmlspecialchars(
                    $sale['full_name'] ?? 'Unknown'
                ) ?>
            </span>

        </div>


        <div class="receipt-info-row">

            <span>
                <strong>Payment:</strong>
            </span>

            <span>
                <?= htmlspecialchars(
                    $sale['payment_method']
                ) ?>
            </span>

        </div>

    </div>


    <!-- Sale Items -->

    <div class="table-responsive">

        <table class="table receipt-items">

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Product
                    </th>

                    <th class="text-center">
                        Qty
                    </th>

                    <th class="text-end">
                        Price
                    </th>

                    <th class="text-end">
                        Subtotal
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (empty($items)): ?>

                    <tr>

                        <td
                            colspan="5"
                            class="text-center text-muted"
                        >
                            No items found.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach (
                        $items as $index => $item
                    ): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $item['product_name']
                                    ?? 'Deleted Product'
                                ) ?>
                            </td>

                            <td class="text-center">
                                <?= (int) $item['quantity'] ?>
                            </td>

                            <td class="text-end">
                                <?= formatCurrency(
                                    $item['price']
                                ) ?>
                            </td>

                            <td class="text-end">
                                <?= formatCurrency(
                                    $item['subtotal']
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>


            <tfoot>

                <tr>

                    <td
                        colspan="4"
                        class="text-end"
                    >
                        <strong>
                            Total
                        </strong>
                    </td>

                    <td class="text-end receipt-total">

                        <?= formatCurrency(
                            $sale['total_amount']
                        ) ?>

                    </td>

                </tr>

            </tfoot>

        </table>

    </div>


    <!-- Footer -->

    <div class="receipt-footer">

        <p class="mb-1">
            Thank you for your business.
        </p>

        <small>
            Receipt #<?= htmlspecialchars($sale['id']) ?>
        </small>

    </div>

</div>


<!-- Action Buttons -->

<div class="receipt-actions no-print">

    <button
        type="button"
        class="btn btn-primary"
        onclick="window.print()"
    >

        <i class="bi bi-printer"></i>

        Print Receipt

    </button>


    <a
        href="index.php"
        class="btn btn-secondary"
    >

        <i class="bi bi-arrow-left"></i>

        Back to Sales

    </a>

</div>


<!-- Main JavaScript -->

<script src="../assets/js/app.js"></script>


</body>

</html>