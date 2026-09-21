<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin']);

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Create CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| Success / Error Messages
|--------------------------------------------------------------------------
*/

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $message = 'User deleted successfully.';
}

if (isset($_GET['error'])) {

    switch ($_GET['error']) {

        case 'self_delete':
            $error = 'You cannot delete your own account.';
            break;

        case 'last_admin':
            $error = 'The last administrator cannot be deleted.';
            break;

        case 'not_found':
            $error = 'User not found.';
            break;

        case 'invalid':
            $error = 'Invalid user selected.';
            break;

        case 'delete_failed':
            $error = 'Unable to delete the user.';
            break;
    }
}


/*
|--------------------------------------------------------------------------
| Add User
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Verify CSRF Token
    |--------------------------------------------------------------------------
    */

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        empty($csrfToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {

        $error = 'Invalid security token. Please refresh the page and try again.';

    } else {

        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'cashier';

        $allowedRoles = [
            'admin',
            'manager',
            'cashier'
        ];

        /*
        |--------------------------------------------------------------------------
        | Validate Input
        |--------------------------------------------------------------------------
        */

        if (
            $fullName === '' ||
            $username === '' ||
            $password === ''
        ) {

            $error = 'Please fill in all required fields.';

        } elseif (strlen($fullName) > 100) {

            $error = 'Full name is too long.';

        } elseif (
            strlen($username) < 3 ||
            strlen($username) > 50
        ) {

            $error = 'Username must be between 3 and 50 characters.';

        } elseif (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {

            $error = 'Username contains invalid characters.';

        } elseif (!in_array($role, $allowedRoles, true)) {

            $error = 'Invalid user role selected.';

        } elseif (strlen($password) < 6) {

            $error = 'Password must be at least 6 characters long.';

        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | Check Username
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE username = ?
                    LIMIT 1
                ");

                $stmt->execute([$username]);

                if ($stmt->fetch()) {

                    $error =
                        'Username already exists. Please choose another username.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Hash Password
                    |--------------------------------------------------------------------------
                    */

                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Create User
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO users
                        (
                            full_name,
                            username,
                            password,
                            role
                        )
                        VALUES (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $fullName,
                        $username,
                        $hashedPassword,
                        $role
                    ]);

                    $message = 'User created successfully.';

                    /*
                    |--------------------------------------------------------------------------
                    | Refresh CSRF Token
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['csrf_token'] =
                        bin2hex(random_bytes(32));
                }

            } catch (PDOException $e) {

                $error =
                    'Unable to create user. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        full_name,
        username,
        role,
        created_at
    FROM users
    ORDER BY id DESC
");

$users = $stmt->fetchAll();

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
        User Management - Bar Management System
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

        .role-badge {
            text-transform: capitalize;
        }

        .action-buttons {
            white-space: nowrap;
        }

        @media (max-width: 768px) {

            .page-content {
                padding: 15px;
            }

            .page-title {
                font-size: 22px;
            }

            .table {
                font-size: 14px;
            }

            .action-buttons .btn {
                margin-bottom: 4px;
            }

        }

    </style>

</head>

<body>

<?php require_once '../includes/sidebar.php'; ?>

<div class="page-content">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        <h2 class="page-title mb-1">

            <i class="bi bi-people"></i>

            User Management

        </h2>

        <p class="text-muted mb-0">

            Create and manage system users.

        </p>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if ($message): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle"></i>

            <?= htmlspecialchars($message) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if ($error): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle"></i>

            <?= htmlspecialchars($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <div class="row g-4">

        <!-- ADD USER -->

        <div class="col-lg-4">

            <div class="card">

                <div class="card-header bg-white py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-person-plus"></i>

                        Add New User

                    </h5>

                </div>


                <div class="card-body">

                    <form method="POST">

                        <!-- CSRF TOKEN -->

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                        >


                        <!-- FULL NAME -->

                        <div class="mb-3">

                            <label
                                for="full_name"
                                class="form-label"
                            >
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                id="full_name"
                                class="form-control"
                                placeholder="Enter full name"
                                maxlength="100"
                                required
                            >

                        </div>


                        <!-- USERNAME -->

                        <div class="mb-3">

                            <label
                                for="username"
                                class="form-label"
                            >
                                Username
                            </label>

                            <input
                                type="text"
                                name="username"
                                id="username"
                                class="form-control"
                                placeholder="Enter username"
                                maxlength="50"
                                required
                            >

                        </div>


                        <!-- PASSWORD -->

                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label"
                            >
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                placeholder="Minimum 6 characters"
                                minlength="6"
                                required
                            >

                        </div>


                        <!-- ROLE -->

                        <div class="mb-3">

                            <label
                                for="role"
                                class="form-label"
                            >
                                Role
                            </label>

                            <select
                                name="role"
                                id="role"
                                class="form-select"
                                required
                            >

                                <option value="cashier">
                                    Cashier
                                </option>

                                <option value="manager">
                                    Manager
                                </option>

                                <option value="admin">
                                    Admin
                                </option>

                            </select>

                        </div>


                        <!-- CREATE USER -->

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >

                            <i class="bi bi-person-plus"></i>

                            Create User

                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- USER LIST -->

        <div class="col-lg-8">

            <div class="card">

                <div class="card-header bg-white py-3">

                    <h5 class="mb-0">

                        <i class="bi bi-people"></i>

                        System Users

                    </h5>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Action</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (count($users) > 0): ?>

                                    <?php foreach ($users as $index => $user): ?>

                                        <tr>

                                            <td>
                                                <?= $index + 1 ?>
                                            </td>


                                            <td>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $user['full_name']
                                                    ) ?>

                                                </strong>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $user['username']
                                                ) ?>

                                            </td>


                                            <td>

                                                <span
                                                    class="badge
                                                    <?=
                                                        $user['role'] === 'admin'
                                                            ? 'text-bg-danger'
                                                            : (
                                                                $user['role'] === 'manager'
                                                                    ? 'text-bg-warning'
                                                                    : 'text-bg-primary'
                                                            )
                                                    ?>
                                                    role-badge"
                                                >

                                                    <?= htmlspecialchars(
                                                        $user['role']
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $user['created_at']
                                                ) ?>

                                            </td>


                                            <td class="action-buttons">

                                                <!-- EDIT -->

                                                <a
                                                    href="edit_user.php?id=<?= (int) $user['id'] ?>"
                                                    class="btn btn-sm btn-primary"
                                                >

                                                    <i class="bi bi-pencil-square"></i>

                                                    Edit

                                                </a>


                                                <!-- DELETE -->

                                                <?php if (
                                                    (int) $user['id']
                                                    !==
                                                    (int) $_SESSION['user_id']
                                                ): ?>

                                                    <a
                                                        href="delete_user.php?id=<?= (int) $user['id'] ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm(
                                                            'Are you sure you want to delete this user?'
                                                        );"
                                                    >

                                                        <i class="bi bi-trash"></i>

                                                        Delete

                                                    </a>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="text-center py-4"
                                        >

                                            No users found.

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

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>