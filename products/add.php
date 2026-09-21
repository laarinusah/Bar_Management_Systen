<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin', 'manager']);

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Verify CSRF Token
    |--------------------------------------------------------------------------
    */

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        $submittedToken === '' ||
        !hash_equals($csrfToken, $submittedToken)
    ) {

        $error = 'Invalid security token. Please refresh the page and try again.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Collect Form Data
        |--------------------------------------------------------------------------
        */

        $productName = trim($_POST['product_name'] ?? '');

        $categoryId = trim(
            (string) ($_POST['category_id'] ?? '')
        );

        $buyingPrice = trim(
            (string) ($_POST['buying_price'] ?? '')
        );

        $sellingPrice = trim(
            (string) ($_POST['selling_price'] ?? '')
        );

        $quantity = trim(
            (string) ($_POST['quantity'] ?? '0')
        );

        $minimumStock = trim(
            (string) ($_POST['minimum_stock'] ?? '5')
        );


        /*
        |--------------------------------------------------------------------------
        | Validate Product Name
        |--------------------------------------------------------------------------
        */

        if ($productName === '') {

            $error = 'Product name is required.';

        } elseif (mb_strlen($productName) > 150) {

            $error = 'Product name must not exceed 150 characters.';


        /*
        |--------------------------------------------------------------------------
        | Validate Category
        |--------------------------------------------------------------------------
        */

        } elseif ($categoryId !== '') {

            if (
                !ctype_digit($categoryId) ||
                (int) $categoryId <= 0
            ) {

                $error = 'Please select a valid category.';

            } else {

                try {

                    $categoryCheck = $pdo->prepare("
                        SELECT id
                        FROM categories
                        WHERE id = ?
                        LIMIT 1
                    ");

                    $categoryCheck->execute([
                        (int) $categoryId
                    ]);

                    if (!$categoryCheck->fetch()) {

                        $error = 'The selected category does not exist.';
                    }

                } catch (PDOException $e) {

                    $error = 'Unable to validate the selected category.';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Buying Price
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            if (
                $buyingPrice === '' ||
                !is_numeric($buyingPrice)
            ) {

                $error = 'Please enter a valid buying price.';

            } elseif ((float) $buyingPrice < 0) {

                $error = 'Buying price cannot be negative.';

            } elseif ((float) $buyingPrice > 99999999.99) {

                $error = 'Buying price is too large.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Selling Price
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            if (
                $sellingPrice === '' ||
                !is_numeric($sellingPrice)
            ) {

                $error = 'Please enter a valid selling price.';

            } elseif ((float) $sellingPrice < 0) {

                $error = 'Selling price cannot be negative.';

            } elseif ((float) $sellingPrice > 99999999.99) {

                $error = 'Selling price is too large.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Quantity
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            if (
                $quantity === '' ||
                !ctype_digit($quantity)
            ) {

                $error = 'Quantity must be a whole number.';

            } elseif ((int) $quantity > 2147483647) {

                $error = 'Quantity is too large.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Minimum Stock
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            if (
                $minimumStock === '' ||
                !ctype_digit($minimumStock)
            ) {

                $error = 'Minimum stock must be a whole number.';

            } elseif ((int) $minimumStock > 2147483647) {

                $error = 'Minimum stock value is too large.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Add Product
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            try {

                $categoryValue =
                    $categoryId === ''
                    ? null
                    : (int) $categoryId;

                $buyingPriceValue =
                    round((float) $buyingPrice, 2);

                $sellingPriceValue =
                    round((float) $sellingPrice, 2);

                $quantityValue =
                    (int) $quantity;

                $minimumStockValue =
                    (int) $minimumStock;


                /*
                |--------------------------------------------------------------------------
                | Insert Product
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (
                        category_id,
                        product_name,
                        buying_price,
                        selling_price,
                        quantity,
                        minimum_stock
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $categoryValue,
                    $productName,
                    $buyingPriceValue,
                    $sellingPriceValue,
                    $quantityValue,
                    $minimumStockValue
                ]);


                /*
                |--------------------------------------------------------------------------
                | Success
                |--------------------------------------------------------------------------
                */

                $success = 'Product added successfully.';

                /*
                |--------------------------------------------------------------------------
                | Clear Form
                |--------------------------------------------------------------------------
                */

                $_POST = [];


                /*
                |--------------------------------------------------------------------------
                | Generate New CSRF Token
                |--------------------------------------------------------------------------
                */

                $_SESSION['csrf_token'] =
                    bin2hex(random_bytes(32));

                $csrfToken =
                    $_SESSION['csrf_token'];


            } catch (PDOException $e) {

                /*
                |--------------------------------------------------------------------------
                | Do Not Expose Database Error
                |--------------------------------------------------------------------------
                */

                if ($e->errorInfo[0] ?? '' === '23000') {

                    $error =
                        'The product could not be added because of a database constraint.';

                } else {

                    $error =
                        'Unable to add product. Please try again.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

try {

    $categories = $pdo->query("
        SELECT
            id,
            category_name
        FROM categories
        ORDER BY category_name ASC
    ")->fetchAll();

} catch (PDOException $e) {

    $categories = [];

    if ($error === '') {
        $error = 'Unable to load product categories.';
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
        Add Product - Bar Management System
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


    <!-- Main Application CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body class="bg-light">


<?php

/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
*/

require_once '../includes/sidebar.php';

?>


<!-- Main Content -->

<div class="container-fluid py-4">


    <!-- Page Header -->

    <div class="page-header">

        <div>

            <h2>

                <i class="bi bi-box-seam"></i>

                Add Product

            </h2>

            <p>
                Add a new product to the inventory.
            </p>

        </div>


        <a
            href="index.php"
            class="btn btn-secondary"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Products

        </a>

    </div>


    <!-- Error Message -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <!-- Success Message -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>

            <?= htmlspecialchars(
                $success,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <!-- Product Form -->

    <div class="card shadow-sm">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">

                <i class="bi bi-info-circle"></i>

                Product Information

            </h5>

        </div>


        <div class="card-body">

            <form
                method="POST"
                autocomplete="off"
            >


                <!-- CSRF Token -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <div class="row">


                    <!-- Product Name -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="product_name"
                            class="form-label"
                        >

                            Product Name

                        </label>

                        <input
                            type="text"
                            name="product_name"
                            id="product_name"
                            class="form-control"
                            placeholder="Enter product name"
                            maxlength="150"
                            value="<?= htmlspecialchars(
                                $_POST['product_name'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- Category -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="category_id"
                            class="form-label"
                        >

                            Category

                        </label>

                        <select
                            name="category_id"
                            id="category_id"
                            class="form-select"
                        >

                            <option value="">
                                Select Category
                            </option>


                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int) $category['id'] ?>"
                                    <?= (
                                        ($_POST['category_id'] ?? '') ==
                                        $category['id']
                                    ) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $category['category_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Buying Price -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="buying_price"
                            class="form-label"
                        >

                            Buying Price (GHS)

                        </label>

                        <input
                            type="number"
                            name="buying_price"
                            id="buying_price"
                            class="form-control"
                            step="0.01"
                            min="0"
                            max="99999999.99"
                            placeholder="0.00"
                            value="<?= htmlspecialchars(
                                $_POST['buying_price'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- Selling Price -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="selling_price"
                            class="form-label"
                        >

                            Selling Price (GHS)

                        </label>

                        <input
                            type="number"
                            name="selling_price"
                            id="selling_price"
                            class="form-control"
                            step="0.01"
                            min="0"
                            max="99999999.99"
                            placeholder="0.00"
                            value="<?= htmlspecialchars(
                                $_POST['selling_price'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- Opening Quantity -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="quantity"
                            class="form-label"
                        >

                            Opening Quantity

                        </label>

                        <input
                            type="number"
                            name="quantity"
                            id="quantity"
                            class="form-control"
                            min="0"
                            max="2147483647"
                            step="1"
                            value="<?= htmlspecialchars(
                                $_POST['quantity'] ?? '0',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>


                    <!-- Minimum Stock -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="minimum_stock"
                            class="form-label"
                        >

                            Minimum Stock Level

                        </label>

                        <input
                            type="number"
                            name="minimum_stock"
                            id="minimum_stock"
                            class="form-control"
                            min="0"
                            max="2147483647"
                            step="1"
                            value="<?= htmlspecialchars(
                                $_POST['minimum_stock'] ?? '5',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                        <small class="text-muted">

                            The system will use this value to
                            identify low-stock products.

                        </small>

                    </div>

                </div>


                <hr>


                <!-- Form Buttons -->

                <div class="d-flex gap-2 flex-wrap">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-plus-circle"></i>

                        Add Product

                    </button>


                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >

                        <i class="bi bi-x-circle"></i>

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- Main Application JavaScript -->

<script src="../assets/js/app.js"></script>


</body>

</html>