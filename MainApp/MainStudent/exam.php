<?php
// Path: MainApp/MainStudent/exam.php

// 1. CONFIGURATION & SESSION
date_default_timezone_set('Asia/Jakarta');

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Use Supabase SDK ONLY for authentication
require_once __DIR__ . '/../../Config/supabase.php';
use Core\Services\AuthService;

$supabase = SupabaseConnection::getInstance();
$authService = new AuthService($supabase);
if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../../Config/db_pdo.php';

$student_id = $_SESSION['user']['id'];
$student_id_int = is_numeric($student_id) ? (int)$student_id : 0;

if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    $_SESSION['error'] = "Invalid exam ID";
    header("Location: student_dashboard.php");
    exit;
}
$exam_id = intval($_GET['exam_id']);
$is_resume = isset($_GET['resume']) && $_GET['resume'] === '1';
$pdo = getPDOConnection();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'autosave') {
        handleAutosave($pdo, $student_id_int);
        exit;
    }
    if (isset($_POST['submit_exam'])) {
        handleExamSubmission($pdo, $student_id_int, $exam_id);
        exit;
    }
}

// 2. EXAM DATA FETCHING
try {
    $stmt = $pdo->prepare("SELECT id_exam, title, duration, start_time, end_time, is_active, assignment_id, passing_score FROM tbl_exams WHERE id_exam = ? AND is_active = true LIMIT 1");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();
    if (!$exam) { $_SESSION['error'] = "Exam not found"; header("Location: student_dashboard.php"); exit; }
} catch (PDOException $e) { header("Location: student_dashboard.php"); exit; }

// Get context (Subject/Class)
try {
    $stmt = $pdo->prepare("
        SELECT s.subject_name, c.class_name, ta.class_id 
        FROM tbl_teaching_assignments ta
        JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        JOIN tbl_classes c ON ta.class_id = c.id_class
        WHERE ta.id_assignment = ? LIMIT 1
    ");
    $stmt->execute([$exam['assignment_id']]);
    $meta = $stmt->fetch();
    
    $exam['subject_name'] = $meta['subject_name'] ?? 'Unknown';
    $exam['class_name'] = $meta['class_name'] ?? 'Unknown';
    $exam['class_id'] = $meta['class_id'] ?? 0;
} catch (PDOException $e) { }

// Enrollment Check
try {
    $stmt = $pdo->prepare("SELECT id_enrollment FROM tbl_class_enrollments WHERE student_id = ? AND class_id = ? LIMIT 1");
    $stmt->execute([$student_id_int, $exam['class_id']]);
    if (!$stmt->fetch()) { $_SESSION['error'] = "Not enrolled"; header("Location: student_dashboard.php"); exit; }
} catch (PDOException $e) { }

// Time Window Check
$current_time_str = date('Y-m-d H:i:s');
if ($current_time_str < $exam['start_time']) { $_SESSION['error'] = "Not started"; header("Location: student_dashboard.php"); exit; }
if ($current_time_str > $exam['end_time']) { $_SESSION['error'] = "Ended"; header("Location: student_dashboard.php"); exit; }

// 3. ATTEMPT MANAGEMENT
$attempt = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM tbl_exam_attempts WHERE exam_id = ? AND student_id = ? AND status = 'in_progress' LIMIT 1");
    $stmt->execute([$exam_id, $student_id_int]);
    $existing_attempt = $stmt->fetch();
    
    if ($existing_attempt) {
        $attempt = $existing_attempt;
    } else {
        if ($is_resume) { header("Location: student_dashboard.php"); exit; }
        $stmt = $pdo->prepare("INSERT INTO tbl_exam_attempts (exam_id, student_id, status, started_at) VALUES (?, ?, 'in_progress', ?) RETURNING *");
        $stmt->execute([$exam_id, $student_id_int, date('Y-m-d H:i:s')]);
        $attempt = $stmt->fetch();
    }
} catch (PDOException $e) { header("Location: student_dashboard.php"); exit; }

$_SESSION['current_attempt_id'] = $attempt['id_attempt'];

// 4. QUESTIONS & ANSWERS
try {
    $stmt = $pdo->prepare("SELECT id_question, exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order FROM tbl_questions WHERE exam_id = ? ORDER BY display_order ASC");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll();
} catch (PDOException $e) { $questions = []; }

if (empty($questions)) { header("Location: student_dashboard.php"); exit; }

$existing_answers = [];
try {
    $stmt = $pdo->prepare("SELECT question_id, student_answer FROM tbl_answers WHERE attempt_id = ?");
    $stmt->execute([$attempt['id_attempt']]);
    foreach ($stmt->fetchAll() as $row) { $existing_answers[$row['question_id']] = $row['student_answer']; }
} catch (PDOException $e) { }

