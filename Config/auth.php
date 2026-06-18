<?php
/**
 * Basic Authentication System
 * Provides essential authentication functions for the application
 */

// Include Supabase configuration and Database Adapter
require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/../Core/Services/DatabaseAdapter.php';

use Core\Services\DatabaseAdapter;

/**
 * User authentication with database
 * @param mysqli $conn Database connection (kept for compatibility)
 * @param string $username Username to authenticate
 * @param string $password Password to verify
 * @return array Result array with 'success', 'message', and 'user_data' keys
 */
function authenticate_user($conn, $username, $password) {
    // Note: $conn parameter kept for compatibility but not used
    
    // Validate input parameters
    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Invalid credentials',
            'user_data' => null
        ];
    }
    
    try {
        // Use database adapter for database-agnostic authentication
        $db = DatabaseAdapter::create();
        
        // Query using database adapter (works with both MySQL and Supabase)
        $query = "SELECT id_user, username, role, password, status FROM tbl_users WHERE username = ? LIMIT 1";
        $result = $db->query($query, [$username]);
        $user = $result->fetch_assoc();
        
        if (empty($user)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user_data' => null
            ];
        }
        
        // Verify password (assuming passwords are hashed the same way)
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user_data' => null
            ];
        }
        
        // Check account status
        $status = strtolower($user['status'] ?? '');
        if (!in_array($status, ['active', 'aktif'])) {
            return [
                'success' => false,
                'message' => 'Account is not active',
                'user_data' => null
            ];
        }
        
        // Success - return user data (without password)
        unset($user['password']);
        
        return [
            'success' => true,
            'message' => 'Authentication successful',
            'user_data' => $user
        ];
        
    } catch (\Exception $e) {
        error_log("Database authentication exception: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Authentication system error',
            'user_data' => null
        ];
    }
}

/**
 * Create session after successful authentication
 * @param array $user_data User data from authentication
 */
function create_session($user_data) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['id_user'] = $user_data['id_user'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['role'] = $user_data['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    error_log("Session created for user: " . $user_data['username']);
}

/**
 * Validate session and check for timeout
 * @return array Result with 'valid' and 'message' keys
 */
function validate_session() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Check if session has required variables
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['username'])) {
        return [
            'valid' => false,
            'message' => 'Invalid session'
        ];
    }
    
    $current_time = time();
    
    // Check for session timeout (30 minutes of inactivity)
    $max_lifetime = 1800; // 30 minutes
                     
    if (isset($_SESSION['last_activity']) && ($current_time - $_SESSION['last_activity'] > $max_lifetime)) {
        session_destroy();
        return [
            'valid' => false,
            'message' => 'Session expired'
        ];
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = $current_time;
    
    return [
        'valid' => true,
        'message' => 'Session valid'
    ];
}

/**
 * Redirect user based on role
 * @param string $role User role
 */
function redirect_by_role($role) {
    $redirects = [
        'admin' => 'dashboard.php',
        'teacher' => '../MainApp/MainTeacher/teacher_dashboard.php',
        'student' => '../MainApp/MainStudent/student_dashboard.php'
    ];
    
    $location = $redirects[$role] ?? 'dashboard.php';
    
    header("Location: $location");
    exit();
}

/**
 * Sanitize input data
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Dummy CSRF functions (for compatibility)
 */
function generate_csrf_token() {
    return 'dummy_token';
}

function validate_csrf_token($token, $max_age = 3600) {
    return true; // Always valid in simplified version
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="dummy_token">';
}

/**
 * Dummy logging function (for compatibility)
 */
function log_auth_attempt($username, $ip_address, $success, $reason = '', $context = []) {
    $status = $success ? 'SUCCESS' : 'FAILED';
    error_log("AUTH $status: User='$username', IP='$ip_address', Reason='$reason'");
}

/**
 * Dummy rate limiting functions (for compatibility)
 */
function check_rate_limit($username, $ip_address = null) {
    return [
        'allowed' => true,
        'remaining_time' => 0,
        'attempts_remaining' => 5
    ];
}

function record_failed_attempt($username, $ip_address = null) {
    return [
        'locked_out' => false,
        'lockout_duration' => 0,
        'total_attempts' => 1
    ];
}

function clear_failed_attempts($username, $ip_address = null) {
    // Dummy function - nothing to clear
}

function get_rate_limit_status($username, $ip_address = null) {
    return [
        'attempts_made' => 0,
        'attempts_remaining' => 5,
        'is_locked' => false,
        'lockout_remaining' => 0
    ];
}
?>