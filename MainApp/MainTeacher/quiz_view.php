<?php
// Path: MainApp/MainTeacher/quiz_view.php

require_once __DIR__ . '/../../vendor/autoload.php';
use Core\Services\AuthService;

session_start();
$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;

if ($auth_cached && $_SESSION['user']['role'] === 'teacher') {
    $teacher_id_int = (int)$_SESSION['user']['id'];
} else {
    require_once __DIR__ . '/../../Config/supabase.php';
    $authService = new AuthService($supabase);
    $session_result = $authService->validateSession();
    
    if (!$session_result['valid'] || $_SESSION['user']['role'] !== 'teacher') {
        session_write_close(); header("Location: ../index.php"); exit;
    }
    $_SESSION['auth_verified'] = true;
    $teacher_id_int = (int)$_SESSION['user']['id'];
}
session_write_close();

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($exam_id <= 0) die('Invalid exam ID');

require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

try {
    // 1. Exam Details
    $stmt = $pdo->prepare("
        SELECT e.id_exam, e.title, e.duration, e.passing_score, ta.class_id,
            (SELECT COUNT(*) FROM tbl_questions q WHERE q.exam_id = e.id_exam) as num_questions,
            (SELECT COUNT(*) FROM tbl_class_enrollments ce WHERE ce.class_id = ta.class_id) as total_students
        FROM tbl_exams e
        INNER JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
        WHERE e.id_exam = ? AND ta.teacher_id = ?
    ");
    $stmt->execute([$exam_id, $teacher_id_int]);
    $exam_data = $stmt->fetch();
    
    if (!$exam_data) die('Exam not found or unauthorized');

    // 2. Attempts List
    $stmt = $pdo->prepare("
        SELECT 
            a.id_attempt, a.status, a.submitted_at, a.started_at,
            sc.percentage, sc.passed,
            u.full_name as student_name, u.email,
            (SELECT COUNT(*) FROM tbl_answers ans WHERE ans.attempt_id = a.id_attempt AND ans.is_correct = true) as correct_count
        FROM tbl_exam_attempts a
        LEFT JOIN tbl_scores sc ON a.id_attempt = sc.attempt_id
        LEFT JOIN tbl_users u ON a.student_id = u.id_user
        WHERE a.exam_id = ?
        ORDER BY sc.percentage DESC NULLS LAST
    ");
    $stmt->execute([$exam_id]);
    $attempts = $stmt->fetchAll();
    
    $student_results = [];
    $percentages = [];
    $passed_count = 0;
    
    foreach ($attempts as $attempt) {
        if ($attempt['percentage'] !== null) $percentages[] = $attempt['percentage'];
        if ($attempt['passed']) $passed_count++;
        if (empty($attempt['submitted_at'])) continue;
        
        $start = new DateTime($attempt['started_at']);
        $end = new DateTime($attempt['submitted_at']);
        
        $pct = $attempt['percentage'] ?? 0;
        if ($pct >= 90) $perf = 'Excellent';
        elseif ($pct >= 75) $perf = 'Very Good';
        elseif ($pct >= 60) $perf = 'Satisfactory';
        else $perf = 'Needs Improvement';
        
        $student_results[] = [
            'id_attempt' => $attempt['id_attempt'],
            'status' => $attempt['status'],
            'student_name' => $attempt['student_name'],
            'email' => $attempt['email'],
            'score' => $pct,
            'correct_count' => $attempt['correct_count'],
            'time_taken' => $start->diff($end)->i,
            'performance' => $perf,
            'completed_at' => date('Y-m-d g:i A', strtotime($attempt['submitted_at']))
        ];
    }
    
    $completed = count($student_results);
    $avg_score = !empty($percentages) ? round(array_sum($percentages) / count($percentages), 0) : 0;
    $highest_score = !empty($percentages) ? max($percentages) : 0;
    $lowest_score = !empty($percentages) ? min($percentages) : 0;
    
} catch (Exception $e) { die('Error loading quiz data'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../Assets/CSS/teacher/quiz_view.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-0">Quiz Results</h1>
            <p class="text-secondary small mb-0"><?= htmlspecialchars($exam_data['title']) ?></p>
        </div>
        <a href="teacher_dashboard.php" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3 text-center h-100 border-0 shadow-sm">
                <h6 class="text-secondary">Average</h6>
                <div class="h3 fw-bold text-primary"><?= $avg_score ?>%</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center h-100 border-0 shadow-sm">
                <h6 class="text-secondary">Completed</h6>
                <div class="h3 fw-bold text-dark"><?= $completed ?>/<?= $exam_data['total_students'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center h-100 border-0 shadow-sm">
                <h6 class="text-secondary">Passed</h6>
                <div class="h3 fw-bold text-success"><?= $passed_count ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center h-100 border-0 shadow-sm">
                <h6 class="text-secondary">High Score</h6>
                <div class="h3 fw-bold text-info"><?= $highest_score ?>%</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Student Results</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Student</th>
                        <th>Score</th>
                        <th>Correct</th>
                        <th>Time</th>
                        <th>Completed</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_results as $row): 
                        $badge = match($row['performance']) {
                            'Excellent' => 'bg-success-subtle text-success',
                            'Needs Improvement' => 'bg-danger-subtle text-danger',
                            default => 'bg-primary-subtle text-primary'
                        };
                        
                        $status_badge = '';
                        $action_btn = '';
                        
                        if ($row['status'] === 'submitted') {
                            $status_badge = '<span class="badge bg-warning text-dark">Needs Grading</span>';
                            $action_btn = '<a href="quiz_grade.php?attempt_id='.$row['id_attempt'].'" class="btn btn-sm btn-primary">Rate / Grade</a>';
                        } elseif ($row['status'] === 'graded') {
                            $status_badge = '<span class="badge bg-success">Graded</span>';
                            $action_btn = '<a href="quiz_grade.php?attempt_id='.$row['id_attempt'].'" class="btn btn-sm btn-outline-secondary">Edit Score</a>';
                        } else {
                            $status_badge = '<span class="badge bg-secondary">'.ucfirst($row['status']).'</span>';
                        }
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($row['student_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                        </td>
                        <td><span class="fw-bold"><?= round($row['score'], 1) ?>%</span></td>
                        <td><?= $row['correct_count'] ?>/<?= $exam_data['num_questions'] ?></td>
                        <td><?= $row['time_taken'] ?> min</td>
                        <td class="text-secondary small"><?= $row['completed_at'] ?></td>
                        <td><?= $status_badge ?></td>
                        <td class="pe-4 text-end"><?= $action_btn ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>