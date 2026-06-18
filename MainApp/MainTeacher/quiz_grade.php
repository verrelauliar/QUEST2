<?php
// Path: MainApp/MainTeacher/quiz_grade.php

/**
 * Quiz Grading Interface
 * Allows teachers to manually grade essays and override auto-grades.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Config/supabase.php';
require_once __DIR__ . '/../../Config/db_pdo.php';

use Core\Services\AuthService;

// 1. Auth & Session
session_start();
$authService = new AuthService(SupabaseConnection::getInstance());
if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit;
}

$teacher_id = (int)$_SESSION['user']['id'];
$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

if ($attempt_id <= 0) die("Invalid Attempt ID");

$pdo = getPDOConnection();

// 2. Manual RLS: Verify Teacher owns this attempt via Assignment
$stmt = $pdo->prepare("
    SELECT 
        a.id_attempt, a.student_id, a.submitted_at, a.status,
        u.full_name as student_name,
        e.id_exam, e.title as exam_title, e.passing_score,
        s.total_points, s.points_earned, s.percentage
    FROM tbl_exam_attempts a
    JOIN tbl_exams e ON a.exam_id = e.id_exam
    JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
    JOIN tbl_users u ON a.student_id = u.id_user
    LEFT JOIN tbl_scores s ON a.id_attempt = s.attempt_id
    WHERE a.id_attempt = ? AND ta.teacher_id = ?
");
$stmt->execute([$attempt_id, $teacher_id]);
$attempt = $stmt->fetch();

if (!$attempt) die("Access Denied or Attempt Not Found");

// 3. Handle Form Submission (Save Grades)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $manual_points = $_POST['points'] ?? [];
        $feedback = $_POST['feedback'] ?? [];
        
        $total_earned = 0;
        $total_possible = 0;

        // Fetch questions to get max points per question
        $q_stmt = $pdo->prepare("SELECT id_question, points, question_type FROM tbl_questions WHERE exam_id = ?");
        $q_stmt->execute([$attempt['id_exam']]);
        $questions_meta = $q_stmt->fetchAll();

        $update_ans = $pdo->prepare("
            UPDATE tbl_answers 
            SET points_earned = ?, teacher_feedback = ?, is_correct = ?, graded_by = ?, graded_at = NOW()
            WHERE attempt_id = ? AND question_id = ?
        ");

        foreach ($questions_meta as $q) {
            $qid = $q['id_question'];
            $max_points = $q['points'];
            $total_possible += $max_points;

            $p = isset($manual_points[$qid]) ? (float)$manual_points[$qid] : 0;
            
            // Logic for is_correct (Full points = correct)
            $is_correct = ($p >= $max_points); 

            $fb = $feedback[$qid] ?? '';

            $update_ans->execute([
                $p, 
                $fb, 
                $is_correct ? 'true' : 'false', 
                $teacher_id, 
                $attempt_id, 
                $qid
            ]);

            $total_earned += $p;
        }

        // Update Score Table
        $pct = ($total_possible > 0) ? ($total_earned / $total_possible) * 100 : 0;
        $passed = ($pct >= $attempt['passing_score']);

        $score_stmt = $pdo->prepare("
            UPDATE tbl_scores 
            SET points_earned = ?, percentage = ?, passed = ?, graded_at = NOW() 
            WHERE attempt_id = ?
        ");
        $score_stmt->execute([$total_earned, $pct, $passed ? 'true' : 'false', $attempt_id]);

        // Update Attempt Status to 'graded'
        $status_stmt = $pdo->prepare("UPDATE tbl_exam_attempts SET status = 'graded' WHERE id_attempt = ?");
        $status_stmt->execute([$attempt_id]);

        $pdo->commit();
        
        header("Location: quiz_grade.php?attempt_id=" . $attempt_id . "&saved=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving grades: " . $e->getMessage();
    }
}

// 4. Fetch Questions & Answers
$stmt = $pdo->prepare("
    SELECT 
        q.id_question, q.question_text, q.question_type, q.points as max_points, 
        q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer,
        ans.student_answer, ans.points_earned, ans.teacher_feedback
    FROM tbl_questions q
    LEFT JOIN tbl_answers ans ON q.id_question = ans.question_id AND ans.attempt_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.display_order ASC
");
$stmt->execute([$attempt_id, $attempt['id_exam']]);
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Assessment - <?= htmlspecialchars($attempt['student_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .question-card { border-left: 4px solid #dee2e6; transition: border-color 0.2s; }
        .question-card:focus-within { border-left-color: #0d6efd; }
        .auto-graded { border-left-color: #198754; }
        .needs-grading { border-left-color: #ffc107; }
    </style>
</head>
<body class="bg-light pb-5">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-0">Grading Assessment</h1>
            <p class="text-secondary mb-0">
                <?= htmlspecialchars($attempt['exam_title']) ?> &bull; 
                <span class="text-dark fw-bold"><?= htmlspecialchars($attempt['student_name']) ?></span>
            </p>
        </div>
        <a href="quiz_view.php?id=<?= $attempt['id_exam'] ?>" class="btn btn-outline-secondary">Back to Results</a>
    </div>

    <?php if(isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show">Grades saved successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php foreach($questions as $index => $q): 
                    $is_essay = $q['question_type'] === 'essay';
                    $card_class = $is_essay ? 'needs-grading' : 'auto-graded';
                ?>
                <div class="card shadow-sm border-0 mb-3 question-card <?= $card_class ?>">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge bg-secondary">Q<?= $index + 1 ?> - <?= ucfirst($q['question_type']) ?></span>
                            <span class="text-muted small">Max Points: <?= $q['max_points'] ?></span>
                        </div>
                        
                        <div class="mb-3 fw-bold">
                            <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                        </div>

                        <div class="mb-4">
                            <label class="small text-muted text-uppercase fw-bold">Student Answer</label>
                            <div class="p-3 bg-light rounded border">
                                <?php if($q['question_type'] === 'multiple' || $q['question_type'] === 'truefalse'): ?>
                                    <span class="font-monospace fw-bold"><?= htmlspecialchars($q['student_answer'] ?? '-') ?></span>
                                    <?php if($q['student_answer'] == $q['correct_answer']): ?>
                                        <i class="bi bi-check-circle-fill text-success ms-2"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle-fill text-danger ms-2"></i>
                                        <span class="text-muted small ms-2">(Correct: <?= $q['correct_answer'] ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= nl2br(htmlspecialchars($q['student_answer'] ?? 'No answer provided.')) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Points Earned</label>
                                <input type="number" step="0.5" min="0" max="<?= $q['max_points'] ?>"
                                       name="points[<?= $q['id_question'] ?>]"
                                       class="form-control"
                                       value="<?= $q['points_earned'] !== null ? (float)$q['points_earned'] : 0 ?>">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label small fw-bold">Feedback</label>
                                <input type="text" 
                                       name="feedback[<?= $q['id_question'] ?>]"
                                       class="form-control"
                                       placeholder="Optional feedback..."
                                       value="<?= htmlspecialchars($q['teacher_feedback'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Assessment Summary</h5>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Status</span>
                                <span class="badge <?= $attempt['status'] === 'graded' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= ucfirst($attempt['status']) ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Current Score</span>
                                <span class="fw-bold"><?= $attempt['percentage'] !== null ? round($attempt['percentage'],1).'%' : '--' ?></span>
                            </li>
                        </ul>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2">
                                <i class="bi bi-save me-2"></i>Save Grades
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</body>
</html>