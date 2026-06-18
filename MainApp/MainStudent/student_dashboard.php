<?php
/**
 * OPTIMIZED Student Dashboard
 * * Performance:
 * - Session-Cached Auth
 * - 2 Optimized PDO Queries
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$error_message = $_SESSION['error'] ?? null;
$success_message = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user_data = isset($_SESSION['user']) && isset($_SESSION['user']['role']);

if ($auth_cached && $has_user_data && $_SESSION['user']['role'] === 'student') {
    $student_id = $_SESSION['user']['id'];
    $student_name = $_SESSION['user']['name'];
    session_write_close();
} else {
    require_once __DIR__ . '/../../Config/supabase.php';
    $authService = new AuthService($supabase);
    $session_result = $authService->validateSession();
    
    if (!$session_result['valid'] || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'student') {
        session_write_close();
        header("Location: ../index.php");
        exit;
    }
    
    $_SESSION['auth_verified'] = true;
    $student_id = $_SESSION['user']['id'];
    $student_name = $_SESSION['user']['name'];
    session_write_close();
}

// ============================================================================
// PHASE 2: High-Performance Data Fetching
// ============================================================================
require_once __DIR__ . '/../../Config/db_pdo.php';

$student_id_int = is_numeric($student_id) ? (int)$student_id : 0;

try {
    $pdo = getPDOConnection();
    
    // QUERY 1: Fetch Exams
    $stmt = $pdo->prepare("
        SELECT e.id_exam, e.title, e.duration, e.start_time, e.end_time, e.assignment_id, 
               s.subject_name, c.class_name,
               (SELECT COUNT(*) FROM tbl_questions q WHERE q.exam_id = e.id_exam) as question_count
        FROM tbl_exams e
        INNER JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
        INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        INNER JOIN tbl_classes c ON ta.class_id = c.id_class
        INNER JOIN tbl_class_enrollments ce ON ta.class_id = ce.class_id
        WHERE ce.student_id = ? AND e.is_active = true
        ORDER BY e.start_time DESC
    ");
    $stmt->execute([$student_id_int]);
    $exams = $stmt->fetchAll();
    
    // QUERY 2: Fetch Attempts
    $stmt = $pdo->prepare("
        SELECT a.id_attempt, a.exam_id, a.status, a.started_at, a.submitted_at, sc.percentage, sc.passed
        FROM tbl_exam_attempts a
        LEFT JOIN tbl_scores sc ON a.id_attempt = sc.attempt_id
        WHERE a.student_id = ?
    ");
    $stmt->execute([$student_id_int]);
    $attempts = $stmt->fetchAll();
    
    // Process Data
    $attempts_by_exam = [];
    foreach ($attempts as $att) {
        $attempts_by_exam[$att['exam_id']][] = $att;
    }
    
    $unique_exams = [];
    foreach ($exams as $exam) {
        $unique_exams[$exam['id_exam']] = $exam;
    }
    $exams = array_values($unique_exams);
    
    $pending_exams = [];
    $in_progress_exams = [];
    $completed_exams = [];
    
    foreach ($exams as $exam) {
        $exam_id = $exam['id_exam'];
        $exam_attempts = $attempts_by_exam[$exam_id] ?? [];
        
        if (empty($exam_attempts)) {
            $pending_exams[] = $exam;
            continue;
        }
        
        $latest_in_progress = null;
        $latest_completed = null;
        
        foreach ($exam_attempts as $att) {
            if ($att['status'] === 'in_progress') {
                if (!$latest_in_progress || strtotime($att['started_at']) > strtotime($latest_in_progress['started_at'])) {
                    $latest_in_progress = $att;
                }
            } elseif (in_array($att['status'], ['submitted', 'graded', 'expired'])) {
                if (!$latest_completed || strtotime($att['submitted_at']) > strtotime($latest_completed['submitted_at'])) {
                    $latest_completed = $att;
                }
            }
        }
        
        if ($latest_in_progress !== null) {
            $in_progress_exams[] = array_merge($exam, $latest_in_progress);
        } elseif ($latest_completed !== null) {
            $completed_exams[] = array_merge($exam, $latest_completed);
        }
    }
    
    // Metrics
    $completed = 0;
    $in_progress = 0;
    $total_score = 0;
    $score_count = 0;
    
    foreach ($attempts as $att) {
        if (in_array($att['status'], ['submitted', 'graded', 'expired'])) {
            $completed++;
            if ($att['status'] !== 'expired' && isset($att['percentage']) && $att['percentage'] !== null) {
                $total_score += floatval($att['percentage']);
                $score_count++;
            }
        } elseif ($att['status'] === 'in_progress') {
            $in_progress++;
        }
    }
    
    $pending = count($pending_exams);
    $avg_score = $score_count > 0 ? round($total_score / $score_count, 1) : 0;
    
    usort($pending_exams, fn($a, $b) => strtotime($a['start_time']) - strtotime($b['start_time']));
    usort($in_progress_exams, fn($a, $b) => strtotime($b['started_at']) - strtotime($a['started_at']));
    usort($completed_exams, fn($a, $b) => strtotime($b['submitted_at']) - strtotime($a['submitted_at']));
    
    // [MODIFIED] Removed array_slice to allow scrolling all items
    // $completed_exams = array_slice($completed_exams, 0, 4); 
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $completed = $pending = $in_progress = $avg_score = 0;
    $pending_exams = $in_progress_exams = $completed_exams = [];
}

function getSubjectBadgeClass($subject_name) {
    $map = ['Mathematics'=>'mathematics', 'Science'=>'science', 'Islamic Studies'=>'islamic', 'Bahasa Indonesia'=>'bahasa', 'English Literacy'=>'english'];
    return $map[$subject_name] ?? 'mathematics';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard | LMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Assets/CSS/student/dashboard.css">
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="../../Assets/Images/mumtaza_logo.png" alt="Logo" width="32" height="32" class="me-2">
      Student Dashboard
    </a>
    <div class="d-flex align-items-center gap-3">
      <div class="d-none d-md-block text-end">
        <div class="fw-bold small text-dark"><?= htmlspecialchars($student_name) ?></div>
        <div class="text-muted small" style="font-size: 0.75rem;">Student</div>
      </div>
      <a href="../logout.php" class="btn btn-outline-custom btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">

  <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      <?= htmlspecialchars($error_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- STATISTICS CARDS -->
  <div class="row g-4 mb-5">
    <!-- ... (Statistics cards remain unchanged) ... -->
    <div class="col-6 col-lg-3">
      <div class="card h-100 p-3 p-lg-4 border-0">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="stat-label">Completed</h6>
            <h2 class="stat-value text-success"><?= $completed ?></h2>
            <p class="small text-muted mb-0">Successfully finished</p>
          </div>
          <div class="p-2 bg-success-subtle rounded-circle text-success"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card h-100 p-3 p-lg-4 border-0">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="stat-label">Pending</h6>
            <h2 class="stat-value text-danger"><?= $pending ?></h2>
            <p class="small text-muted mb-0">Awaiting completion</p>
          </div>
          <div class="p-2 bg-danger-subtle rounded-circle text-danger"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card h-100 p-3 p-lg-4 border-0">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="stat-label">In Progress</h6>
            <h2 class="stat-value text-warning"><?= $in_progress ?></h2>
            <p class="small text-muted mb-0">Currently active</p>
          </div>
          <div class="p-2 bg-warning-subtle rounded-circle text-warning"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card h-100 p-3 p-lg-4 border-0">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="stat-label">Avg Score</h6>
            <h2 class="stat-value text-primary"><?= $avg_score ?>%</h2>
            <p class="small text-muted mb-0">Overall performance</p>
          </div>
          <div class="p-2 bg-primary-subtle rounded-circle text-primary"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg></div>
        </div>
      </div>
    </div>
  </div>

  <!-- PENDING EXAMS -->
  <div class="mb-5">
    <div class="section-header">Pending Exams</div>
    <div class="section-subtext">Complete these exams before the due date</div>
    
    <?php if (empty($pending_exams)): ?>
      <div class="card p-4 text-center text-muted border-0">
        <p class="mb-0">You're all caught up! No pending exam.</p>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach($pending_exams as $exam): ?>
        <div class="col-md-6">
          <div class="card h-100 p-4 card-pending border-0 shadow-sm">
            <div class="d-flex flex-column h-100">
                <div class="mb-3">
                    <span class="badge badge-<?= getSubjectBadgeClass($exam['subject_name']) ?> mb-2"><?= htmlspecialchars($exam['subject_name']) ?></span>
                    <h5 class="card-title text-truncate"><?= htmlspecialchars($exam['title']) ?></h5>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="info-label">Questions</div>
                        <div class="info-value"><?= $exam['question_count'] ?? '?' ?></div>
                    </div>
                    <div class="col-4">
                        <div class="info-label">Duration</div>
                        <div class="info-value"><?= htmlspecialchars($exam['duration']) ?>m</div>
                    </div>
                    <div class="col-4">
                        <div class="info-label">Due Date</div>
                        <div class="info-value"><?= date('m/d/Y', strtotime($exam['end_time'])) ?></div>
                    </div>
                </div>
                
                <div class="mt-auto">
                    <a href="exam.php?exam_id=<?= $exam['id_exam'] ?>" class="btn btn-primary w-100">Start Exam</a>
                </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- IN PROGRESS -->
  <?php if (!empty($in_progress_exams)): ?>
  <div class="mb-5">
    <div class="section-header">In Progress</div>
    <div class="section-subtext">Continue where you left off</div>
    <div class="row g-4">
        <?php foreach($in_progress_exams as $exam): ?>
        <div class="col-md-6">
          <div class="card h-100 p-4 card-in-progress border-0 shadow-sm">
            <div class="d-flex flex-column h-100">
                <div class="mb-3">
                    <span class="badge badge-<?= getSubjectBadgeClass($exam['subject_name']) ?> mb-2"><?= htmlspecialchars($exam['subject_name']) ?></span>
                    <h5 class="card-title text-truncate"><?= htmlspecialchars($exam['title']) ?></h5>
                </div>
                <div class="mb-4">
                    <div class="info-label">Started On</div>
                    <div class="info-value"><?= date('M d, Y \a\t H:i', strtotime($exam['started_at'])) ?></div>
                </div>
                <div class="mt-auto">
                    <a href="exam.php?exam_id=<?= $exam['id_exam'] ?>&resume=1" class="btn btn-primary w-100" style="background-color: var(--warning-color); border-color: var(--warning-color);">Resume Exam</a>
                </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- COMPLETED -->
  <div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <div class="section-header">Completed Exams</div>
            <div class="section-subtext mb-0">Review your past performance</div>
        </div>
        <a href="my-results.php" class="btn btn-link text-decoration-none">View All &rarr;</a>
    </div>
    
    <?php if (empty($completed_exams)): ?>
      <div class="card p-4 text-center text-muted border-0">
        <p class="mb-0">No completed exams yet.</p>
      </div>
    <?php else: ?>
      
      <!-- [MODIFIED] Scrollable Frame Container -->
      <div class="scrollable-exams-frame">
          <div class="row g-4 mt-0"> <!-- mt-0 removes extra top margin inside scroll -->
            <?php foreach($completed_exams as $exam): 
                $score = round($exam['percentage'] ?? 0, 1); 
                $passed = $exam['passed'] ?? false;
            ?>
            <div class="col-md-6">
              <div class="card h-100 p-4 card-completed border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge badge-<?= getSubjectBadgeClass($exam['subject_name']) ?> mb-2"><?= htmlspecialchars($exam['subject_name']) ?></span>
                        <h5 class="card-title text-truncate mb-0"><?= htmlspecialchars($exam['title']) ?></h5>
                    </div>
                    <span class="badge bg-light text-dark border">Completed</span>
                </div>
                
                <div class="row align-items-end mb-3">
                    <div class="col-6">
                        <div class="info-label">Your Score</div>
                        <div class="fs-4 fw-bold <?= $passed ? 'text-success' : 'text-danger' ?>"><?= $score ?>%</div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="info-label">Completed On</div>
                        <div class="info-value"><?= date('m/d/Y', strtotime($exam['submitted_at'])) ?></div>
                    </div>
                </div>
                
                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar <?= $passed ? 'bg-success' : 'bg-danger' ?>" style="width: <?= min($score, 100) ?>%"></div>
                </div>
                
                <a href="view-results.php?attempt_id=<?= $exam['id_attempt'] ?>" class="btn btn-outline-custom w-100 mt-auto">View Results</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
      </div>
      <!-- [END MODIFIED] -->

    <?php endif; ?>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>