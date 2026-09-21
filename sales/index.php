<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.product_name,
        p.selling_price,
        p.quantity,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.quantity > 0
    ORDER BY p.product_name ASC
");

$products = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sales / POS - Bar Management System</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<?php require_once '../includes/sidebar.php'; ?>

<div class="container-fluid py-4">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h2>
                <i class="bi bi-cart-check"></i>
                Sales / POS
            </h2>

            <p>
                Record sales and manage customer purchases.
            </p>

        </div>

    </div>


    <!-- SEARCH AND CATEGORY FILTER -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <div class="row g-3">

                <!-- Search -->

                <div class="col-md-6">

                    <label
                        for="productSearch"
                        class="form-label"
                    >
                        Search Product
                    </label>

                    <div class="search-box">

                        <i class="bi bi-search"></i>

                        <input
                            type="text"
                            id="productSearch"
                            class="form-control"
                            placeholder="Search product..."
                        >

                    </div>

                </div>


                <!-- Category -->

                <div class="col-md-6">

                    <label
                        for="categoryFilter"
                        class="form-label"
                    >
                        Category
                    </label>

                    <select
                        id="categoryFilter"
                        class="form-select"
                    >

                        <option value="">
                            All Categories
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= (int) $category['id'] ?>"
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

            </div>

        </div>

    </div>


    <div class="row g-4">

        <!-- PRODUCTS -->

        <div class="col-lg-8">

            <div class="card shadow-sm">

                <div class="card-header bg-white">

                    <h5 class="mb-0">

                        <i class="bi bi-box-seam"></i>

                        Available Products

                    </h5>

                </div>


                <div class="card-body">

                    <?php if (empty($products)): ?>

                        <div class="empty-state">

                            <i class="bi bi-box-seam"></i>

                            <h5>
                                No Products Available
                            </h5>

                            <p>
                                There are currently no products
                                with available stock.
                            </p>

                        </div>

                    <?php else: ?>

                        <div
                            class="row g-3"
                            id="productsContainer"
                        >

                            <?php foreach ($products as $product): ?>

                                <div
                                    class="col-md-6 col-xl-4 product-card"
                                    data-name="<?= htmlspecialchars(
                                        strtolower($product['product_name']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    data-category="<?= (int) (
                                        $product['category_id'] ?? 0
                                    ) ?>"
                                >

                                    <div
                                        class="card h-100 border pos-product"
                                        onclick="addToCart(
                                            <?= (int) $product['id'] ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    $product['product_name'],
                                                    JSON_HEX_TAG |
                                                    JSON_HEX_APOS |
                                                    JSON_HEX_QUOT |
                                                    JSON_HEX_AMP
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= (float) $product['selling_price'] ?>,
                                            <?= (int) $product['quantity'] ?>
                                        )"
                                    >

                                        <div class="card-body">

                                            <h6 class="card-title">

                                                <?= htmlspecialchars(
                                                    $product['product_name'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </h6>

                                            <p class="text-muted mb-2">

                                                <?= htmlspecialchars(
                                                    $product['category_name']
                                                        ?? 'Uncategorized',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </p>

                                            <div class="d-flex justify-content-between align-items-center">

                                                <strong class="text-primary">

                                                    GHS
                                                    <?= number_format(
                                                        (float) $product['selling_price'],
                                                        2
                                                    ) ?>

                                                </strong>

                                                <span class="badge bg-secondary">

                                                    Stock:
                                                    <?= (int) $product['quantity'] ?>

                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div
                            id="noProductsMessage"
                            class="empty-state d-none"
                        >

                            <i class="bi bi-search"></i>

                            <h5>
                                No Products Found
                            </h5>

                            <p>
                                Try another search or category.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- CART -->

        <div class="col-lg-4">

            <div class="card shadow-sm pos-cart">

                <div class="card-header bg-primary text-white">

                    <div class="d-flex justify-content-between align-items-center">

                        <h5 class="mb-0">

                            <i class="bi bi-cart3"></i>

                            Current Sale

                        </h5>

                        <span
                            id="cartCount"
                            class="badge bg-light text-dark"
                        >
                            0
                        </span>

                    </div>

                </div>


                <div class="card-body">

                    <!-- CART ITEMS -->

                    <div
                        id="cartItems"
                        class="mb-3"
                    >

                        <div class="empty-state py-4">

                            <i class="bi bi-cart"></i>

                            <p class="mb-0">
                                Cart is empty
                            </p>

                        </div>

                    </div>


                    <!-- TOTAL -->

                    <div class="border-top pt-3">

                        <div class="d-flex justify-content-between mb-3">

                            <strong>
                                Total
                            </strong>

                            <strong
                                id="cartTotal"
                                class="text-primary fs-5"
                            >
                                GHS 0.00
                            </strong>

                        </div>


                        <!-- PAYMENT METHOD -->

                        <div class="mb-3">

                            <label
                                for="paymentMethod"
                                class="form-label"
                            >
                                Payment Method
                            </label>

                            <select
                                id="paymentMethod"
                                class="form-select"
                            >

                                <option value="Cash">
                                    Cash
                                </option>

                                <option value="Mobile Money">
                                    Mobile Money
                                </option>

                                <option value="Card">
                                    Card
                                </option>

                            </select>

                        </div>


                        <!-- COMPLETE SALE -->

                        <button
                            type="button"
                            id="completeSaleBtn"
                            class="btn btn-success w-100"
                            onclick="processSale()"
                            disabled
                        >

                            <i class="bi bi-check-circle"></i>

                            Complete Sale

                        </button>


                        <!-- CLEAR CART -->

                        <button
                            type="button"
                            class="btn btn-outline-danger w-100 mt-2"
                            onclick="clearCart()"
                        >

                            <i class="bi bi-trash"></i>

                            Clear Cart

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

const csrfToken =
    <?= json_encode(
        $csrfToken,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;


/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/

let cart = [];


/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/

function addToCart(
    id,
    name,
    price,
    stock
) {

    id = Number(id);
    price = Number(price);
    stock = Number(stock);

    const existingItem =
        cart.find(item => item.id === id);

    if (existingItem) {

        if (existingItem.quantity >= stock) {

            alert(
                "You cannot add more than the available stock."
            );

            return;
        }

        existingItem.quantity++;

    } else {

        cart.push({
            id: id,
            name: name,
            price: price,
            quantity: 1,
            stock: stock
        });

    }

    renderCart();
}


/*
|--------------------------------------------------------------------------
| CHANGE QUANTITY
|--------------------------------------------------------------------------
*/

function changeQuantity(
    id,
    change
) {

    const item =
        cart.find(item => item.id === id);

    if (!item) {
        return;
    }

    const newQuantity =
        item.quantity + change;

    if (newQuantity <= 0) {

        removeFromCart(id);

        return;
    }

    if (newQuantity > item.stock) {

        alert(
            "You cannot exceed the available stock."
        );

        return;
    }

    item.quantity =
        newQuantity;

    renderCart();
}


/*
|--------------------------------------------------------------------------
| REMOVE FROM CART
|--------------------------------------------------------------------------
*/

function removeFromCart(id) {

    cart =
        cart.filter(
            item => item.id !== id
        );

    renderCart();
}


/*
|--------------------------------------------------------------------------
| CLEAR CART
|--------------------------------------------------------------------------
*/

function clearCart() {

    if (cart.length === 0) {
        return;
    }

    if (
        !confirm(
            "Are you sure you want to clear the cart?"
        )
    ) {
        return;
    }

    cart = [];

    renderCart();
}


/*
|--------------------------------------------------------------------------
| RENDER CART
|--------------------------------------------------------------------------
*/

function renderCart() {

    const cartItems =
        document.getElementById("cartItems");

    const cartCount =
        document.getElementById("cartCount");

    const cartTotal =
        document.getElementById("cartTotal");

    const completeSaleBtn =
        document.getElementById("completeSaleBtn");


    if (
        !cartItems ||
        !cartCount ||
        !cartTotal ||
        !completeSaleBtn
    ) {
        return;
    }


    if (cart.length === 0) {

        cartItems.innerHTML = `
            <div class="empty-state py-4">

                <i class="bi bi-cart"></i>

                <p class="mb-0">
                    Cart is empty
                </p>

            </div>
        `;

        cartCount.textContent = "0";

        cartTotal.textContent =
            "GHS 0.00";

        completeSaleBtn.disabled =
            true;

        return;
    }


    let total = 0;

    let itemCount = 0;

    let html = "";


    cart.forEach(function (item) {

        const subtotal =
            item.price * item.quantity;

        total += subtotal;

        itemCount += item.quantity;


        html += `

            <div class="cart-item">

                <div class="d-flex justify-content-between">

                    <div>

                        <strong>
                            ${escapeHtml(item.name)}
                        </strong>

                        <div class="text-muted small">

                            GHS
                            ${item.price.toFixed(2)}
                            ×
                            ${item.quantity}

                        </div>

                    </div>

                    <strong>

                        GHS
                        ${subtotal.toFixed(2)}

                    </strong>

                </div>


                <div class="d-flex justify-content-between align-items-center mt-2">

                    <div class="quantity-control">

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            onclick="changeQuantity(
                                ${item.id},
                                -1
                            )"
                        >
                            -
                        </button>

                        <span>
                            ${item.quantity}
                        </span>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            onclick="changeQuantity(
                                ${item.id},
                                1
                            )"
                        >
                            +
                        </button>

                    </div>


                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        onclick="removeFromCart(
                            ${item.id}
                        )"
                    >

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

            </div>

        `;

    });


    cartItems.innerHTML =
        html;

    cartCount.textContent =
        itemCount;

    cartTotal.textContent =
        "GHS " + total.toFixed(2);

    completeSaleBtn.disabled =
        false;
}


/*
|--------------------------------------------------------------------------
| PROCESS SALE
|--------------------------------------------------------------------------
*/

function processSale() {

    if (cart.length === 0) {

        alert(
            "Please add at least one product to the cart."
        );

        return;
    }


    const paymentMethod =
        document.getElementById(
            "paymentMethod"
        ).value;


    if (!paymentMethod) {

        alert(
            "Please select a payment method."
        );

        return;
    }


    const total =
        cart.reduce(
            function (sum, item) {

                return sum +
                    (
                        item.price *
                        item.quantity
                    );

            },
            0
        );


    if (total <= 0) {

        alert(
            "The sale total must be greater than zero."
        );

        return;
    }


    if (
        !confirm(
            "Complete this sale for GHS " +
            total.toFixed(2) +
            "?"
        )
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE FORM
    |--------------------------------------------------------------------------
    */

    const form =
        document.createElement("form");

    form.method =
        "POST";

    form.action =
        "process.php";


    /*
    |--------------------------------------------------------------------------
    | CART DATA
    |--------------------------------------------------------------------------
    */

    const cartInput =
        document.createElement("input");

    cartInput.type =
        "hidden";

    cartInput.name =
        "cart";

    cartInput.value =
        JSON.stringify(
            cart.map(function (item) {

                return {
                    id: item.id,
                    quantity: item.quantity
                };

            })
        );


    form.appendChild(
        cartInput
    );


    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHOD
    |--------------------------------------------------------------------------
    */

    const paymentInput =
        document.createElement("input");

    paymentInput.type =
        "hidden";

    paymentInput.name =
        "payment_method";

    paymentInput.value =
        paymentMethod;


    form.appendChild(
        paymentInput
    );


    /*
    |--------------------------------------------------------------------------
    | CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    const csrfInput =
        document.createElement("input");

    csrfInput.type =
        "hidden";

    csrfInput.name =
        "csrf_token";

    csrfInput.value =
        csrfToken;


    form.appendChild(
        csrfInput
    );


    /*
    |--------------------------------------------------------------------------
    | SUBMIT
    |--------------------------------------------------------------------------
    */

    document.body.appendChild(
        form
    );

    form.submit();
}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value)
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );
}


/*
|--------------------------------------------------------------------------
| PRODUCT SEARCH
|--------------------------------------------------------------------------
*/

document
    .getElementById("productSearch")
    ?.addEventListener(
        "input",
        filterProducts
    );


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

document
    .getElementById("categoryFilter")
    ?.addEventListener(
        "change",
        filterProducts
    );


/*
|--------------------------------------------------------------------------
| FILTER PRODUCTS
|--------------------------------------------------------------------------
*/

function filterProducts() {

    const searchInput =
        document.getElementById(
            "productSearch"
        );

    const categoryInput =
        document.getElementById(
            "categoryFilter"
        );


    const search =
        (
            searchInput?.value ||
            ""
        )
        .toLowerCase()
        .trim();


    const category =
        categoryInput?.value ||
        "";


    const productCards =
        document.querySelectorAll(
            ".product-card"
        );


    let visibleCount = 0;


    productCards.forEach(
        function (card) {

            const name =
                card.dataset.name ||
                "";

            const cardCategory =
                card.dataset.category ||
                "";


            const matchesSearch =
                name.includes(search);


            const matchesCategory =
                category === "" ||
                cardCategory === category;


            if (
                matchesSearch &&
                matchesCategory
            ) {

                card.style.display =
                    "";

                visibleCount++;

            } else {

                card.style.display =
                    "none";

            }

        }
    );


    const noProductsMessage =
        document.getElementById(
            "noProductsMessage"
        );


    if (noProductsMessage) {

        if (visibleCount === 0) {

            noProductsMessage.classList.remove(
                "d-none"
            );

        } else {

            noProductsMessage.classList.add(
                "d-none"
            );

        }

    }

}


/*
|--------------------------------------------------------------------------
| INITIALIZE
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        renderCart();

    }
);

</script>


<script src="../assets/js/app.js"></script>

</body>

</html>