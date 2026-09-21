<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin', 'manager']);


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));

}

$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';


/*
|--------------------------------------------------------------------------
| Process Stock-In
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token'] ?? '';

    if (
        empty($submittedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $submittedToken
        )
    ) {

        $error =
            'Invalid security token. Please refresh the page and try again.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | Get Product ID
        |--------------------------------------------------------------------------
        */

        $productId =
            filter_input(
                INPUT_POST,
                'product_id',
                FILTER_VALIDATE_INT
            );


        /*
        |--------------------------------------------------------------------------
        | Get Quantity
        |--------------------------------------------------------------------------
        */

        $quantity =
            filter_input(
                INPUT_POST,
                'quantity',
                FILTER_VALIDATE_INT
            );


        /*
        |--------------------------------------------------------------------------
        | Get Description
        |--------------------------------------------------------------------------
        */

        $description =
            trim(
                $_POST['description'] ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Validate Description Length
        |--------------------------------------------------------------------------
        */

        if (mb_strlen($description) > 255) {

            $error =
                'Description must not exceed 255 characters.';

        }


        /*
        |--------------------------------------------------------------------------
        | Validate Product
        |--------------------------------------------------------------------------
        */

        elseif (
            $productId === false ||
            $productId === null ||
            $productId <= 0
        ) {

            $error =
                'Please select a valid product.';

        }


        /*
        |--------------------------------------------------------------------------
        | Validate Quantity
        |--------------------------------------------------------------------------
        */

        elseif (
            $quantity === false ||
            $quantity === null ||
            $quantity <= 0
        ) {

            $error =
                'Stock quantity must be greater than zero.';

        }


        /*
        |--------------------------------------------------------------------------
        | Maximum Quantity Protection
        |--------------------------------------------------------------------------
        */

        elseif ($quantity > 1000000) {

            $error =
                'The stock quantity is too large.';

        }


        /*
        |--------------------------------------------------------------------------
        | Process Database Transaction
        |--------------------------------------------------------------------------
        */

        else {

            try {


                /*
                |--------------------------------------------------------------------------
                | Start Transaction
                |--------------------------------------------------------------------------
                */

                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | Check and Lock Product
                |--------------------------------------------------------------------------
                |
                | FOR UPDATE prevents another transaction from changing
                | this product at the same time.
                |
                */

                $stmt =
                    $pdo->prepare("
                        SELECT
                            id,
                            product_name,
                            quantity
                        FROM products
                        WHERE id = ?
                        LIMIT 1
                        FOR UPDATE
                    ");


                $stmt->execute([
                    $productId
                ]);


                $product =
                    $stmt->fetch();


                /*
                |--------------------------------------------------------------------------
                | Product Exists?
                |--------------------------------------------------------------------------
                */

                if (!$product) {

                    throw new Exception(
                        'Product not found.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Calculate New Stock
                |--------------------------------------------------------------------------
                */

                $currentStock =
                    (int) $product['quantity'];


                $newStock =
                    $currentStock + $quantity;


                /*
                |--------------------------------------------------------------------------
                | Prevent Integer Overflow
                |--------------------------------------------------------------------------
                */

                if ($newStock > 2147483647) {

                    throw new Exception(
                        'The resulting stock quantity is too large.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Increase Product Quantity
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $pdo->prepare("
                        UPDATE products
                        SET quantity = ?
                        WHERE id = ?
                    ");


                $stmt->execute([
                    $newStock,
                    $productId
                ]);


                /*
                |--------------------------------------------------------------------------
                | Record Stock Movement
                |--------------------------------------------------------------------------
                */

                $movementDescription =
                    $description !== ''
                        ? $description
                        : 'Stock added';


                $stmt =
                    $pdo->prepare("
                        INSERT INTO stock_movements
                        (
                            product_id,
                            movement_type,
                            quantity,
                            description
                        )
                        VALUES
                        (
                            ?,
                            'stock_in',
                            ?,
                            ?
                        )
                    ");


                $stmt->execute([
                    $productId,
                    $quantity,
                    $movementDescription
                ]);


                /*
                |--------------------------------------------------------------------------
                | Commit Transaction
                |--------------------------------------------------------------------------
                */

                $pdo->commit();


                /*
                |--------------------------------------------------------------------------
                | Success Message
                |--------------------------------------------------------------------------
                */

                $success =
                    $quantity .
                    ' units of ' .
                    $product['product_name'] .
                    ' added successfully. New stock: ' .
                    $newStock;


                /*
                |--------------------------------------------------------------------------
                | Clear Form Values
                |--------------------------------------------------------------------------
                */

                $_POST['product_id'] = '';
                $_POST['quantity'] = '';
                $_POST['description'] = '';


                /*
                |--------------------------------------------------------------------------
                | Regenerate CSRF Token
                |--------------------------------------------------------------------------
                |
                | This provides an additional layer of protection after
                | a successful state-changing request.
                |
                */

                $_SESSION['csrf_token'] =
                    bin2hex(random_bytes(32));

                $csrfToken =
                    $_SESSION['csrf_token'];


            } catch (Throwable $e) {


                /*
                |--------------------------------------------------------------------------
                | Rollback Transaction
                |--------------------------------------------------------------------------
                */

                if ($pdo->inTransaction()) {

                    $pdo->rollBack();

                }


                /*
                |--------------------------------------------------------------------------
                | Error Message
                |--------------------------------------------------------------------------
                */

                $error =
                    'Unable to add stock: ' .
                    $e->getMessage();

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| Get Products
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query("
        SELECT
            id,
            product_name,
            quantity
        FROM products
        ORDER BY product_name ASC
    ");


$products =
    $stmt->fetchAll();

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
        Stock In - Bar Management System
    </title>


    <!-- Bootstrap CSS -->

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


        /*
        |--------------------------------------------------------------------------
        | Main Content
        |--------------------------------------------------------------------------
        */

        .main-content {

            min-height: 100vh;

        }


        /*
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        */

        .page-header {

            background: #ffffff;

            padding: 20px 25px;

            border-bottom: 1px solid #ddd;

        }


        /*
        |--------------------------------------------------------------------------
        | Page Content
        |--------------------------------------------------------------------------
        */

        .content {

            padding: 25px;

        }


        /*
        |--------------------------------------------------------------------------
        | Stock Card
        |--------------------------------------------------------------------------
        */

        .stock-card {

            border: none;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        /*
        |--------------------------------------------------------------------------
        | Responsive Design
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


        <div
            class="d-flex
                   justify-content-between
                   align-items-center"
        >


            <div>

                <h2 class="mb-1">

                    <i
                        class="bi bi-box-arrow-in-down"
                    ></i>

                    Stock In

                </h2>


                <p class="text-muted mb-0">

                    Record new stock received into inventory.

                </p>

            </div>


            <a
                href="index.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Inventory

            </a>


        </div>


    </div>


    <!-- ======================================================
         PAGE CONTENT
    ======================================================= -->

    <div class="content">


        <!-- ==================================================
             ERROR MESSAGE
        =================================================== -->

        <?php if ($error !== ''): ?>

            <div
                class="alert alert-danger"
                role="alert"
            >

                <i
                    class="bi
                           bi-exclamation-triangle
                           me-2"
                ></i>

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             SUCCESS MESSAGE
        =================================================== -->

        <?php if ($success !== ''): ?>

            <div
                class="alert alert-success"
                role="alert"
            >

                <i
                    class="bi
                           bi-check-circle
                           me-2"
                ></i>

                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             STOCK FORM
        =================================================== -->

        <div class="card stock-card">


            <!-- CARD HEADER -->

            <div
                class="card-header
                       bg-success
                       text-white"
            >

                <h5 class="mb-0">

                    <i
                        class="bi
                               bi-box-arrow-in-down
                               me-2"
                    ></i>

                    Add Stock

                </h5>

            </div>


            <!-- CARD BODY -->

            <div class="card-body">


                <?php if (count($products) > 0): ?>


                    <form
                        method="POST"
                        autocomplete="off"
                    >


                        <!-- ======================================
                             CSRF TOKEN
                        ======================================= -->

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $csrfToken,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >


                        <!-- ======================================
                             PRODUCT
                        ======================================= -->

                        <div class="mb-3">


                            <label
                                for="product_id"
                                class="form-label"
                            >

                                Product

                            </label>


                            <select
                                name="product_id"
                                id="product_id"
                                class="form-select"
                                required
                            >


                                <option value="">

                                    Select Product

                                </option>


                                <?php foreach (
                                    $products
                                    as $product
                                ): ?>


                                    <option
                                        value="<?= (int) $product['id'] ?>"
                                        <?= (
                                            isset(
                                                $_POST['product_id']
                                            ) &&
                                            (int) $_POST['product_id']
                                                ===
                                            (int) $product['id']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $product['product_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        —

                                        Current Stock:

                                        <?= (int) $product['quantity'] ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <!-- ======================================
                             QUANTITY
                        ======================================= -->

                        <div class="mb-3">


                            <label
                                for="quantity"
                                class="form-label"
                            >

                                Quantity Received

                            </label>


                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                class="form-control"
                                min="1"
                                max="1000000"
                                value="<?= htmlspecialchars(
                                    $_POST['quantity'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                placeholder="Enter quantity received"
                                required
                            >


                        </div>


                        <!-- ======================================
                             DESCRIPTION
                        ======================================= -->

                        <div class="mb-3">


                            <label
                                for="description"
                                class="form-label"
                            >

                                Description

                                <span class="text-muted">

                                    (Optional)

                                </span>

                            </label>


                            <textarea
                                name="description"
                                id="description"
                                class="form-control"
                                rows="4"
                                maxlength="255"
                                placeholder="Example: New stock received from supplier"
                            ><?= htmlspecialchars(
                                $_POST['description'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>


                            <div class="form-text">

                                Maximum 255 characters.

                            </div>


                        </div>


                        <hr>


                        <!-- ======================================
                             BUTTONS
                        ======================================= -->

                        <div
                            class="d-flex
                                   gap-2
                                   flex-wrap"
                        >


                            <button
                                type="submit"
                                class="btn btn-success"
                            >

                                <i
                                    class="bi
                                           bi-plus-circle
                                           me-1"
                                ></i>

                                Add Stock

                            </button>


                            <a
                                href="index.php"
                                class="btn btn-secondary"
                            >

                                Cancel

                            </a>


                        </div>


                    </form>


                <?php else: ?>


                    <!-- ==========================================
                         NO PRODUCTS
                    =========================================== -->

                    <div
                        class="text-center
                               py-5"
                    >


                        <i
                            class="bi
                                   bi-box-seam
                                   fs-1
                                   text-muted"
                        ></i>


                        <h5 class="mt-3">

                            No Products Available

                        </h5>


                        <p class="text-muted">

                            Add a product before recording stock.

                        </p>


                        <a
                            href="../products/add.php"
                            class="btn btn-primary"
                        >

                            <i
                                class="bi
                                       bi-plus-circle
                                       me-1"
                            ></i>

                            Add Product

                        </a>


                    </div>


                <?php endif; ?>


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