// 5. TIMER CALCULATION
$started_at = new DateTime($attempt['started_at']);
$current_time_obj = new DateTime();
$duration_seconds = $exam['duration'] * 60;
$elapsed_time = $current_time_obj->getTimestamp() - $started_at->getTimestamp();
$time_remaining = max(0, $duration_seconds - $elapsed_time);

$init_minutes = floor($time_remaining / 60);
$init_seconds = $time_remaining % 60;
$timer_display = sprintf("%02d:%02d", $init_minutes, $init_seconds);

if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// --- Functions ---
function handleAutosave($pdo, $student_id) { 
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false]); return;
    }
    $qid = intval($_POST['question_id']);
    $ans = $_POST['answer'];
    $aid = $_SESSION['current_attempt_id'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tbl_answers (attempt_id, question_id, student_answer, submitted_at) 
            VALUES (?, ?, ?, NOW()) 
            ON CONFLICT (attempt_id, question_id) 
            DO UPDATE SET student_answer = EXCLUDED.student_answer, submitted_at = NOW()
        ");
        $stmt->execute([$aid, $qid, $ans]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false]); }
}

function handleExamSubmission($pdo, $student_id, $exam_id) {
    $attempt_id = $_SESSION['current_attempt_id'];
    
    // 1. Fetch Questions for Grading Logic
    $stmt = $pdo->prepare("SELECT id_question, question_type, points, correct_answer FROM tbl_questions WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $qs = $stmt->fetchAll();
    
    $total_possible = 0;
    $points_earned = 0;
    $has_essay = false;
    $submitted_answers = $_POST['answers'] ?? [];

    $save_stmt = $pdo->prepare("
        INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, submitted_at) 
        VALUES (?, ?, ?, ?, ?, NOW()) 
        ON CONFLICT (attempt_id, question_id) 
        DO UPDATE SET student_answer = EXCLUDED.student_answer, is_correct = EXCLUDED.is_correct, points_earned = EXCLUDED.points_earned, submitted_at = NOW()
    ");

    foreach ($qs as $q) {
        $total_possible += $q['points'];
        $student_ans = $submitted_answers[$q['id_question']] ?? null;
        
        $is_correct = null; // Default null (unknown/pending)
        $earned = 0;

        if ($q['question_type'] === 'essay') {
            $has_essay = true;
            // Essay points remain 0 and is_correct null until teacher grades it
        } else {
            // Auto-grade logic (Strict comparison)
            if ($student_ans !== null && $student_ans === $q['correct_answer']) {
                $is_correct = true;
                $earned = $q['points'];
            } else {
                $is_correct = false;
                $earned = 0;
            }
        }
        
        $points_earned += $earned;

        // Save Answer
        $save_stmt->execute([
            $attempt_id, 
            $q['id_question'], 
            $student_ans, 
            $is_correct === null ? null : ($is_correct ? 'true' : 'false'), 
            $earned
        ]);
    }

    // 2. Determine Status
    // If essays exist, status is 'submitted' (Pending Teacher Review).
    // If NO essays, status is 'graded' (Final).
    $final_status = $has_essay ? 'submitted' : 'graded';

    // 3. Update Attempt
    $stmt = $pdo->prepare("UPDATE tbl_exam_attempts SET status = ?, submitted_at = NOW(), time_remaining = 0 WHERE id_attempt = ?");
    $stmt->execute([$final_status, $attempt_id]);

    // 4. Calculate Score
    $pct = $total_possible > 0 ? ($points_earned / $total_possible) * 100 : 0;
    
    $stmt = $pdo->prepare("SELECT passing_score FROM tbl_exams WHERE id_exam = ?");
    $stmt->execute([$exam_id]);
    $pass_score = $stmt->fetchColumn() ?: 60;
    
    // Pass/Fail is only definitive if fully graded. Otherwise false (pending).
    $passed = ($final_status === 'graded') ? ($pct >= $pass_score) : false;

    $stmt = $pdo->prepare("
        INSERT INTO tbl_scores (attempt_id, total_points, points_earned, percentage, passed, graded_at) 
        VALUES (?, ?, ?, ?, ?, NOW()) 
        ON CONFLICT (attempt_id) DO UPDATE SET points_earned = EXCLUDED.points_earned, percentage = EXCLUDED.percentage, passed = EXCLUDED.passed, graded_at = NOW()
    ");
    $stmt->execute([$attempt_id, $total_possible, $points_earned, $pct, $passed ? 'true' : 'false']);

    unset($_SESSION['current_attempt_id']);
    
    // Redirect with Status Flag
    header("Location: student_dashboard.php?status=" . $final_status);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($exam['title']) ?> | Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../Assets/CSS/student/exam.css">
</head>
<body>
    
    <div id="exam-app" 
         data-exam-id="<?= $exam_id ?>" 
         data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
         data-total-questions="<?= count($questions) ?>"
         data-time-remaining="<?= $time_remaining ?>">

    <nav class="navbar fixed-top">
        <div class="container-xl px-4">
            <div class="navbar-content w-100 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <img src="../../Assets/Images/logosmp2sindur.png" alt="Logo" height="32">
                    <div class="d-flex flex-column justify-content-center">
                        <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 300px;"><?= htmlspecialchars($exam['title']) ?></h6>
                        <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($exam['subject_name']) ?></small>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-4">
                    <div class="timer-display" id="timer">
                        <i class="bi bi-clock"></i>
                        <span id="time-display"><?= $timer_display ?></span>
                    </div>
                    <a href="student_dashboard.php" class="btn btn-sm btn-outline-secondary px-3">Exit Quiz</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="exam-layout-container pt-4 pb-5" style="margin-top: 70px;">
        <form id="exam-form" method="POST" action="exam.php?exam_id=<?= $exam_id ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="submit_exam" value="1">

            <div class="row g-4">
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="card sidebar-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-secondary">Questions</h6>
                            <span class="badge bg-light text-dark border"><span id="answered-count"><?= count($existing_answers) ?></span> of <?= count($questions) ?></span>
                        </div>

                        <div class="q-grid-container mb-3">
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($questions as $index => $q): 
                                    $qNum = $index + 1;
                                    $isAnswered = isset($existing_answers[$q['id_question']]) && $existing_answers[$q['id_question']] !== '';
                                    $statusClass = $isAnswered ? 'answered' : '';
                                    if ($index === 0) $statusClass .= ' active';
                                ?>
                                <button type="button" class="q-num <?= $statusClass ?>" 
                                        data-question="<?= $qNum ?>"
                                        data-q-id="<?= $q['id_question'] ?>">
                                    <?= $qNum ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card question-card">
                        <div class="question-header">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small fw-bold text-uppercase tracking-wide">Question <span id="current-question">1</span></span>
                                <span class="text-muted small fw-medium">of <?= count($questions) ?></span>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar progress-bar-custom" id="progress-fill" role="progressbar" style="width: <?= round(1/count($questions)*100) ?>%"></div>
                            </div>
                        </div>

                        <div class="flex-grow-1">
                            <?php foreach ($questions as $index => $question): 
                                $qNum = $index + 1;
                                $isHidden = $index === 0 ? '' : 'hidden';
                                $val = $existing_answers[$question['id_question']] ?? '';
                            ?>
                            <div class="question-container <?= $isHidden ?>" id="question-<?= $qNum ?>">
                                <div class="question-text">
                                    <?= nl2br(htmlspecialchars($question['question_text'])) ?>
                                </div>

                                <div class="options-list d-flex flex-column gap-3">
                                    <?php if ($question['question_type'] === 'multiple'): 
                                        $opts = ['A'=>$question['option_a'], 'B'=>$question['option_b'], 'C'=>$question['option_c'], 'D'=>$question['option_d']];
                                        foreach($opts as $k => $v): if($v): 
                                            $isChecked = ($val === $k);
                                    ?>
                                        <label class="option-label">
                                            <input class="option-input" type="radio" 
                                                   name="answers[<?= $question['id_question'] ?>]" 
                                                   value="<?= $k ?>" 
                                                   <?= $isChecked ? 'checked' : '' ?>
                                                   data-question-id="<?= $question['id_question'] ?>">
                                            <div class="option-card">
                                                <div class="opt-letter"><?= $k ?></div>
                                                <span class="fw-medium text-dark"><?= htmlspecialchars($v) ?></span>
                                            </div>
                                        </label>
                                        <?php endif; endforeach; ?>
                                    
                                    <?php elseif ($question['question_type'] === 'truefalse'): 
                                        $tf = ['T'=>'True', 'F'=>'False'];
                                        foreach($tf as $k => $v): 
                                            $isChecked = ($val === $k);
                                    ?>
                                        <label class="option-label">
                                            <input class="option-input" type="radio" 
                                                   name="answers[<?= $question['id_question'] ?>]" 
                                                   value="<?= $k ?>" 
                                                   <?= $isChecked ? 'checked' : '' ?>
                                                   data-question-id="<?= $question['id_question'] ?>">
                                            <div class="option-card">
                                                <div class="opt-letter"><?= $k==='T'?'T':'F' ?></div>
                                                <span class="fw-medium text-dark"><?= $v ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>

                                    <?php elseif ($question['question_type'] === 'essay'): ?>
                                        <textarea class="form-control bg-light border-0 p-3" rows="8" 
                                                  name="answers[<?= $question['id_question'] ?>]" 
                                                  placeholder="Type your answer here..." 
                                                  data-question-id="<?= $question['id_question'] ?>"><?= htmlspecialchars($val) ?></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" id="prev-btn" disabled>Previous</button>
                            <div>
                                <button type="button" class="btn btn-primary px-4 rounded-pill" id="next-btn">Next</button>
                                <button type="submit" class="btn btn-success px-4 rounded-pill hidden" id="submit-btn">Submit Quiz</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <script src="../../Assets/JS/student/exam_logic.js"></script>
</body>
</html>