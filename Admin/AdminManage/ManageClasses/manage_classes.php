<?php
/**
 * Manage Classes (Admin Panel) - Hybrid Architecture (Session-Cached Auth + PDO)
 * 
 * @see constitution.md for architecture details
 */
declare(strict_types=1);

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../../vendor/autoload.php';

use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

if ($auth_cached && $has_user) {
    // FAST PATH: Auth already verified
    session_write_close();
} else {
    // SLOW PATH: First request - full Supabase validation
    require_once __DIR__ . '/../../../Config/supabase.php';
    
    $supabase = SupabaseConnection::getInstance();
    $authService = new AuthService($supabase);
    
    if (!$authService->validateSession()['valid'] || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        session_write_close();
        header('Location: ../../login.php');
        exit();
    }
    
    $_SESSION['auth_verified'] = true;
    session_write_close();
}

// ============================================================================
// PHASE 2: Data Fetching via PDO
// ============================================================================
require_once __DIR__ . '/../../../Config/db_pdo.php';

$classes = [];
$stats = [];

try {
    $pdo = getPDOConnection();
    
    // 1. Fetch all classes
    $stmt = $pdo->prepare("
        SELECT id_class, class_name, grade_level, academic_year, status 
        FROM tbl_classes 
        ORDER BY grade_level ASC, class_name ASC
    ");
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($classes)) {
        $class_ids = array_column($classes, 'id_class');
        $placeholders = implode(',', array_fill(0, count($class_ids), '?'));
        
        // 2. Count students per class
        $stmt = $pdo->prepare("
            SELECT class_id, COUNT(*) as count 
            FROM tbl_class_enrollments 
            WHERE class_id IN ($placeholders)
            GROUP BY class_id
         ");
        $stmt->execute($class_ids);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['class_id']]['students'] = (int)$row['count'];
        }
        
        // 3. Count subjects and teachers per class
        $stmt = $pdo->prepare("
            SELECT class_id, COUNT(*) as subject_count, COUNT(DISTINCT teacher_id) as teacher_count
            FROM tbl_teaching_assignments 
            WHERE class_id IN ($placeholders)
            GROUP BY class_id
        ");
        $stmt->execute($class_ids);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['class_id']]['subjects'] = (int)$row['subject_count'];
            $stats[$row['class_id']]['teachers'] = (int)$row['teacher_count'];
        }
    }
    
} catch (PDOException $e) {
    error_log("ManageClasses PDO Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Classes | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../admin.css">
    <link rel="stylesheet" href="classes_style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top px-4 bg-white border-bottom" style="height: 72px; z-index: 1030;">
    <div class="d-flex align-items-center w-100">
        <button class="btn btn-light me-3 border" id="sidebarToggle">☰</button>
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="#">
            <img src="../../../Assets/Images/mumtaza_logo.png" alt="Logo" height="30">
            <span>Manage Classes</span>
        </a>
        <div class="ms-auto">
            <a href="../../dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
        </div>
    </div>
</nav>

<div class="admin-layout" style="margin-top: 72px; display: flex;">
    <?php require_once __DIR__ . '/../../sidebar.php'; ?>

    <main class="admin-main flex-grow-1 p-4" style="margin-left: 280px; transition: 0.3s;">
        <div class="manage-classes-wrapper">
            
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div class="section-lead">
                    <p class="eyebrow">Administration</p>
                    <h3>Classes</h3>
                    <p class="text-muted mb-0">Manage academic class groups, subjects, and enrollments.</p>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createClassModal">
                    <i class="bi bi-plus-lg me-1"></i>New Class
                </button>
            </div>

            <div class="row g-4">
                <?php if (empty($classes)): ?>
                    <div class="col-12">
                        <div class="p-5 text-center border rounded-3 bg-light text-muted">
                            <div class="fs-1 mb-2"><i class="bi bi-folder2-open"></i></div>
                            <h4>No Classes Found</h4>
                            <p>Get started by creating your first class group.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($classes as $c): 
                        $cid = (int)$c['id_class'];
                        $sCount = $stats[$cid]['students'] ?? 0;
                        $subCount = $stats[$cid]['subjects'] ?? 0;
                        $tCount = $stats[$cid]['teachers'] ?? 0;
                        $grade = $c['grade_level'] ?? '?';
                        $year = $c['academic_year'] ?? 'N/A';
                        $status = $c['status'] ?? 'active';
                    ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="class-card h-100 position-relative">
                            
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3><?= htmlspecialchars($c['class_name']) ?></h3>
                                <span class="status-badge <?= $status === 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </div>
                            
                            <div class="meta-row">
                                <span>Grade <?= htmlspecialchars((string)$grade) ?></span>
                                <span class="mx-2">•</span>
                                <span><?= htmlspecialchars($year) ?></span>
                            </div>

                            <div class="stats-row">
                                <div class="stat-item" title="Students Enrolled">
                                    <i class="bi bi-people"></i>
                                    <span><?= $sCount ?> Students</span>
                                </div>
                                <div class="stat-item" title="Subjects">
                                    <i class="bi bi-book"></i>
                                    <span><?= $subCount ?> Subjects</span>
                                </div>
                            </div>

                            <a href="classes_detail.php?id=<?= $cid ?>" class="stretched-link"></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="createClassModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="createClassForm" action="classes_crud.php" method="POST">
            <div class="modal-header">
                <h5 class="modal-title">Create New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label fw-bold">Class Name</label>
                    <input type="text" name="class_name" class="form-control" placeholder="e.g. 7A - Unggulan" required>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Grade Level</label>
                        <select name="grade_level" class="form-select" required>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" placeholder="2025/2026" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Class</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../Assets/JS/admin/admin_utils.js"></script>
<script src="../../../Assets/JS/admin/admin_sidebar.js"></script>
<script src="../../../Assets/JS/admin/admin_common.js"></script>
<script src="../../../Assets/JS/admin/manage_classes.js"></script>
</body>
</html>