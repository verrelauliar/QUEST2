<?php
/**
 * Subject Management Page (Teacher)
 * 
 * Displays teacher's assigned subjects with class information.
 * Uses Session-Cached Auth + PDO for optimal performance.
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
    $username = htmlspecialchars($_SESSION['user']['name']);
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
    $username = htmlspecialchars($_SESSION['user']['name']);
}

session_write_close();

// ============================================================================
// PHASE 2: Fetch subjects and assignments using PDO
// ============================================================================
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

$subjects = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            ta.id_assignment,
            s.id_subject,
            s.subject_name,
            s.description as subject_description,
            c.id_class,
            c.class_name,
            c.grade_level,
            (SELECT COUNT(*) FROM tbl_class_enrollments ce WHERE ce.class_id = c.id_class) as student_count,
            (SELECT COUNT(*) FROM tbl_exams e WHERE e.assignment_id = ta.id_assignment) as quiz_count
        FROM tbl_teaching_assignments ta
        INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        INNER JOIN tbl_classes c ON ta.class_id = c.id_class
        WHERE ta.teacher_id = ?
        ORDER BY s.subject_name, c.class_name
    ");
    $stmt->execute([$teacher_id_int]);
    $subjects = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading subjects: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Subjects | QUEST</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm border">
        <div class="d-flex align-items-center gap-3">
            <img src="../../Assets/Images/mumtaza_logo.png" alt="Logo" width="30" height="30" class="object-fit-contain">
            <h1 class="h5 mb-0 fw-bold text-dark">📚 Manage Subjects</h1>
        </div>
        <a href="teacher_dashboard.php" class="btn btn-light border btn-sm">← Back to Dashboard</a>
    </div>

    <div class="mb-4">
        <h2 class="h4 fw-bold text-dark mb-1">Your Teaching Assignments</h2>
        <p class="text-secondary small mb-0">View your assigned subjects and classes</p>
    </div>

    <?php if (empty($subjects)): ?>
        <div class="text-center p-5 bg-white rounded-3 shadow-sm border">
            <div class="mb-3 text-muted">
                <i class="bi bi-journal-x" style="font-size: 2rem;"></i>
            </div>
            <h3 class="h6 text-secondary fw-bold">No Subjects Assigned</h3>
            <p class="text-muted small mb-0">You don't have any teaching assignments yet. Contact your administrator.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($subjects as $subject): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-bold text-dark mb-2"><?= htmlspecialchars($subject['subject_name']) ?></h3>
                        <span class="badge bg-primary-subtle text-primary mb-3">
                            <?= htmlspecialchars($subject['class_name']) ?> (Grade <?= htmlspecialchars($subject['grade_level'] ?? 'N/A') ?>)
                        </span>
                        
                        <p class="text-secondary small mb-4" style="min-height: 40px;">
                            <?= !empty($subject['subject_description']) ? htmlspecialchars($subject['subject_description']) : 'No description available.' ?>
                        </p>
                        
                        <div class="row pt-3 border-top g-0 text-center">
                            <div class="col-6 border-end">
                                <div class="small text-secondary mb-1">Students</div>
                                <div class="h5 fw-bold text-dark mb-0"><?= (int)$subject['student_count'] ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-secondary mb-1">Quizzes</div>
                                <div class="h5 fw-bold text-dark mb-0"><?= (int)$subject['quiz_count'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html