<?php
/**
 * Logout Handler
 * Pilah-Pilih Partner Dashboard
 * * Securely destroys user session and logs the action
 */

require_once 'config.php';

// Log the logout action before destroying session
if (isset($_SESSION['user_id'])) {
    log_audit_action($_SESSION['user_id'], 'LOGOUT', 'User logged out', $pdo);
    $user_id = $_SESSION['user_id'];
}

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
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

// Redirect to login with success message
header('Location: login.php?logout=1');
exit();
?>