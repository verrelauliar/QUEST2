<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// HYBRID PATTERN - Step 1: Keep Supabase SDK for Authentication (per constitution.md)
require_once __DIR__ . '/../../Config/supabase.php';
use Core\Services\AuthService;

// Get singleton connection for AuthService
$supabase = SupabaseConnection::getInstance();
$authService = new AuthService($supabase);
if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

// HYBRID PATTERN - Step 2: Add PDO Connection for Data Operations (high-performance)
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

$student_id = (int)$_SESSION['user']['id'];
$attempt_id = isset($_GET['attempt_id']) ? (int) $_GET['attempt_id'] : 0;

if ($attempt_id <= 0) {
    header("Location: student_dashboard.php");
    exit;
}

// HYBRID PATTERN - Step 3: Manual RLS Security Validation (critical security requirement)
try {
    // Verify student owns this attempt (Manual RLS)
    $stmt = $pdo->prepare("
        SELECT id_attempt FROM tbl_exam_attempts
        WHERE id_attempt = ? AND student_id = ?
        LIMIT 1
    ");
    $stmt->execute([$attempt_id, $student_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Access denied to exam results";
        header("Location: student_dashboard.php");
        exit;
    }

    // HYBRID PATTERN - Step 4: Replace ALL Supabase SDK operations with PDO queries
    // Optimized batch queries to eliminate N+1 patterns and achieve <200ms performance
    
    // Step 1: Get attempt data, exam details, and assignment in single optimized query
    $stmt = $pdo->prepare("
        SELECT
            ea.id_attempt,
            ea.exam_id,
            ea.status,
            ea.submitted_at,
            e.title,
            e.show_results,
            e.allow_review,
            e.assignment_id,
            s.percentage,
            s.points_earned,
            s.total_points,
            s.passed
        FROM tbl_exam_attempts ea
        JOIN tbl_exams e ON ea.exam_id = e.id_exam
        LEFT JOIN tbl_scores s ON ea.id_attempt = s.attempt_id
        WHERE ea.id_attempt = ? AND ea.student_id = ?
    ");
    $stmt->execute([$attempt_id, $student_id]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['show_results']) {
        header("Location: student_dashboard.php");
        exit;
    }
    
    // Separate combined data into individual variables for backward compatibility
    $attempt_data = [
        'id_attempt' => $result['id_attempt'],
        'exam_id' => $result['exam_id'],
        'status' => $result['status'],
        'submitted_at' => $result['submitted_at']
    ];
    
    $exam = [
        'id_exam' => $result['exam_id'],
        'title' => $result['title'],
        'show_results' => $result['show_results'],
        'allow_review' => $result['allow_review'],
        'assignment_id' => $result['assignment_id']
    ];
    
    $score = [
        'percentage' => $result['percentage'] ?? 0,
        'points_earned' => $result['points_earned'] ?? 0,
        'total_points' => $result['total_points'] ?? 0,
        'passed' => $result['passed'] ?? 0
    ];
    
    // Step 2: Get subject name via teaching assignment
    $stmt = $pdo->prepare("
        SELECT s.subject_name
        FROM tbl_teaching_assignments ta
        JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        WHERE ta.id_assignment = ?
    ");
    $stmt->execute([$result['assignment_id']]);
    $subject_result = $stmt->fetch();
    $subject_name = $subject_result['subject_name'] ?? 'Unknown';
    
    // Step 3: Get all questions for the exam (batch operation)
    $stmt = $pdo->prepare("
        SELECT
            id_question,
            question_text,
            question_type,
            points,
            option_a,
            option_b,
            option_c,
            option_d,
            correct_answer,
            display_order
        FROM tbl_questions
        WHERE exam_id = ?
        ORDER BY display_order ASC
    ");
    $stmt->execute([$result['exam_id']]);
    $questions = $stmt->fetchAll();
    
    if (!empty($questions)) {
        // Step 4: Get all student answers for these questions in single query (eliminates N+1)
        $question_ids = array_column($questions, 'id_question');
        $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
        
        $stmt = $pdo->prepare("
            SELECT
                question_id,
                student_answer,
                is_correct,
                points_earned,
                teacher_feedback
            FROM tbl_answers
            WHERE attempt_id = ? AND question_id IN ($placeholders)
        ");
        $params = array_merge([$attempt_id], $question_ids);
        $stmt->execute($params);
        $answers_data = $stmt->fetchAll();
        
        // Create answers lookup map for O(1) access (efficient)
        $answers_map = [];
        foreach ($answers_data as $answer) {
            $answers_map[$answer['question_id']] = $answer;
        }
        
        // Step 5: Enrich questions with answers (single pass, maintains O(n) complexity)
        $correct_count = 0;
        $wrong_count = 0;
        
        foreach ($questions as &$question) {
            $answer = $answers_map[$question['id_question']] ?? null;
            
            if ($answer) {
                $question['student_answer'] = $answer['student_answer'];
                $question['is_correct'] = $answer['is_correct'];
                $question['points_earned'] = $answer['points_earned'];
                $question['teacher_feedback'] = $answer['teacher_feedback'];
                
                // Count correct/wrong answers based on points earned vs total points
                $type = $question['question_type'];
                $earned = (float) ($answer['points_earned'] ?? 0);
                $points = (float) $question['points'];
                
                // Check if answer was submitted
                $student_answer = $question['student_answer'] ?? null;
                $has_answer = ($student_answer !== null && trim($student_answer) !== '');
                
                if (!$has_answer) {
                    // No answer provided - automatically wrong
                    $wrong_count++;
                } elseif ($type === 'essay') {
                    // Essay: correct if earned full points, otherwise wrong (including pending)
                    if ($earned >= $points) {
                        $correct_count++;
                    } else {
                        $wrong_count++;
                    }
                } else {
                    // MCQ/TrueFalse: use is_correct flag
                    if ((int) $answer['is_correct'] === 1) {
                        $correct_count++;
                    } else {
                        $wrong_count++;
                    }
                }
            } else {
                // No answer found
                $question['student_answer'] = null;
                $question['is_correct'] = null;
                $question['points_earned'] = null;
                $question['teacher_feedback'] = null;
                $wrong_count++;
            }
        }
        unset($question); // Break reference
    } else {
        $correct_count = 0;
        $wrong_count = 0;
    }
    
    // Combine attempt data for backward compatibility
    $attempt = array_merge($attempt_data, $exam, $score);
    
} catch (Exception $e) {
    error_log("View results error: " . $e->getMessage());
    header("Location: student_dashboard.php");
    exit;
}

$total_questions = count($questions);
$score_percentage = round($attempt['percentage'], 1);
$completed_on = $attempt['submitted_at'] ? date('d/m/Y', strtotime($attempt['submitted_at'])) : '-';

if ($score_percentage >= 90) {
    $performance_text = 'Excellent!';
} elseif ($score_percentage >= 75) {
    $performance_text = 'Great job!';
} elseif ($score_percentage >= 60) {
    $performance_text = 'Keep going!';
} else {
    $performance_text = 'Review recommended';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($subject_name) ?> - Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../Assets/CSS/student/view-results.css">
</head>
<body>
    <nav class="navbar navbar-light bg-white sticky-top shadow-sm mb-4">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3">
                <img src="../../Assets/Images/mumtaza_logo.png" alt="Logo" height="40">
                <div class="d-flex flex-column">
                    <h1 class="h5 mb-0 fw-bold"><?= htmlspecialchars($attempt['title']) ?></h1>
                    <small class="text-muted"><?= htmlspecialchars($subject_name) ?></small>
                </div>
            </div>
            <div>
                <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="container-fluid pb-5">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-md-4 col-lg-3">
                <div class="card shadow-sm border-0 sticky-top sticky-sidebar">
                    <div class="card-body">
                        <!-- Score Card -->
                        <div class="text-center p-4 rounded-3 mb-3 score-card-bg">
                            <h6 class="text-uppercase text-muted fw-bold small tracking-wide mb-2">Your Score</h6>
                            <div class="display-4 fw-bold text-primary mb-1"><?= $score_percentage ?>%</div>
                            <div class="text-muted fw-medium"><?= htmlspecialchars($performance_text) ?></div>
                        </div>

                        <!-- Stats -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 rounded border bg-success-subtle border-success-subtle text-center">
                                    <div class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> <?= $correct_count ?></div>
                                    <div class="small text-muted">Correct</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded border bg-danger-subtle border-danger-subtle text-center">
                                    <div class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i> <?= $wrong_count ?></div>
                                    <div class="small text-muted">Wrong</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        
                        <!-- Details -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Completed</span>
                                <span class="fw-medium small"><?= htmlspecialchars($completed_on) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Questions</span>
                                <span class="fw-medium small"><?= $total_questions ?></span>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Question Navigator -->
                        <?php if ($attempt['allow_review']): ?>
                            <h6 class="text-uppercase text-muted fw-bold small tracking-wide mb-3">Navigator</h6>
                            
                            <div id="question-navigator" class="d-flex flex-wrap gap-2 justify-content-center">
                                <?php foreach ($questions as $index => $question):
                                // Determine styling based on correctness
                                if ($question['question_type'] === 'essay' && ($question['points_earned'] ?? null) === null) {
                                    // Essay Pending
                                    $btn_class = 'btn-outline-warning';
                                } else {
                                    // Check if correct (Essay vs Auto-graded)
                                    $is_correct = false;
                                    if ($question['question_type'] === 'essay') {
                                        $is_correct = (float) ($question['points_earned'] ?? 0) >= (float) $question['points'];
                                    } else {
                                        $is_correct = (int) $question['is_correct'] === 1;
                                    }
                                    $btn_class = $is_correct ? 'btn-outline-success' : 'btn-outline-danger';
                                }
                                ?>
                                <button type="button"
                                class="btn btn-sm <?= $btn_class ?> q-result p-0 d-flex align-items-center justify-content-center nav-btn-sm fw-bold"
                                data-index="<?= $index ?>"
                                style="width: 32px; height: 32px;">
                            <?= $index + 1 ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning small mb-0">
                        Detailed review disabled.
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-8 col-lg-9">
                <div class="card shadow-sm border-0 result-content-card">
                    <div class="card-body p-4 p-lg-5">
                        <?php if ($attempt['allow_review']): ?>
                            <?php if ($total_questions === 0): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-info-circle text-muted display-1"></i>
                                    <h4 class="mt-4 text-muted">No questions available.</h4>
                                    <p class="text-muted">Please contact your teacher if you believe this is an error.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($questions as $index => $question):
                                    $question_number = $index + 1;
                                    $active_class = $index === 0 ? ' active' : '';
                                    // Calculate progress properly
                                    $progress_width = $total_questions > 0 ? ( ($question_number / $total_questions) * 100 ) : 0;
                                    $earned_points = isset($question['points_earned']) && $question['points_earned'] !== null ? $question['points_earned'] : 0;
                                ?>
                                    <div class="question-slide<?= $active_class ?>" data-index="<?= $index ?>">
                                        <!-- Progress -->
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Question <span class="fw-bold text-dark"><?= $question_number ?></span> of <?= $total_questions ?></span>
                                            <span class="text-muted small"><?= round($progress_width) ?>%</span>
                                        </div>
                                        <div class="progress mb-4 progress-sm">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progress_width ?>%"></div>
                                        </div>
                                        
                                        <h4 class="mb-4 fw-bold"><?= htmlspecialchars($question['question_text']) ?></h4>

                                        <?php if (in_array($question['question_type'], ['multiple', 'truefalse'], true)): ?>
                                            <div class="d-flex flex-column gap-3 mb-4">
                                                <?php
                                                $options = $question['question_type'] === 'truefalse'
                                                    ? ['T' => 'True', 'F' => 'False']
                                                    : ['A' => $question['option_a'], 'B' => $question['option_b'], 'C' => $question['option_c'], 'D' => $question['option_d']];

                                                foreach ($options as $code => $label):
                                                    if ($label === null || $label === '') {
                                                        if ($question['question_type'] === 'multiple') continue;
                                                        $label = $label ?? '';
                                                    }

                                                    $is_selected = strtoupper((string) $question['student_answer']) === $code;
                                                    $is_correct_option = strtoupper((string) $question['correct_answer']) === $code;
                                                    
                                                    $border_class = 'border';
                                                    $bg_class = 'bg-white';
                                                    $icon_html = '<div class="rounded-circle border d-flex align-items-center justify-content-center option-icon"></div>';
                                                    
                                                    if ($is_correct_option) {
                                                        $border_class = 'border-success';
                                                        $bg_class = 'bg-success-subtle';
                                                        $icon_html = '<i class="bi bi-check-circle-fill text-success"></i>';
                                                    } elseif ($is_selected && !$is_correct_option) {
                                                        $border_class = 'border-danger';
                                                        $bg_class = 'bg-danger-subtle';
                                                        $icon_html = '<i class="bi bi-x-circle-fill text-danger"></i>';
                                                    } elseif ($is_selected) { // Should be covered by first case if correct, but just in case
                                                        $border_class = 'border-primary';
                                                    }
                                                ?>
                                                    <div class="card card-body <?= $border_class ?> <?= $bg_class ?> p-3 transition-all">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <?= $icon_html ?>
                                                            <div class="flex-grow-1">
                                                                <span class="d-block"><?= htmlspecialchars($label) ?></span>
                                                                <?php if ($is_correct_option): ?>
                                                                    <span class="badge bg-success text-white small mt-1">Correct Answer</span>
                                                                <?php elseif ($is_selected && !$is_correct_option): ?>
                                                                    <span class="badge bg-danger text-white small mt-1">Your Answer</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <!-- Essay/Text logic -->
                                            <div class="p-4 rounded-3 bg-light mb-3 border">
                                                <h6 class="fw-bold mb-2">Your Answer:</h6>
                                                <p class="mb-0 text-break"><?= nl2br(htmlspecialchars($question['student_answer'] ?? 'No response submitted.')) ?></p>
                                            </div>
                                            
                                            <?php if ($earned_points !== null && $earned_points >= 0): ?>
                                                <div class="p-4 rounded-3 bg-success-subtle border border-success-subtle mb-3">
                                                    <h6 class="fw-bold text-success mb-2">Teacher's Feedback:</h6>
                                                    <p class="mb-0 text-dark">
                                                        <?= $question['teacher_feedback'] ? nl2br(htmlspecialchars($question['teacher_feedback'])) : '<em>No feedback provided.</em>' ?>
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-primary fs-6">Score: <?= (float) $earned_points ?> / <?= (float) $question['points'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning d-flex align-items-center gap-2">
                                                    <i class="bi bi-clock-history"></i>
                                                    <div>
                                                        <strong>Pending Review</strong>
                                                        <div class="small">Your essay is awaiting teacher evaluation.</div>
                                                    </div>
                                                </div>
                                                <div class="text-end text-muted small">
                                                    Possible Points: <?= (float) $question['points'] ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Navigation Buttons -->
                                <div class="d-flex justify-content-between mt-5 pt-4 border-top">
                                    <button class="btn btn-outline-secondary px-4" id="prevBtn" type="button">← Previous</button>
                                    <button class="btn btn-primary px-4" id="nextBtn" type="button">Next →</button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-lock-fill text-muted display-1"></i>
                                <h4 class="mt-4 text-muted">Detailed Review Not Available</h4>
                                <p class="text-muted">The teacher has disabled detailed review for this exam.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($attempt['allow_review'] && $total_questions > 0): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // SELECTORS
        const slides = document.querySelectorAll('.question-slide');
        // FIX: Updated selector to match the new ID
        const navButtons = document.querySelectorAll('#question-navigator .q-result');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        let currentIndex = 0;

        // CORE FUNCTIONS
        function updateControls() {
            if (!prevBtn || !nextBtn) return;

            // Handle Previous Button
            prevBtn.disabled = currentIndex === 0;
            prevBtn.style.opacity = prevBtn.disabled ? '0.5' : '1';

            // Handle Next Button
            const atEnd = currentIndex === slides.length - 1;
            nextBtn.disabled = atEnd;
            nextBtn.style.opacity = atEnd ? '0.5' : '1';
        }

        function showSlide(index) {
            // Bounds check
            if (index < 0 || index >= slides.length) return;

            // 1. Toggle Slides
            slides.forEach(slide => slide.classList.remove('active'));
            slides[index].classList.add('active');

            // 2. Toggle Navigator Buttons
            navButtons.forEach(btn => {
                const btnIndex = parseInt(btn.dataset.index, 10);
                // Simple active class toggle
                if (btnIndex === index) {
                    btn.classList.add('active', 'ring-2', 'ring-offset-1'); // Visual focus
                } else {
                    btn.classList.remove('active', 'ring-2', 'ring-offset-1');
                }
            });

            // 3. Update State
            currentIndex = index;
            updateControls();

            // 4. Smooth Scroll to Top (UX)
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function handleNav(direction) {
            showSlide(currentIndex + direction);
        }

        // EVENT LISTENERS
        navButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const target = parseInt(this.dataset.index, 10);
                showSlide(target);
            });
        });

        if (prevBtn) prevBtn.addEventListener('click', () => handleNav(-1));
        if (nextBtn) nextBtn.addEventListener('click', () => handleNav(1));

        // INITIALIZATION
        showSlide(0);
    });
</script>
<?php endif; ?>
</body>
</html>