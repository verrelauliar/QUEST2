<?php
/**
 * OPTIMIZED Quiz Creation
 * 
 * Performance improvements:
 * 1. Session-cached auth to skip Supabase HTTP on subsequent requests
 * 2. PDO transactions for atomic exam + questions insert (O(1) vs O(n) HTTP calls)
 * 3. Single prepared statement reused for all questions
 * 
 * Security: Manual RLS - validates assignment ownership before insert
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
// PHASE 2: Load PDO and fetch assignments for dropdown
// ============================================================================
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

$message = "";

// ============================================================================
// PHASE 3: Handle form submission with PDO Transaction
// ============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $duration = intval($_POST['duration'] ?? 0);
    $questions = $_POST['questions'] ?? [];

    if ($title === "" || $assignment_id <= 0 || $duration <= 0) {
        $message = "⚠️ Harap isi semua kolom dengan benar.";
    } else {
        try {
            // =====================================================================
            // MANUAL RLS: Verify assignment belongs to this teacher
            // =====================================================================
            $stmt = $pdo->prepare("
                SELECT id_assignment 
                FROM tbl_teaching_assignments 
                WHERE id_assignment = ? AND teacher_id = ?
            ");
            $stmt->execute([$assignment_id, $teacher_id_int]);
            
            if (!$stmt->fetch()) {
                throw new Exception("Unauthorized: You do not own this assignment");
            }
            
            // =====================================================================
            // BEGIN TRANSACTION: Atomic insert of exam + questions
            // =====================================================================
            $pdo->beginTransaction();
            
            // Get current timestamps
            $now = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // 1. Insert exam header
            $stmt = $pdo->prepare("
                INSERT INTO tbl_exams 
                    (assignment_id, title, instructions, duration, passing_score, 
                     start_time, end_time, is_active, show_results, allow_review)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id_exam
            ");
            $stmt->execute([
                $assignment_id,
                $title,
                trim($_POST['description'] ?? '') ?: null,
                $duration,
                60.0,
                $now,
                $endDate,
                true,
                true,
                true
            ]);
            $exam_row = $stmt->fetch();
            $exam_id = $exam_row['id_exam'];
            
            if (!$exam_id) {
                throw new Exception("Failed to create exam");
            }
            
            // 2. Prepare question statement ONCE (optimization)
            $stmtQ = $pdo->prepare("
                INSERT INTO tbl_questions 
                    (exam_id, question_text, question_type, option_a, option_b, 
                     option_c, option_d, correct_answer, display_order, points)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // 3. Insert all questions (batch - microseconds per loop, NOT over HTTP)
            $order = 0;
            foreach ($questions as $q) {
                $qText = trim($q['text'] ?? '');
                if (empty($qText)) continue;
                
                $order++;
                $qType = $q['type'] ?? 'multiple';
                
                if (!in_array($qType, ['multiple', 'essay', 'truefalse'])) {
                    $qType = 'multiple';
                }
                
                $stmtQ->execute([
                    $exam_id,
                    $qText,
                    $qType,
                    !empty($q['a']) ? trim($q['a']) : null,
                    !empty($q['b']) ? trim($q['b']) : null,
                    !empty($q['c']) ? trim($q['c']) : null,
                    !empty($q['d']) ? trim($q['d']) : null,
                    !empty($q['correct']) ? strtoupper(trim($q['correct'])) : null,
                    $order,
                    1.0
                ]);
            }
            
            $pdo->commit();
            $message = "✅ Exam Berhasil Dibuat!";
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Quiz create error: " . $e->getMessage());
            $message = "❌ Gagal Menyimpan Exam: " . $e->getMessage();
        }
    }
}

// ============================================================================
// PHASE 4: Fetch assignments for dropdown using PDO
// ============================================================================
$assignments = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            ta.id_assignment,
            s.subject_name,
            c.class_name
        FROM tbl_teaching_assignments ta
        INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        INNER JOIN tbl_classes c ON ta.class_id = c.id_class
        WHERE ta.teacher_id = ?
        ORDER BY s.subject_name, c.class_name
    ");
    $stmt->execute([$teacher_id_int]);
    $assignments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading assignments: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create New Quiz | QUEST</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Assets/CSS/teacher/quiz_create.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width: 980px;">
  <div class="d-flex align-items-center gap-3 mb-4 bg-white p-3 rounded-3 shadow-sm border">
      <img src="../../Assets/Images/mumtaza_logo.png" alt="Logo" width="30" height="30" class="object-fit-contain">
      <h1 class="h5 mb-0 fw-bold text-dark">Create New Quiz</h1>
  </div>

  <form id="quizForm" method="POST" enctype="multipart/form-data">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-body p-4">
        <div class="mb-4">
          <h5 class="fw-bold mb-1">Quiz Details</h5>
          <p class="text-muted small mb-0">Basic information about your quiz</p>
        </div>

        <div class="row g-3">
          <div class="col-12">
            <div class="form-floating">
              <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Islamic Studies - Unit 1" required>
              <label for="title">Quiz Title *</label>
            </div>
          </div>

          <div class="col-12">
            <div class="form-floating">
              <textarea class="form-control" id="description" name="description" placeholder="Description" style="height: 100px"></textarea>
              <label for="description">Quiz Description</label>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select" id="assignment_id" name="assignment_id" required>
                <option value="" selected disabled>Select subject</option>
                <?php foreach ($assignments as $assignment): ?>
                    <option value="<?= (int)$assignment['id_assignment'] ?>">
                        <?= htmlspecialchars($assignment['subject_name'] . ' — ' . $assignment['class_name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
              <label for="assignment_id">Subject *</label>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="number" class="form-control" id="duration" name="duration" value="30" min="1" required>
              <label for="duration">Duration (minutes) *</label>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="date" class="form-control" id="due_date" name="due_date">
              <label for="due_date">Due Date</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="my-4 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="fw-bold mb-1">Questions</h5>
        <p class="text-muted small mb-0">Add and configure quiz questions</p>
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

    <div id="questions-container" class="vstack gap-4"></div>

    <div class="d-flex justify-content-end gap-2 mt-5 mb-5 pb-5">
      <a href="teacher_dashboard.php" class="btn btn-light border">Cancel</a>
      <button type="submit" class="btn btn-primary px-4">Create Quiz</button>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-<?= strpos($message,'✅')!==false ? 'success' : 'danger' ?> mt-3" role="alert">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Assets/JS/teacher/quiz_create.js"></script>
</body>
</html>