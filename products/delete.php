<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

requireRole(['admin']);

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId) {
    die('Invalid product ID.');
}


/*
|--------------------------------------------------------------------------
| Check whether the product exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, product_name
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
| Delete Product
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        DELETE FROM products
        WHERE id = ?
    ");

    $stmt->execute([$productId]);

    header('Location: index.php?deleted=1');
    exit;

} catch (PDOException $e) {

    die(
        'Unable to delete product. ' .
        htmlspecialchars($e->getMessage())
    );

}