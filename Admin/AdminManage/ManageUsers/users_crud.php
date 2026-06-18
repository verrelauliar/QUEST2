<?php
/**
 * User CRUD Operations - PDO Implementation
 * 
 * Provides high-performance database operations for user management.
 * Uses prepared statements for security (SQL injection prevention).
 * Supports both traditional POST and AJAX API calls.
 * 
 * @see constitution.md for architecture details
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../Config/db_pdo.php';

// ============================================================================
// AJAX API Handler - Process JSON requests when action parameter is present
// ============================================================================
if (isset($_POST['action']) || isset($_GET['action'])) {
    session_start();
    
    // Verify admin auth
    if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $admin_id = (int)$_SESSION['user']['id'];
    
    try {
        switch ($action) {
            case 'add':
                $result = addUser($_POST);
                $success = strpos($result, 'successfully') !== false;
                echo json_encode(['success' => $success, 'message' => $result]);
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $result = updateUser($id, $_POST);
                $success = strpos($result, 'updated') !== false || strpos($result, 'successfully') !== false;
                echo json_encode(['success' => $success, 'message' => $result]);
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $result = deleteUser($id, $admin_id);
                $success = strpos($result, 'deleted') !== false;
                echo json_encode(['success' => $success, 'message' => $result]);
                break;
                
            case 'get':
                $id = (int)($_GET['id'] ?? 0);
                $user = getUserById($id);
                echo json_encode(['success' => true, 'data' => $user]);
                break;
                
            case 'list':
                $users = getAllUsers();
                echo json_encode(['success' => true, 'data' => $users]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Add this near the top of users_crud.php, after the AJAX API Handler section

if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user_import_template.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($output, ['username', 'email', 'password', 'full_name', 'role']);
    
    // Sample rows (optional - remove if you want empty template)
    fputcsv($output, ['jdoe', 'jdoe@school.com', 'Password123', 'John Doe', 'student']);
    fputcsv($output, ['msmith', 'msmith@school.com', 'Password456', 'Mary Smith', 'teacher']);
    
    fclose($output);
    exit();
}

/**
 * Escape HTML for output
 */
function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Get all users ordered by full_name
 * 
 * @return array List of users
 */
function getAllUsers(): array
{
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("
            SELECT id_user, username, full_name, email, role, status 
            FROM tbl_users 
            ORDER BY full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getAllUsers PDO error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a single user by ID
 * 
 * @param int $id User ID
 * @return array|null User data or null if not found
 */
function getUserById(int $id): ?array
{
    if ($id <= 0) return null;
    
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("
            SELECT id_user, username, full_name, email, role, status 
            FROM tbl_users 
            WHERE id_user = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    } catch (PDOException $e) {
        error_log("getUserById PDO error: " . $e->getMessage());
        return null;
    }
}

/**
 * Add a new user
 * 
 * @param array $data User data from form
 * @return string Status message
 */
function addUser(array $data): string
{
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $role = $data['role'] ?? '';
    $status = $data['status'] ?? 'active';

    // Validation
    if ($username === '' || strlen($username) < 3) {
        return 'Username must be at least 3 characters.';
    }
    if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
        return 'Invalid role.';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        return 'Invalid status.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email format.';
    }

    try {
        $pdo = getPDOConnection();
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id_user FROM tbl_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            return 'Username already taken.';
        }

        // Hash password (use random if not provided)
        $hash = password_hash($password ?: bin2hex(random_bytes(6)), PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO tbl_users (username, password, full_name, email, role, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $hash, $full_name, $email, $role, $status]);
        
        return 'User added successfully.';
        
    } catch (PDOException $e) {
        error_log("addUser PDO error: " . $e->getMessage());
        return 'Failed to add user.';
    }
}

/**
 * Update an existing user
 * 
 * @param int $id User ID
 * @param array $data User data from form
 * @return string Status message
 */
