<?php
session_start();

/*
 |----------------------------------------
 | Destroy all session data
 |----------------------------------------
 */
$_SESSION = [];

// Destroy session cookie (important)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

include '../../includes/app.php';
/*
 |----------------------------------------
 | Redirect user to login page
 |----------------------------------------
 */
header("Location: " . BASE_URL . "index");
exit;
exit;
