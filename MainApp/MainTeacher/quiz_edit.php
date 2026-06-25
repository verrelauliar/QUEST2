<?php
/**
 * OPTIMIZED Quiz Edit
 * 
 * Performance improvements:
 * 1. Session-cached auth to skip Supabase HTTP on subsequent requests
 * 2. Reduced from 5 SDK calls to 2 optimized PDO queries
 * 3. Manual RLS: Verifies teacher owns the exam before edit
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
// PHASE 2: Validate exam_id parameter
// ============================================================================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: teacher_dashboard.php");
    exit;
}

// ============================================================================
// PHASE 3: Load PDO and fetch/update quiz
// ============================================================================
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
    $questions = $_POST['questions'] ?? [];

    if ($title && $duration > 0) {
        try {
            // 1. Manual RLS: Verify teacher owns this exam
            $checkStmt = $pdo->prepare("
                SELECT e.id_exam 
                FROM tbl_exams e
                INNER JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
                WHERE e.id_exam = ? AND ta.teacher_id = ?
            ");
            $checkStmt->execute([$id, $teacher_id_int]);
            
            if (!$checkStmt->fetch()) {
                header("Location: teacher_dashboard.php?error=unauthorized");
                exit;
            }

            // 2. Start Transaction
            $pdo->beginTransaction();

            // 3. Update Exam Header
            $stmt = $pdo->prepare("UPDATE tbl_exams SET title = ?, duration = ? WHERE id_exam = ?");
            $stmt->execute([$title, $duration, $id]);

            // 4. Sync Questions
            // Get current DB question IDs
            $stmtQ = $pdo->prepare("SELECT id_question FROM tbl_questions WHERE exam_id = ?");
            $stmtQ->execute([$id]);
            $dbQuestionIds = $stmtQ->fetchAll(PDO::FETCH_COLUMN);

            // Get submitted IDs (filter out empty/new ones)
            $submittedIds = [];
            foreach ($questions as $q) {
                if (!empty($q['id'])) {
                    $submittedIds[] = (int)$q['id'];
                }
            }

            // Identify IDs to delete
            $idsToDelete = array_diff($dbQuestionIds, $submittedIds);
            if (!empty($idsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $deleteStmt = $pdo->prepare("DELETE FROM tbl_questions WHERE id_question IN ($placeholders)");
                $deleteStmt->execute(array_values($idsToDelete));
            }

            // Prepare Upsert Statements
            $updateStmt = $pdo->prepare("
                UPDATE tbl_questions 
                SET question_text = ?, question_type = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ?, display_order = ?
                WHERE id_question = ? AND exam_id = ?
            ");

            $insertStmt = $pdo->prepare("
                INSERT INTO tbl_questions 
                (exam_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, display_order, points)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $order = 0;
            foreach ($questions as $q) {
                $qText = trim($q['text'] ?? '');
                if (empty($qText)) continue;

                $order++;
                $qType = $q['type'] ?? 'multiple';
                if (!in_array($qType, ['multiple', 'essay', 'truefalse'])) {
                    $qType = 'multiple';
                }
                
                $optA = !empty($q['a']) ? trim($q['a']) : null;
                $optB = !empty($q['b']) ? trim($q['b']) : null;
                $optC = !empty($q['c']) ? trim($q['c']) : null;
                $optD = !empty($q['d']) ? trim($q['d']) : null;
                $correct = !empty($q['correct']) ? strtoupper(trim($q['correct'])) : null;
                
                if (!empty($q['id']) && in_array((int)$q['id'], $dbQuestionIds)) {
                    // Update
                    $updateStmt->execute([
                        $qText, $qType, $optA, $optB, $optC, $optD, $correct, $order, 
                        (int)$q['id'], $id
                    ]);
                } else {
                    // Insert
                    $insertStmt->execute([
                        $id, $qText, $qType, $optA, $optB, $optC, $optD, $correct, $order, 1.0
                    ]);
                }
            }
            
            $pdo->commit();
            header("Location: teacher_dashboard.php?msg=updated");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Failed to update quiz: " . $e->getMessage());
            header("Location: teacher_dashboard.php?error=update_failed");
            exit;
        }
    }
}

// Fetch quiz data with Manual RLS in single query
try {
    $stmt = $pdo->prepare("
        SELECT 
            e.id_exam,
            e.title,
            e.duration,
            e.assignment_id,
            s.subject_name,
            c.class_name
        FROM tbl_exams e
        INNER JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
        INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        INNER JOIN tbl_classes c ON ta.class_id = c.id_class
        WHERE e.id_exam = ? AND ta.teacher_id = ?
    ");
    $stmt->execute([$id, $teacher_id_int]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        header("Location: teacher_dashboard.php?error=unauthorized");
        exit;
    }

    // Fetch questions
    $stmtQ = $pdo->prepare("
        SELECT * FROM tbl_questions 
        WHERE exam_id = ? 
        ORDER BY display_order ASC, id_question ASC
    ");
    $stmtQ->execute([$id]);
    $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error loading quiz: " . $e->getMessage());
    header("Location: teacher_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../Assets/CSS/teacher/quiz_edit.css">

</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 980px;">
    <div class="d-flex align-items-center gap-3 mb-4 bg-white p-3 rounded-3 shadow-sm border">
        <img src="../../Assets/Images/logosmp2sindur.png" alt="Logo" width="30" height="30" class="object-fit-contain">
        <h1 class="h5 mb-0 fw-bold text-dark">✏️ Edit Quiz</h1>
    </div>

    <form method="POST" id="quizForm">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Quiz Details</h5>
                    <p class="text-muted small mb-0">Basic information about your quiz</p>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($quiz['title'] ?? '') ?>" placeholder="Quiz Title" required>
                            <label for="title">Quiz Title</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="assignment" value="<?= htmlspecialchars(($quiz['subject_name'] ?? '') . ' — ' . ($quiz['class_name'] ?? '')) ?>" placeholder="Assignment" disabled readonly>
                            <label for="assignment">Assignment</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" id="duration" name="duration" value="<?= (int)($quiz['duration'] ?? 0) ?>" placeholder="Duration" required min="1">
                            <label for="duration">Duration (minutes)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="my-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1">Questions</h5>
                <p class="text-muted small mb-0">Manage quiz questions</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary rounded-pill btn-sm" onclick="addMultipleChoice()">
                    <i class="bi bi-plus-lg"></i> + Add Multiple Choice
                </button>
                <button type="button" class="btn btn-outline-primary rounded-pill btn-sm" onclick="addEssay()">
                    <i class="bi bi-plus-lg"></i> + Add Essay
                </button>
            </div>
        </div>

        <div id="questions-container" class="vstack gap-4">
            <?php 
            $qIndex = 0;
            foreach ($questions as $q): 
                $qIndex++;
                $idx = $qIndex;
                $type = $q['question_type'] ?? 'multiple';
            ?>
            <div class="card border-0 shadow-sm" id="qcard-<?= $idx ?>">
                <input type="hidden" name="questions[<?= $idx ?>][id]" value="<?= $q['id_question'] ?>">
                <input type="hidden" name="questions[<?= $idx ?>][type]" value="<?= $type ?>">
                
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">Question <?= $idx ?></span>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= $type === 'essay' ? 'Essay' : 'Multiple Choice' ?></h6>
                                <small class="text-muted"><?= $type === 'essay' ? 'Open-ended question' : 'Enter the question and options below' ?></small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="removeQuestion(<?= $idx ?>)">Delete</button>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea name="questions[<?= $idx ?>][text]" required class="form-control" placeholder="Enter your question here" style="height: 100px"><?= htmlspecialchars($q['question_text'] ?? '') ?></textarea>
                        <label>Question Text *</label>
                    </div>

                    <?php if ($type === 'multiple'): ?>
                    <div class="vstack gap-2" id="options-list-<?= $idx ?>">
                        <?php 
                        $options = [
                            ['key' => 'a', 'val' => $q['option_a']],
                            ['key' => 'b', 'val' => $q['option_b']],
                            ['key' => 'c', 'val' => $q['option_c']],
                            ['key' => 'd', 'val' => $q['option_d']]
                        ];
                        // Filter out empty options if desired, or show all 4 standard ones? 
                        // Standard logic usually shows 4. Let's show A, B, C, D if they exist or at least A and B.
                        // For editing, let's just show all 4 if they have values, or standard A-D placeholders.
                        // Actually, let's stick to the 4 standard options structure for now as per DB schema.
                        foreach ($options as $opt):
                            if (empty($opt['val'])) continue;
                            $letter = strtoupper($opt['key']);
                            $isChecked = ($q['correct_answer'] === $letter) ? 'checked' : '';
                        ?>
                        <div class="option-row input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary" style="width: 40px; justify-content: center;"><?= $letter ?></span>
                            <input type="text" class="form-control" name="questions[<?= $idx ?>][<?= $opt['key'] ?>]" value="<?= htmlspecialchars($opt['val']) ?>" placeholder="Option <?= $letter ?>" required>
                            <div class="input-group-text bg-white">
                                <input class="form-check-input mt-0" type="radio" name="questions[<?= $idx ?>][correct]" value="<?= $letter ?>" required <?= $isChecked ?>>
                            </div>
                            <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                <i class="bi bi-x-lg">✕</i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="addOption(<?= $idx ?>)">+ Add Option</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-5 mb-5 pb-5">
            <a href="teacher_dashboard.php" class="btn btn-light border px-4">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize qIndex for JS
    let qIndex = <?= count($questions) ?>;
</script>
<script src="../../Assets/JS/teacher/quiz_edit.js"></script>
</body>
</html>
