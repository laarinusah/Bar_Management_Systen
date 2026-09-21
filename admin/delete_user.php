<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin']);

$userId = (int) ($_GET['id'] ?? 0);

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| Validate User ID
|--------------------------------------------------------------------------
*/

if ($userId <= 0) {
    header("Location: users.php?error=invalid");
    exit;
}


/*
|--------------------------------------------------------------------------
| Prevent Self Deletion
|--------------------------------------------------------------------------
*/

if ($userId === $currentUserId) {
    header("Location: users.php?error=self_delete");
    exit;
}


/*
|--------------------------------------------------------------------------
| Check User Exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, full_name, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php?error=not_found");
    exit;
}


/*
|--------------------------------------------------------------------------
| Prevent Deleting Last Administrator
|--------------------------------------------------------------------------
*/

if ($user['role'] === 'admin') {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'admin'
    ");

    $adminCount = (int) $stmt->fetchColumn();

    if ($adminCount <= 1) {
        header("Location: users.php?error=last_admin");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Delete User
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $stmt->execute([$userId]);

    header("Location: users.php?deleted=1");
    exit;

} catch (PDOException $e) {

    header("Location: users.php?error=delete_failed");
    exit;
}