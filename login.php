<?php

session_start();

require_once 'config/database.php';

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
| Process Login
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

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Validate Input
        |--------------------------------------------------------------------------
        */

        if ($username === '' || $password === '') {

            $error = 'Please enter both username and password.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Find User
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    full_name,
                    username,
                    password,
                    role
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([$username]);

            $user = $stmt->fetch();

            /*
            |--------------------------------------------------------------------------
            | Verify Password
            |--------------------------------------------------------------------------
            */

            if (
                $user &&
                password_verify($password, $user['password'])
            ) {

                /*
                |--------------------------------------------------------------------------
                | Regenerate Session ID
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);

                /*
                |--------------------------------------------------------------------------
                | Store User Information
                |--------------------------------------------------------------------------
                */

                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                /*
                |--------------------------------------------------------------------------
                | Create New CSRF Token
                |--------------------------------------------------------------------------
                */

                $_SESSION['csrf_token'] =
                    bin2hex(random_bytes(32));

                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                */

                header('Location: admin/dashboard.php');
                exit;

            } else {

                $error = 'Invalid username or password.';

            }
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

    <title>Login - Bar Management System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            min-height: 100vh;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.10);
        }

        .login-header {
            text-align: center;
            padding-top: 25px;
        }

        .login-icon {
            font-size: 45px;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card login-card mx-auto">

        <div class="login-header">

            <div class="login-icon">
                🍹
            </div>

            <h3 class="mt-2">
                Bar Management System
            </h3>

            <p class="text-muted">
                Sign in to continue
            </p>

        </div>


        <div class="card-body p-4">

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <form method="POST" action="">

                <!-- CSRF Token -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                >


                <!-- Username -->

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
                        autocomplete="username"
                        maxlength="50"
                        required
                    >

                </div>


                <!-- Password -->

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
                        placeholder="Enter password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <!-- Login Button -->

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                >
                    Login
                </button>

            </form>

        </div>


        <div class="card-footer text-center text-muted">

            <small>
                Bar Management System
            </small>

        </div>

    </div>

</div>

</body>

</html>