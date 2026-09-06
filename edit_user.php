<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin']);

$error = '';
$success = '';

$userId = (int) ($_GET['id'] ?? 0);

if ($userId <= 0) {
    header("Location: users.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, full_name, username, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Update User
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $allowedRoles = ['admin', 'manager', 'cashier'];

    if ($fullName === '' || $username === '') {

        $error = 'Full name and username are required.';

    } elseif (!in_array($role, $allowedRoles, true)) {

        $error = 'Invalid role selected.';

    } else {

        try {

            /*
             * Check username belongs to another user.
             */

            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $username,
                $userId
            ]);

            if ($stmt->fetch()) {

                $error = 'Username already exists.';

            } else {

                /*
                 * Update with or without password.
                 */

                if ($password !== '') {

                    if (strlen($password) < 6) {

                        $error = 'Password must be at least 6 characters long.';

                    } else {

                        $hashedPassword = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET
                                full_name = ?,
                                username = ?,
                                password = ?,
                                role = ?
                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $fullName,
                            $username,
                            $hashedPassword,
                            $role,
                            $userId
                        ]);

                        $success = 'User updated successfully.';

                    }

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET
                            full_name = ?,
                            username = ?,
                            role = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $fullName,
                        $username,
                        $role,
                        $userId
                    ]);

                    $success = 'User updated successfully.';

                }

                /*
                 * Reload updated user.
                 */

                $stmt = $pdo->prepare("
                    SELECT id, full_name, username, role
                    FROM users
                    WHERE id = ?
                    LIMIT 1
                ");

                $stmt->execute([$userId]);

                $user = $stmt->fetch();

            }

        } catch (PDOException $e) {

            $error = 'Unable to update user. Please try again.';

        }

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
        Edit User - Bar Management System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f8f9fa;
        }

        .page-content {
            padding: 25px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 768px) {

            .page-content {
                padding: 15px;
            }

            .page-title {
                font-size: 22px;
            }

        }

    </style>

</head>

<body>

<?php require_once '../includes/sidebar.php'; ?>

<div class="page-content">

    <div class="mb-4">

        <h2 class="page-title mb-1">

            <i class="bi bi-person-gear"></i>

            Edit User

        </h2>

        <p class="text-muted mb-0">

            Update the selected user's information.

        </p>

    </div>


    <?php if ($success): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle"></i>

            <?= htmlspecialchars($success) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <div class="row">

        <div class="col-lg-6">

            <div class="card">

                <div class="card-header bg-white py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-person"></i>

                        User Information

                    </h5>

                </div>


                <div class="card-body">

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $user['full_name']
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Username
                            </label>

                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $user['username']
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                New Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                minlength="6"
                                placeholder="Leave blank to keep current password"
                            >

                            <small class="text-muted">
                                Leave blank if you do not want to change the password.
                            </small>

                        </div>


                        <div class="mb-4">

                            <label class="form-label">
                                Role
                            </label>

                            <select
                                name="role"
                                class="form-select"
                                required
                            >

                                <option
                                    value="cashier"
                                    <?= $user['role'] === 'cashier'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Cashier
                                </option>

                                <option
                                    value="manager"
                                    <?= $user['role'] === 'manager'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Manager
                                </option>

                                <option
                                    value="admin"
                                    <?= $user['role'] === 'admin'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Admin
                                </option>

                            </select>

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-save"></i>

                                Save Changes

                            </button>


                            <a
                                href="users.php"
                                class="btn btn-secondary"
                            >

                                <i class="bi bi-arrow-left"></i>

                                Back

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>