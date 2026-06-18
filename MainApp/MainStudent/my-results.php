<?php
// Path: MainApp/MainStudent/my-results.php

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Config/supabase.php';
use Core\Services\AuthService;

$authService = new AuthService(SupabaseConnection::getInstance());
if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();
$student_id = (int)$_SESSION['user']['id'];
$student_name = $_SESSION['user']['name'] ?? 'Student';

// Optimized Query (Manual RLS Enforced via student_id)
try {
    $stmt = $pdo->prepare("
        SELECT 
            ea.id_attempt, ea.status, ea.submitted_at, 
            e.title, e.show_results, 
            s.subject_name,
            sc.percentage, sc.passed
        FROM tbl_exam_attempts ea
        JOIN tbl_exams e ON ea.exam_id = e.id_exam
        JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
        JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        LEFT JOIN tbl_scores sc ON ea.id_attempt = sc.attempt_id
        WHERE ea.student_id = ? AND ea.status IN ('submitted', 'graded', 'expired')
        ORDER BY ea.submitted_at DESC
    ");
    $stmt->execute([$student_id]);
    $attempts = $stmt->fetchAll();
} catch (Exception $e) { $attempts = []; }

function getSubjectBadgeColor($subject) {
    if (stripos($subject, 'matematika') !== false) return 'info';
    if (stripos($subject, 'ipa') !== false) return 'primary';
    if (stripos($subject, 'islam') !== false) return 'success';
    if (stripos($subject, 'bahasa') !== false) return 'warning';
    return 'secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../Assets/CSS/student/my-results.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="#">
                <img src="../../Assets/Images/mumtaza_logo.png" alt="Logo" width="30" height="30"> Mumtaza
            </a>
            <div class="d-flex align-items-center">
                <span class="me-3 text-secondary d-none d-md-inline">Welcome, <b class="text-dark"><?= htmlspecialchars($student_name) ?></b></span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold mb-0">My Exam Results</h1>
            <a href="student_dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
        </div>

        <div class="card shadow-sm border-0">
            <?php if (empty($attempts)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x text-muted display-1"></i>
                    <p class="mt-3 text-muted">No completed exams found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Subject</th>
                                <th>Exam Title</th>
                                <th>Date Completed</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attempts as $attempt): 
                                $badgeColor = getSubjectBadgeColor($attempt['subject_name']);
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?> border border-<?= $badgeColor ?>-subtle">
                                        <?= htmlspecialchars($attempt['subject_name']) ?>
                                    </span>
                                </td>
                                <td class="fw-medium"><?= htmlspecialchars($attempt['title']) ?></td>
                                <td class="text-muted small"><?= date('M d, Y H:i', strtotime($attempt['submitted_at'])) ?></td>
                                <td>
                                    <?php if ($attempt['status'] === 'submitted'): ?>
                                        <span class="badge bg-warning text-dark border border-warning-subtle">Pending Assessment</span>
                                    <?php elseif ($attempt['percentage'] !== null): ?>
                                        <?php $cls = $attempt['passed'] ? 'text-success bg-success-subtle' : 'text-danger bg-danger-subtle'; ?>
                                        <span class="fw-bold px-2 py-1 rounded <?= $cls ?>"><?= round($attempt['percentage'], 1) ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attempt['status'] === 'submitted'): ?>
                                        <span class="badge bg-light text-dark border">Submitted</span>
                                    <?php elseif ($attempt['status'] === 'graded'): ?>
                                        <?php if ($attempt['passed']): ?>
                                            <span class="badge bg-success">Passed</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= ucfirst($attempt['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if ($attempt['show_results'] && $attempt['status'] === 'graded'): ?>
                                        <a href="view-results.php?attempt_id=<?= $attempt['id_attempt'] ?>" class="btn btn-sm btn-primary">View Details</a>
                                    <?php else: ?>
                                        <button disabled class="btn btn-sm btn-outline-secondary">View Details</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>