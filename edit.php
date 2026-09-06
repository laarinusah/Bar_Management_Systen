<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin', 'manager']);

$error = '';
$success = '';

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId) {
    die('Invalid product ID.');
}


/*
|--------------------------------------------------------------------------
| Get Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$productId]);

$product = $stmt->fetch();

if (!$product) {
    die('Product not found.');
}


/*
|--------------------------------------------------------------------------
| Update Product
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productName = trim(
        $_POST['product_name'] ?? ''
    );

    $categoryId = $_POST['category_id'] ?? '';

    $buyingPrice = $_POST['buying_price'] ?? '';

    $sellingPrice = $_POST['selling_price'] ?? '';

    $quantity = $_POST['quantity'] ?? '';

    $minimumStock = $_POST['minimum_stock'] ?? '';


    if ($productName === '') {

        $error = 'Product name is required.';

    } elseif (
        $sellingPrice === '' ||
        !is_numeric($sellingPrice) ||
        $sellingPrice < 0
    ) {

        $error = 'Please enter a valid selling price.';

    } elseif (
        !is_numeric($buyingPrice) ||
        $buyingPrice < 0
    ) {

        $error = 'Please enter a valid buying price.';

    } elseif (
        !is_numeric($quantity) ||
        $quantity < 0
    ) {

        $error = 'Please enter a valid quantity.';

    } elseif (
        !is_numeric($minimumStock) ||
        $minimumStock < 0
    ) {

        $error = 'Please enter a valid minimum stock level.';

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE products
                SET
                    category_id = ?,
                    product_name = ?,
                    buying_price = ?,
                    selling_price = ?,
                    quantity = ?,
                    minimum_stock = ?
                WHERE id = ?
            ");

            $stmt->execute([

                $categoryId !== ''
                    ? (int) $categoryId
                    : null,

                $productName,

                $buyingPrice,

                $sellingPrice,

                (int) $quantity,

                (int) $minimumStock,

                $productId

            ]);


            $success =
                'Product updated successfully.';


            /*
            |--------------------------------------------------------------------------
            | Reload Updated Product
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT *
                FROM products
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$productId]);

            $product = $stmt->fetch();


        } catch (PDOException $e) {

            $error =
                'Unable to update product: ' .
                $e->getMessage();

        }

    }

}


/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

$categories = $pdo->query("
    SELECT
        id,
        category_name
    FROM categories
    ORDER BY category_name ASC
")->fetchAll();

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
        Edit Product - Bar Management System
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
| Reusable Sidebar
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

                <i class="bi bi-pencil-square"></i>

                Edit Product

            </h2>

            <p>

                Update product information.

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

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- Success Message -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>

            <?= htmlspecialchars($success) ?>

        </div>

    <?php endif; ?>


    <!-- Product Form -->

    <div class="card shadow-sm">


        <!-- Card Header -->

        <div class="card-header bg-warning">

            <h5 class="mb-0">

                <i class="bi bi-box-seam"></i>

                Product Information

            </h5>

        </div>


        <!-- Card Body -->

        <div class="card-body">


            <form method="POST">


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
                            value="<?= htmlspecialchars(
                                $product['product_name']
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


                            <?php foreach (
                                $categories
                                as $category
                            ): ?>

                                <option
                                    value="<?= (int) $category['id'] ?>"
                                    <?= (
                                        (int) $product['category_id'] ===
                                        (int) $category['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $category['category_name']
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
                            value="<?= htmlspecialchars(
                                $product['buying_price']
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
                            value="<?= htmlspecialchars(
                                $product['selling_price']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- Current Quantity -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="quantity"
                            class="form-label"
                        >

                            Current Quantity

                        </label>

                        <input
                            type="number"
                            name="quantity"
                            id="quantity"
                            class="form-control"
                            min="0"
                            value="<?= htmlspecialchars(
                                $product['quantity']
                            ) ?>"
                            required
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
                            value="<?= htmlspecialchars(
                                $product['minimum_stock']
                            ) ?>"
                            required
                        >

                        <small class="text-muted">

                            The system will use this value to
                            identify low-stock products.

                        </small>

                    </div>


                </div>


                <hr>


                <!-- Buttons -->

                <div class="d-flex gap-2 flex-wrap">


                    <button
                        type="submit"
                        class="btn btn-warning"
                    >

                        <i class="bi bi-save"></i>

                        Update Product

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
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<!-- Main Application JavaScript -->

<script src="../assets/js/app.js"></script>


</body>

</html>