<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin', 'manager']);

$stmt = $pdo->query("
    SELECT
        products.id,
        products.product_name,
        products.buying_price,
        products.selling_price,
        products.quantity,
        products.minimum_stock,
        categories.category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    ORDER BY products.id DESC
");

$products = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products - Bar Management System</title>


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


    <style>

        body {
            background: #f5f6fa;
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

        .product-card {
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

        @media (max-width: 768px) {

            .content {
                padding: 15px;
            }

            .page-header {
                padding: 15px;
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


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-box-seam"></i>

                    Products

                </h2>

                <p class="text-muted mb-0">

                    Manage products and inventory information.

                </p>

            </div>


            <?php if (
                $_SESSION['user_role'] === 'admin' ||
                $_SESSION['user_role'] === 'manager'
            ): ?>

                <a
                    href="add.php"
                    class="btn btn-primary"
                >

                    <i class="bi bi-plus-circle me-1"></i>

                    Add Product

                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- ======================================================
         CONTENT
    ======================================================= -->

    <div class="content">


        <!-- PRODUCT LIST -->

        <div class="card product-card">


            <!-- CARD HEADER -->

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-list-ul me-2"></i>

                    Product List

                </h5>

            </div>


            <!-- CARD BODY -->

            <div class="card-body p-0">


                <div class="table-responsive">


                    <table class="table table-hover table-bordered mb-0">


                        <!-- TABLE HEADER -->

                        <thead class="table-dark">

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Product Name
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Buying Price
                                </th>

                                <th>
                                    Selling Price
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Minimum Stock
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>


                        <?php if (count($products) > 0): ?>


                            <?php foreach ($products as $product): ?>


                                <?php

                                $quantity = (int) $product['quantity'];

                                $minimumStock = (int) $product['minimum_stock'];


                                /*
                                |--------------------------------------------------------------------------
                                | Product Stock Status
                                |--------------------------------------------------------------------------
                                */

                                if ($quantity <= 0) {

                                    $status = 'Out of Stock';

                                    $statusClass = 'danger';

                                } elseif ($quantity <= $minimumStock) {

                                    $status = 'Low Stock';

                                    $statusClass = 'warning';

                                } else {

                                    $status = 'In Stock';

                                    $statusClass = 'success';

                                }

                                ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?= (int) $product['id'] ?>

                                    </td>


                                    <!-- PRODUCT NAME -->

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


                                    <!-- BUYING PRICE -->

                                    <td>

                                        GHS
                                        <?= number_format(
                                            (float) $product['buying_price'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- SELLING PRICE -->

                                    <td>

                                        <strong>

                                            GHS
                                            <?= number_format(
                                                (float) $product['selling_price'],
                                                2
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- QUANTITY -->

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


                                    <!-- ACTIONS -->

                                    <td>


                                        <div class="btn-group btn-group-sm">


                                            <!-- EDIT -->

                                            <?php if (
                                                $_SESSION['user_role'] === 'admin' ||
                                                $_SESSION['user_role'] === 'manager'
                                            ): ?>

                                                <a
                                                    href="edit.php?id=<?= (int) $product['id'] ?>"
                                                    class="btn btn-warning"
                                                    title="Edit Product"
                                                >

                                                    <i class="bi bi-pencil"></i>

                                                </a>

                                            <?php endif; ?>


                                            <!-- DELETE -->

                                            <?php if (
                                                $_SESSION['user_role'] === 'admin'
                                            ): ?>

                                                <a
                                                    href="delete.php?id=<?= (int) $product['id'] ?>"
                                                    class="btn btn-danger"
                                                    title="Delete Product"
                                                    onclick="return confirm('Are you sure you want to delete this product?');"
                                                >

                                                    <i class="bi bi-trash"></i>

                                                </a>

                                            <?php endif; ?>


                                        </div>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <!-- NO PRODUCTS -->

                            <tr>

                                <td
                                    colspan="9"
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


    </div>


</div>


<!-- ==========================================================
     BOOTSTRAP JAVASCRIPT
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- Main Application JavaScript -->

<script src="../assets/js/app.js"></script>


</body>

</html>