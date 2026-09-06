<?php

/*
|--------------------------------------------------------------------------
| Start Secure Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    $isHttps = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| Check Whether User Is Logged In
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function requireLogin(): void
{
    if (!isLoggedIn()) {

        header('Location: ../login.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Specific Role
|--------------------------------------------------------------------------
*/

function requireRole(array $allowedRoles): void
{
    requireLogin();

    $userRole = $_SESSION['user_role'] ?? '';

    if (!in_array($userRole, $allowedRoles, true)) {

        http_response_code(403);

        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >
            <title>Access Denied</title>

            <link
                href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
                rel="stylesheet"
            >
        </head>

        <body class="bg-light">

            <div class="container py-5">

                <div class="card shadow-sm mx-auto" style="max-width: 500px;">

                    <div class="card-body text-center p-5">

                        <div class="display-4 mb-3">
                            🔒
                        </div>

                        <h3 class="mb-3">
                            Access Denied
                        </h3>

                        <p class="text-muted">
                            You do not have permission to access this page.
                        </p>

                        <a
                            href="../admin/dashboard.php"
                            class="btn btn-primary"
                        >
                            Back to Dashboard
                        </a>

                    </div>

                </div>

            </div>

        </body>
        </html>';

        exit;
    }
}