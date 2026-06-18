<?php
/**
 * Secure Logout Handler
 * Implements proper session destruction with regeneration
 * Enhanced with override session support
 */

// Note: audit_logger.php has been removed as part of security simplification

// Start or continue session
session_start();

// Log logout event for security monitoring
$username = $_SESSION['username'] ?? 'unknown';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$current_time = time();

$session_duration = isset($_SESSION['login_time']) ? $current_time - $_SESSION['login_time'] : 0;
$log_message = sprintf("[%s] LOGOUT: User='%s', IP='%s', Session_Duration=%d seconds",
    date('Y-m-d H:i:s'), $username, $ip_address, $session_duration);

error_log($log_message);

// 1. Regenerate session ID before destruction to prevent session fixation
session_regenerate_id(true);

// 2. Unset all session variables
$_SESSION = array();

// 3. If session cookies are used, destroy cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy session completely
session_destroy();

// 5. Redirect to login page with a clean slate
header("Location: login.php");
exit; // Always use exit after header Location
?>