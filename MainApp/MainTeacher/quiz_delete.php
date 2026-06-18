<?php
/**
 * OPTIMIZED Quiz Delete
 * 
 * Performance improvements:
 * 1. Session-cached auth to skip Supabase HTTP on subsequent requests
 * 2. Single PDO delete with Manual RLS embedded in WHERE clause
 * 3. Secure ownership verification - only deletes if teacher owns the exam
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user_data = isset($_SESSION['user']) && isset($_SESSION['user']['role']);

if ($auth_cached && $has_user_data && $_SESSION['user']['role'] === 'teacher') {
    // FAST PATH: Auth already verified (~0ms overhead)
    $teacher_id_int = (int)$_SESSION['user']['id'];
} else {
    // SLOW PATH: First request - full Supabase validation
    require_once __DIR__ . '/../../Config/supabase.php';
    
    $authService = new AuthService($supabase);
    $session_result = $authService->validateSession();
    
    if (!$session_result['valid'] || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'teacher') {
        session_write_close();
        header("Location: ../index.php");
        exit;
    }
    
    $_SESSION['auth_verified'] = true;
    $teacher_id_int = (int)$_SESSION['user']['id'];
}

session_write_close();

// ============================================================================
// PHASE 2: Handle delete with PDO and Manual RLS
// ============================================================================
if (isset($_GET['id'])) {
    $exam_id = (int)$_GET['id'];
    
    if ($exam_id <= 0) {
        echo "<script>alert('Invalid exam ID'); window.history.back();</script>";
        exit;
    }

    require_once __DIR__ . '/../../Config/db_pdo.php';
    $pdo = getPDOConnection();

    try {
        // Secure delete with Manual RLS: Only delete if teacher owns this exam
        // The subquery ensures the teacher_id matches before any deletion occurs
        $stmt = $pdo->prepare("
            DELETE FROM tbl_exams 
            WHERE id_exam = ? 
            AND assignment_id IN (
                SELECT id_assignment 
                FROM tbl_teaching_assignments 
                WHERE teacher_id = ?
            )
        ");
        $stmt->execute([$exam_id, $teacher_id_int]);
        
        if ($stmt->rowCount() === 0) {
            echo "<script>alert('Quiz not found or unauthorized'); window.history.back();</script>";
            exit;
        }

        echo "<script>alert('Quiz berhasil dihapus!'); window.location.href='teacher_dashboard.php';</script>";
    } catch (Exception $e) {
        error_log("Failed to delete quiz: " . $e->getMessage());
        echo "<script>alert('Gagal menghapus quiz'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('ID Quiz tidak ditemukan!'); window.history.back();</script>";
}
?>
