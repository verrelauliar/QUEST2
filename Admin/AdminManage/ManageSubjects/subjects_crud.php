<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../Config/db_pdo.php';

use Core\Services\AuthService;

// Set JSON Content-Type header
header('Content-Type: application/json');

// ─────────────────────────────────────────────────────────────
// Session Management & Authentication
// ─────────────────────────────────────────────────────────────
session_start();

if (empty($_SESSION['auth_verified'])) {
    $auth = new AuthService();
    $user = $auth->getCurrentUser();
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access. Please login as admin.'
        ]);
        exit;
    }
    
    $_SESSION['user'] = $user;
    $_SESSION['auth_verified'] = true;
}

// Manual RLS: Admin only
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login as admin.'
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────
// Main Logic with Error Handling
// ─────────────────────────────────────────────────────────────
try {
    // DEBUG: Log received data
    error_log("=== subjects_crud.php Debug ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session auth: " . (isset($_SESSION['auth_verified']) ? 'true' : 'false'));
    error_log("User role: " . (isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'none'));
    error_log("Checking for add: " . (isset($_POST['add']) ? 'found' : 'not found'));
    error_log("Checking for update: " . (isset($_POST['update']) ? 'found' : 'not found'));
    error_log("Checking for action_type: " . (isset($_POST['action_type']) ? $_POST['action_type'] : 'not found'));
    
    $pdo = getPDOConnection();
    
    // Validate action - check for button names OR action_type hidden field
    $action = '';
    $id = null;
    
    if (isset($_POST['add']) || (isset($_POST['action_type']) && $_POST['action_type'] === 'add')) {
        $action = 'add';
        error_log("Action determined: add");
    } elseif (isset($_POST['update']) || (isset($_POST['action_type']) && $_POST['action_type'] === 'update')) {
        $action = 'edit';
        error_log("Action determined: edit");
    } elseif (isset($_POST['delete']) || (isset($_POST['action_type']) && $_POST['action_type'] === 'delete') || isset($_GET['delete'])) {
        $action = 'delete';
        $id = filter_input(INPUT_POST, 'delete', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
        error_log("Action determined: delete");
    }
    
    if (!in_array($action, ['add', 'edit', 'delete'], true)) {
        error_log("ERROR: Invalid action - neither 'add', 'update', nor 'delete' found in POST data");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        exit;
    }
    
    // Only validate subject_name and subject_code for add/edit actions
    if ($action === 'add' || $action === 'edit') {
        // Validate subject name
        $subject_name = trim($_POST['subject_name'] ?? '');
        if ($subject_name === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Subject name is required'
            ]);
            exit;
        }
        
        // Validate subject code
        $subject_code = trim($_POST['subject_code'] ?? '');
        if ($subject_code === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Subject code is required'
            ]);
            exit;
        }
        
        // Validate subject_code format (alphanumeric, hyphens, underscores allowed)
        if (!preg_match('/^[a-zA-Z0-9-_]+$/', $subject_code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Subject code can only contain letters, numbers, hyphens, and underscores'
            ]);
            exit;
        }
        
        // Get description (optional field)
        $description = trim($_POST['description'] ?? '');
        
        // Get ID for edit
        if ($action === 'edit') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid subject ID'
                ]);
                exit;
            }
        }
    }
    
    if ($action === 'add') {
        // Check for duplicate subject_code (for add operation)
        $checkStmt = $pdo->prepare("SELECT id_subject FROM tbl_subjects WHERE subject_code = ?");
        $checkStmt->execute([$subject_code]);
        if ($checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Subject code already exists. Please choose a different code.'
            ]);
            exit;
        }
        
        // Add new subject
        try {
            $stmt = $pdo->prepare('INSERT INTO tbl_subjects (subject_name, subject_code, description) VALUES (?, ?, ?)');
            $stmt->execute([$subject_name, $subject_code, $description]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Subject added successfully',
                'data' => [
                    'id_subject' => $pdo->lastInsertId()
                ]
            ]);
            exit;
        } catch (PDOException $e) {
            error_log('Insert subject error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ]);
            exit;
        }
    } elseif ($action === 'edit') {
        // Update existing subject
        // Check for duplicate subject_code for a DIFFERENT subject (for edit operation)
        $checkStmt = $pdo->prepare("SELECT id_subject FROM tbl_subjects WHERE subject_code = ? AND id_subject != ?");
        $checkStmt->execute([$subject_code, $id]);
        if ($checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Subject code already exists. Please choose a different code.'
            ]);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare('UPDATE tbl_subjects SET subject_name = ?, subject_code = ?, description = ? WHERE id_subject = ?');
            $stmt->execute([$subject_name, $subject_code, $description, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Subject updated successfully'
            ]);
            exit;
        } catch (PDOException $e) {
            error_log('Update subject error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ]);
            exit;
        }
    } elseif ($action === 'delete') {
        // Handle delete operation
        // Validate ID
        if (empty($id) || $id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid subject ID'
            ]);
            exit;
        }
        
        // Check if subject exists
        $checkStmt = $pdo->prepare("SELECT id_subject FROM tbl_subjects WHERE id_subject = ?");
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Subject not found'
            ]);
            exit;
        }
        
        // Check for dependencies (teaching assignments, exams, etc.)
        $depCheck = $pdo->prepare("SELECT id_assignment FROM tbl_teaching_assignments WHERE subject_id = ? LIMIT 1");
        $depCheck->execute([$id]);
        if ($depCheck->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete subject: it is assigned to one or more classes. Please remove teaching assignments first.'
            ]);
            exit;
        }
        
        // Perform delete
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM tbl_subjects WHERE id_subject = ?");
            $deleteStmt->execute([$id]);
            
            // Check if delete was successful
            if ($deleteStmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Subject deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete subject'
                ]);
            }
            exit;
        } catch (PDOException $e) {
            error_log('Delete subject error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting the subject. Please try again.'
            ]);
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log('Subject CRUD error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
    exit;
} finally {
    // Close database connection
    if (isset($pdo)) {
        $pdo = null;
    }
}
