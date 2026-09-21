<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();


/*
|--------------------------------------------------------------------------
| ONLY POST REQUESTS ARE ALLOWED
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

$submittedToken =
    $_POST['csrf_token'] ?? '';

$sessionToken =
    $_SESSION['csrf_token'] ?? '';


if (
    empty($submittedToken) ||
    empty($sessionToken) ||
    !hash_equals(
        $sessionToken,
        $submittedToken
    )
) {

    showSaleError(
        "Invalid security token. Please refresh the POS page and try again."
    );
}


/*
|--------------------------------------------------------------------------
| ERROR PAGE FUNCTION
|--------------------------------------------------------------------------
*/

function showSaleError(
    string $message
): void {

    http_response_code(400);

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
            Sale Error
        </title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
            rel="stylesheet"
        >

    </head>

    <body class="bg-light">

        <div class="container py-5">

            <div class="row justify-content-center">

                <div class="col-md-7">

                    <div class="card shadow-sm">

                        <div class="card-body text-center p-5">

                            <div class="display-5 text-danger mb-3">

                                <i class="bi bi-exclamation-triangle"></i>

                            </div>

                            <h3 class="text-danger mb-3">

                                Sale Could Not Be Completed

                            </h3>

                            <div class="alert alert-danger">

                                <?= htmlspecialchars(
                                    $message,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                            <a
                                href="index.php"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-arrow-left"></i>

                                Back to POS

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$cartJson =
    $_POST['cart'] ?? '';

$paymentMethod =
    trim(
        $_POST['payment_method'] ?? 'Cash'
    );


/*
|--------------------------------------------------------------------------
| VALIDATE CART SUBMISSION
|--------------------------------------------------------------------------
*/

if ($cartJson === '') {

    showSaleError(
        "No shopping cart was submitted."
    );
}


$cart =
    json_decode(
        $cartJson,
        true
    );


if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($cart) ||
    empty($cart)
) {

    showSaleError(
        "Your cart is empty or contains invalid information."
    );
}


/*
|--------------------------------------------------------------------------
| LIMIT CART SIZE
|--------------------------------------------------------------------------
*/

if (count($cart) > 100) {

    showSaleError(
        "Too many products were submitted."
    );
}


/*
|--------------------------------------------------------------------------
| VALID PAYMENT METHODS
|--------------------------------------------------------------------------
*/

$allowedPaymentMethods = [

    'Cash',

    'Mobile Money',

    'Card'

];


if (
    !in_array(
        $paymentMethod,
        $allowedPaymentMethods,
        true
    )
) {

    showSaleError(
        "Invalid payment method."
    );
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId =
    (int) (
        $_SESSION['user_id'] ?? 0
    );


if ($userId <= 0) {

    showSaleError(
        "Your session has expired. Please login again."
    );
}


/*
|--------------------------------------------------------------------------
| NORMALIZE CART
|--------------------------------------------------------------------------
|
| We do not trust the price or stock sent by JavaScript.
| The database is always used as the source of truth.
|
*/

$normalizedCart = [];


foreach ($cart as $item) {

    if (!is_array($item)) {

        showSaleError(
            "Invalid product information."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT ID
    |--------------------------------------------------------------------------
    */

    $productId =
        filter_var(
            $item['id'] ?? null,
            FILTER_VALIDATE_INT
        );


    /*
    |--------------------------------------------------------------------------
    | QUANTITY
    |--------------------------------------------------------------------------
    */

    $requestedQuantity =
        filter_var(
            $item['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );


    if (
        $productId === false ||
        $productId <= 0 ||
        $requestedQuantity === false ||
        $requestedQuantity <= 0
    ) {

        showSaleError(
            "Invalid product or quantity."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | QUANTITY LIMIT
    |--------------------------------------------------------------------------
    */

    if ($requestedQuantity > 100000) {

        showSaleError(
            "The requested quantity is too large."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMBINE DUPLICATE PRODUCTS
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $normalizedCart[$productId]
        )
    ) {

        $normalizedCart[$productId]
            += $requestedQuantity;

    } else {

        $normalizedCart[$productId]
            = $requestedQuantity;
    }


    /*
    |--------------------------------------------------------------------------
    | FINAL QUANTITY LIMIT
    |--------------------------------------------------------------------------
    */

    if (
        $normalizedCart[$productId]
        > 100000
    ) {

        showSaleError(
            "The requested quantity is too large."
        );
    }

}


/*
|--------------------------------------------------------------------------
| MAKE SURE CART IS NOT EMPTY
|--------------------------------------------------------------------------
*/

if (empty($normalizedCart)) {

    showSaleError(
        "Your cart is empty."
    );
}


/*
|--------------------------------------------------------------------------
| PROCESS SALE
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | TOTAL AMOUNT
    |--------------------------------------------------------------------------
    */

    $totalAmount = 0.00;


    /*
    |--------------------------------------------------------------------------
    | VALIDATED ITEMS
    |--------------------------------------------------------------------------
    */

    $validatedItems = [];


    /*
    |--------------------------------------------------------------------------
    | CHECK PRODUCTS AND STOCK
    |--------------------------------------------------------------------------
    */

    foreach (
        $normalizedCart
        as $productId => $requestedQuantity
    ) {


        /*
        |--------------------------------------------------------------------------
        | LOCK PRODUCT ROW
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                product_name,
                selling_price,
                quantity
            FROM products
            WHERE id = ?
            FOR UPDATE
        ");


        $stmt->execute([
            $productId
        ]);


        $product =
            $stmt->fetch();


        /*
        |--------------------------------------------------------------------------
        | PRODUCT EXISTS?
        |--------------------------------------------------------------------------
        */

        if (!$product) {

            throw new Exception(
                "One of the selected products no longer exists."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | AVAILABLE STOCK
        |--------------------------------------------------------------------------
        */

        $availableStock =
            (int) $product['quantity'];


        /*
        |--------------------------------------------------------------------------
        | CHECK STOCK
        |--------------------------------------------------------------------------
        */

        if (
            $requestedQuantity
            > $availableStock
        ) {

            throw new Exception(
                "Not enough stock for: " .
                $product['product_name'] .
                ". Available stock: " .
                $availableStock
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SELLING PRICE
        |--------------------------------------------------------------------------
        */

        $price =
            (float) $product['selling_price'];


        if ($price < 0) {

            throw new Exception(
                "Invalid selling price for: " .
                $product['product_name']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SUBTOTAL
        |--------------------------------------------------------------------------
        */

        $subtotal =
            round(
                $price *
                $requestedQuantity,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $totalAmount =
            round(
                $totalAmount +
                $subtotal,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | SAVE VALIDATED ITEM
        |--------------------------------------------------------------------------
        */

        $validatedItems[] = [

            'product_id' =>
                (int) $product['id'],

            'product_name' =>
                $product['product_name'],

            'quantity' =>
                $requestedQuantity,

            'price' =>
                $price,

            'subtotal' =>
                $subtotal,

            'current_stock' =>
                $availableStock

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | TOTAL MUST BE GREATER THAN ZERO
    |--------------------------------------------------------------------------
    */

    if ($totalAmount <= 0) {

        throw new Exception(
            "The sale total must be greater than zero."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE SALE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO sales
        (
            user_id,
            total_amount,
            payment_method
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");


    $stmt->execute([

        $userId,

        $totalAmount,

        $paymentMethod

    ]);


    /*
    |--------------------------------------------------------------------------
    | SALE ID
    |--------------------------------------------------------------------------
    */

    $saleId =
        (int) $pdo->lastInsertId();


    if ($saleId <= 0) {

        throw new Exception(
            "Unable to create the sale."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARE INSERT SALE ITEM
    |--------------------------------------------------------------------------
    */

    $insertSaleItem =
        $pdo->prepare("
            INSERT INTO sale_items
            (
                sale_id,
                product_id,
                quantity,
                price,
                subtotal
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


    /*
    |--------------------------------------------------------------------------
    | PREPARE UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    $updateProduct =
        $pdo->prepare("
            UPDATE products
            SET quantity = ?
            WHERE id = ?
        ");


    /*
    |--------------------------------------------------------------------------
    | PREPARE STOCK MOVEMENT
    |--------------------------------------------------------------------------
    */

    $insertMovement =
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
                'stock_out',
                ?,
                ?
            )
        ");


    /*
    |--------------------------------------------------------------------------
    | SAVE SALE ITEMS AND UPDATE STOCK
    |--------------------------------------------------------------------------
    */

    foreach (
        $validatedItems
        as $item
    ) {


        /*
        |--------------------------------------------------------------------------
        | SALE ITEM
        |--------------------------------------------------------------------------
        */

        $insertSaleItem->execute([

            $saleId,

            $item['product_id'],

            $item['quantity'],

            $item['price'],

            $item['subtotal']

        ]);


        /*
        |--------------------------------------------------------------------------
        | NEW STOCK
        |--------------------------------------------------------------------------
        */

        $newStock =
            $item['current_stock']
            -
            $item['quantity'];


        /*
        |--------------------------------------------------------------------------
        | UPDATE PRODUCT STOCK
        |--------------------------------------------------------------------------
        */

        $updateProduct->execute([

            $newStock,

            $item['product_id']

        ]);


        /*
        |--------------------------------------------------------------------------
        | STOCK MOVEMENT DESCRIPTION
        |--------------------------------------------------------------------------
        */

        $description =
            "Sale #" .
            $saleId .
            " - " .
            $item['product_name'];


        /*
        |--------------------------------------------------------------------------
        | RECORD STOCK OUT
        |--------------------------------------------------------------------------
        */

        $insertMovement->execute([

            $item['product_id'],

            $item['quantity'],

            $description

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT TRANSACTION
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO RECEIPT
    |--------------------------------------------------------------------------
    */

    header(
        "Location: receipt.php?id=" .
        urlencode(
            (string) $saleId
        )
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK IF NECESSARY
    |--------------------------------------------------------------------------
    */

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();

    }


    /*
    |--------------------------------------------------------------------------
    | SHOW ERROR
    |--------------------------------------------------------------------------
    */

    showSaleError(
        $e->getMessage()
    );

}