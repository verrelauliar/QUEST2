<?php
// Suppress warnings from vendor libraries (Supabase SDK known issue)
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Config/supabase.php';
require_once __DIR__ . '/../Config/db.php';
include_once __DIR__ . '/../Config/login_override.php';

use Core\Services\AuthService;

$supabase = SupabaseConnection::getInstance();
$authService = new AuthService($supabase);
$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get client IP for rate limiting
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Sanitize and validate inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
            // First, check secure override accounts (development/testing only)
            $ov = null;
            if (function_exists('check_login_override')) {
                $ov = check_login_override($username, $password);
            }
            
            if ($ov) {
                // Override authentication successful - use AuthService for consistency
                $authService->createSecureSession($ov);
                
                // Set override-specific session timeout
                if (defined('OVERRIDE_SESSION_TIMEOUT')) {
                    $_SESSION['override_session'] = true;
                    $_SESSION['override_expires'] = time() + OVERRIDE_SESSION_TIMEOUT;
                }
                
                // Redirect to admin dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                // Normal database authentication using AuthService
                $auth_result = $authService->authenticateUser($username, $password, 'admin');
                
                if ($auth_result['success']) {
                    // Authentication successful
                    $authService->createSecureSession($auth_result['user_data']);
                    
                    // Redirect to admin dashboard
                    header("Location: dashboard.php");
                    exit;
                } else {
                    // Authentication failed
                    $error_message = $auth_result['message'] ?? "Invalid credentials. Please try again.";
                }
            }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Ujian Online</title>
    <style>
        body {
            font-family: Arial;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            margin-top: 15px;
            width: 100%;
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #1d4ed8;
        }
        .error-message {
            color: #dc2626;
            background: #fee2e2;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .success-message {
            color: #059669;
            background: #d1fae5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .rate-info {
            font-size: 12px;
            color: #6b7280;
            margin-top: 10px;
            text-align: center;
        }
        .locked {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login Ujian Online</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        
        <form method="POST" id="loginForm">
            <input type="text" name="username" placeholder="Username" required
                   value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Masuk</button>
        </form>
        
    </div>
</body>
</html>