function updateUser(int $id, array $data): string
{
    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $role = $data['role'] ?? '';
    $status = $data['status'] ?? 'active';
    $password = $data['password'] ?? '';

    // Validation
    if ($id <= 0) {
        return 'Invalid user ID.';
    }
    if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
        return 'Invalid role.';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        return 'Invalid status.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email format.';
    }

    try {
        $pdo = getPDOConnection();
        
        if ($password !== '') {
            // Update with new password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE tbl_users 
                SET full_name = ?, email = ?, role = ?, status = ?, password = ?
                WHERE id_user = ?
            ");
            $stmt->execute([$full_name, $email, $role, $status, $hash, $id]);
        } else {
            // Update without password change
            $stmt = $pdo->prepare("
                UPDATE tbl_users 
                SET full_name = ?, email = ?, role = ?, status = ?
                WHERE id_user = ?
            ");
            $stmt->execute([$full_name, $email, $role, $status, $id]);
        }
        
        return 'User updated.';
        
    } catch (PDOException $e) {
        error_log("updateUser PDO error: " . $e->getMessage());
        return 'Update failed.';
    }
}

/**
 * Delete a user
 * 
 * @param int $id User ID to delete
 * @param int $sessionId Current admin's ID (cannot delete self)
 * @return string Status message
 */
function deleteUser(int $id, int $sessionId): string
{
    if ($id === $sessionId) {
        return 'Cannot delete your own account.';
    }
    if ($id <= 0) {
        return 'Invalid ID.';
    }

    try {
        $pdo = getPDOConnection();
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id_user FROM tbl_users WHERE id_user = ? LIMIT 1");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            return 'User not found.';
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM tbl_users WHERE id_user = ?");
        $stmt->execute([$id]);
        
        return 'User deleted.';
        
    } catch (PDOException $e) {
        error_log("deleteUser PDO error: " . $e->getMessage());
        return 'Delete failed.';
    }
}
function bulkImportUsers(array $file): array 
{
    $newIds = [];
    $successCount = 0;
    $errors = [];

    try {
        $pdo = getPDOConnection();
        
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            fgetcsv($handle); // Skip header row

            $checkStmt = $pdo->prepare("SELECT id_user FROM tbl_users WHERE email = ? OR username = ? LIMIT 1");
            $insertStmt = $pdo->prepare("
                INSERT INTO tbl_users (username, email, full_name, role, password, status) 
                VALUES (?, ?, ?, ?, ?, 'active')
            ");

            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 5) continue; // Changed from 4 to 5

                $username = trim($data[0]);
                $email = trim($data[1]);
                $csvPassword = trim($data[2]);  // NEW: Read password from CSV
                $fullName = trim($data[3]);     // Changed from index 2 to 3
                $role = strtolower(trim($data[4])); // Changed from index 3 to 4

                // Validation
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Skip: Invalid email for $username";
                    continue;
                }

                if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
                    $errors[] = "Skip: Invalid role '$role' for $username";
                    continue;
                }

                // Use CSV password if provided, otherwise default
                $password = !empty($csvPassword) 
                    ? password_hash($csvPassword, PASSWORD_DEFAULT)
                    : password_hash('password123', PASSWORD_DEFAULT);

                // Check for duplicates
                $checkStmt->execute([$email, $username]);
                if ($checkStmt->fetch()) {
                    $errors[] = "Skip: $username already exists.";
                    continue;
                }

                // Insert User
                $insertStmt->execute([$username, $email, $fullName, $role, $password]);
                $newIds[] = (int)$pdo->lastInsertId();
                $successCount++;
            }
            fclose($handle);
        }
    } catch (PDOException $e) {
        error_log("bulkImportUsers PDO error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error during import: ' . $e->getMessage(),
            'newIds' => []
        ];
    }

    $message = "Imported $successCount users successfully.";
    if (!empty($errors)) {
        $message .= " Errors: " . implode('; ', $errors);
    }

    return [
        'success' => $successCount > 0,
        'message' => $message,
        'newIds' => $newIds
    ];
}