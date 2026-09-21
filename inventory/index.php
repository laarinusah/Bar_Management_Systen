<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin', 'manager']);


/*
|--------------------------------------------------------------------------
| Get Inventory
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        products.id,
        products.product_name,
        products.quantity,
        products.minimum_stock,
        categories.category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    ORDER BY products.product_name ASC
");

$products = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Inventory Statistics
|--------------------------------------------------------------------------
*/

$totalProducts = count($products);

$totalStock = 0;
$lowStock = 0;
$outOfStock = 0;

foreach ($products as $product) {

    $quantity = (int) $product['quantity'];
    $minimumStock = (int) $product['minimum_stock'];

    $totalStock += $quantity;

    if ($quantity <= 0) {

        $outOfStock++;

    } elseif ($quantity <= $minimumStock) {

        $lowStock++;

    }

}


/*
|--------------------------------------------------------------------------
| Recent Stock Movements
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        stock_movements.id,
        stock_movements.movement_type,
        stock_movements.quantity,
        stock_movements.description,
        stock_movements.created_at,
        products.product_name
    FROM stock_movements
    INNER JOIN products
        ON stock_movements.product_id = products.id
    ORDER BY stock_movements.id DESC
    LIMIT 10
");

$movements = $stmt->fetchAll();

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
        Inventory - Bar Management System
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


<!-- ==========================================================
     MAIN CONTENT
========================================================== -->

<div class="container-fluid py-4">


    <!-- ======================================================
         PAGE HEADER
    ======================================================= -->

    <div class="page-header">

        <div>

            <h2>

                <i class="bi bi-boxes"></i>

                Inventory

            </h2>

            <p>

                Monitor current stock levels and stock movements.

            </p>

        </div>


        <?php if (
            $_SESSION['user_role'] === 'admin' ||
            $_SESSION['user_role'] === 'manager'
        ): ?>

            <a
                href="stock_in.php"
                class="btn btn-success"
            >

                <i class="bi bi-plus-circle me-1"></i>

                Stock In

            </a>

        <?php endif; ?>

    </div>


    <!-- ======================================================
         INVENTORY STATISTICS
    ======================================================= -->

    <div class="row g-3 mb-4">


        <!-- TOTAL PRODUCTS -->

        <div class="col-6 col-lg-3">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="text-muted mb-2">

                                Total Products

                            </h6>

                            <h3 class="mb-0">

                                <?= $totalProducts ?>

                            </h3>

                        </div>

                        <div class="dashboard-icon bg-primary-subtle text-primary">

                            <i class="bi bi-box-seam"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- TOTAL STOCK -->

        <div class="col-6 col-lg-3">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="text-muted mb-2">

                                Total Stock

                            </h6>

                            <h3 class="mb-0">

                                <?= $totalStock ?>

                            </h3>

                        </div>

                        <div class="dashboard-icon bg-success-subtle text-success">

                            <i class="bi bi-stack"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- LOW STOCK -->

        <div class="col-6 col-lg-3">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="text-muted mb-2">

                                Low Stock

                            </h6>

                            <h3 class="mb-0">

                                <?= $lowStock ?>

                            </h3>

                        </div>

                        <div class="dashboard-icon bg-warning-subtle text-warning">

                            <i class="bi bi-exclamation-triangle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- OUT OF STOCK -->

        <div class="col-6 col-lg-3">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="text-muted mb-2">

                                Out of Stock

                            </h6>

                            <h3 class="mb-0">

                                <?= $outOfStock ?>

                            </h3>

                        </div>

                        <div class="dashboard-icon bg-danger-subtle text-danger">

                            <i class="bi bi-x-circle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>


    <!-- ======================================================
         CURRENT INVENTORY
    ======================================================= -->

    <div class="card shadow-sm mb-4">


        <!-- CARD HEADER -->

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-boxes me-2"></i>

                Current Inventory

            </h5>

        </div>


        <!-- CARD BODY -->

        <div class="card-body p-0">


            <div class="table-responsive">


                <table class="table table-hover table-bordered mb-0">


                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Product</th>

                            <th>Category</th>

                            <th>Current Stock</th>

                            <th>Minimum Stock</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (count($products) > 0): ?>


                        <?php foreach ($products as $product): ?>


                            <?php

                            $quantity =
                                (int) $product['quantity'];

                            $minimumStock =
                                (int) $product['minimum_stock'];


                            if ($quantity <= 0) {

                                $status =
                                    'Out of Stock';

                                $statusClass =
                                    'danger';

                            } elseif (
                                $quantity <= $minimumStock
                            ) {

                                $status =
                                    'Low Stock';

                                $statusClass =
                                    'warning';

                            } else {

                                $status =
                                    'In Stock';

                                $statusClass =
                                    'success';

                            }

                            ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?= (int) $product['id'] ?>

                                </td>


                                <!-- PRODUCT -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $product['product_name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $product['category_name']
                                        ?? 'Uncategorized'
                                    ) ?>

                                </td>


                                <!-- CURRENT STOCK -->

                                <td>

                                    <strong>

                                        <?= $quantity ?>

                                    </strong>

                                </td>


                                <!-- MINIMUM STOCK -->

                                <td>

                                    <?= $minimumStock ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="badge text-bg-<?= $statusClass ?>"
                                    >

                                        <?= $status ?>

                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td>


                                    <?php if (
                                        $_SESSION['user_role'] === 'admin' ||
                                        $_SESSION['user_role'] === 'manager'
                                    ): ?>


                                        <a
                                            href="stock_in.php"
                                            class="btn btn-success btn-sm"
                                        >

                                            <i class="bi bi-plus-circle me-1"></i>

                                            Stock In

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
                                colspan="7"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-box-seam fs-1 text-muted"
                                ></i>


                                <p class="mt-2 mb-0">

                                    No products found.

                                </p>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </div>


    <!-- ======================================================
         STOCK MOVEMENT HISTORY
    ======================================================= -->

    <div class="card shadow-sm">


        <!-- CARD HEADER -->

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-clock-history me-2"></i>

                Recent Stock Movements

            </h5>

        </div>


        <!-- CARD BODY -->

        <div class="card-body p-0">


            <div class="table-responsive">


                <table class="table table-hover table-bordered mb-0">


                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Product</th>

                            <th>Movement</th>

                            <th>Quantity</th>

                            <th>Description</th>

                            <th>Date</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (count($movements) > 0): ?>


                        <?php foreach ($movements as $movement): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?= (int) $movement['id'] ?>

                                </td>


                                <!-- PRODUCT -->

                                <td>

                                    <?= htmlspecialchars(
                                        $movement['product_name']
                                    ) ?>

                                </td>


                                <!-- MOVEMENT -->

                                <td>


                                    <?php if (
                                        $movement['movement_type']
                                        === 'stock_in'
                                    ): ?>


                                        <span class="badge text-bg-success">

                                            <i class="bi bi-arrow-down-circle me-1"></i>

                                            Stock In

                                        </span>


                                    <?php else: ?>


                                        <span class="badge text-bg-danger">

                                            <i class="bi bi-arrow-up-circle me-1"></i>

                                            Stock Out

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- QUANTITY -->

                                <td>

                                    <strong>

                                        <?= (int) $movement['quantity'] ?>

                                    </strong>

                                </td>


                                <!-- DESCRIPTION -->

                                <td>

                                    <?= htmlspecialchars(
                                        $movement['description']
                                        ?? ''
                                    ) ?>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $movement['created_at']
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5 text-muted"
                            >

                                <i
                                    class="bi bi-clock-history fs-1 d-block mb-2"
                                ></i>

                                No stock movements recorded yet.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


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