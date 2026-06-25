<?php
/**
 * OPTIMIZED Teacher Dashboard
 * UI Updated: Centered Layout (Figma Match)
 * Architecture: Session-Cached Auth + PDO Data Fetching
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
    session_write_close();
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
    session_write_close();
}

// ============================================================================
// PHASE 2: High-Performance Data Fetching (PDO)
// ============================================================================
require_once __DIR__ . '/../../Config/db_pdo.php';

try {
    $pdo = getPDOConnection();
    
    // 1. Get all quizzes with subject/class info
    $stmt = $pdo->prepare("
        SELECT 
            e.id_exam, e.assignment_id, e.title, e.duration, e.is_active,
            s.subject_name, c.class_name, c.id_class,
            (SELECT COUNT(*) FROM tbl_questions q WHERE q.exam_id = e.id_exam) as num_questions,
            (SELECT COUNT(*) FROM tbl_class_enrollments ce WHERE ce.class_id = c.id_class) as total_students
        FROM tbl_exams e
        INNER JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
        INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        INNER JOIN tbl_classes c ON ta.class_id = c.id_class
        WHERE ta.teacher_id = ?
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$teacher_id_int]);
    $quizzes = $stmt->fetchAll();
    
    // 2. Get stats via bulk fetch
    $exam_ids = array_column($quizzes, 'id_exam');
    $attempts = [];
    
    if (!empty($exam_ids)) {
        $placeholders = implode(',', array_fill(0, count($exam_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT a.exam_id, a.student_id, sc.percentage, sc.passed
            FROM tbl_exam_attempts a
            LEFT JOIN tbl_scores sc ON a.id_attempt = sc.attempt_id
            WHERE a.exam_id IN ($placeholders)
        ");
        $stmt->execute($exam_ids);
        $attempts = $stmt->fetchAll();
    }
    
    // Process stats in memory (O(n))
    $attempts_by_exam = [];
    foreach ($attempts as $att) {
        $attempts_by_exam[$att['exam_id']][] = $att;
    }
    
    $student_counts = [];
    foreach ($quizzes as &$quiz) {
        $student_counts[$quiz['id_class']] = $quiz['total_students'];
        $exam_attempts = $attempts_by_exam[$quiz['id_exam']] ?? [];
        
        if (!empty($exam_attempts)) {
            $scores = array_filter(array_column($exam_attempts, 'percentage'), fn($v) => $v !== null);
            $quiz['avg_score'] = !empty($scores) ? array_sum($scores) / count($scores) : 0;
            
            $passed_count = count(array_filter($exam_attempts, fn($a) => !empty($a['passed'])));
            $quiz['progress'] = $quiz['total_students'] > 0 
                ? ($passed_count . '/' . $quiz['total_students']) 
                : '0/' . $quiz['total_students'];
            
            // Calculate progress bar percentage based on attempts vs total students
            $unique_attempts = count(array_unique(array_column($exam_attempts, 'student_id')));
            $quiz['progress_pct'] = $quiz['total_students'] > 0 
                ? ($unique_attempts / $quiz['total_students']) * 100 
                : 0;
        } else {
            $quiz['avg_score'] = 0;
            $quiz['progress'] = '0/' . $quiz['total_students'];
            $quiz['progress_pct'] = 0;
        }
    }
    unset($quiz);
    
} catch (Exception $e) {
    die($e->getMessage());
}

// Summary Stats
$total_students = !empty($student_counts) ? array_sum($student_counts) : 0;
$unique_students_attempted = count(array_unique(array_column($attempts, 'student_id')));
$completion_rate = ($total_students > 0) ? round(($unique_students_attempted / $total_students) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Dashboard | QUEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../Assets/CSS/teacher/dashboard_teacher.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg bg-white sticky-top border-bottom py-3 mb-5">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <img src="../../Assets/Images/logosmp2sindur.png" alt="Logo" width="40" height="40" class="object-fit-contain">
                <div>
                    <h5 class="fw-bold m-0 text-dark">Teacher Dashboard</h5>
                    <small class="text-secondary">Welcome back, <?= $username ?></small>
                </div>
            </div>
            
            <form method="POST" action="../logout.php" class="ms-auto">
                <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </nav>

    <div class="container pb-5">
        
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-4 rounded-3 bg-white hover-shadow">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary fw-medium">Total Quizzes</span>
                        <i class="bi bi-book text-muted fs-5"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"><?= count($quizzes) ?></h2>
                    <small class="text-muted">Active assessments</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-4 rounded-3 bg-white hover-shadow">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary fw-medium">Total Students</span>
                        <i class="bi bi-people text-muted fs-5"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"><?= $total_students ?></h2>
                    <small class="text-muted">Enrolled students</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-4 rounded-3 bg-white hover-shadow">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary fw-medium">Completion Rate</span>
                        <i class="bi bi-graph-up-arrow text-danger fs-5"></i>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h2 class="fw-bold text-danger mb-1"><?= $completion_rate ?>%</h2>
                    </div>
                    <small class="text-muted">Average completion</small>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">Your Quizzes</h4>
                <p class="text-secondary mb-0">Manage and create quizzes for your students</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark d-flex align-items-center gap-2 px-3 fw-medium">
                    <i class="bi bi-upload"></i> Bulk Upload
                </button>
                <a href="quiz_create.php" class="btn btn-dark d-flex align-items-center gap-2 px-3 fw-medium">
                    <i class="bi bi-plus-lg"></i> Create New Quiz
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($quizzes)): ?>
                <?php foreach ($quizzes as $quiz): ?>
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden hover-shadow">
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="mb-2">
                                    <h5 class="fw-bold text-dark mb-2 text-truncate" title="<?= htmlspecialchars($quiz['title']) ?>">
                                        <?= htmlspecialchars($quiz['title']) ?>
                                    </h5>
                                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-1 fw-medium">
                                        <?= htmlspecialchars($quiz['subject_name']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-6">
                                    <small class="text-secondary d-block mb-1">Questions</small>
                                    <span class="fw-bold text-dark"><?= $quiz['num_questions'] ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-secondary d-block mb-1">Duration</small>
                                    <span class="fw-bold text-dark"><?= $quiz['duration'] ?> minutes</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-secondary">Progress</span>
                                    <span class="text-dark fw-medium"><?= $quiz['progress'] ?> students</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 6px; background-color: #e2e8f0;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $quiz['progress_pct'] ?>%; background-color: #0f766e;" 
                                         aria-valuenow="<?= $quiz['progress_pct'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-end pt-2">
                                <div>
                                    <small class="text-secondary d-block mb-1">Average Score</small>
                                    <span class="h4 fw-bold text-dark mb-0"><?= round($quiz['avg_score']) ?>%</span>
                                </div>
                                <a href="quiz_view.php?id=<?= $quiz['id_exam'] ?>" class="btn btn-success text-white px-4 fw-medium">
                                    <i class="bi bi-file-earmark-text me-2"></i> View Results
                                </a>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-top p-0">
                            <div class="d-flex">
                                <a href="quiz_edit.php?id=<?= $quiz['id_exam'] ?>" 
                                   class="btn btn-link text-decoration-none text-secondary fw-medium w-50 py-3 border-end d-flex justify-content-center align-items-center gap-2">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="quiz_delete.php?id=<?= $quiz['id_exam'] ?>" 
                                   class="btn btn-link text-decoration-none text-danger fw-medium w-50 py-3 d-flex justify-content-center align-items-center gap-2"
                                   onclick="return confirm('Are you sure you want to delete this quiz?');">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center p-5 text-muted border rounded-3 bg-white shadow-sm">
                        <i class="bi bi-journal-plus display-4 mb-3 d-block text-secondary"></i>
                        <h5 class="fw-bold">No Quizzes Found</h5>
                        <p class="mb-4">Get started by creating your first assessment.</p>
                        <a href="quiz_create.php" class="btn btn-primary px-4">Create New Quiz</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>