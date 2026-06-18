<?php
/**
 * Class Detail View - Hybrid Architecture (Session-Cached Auth + PDO)
 * 
 * @see constitution.md for architecture details
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

if ($auth_cached && $has_user) {
    session_write_close();
} else {
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
$class_id = (int)($_GET['id'] ?? 0);

require_once __DIR__ . '/../../../Config/db_pdo.php';

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM tbl_classes WHERE id_class = ? LIMIT 1");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        header('Location: manage_classes.php');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("ClassDetail PDO Error: " . $e->getMessage());
    header('Location: manage_classes.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Class: <?= htmlspecialchars($class['class_name']) ?></title>
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
            <span>Class Details</span>
        </a>
        <div class="ms-auto d-flex gap-2">
            <a href="manage_classes.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Classes</a>
            <a href="../../dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house me-1"></i>Dashboard</a>
        </div>
    </div>
</nav>

<div class="admin-layout" style="margin-top: 72px; display: flex;">
    <?php include __DIR__ . '/../../sidebar.php'; ?>

    <main class="admin-main flex-grow-1 p-4" style="margin-left: 280px; transition: 0.3s;">
        <div class="manage-classes-wrapper">
            
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div class="section-lead">
                    <p class="eyebrow">Class Detail</p>
                    <h3><?= htmlspecialchars($class['class_name']) ?></h3>
                    <p class="text-muted mb-0">
                        Grade <?= htmlspecialchars((string)$class['grade_level']) ?> • <?= htmlspecialchars($class['academic_year']) ?>
                    </p>
                </div>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#editClassModal">
                    <i class="bi bi-pencil me-1"></i>Edit Details
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                    <ul class="nav nav-tabs card-header-tabs" id="classTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" id="curriculum-tab" data-bs-toggle="tab" data-bs-target="#tab-curriculum" type="button"><i class="bi bi-book me-1"></i>Curriculum</button></li>
                        <li class="nav-item"><button class="nav-link" id="enrollment-tab" data-bs-toggle="tab" data-bs-target="#tab-enrollment" type="button"><i class="bi bi-people me-1"></i>Enrollment</button></li>
                        <li class="nav-item"><button class="nav-link" id="exams-tab" data-bs-toggle="tab" data-bs-target="#tab-exams" type="button"><i class="bi bi-file-earmark-text me-1"></i>Exams</button></li>
                    </ul>
                </div>
                
                <div class="card-body p-4">
                    <div class="tab-content" id="classTabsContent">
                        <div class="tab-pane fade show active" id="tab-curriculum">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0 text-dark">Assigned Subjects</h5>
                                <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="addSubjectRow()"><i class="bi bi-plus-lg me-1"></i>Add Subject</button>
                            </div>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead><tr><th style="width:35%">Subject</th><th style="width:35%">Teacher</th><th style="width:15%">Status</th><th style="width:15%">Actions</th></tr></thead>
                                    <tbody id="curriculum-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-enrollment">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0 text-dark">Student Roster</h5>
                                <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="openManageStudentsModal()"><i class="bi bi-person-plus me-1"></i>Manage Students</button>
                            </div>
                            <div id="enrollment-list"></div>
                        </div>

                        <div class="tab-pane fade" id="tab-exams">
                            <h5 class="fw-bold mb-3 text-dark">Exam Schedule</h5>
                            <div id="exam-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="editClassForm">
            <div class="modal-header">
                <h5 class="modal-title">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_class" value="<?= $class_id ?>">
                <input type="hidden" name="action" value="update">
                <div class="mb-3">
                    <label class="form-label fw-bold">Class Name</label>
                    <input type="text" name="class_name" value="<?= htmlspecialchars($class['class_name']) ?>" class="form-control" required>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Grade</label>
                        <input type="number" name="grade_level" value="<?= $class['grade_level'] ?>" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Year</label>
                        <input type="text" name="academic_year" value="<?= htmlspecialchars($class['academic_year']) ?>" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditClass()">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="manageStudentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"> <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Manage Students</h5>
                    <p class="text-muted small mb-0">Select students to enroll in <strong><?= htmlspecialchars($class['class_name']) ?></strong></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body pt-3">
                <div class="row g-3 align-items-center dual-list-wrapper">
                    
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-bold small text-muted">Available Students</label>
                            <span class="badge bg-light text-dark border" id="count-available">0</span>
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white text-muted">🔍</span>
                            <input type="text" class="form-control" placeholder="Search..." onkeyup="filterList(this, 'availableList')">
                        </div>
                        <div class="list-group dual-list-box shadow-sm" id="availableList">
                            </div>
                    </div>

                    <div class="col-md-2">
                        <div class="dual-list-controls">
                            <button class="btn btn-primary control-btn" onclick="moveSelected('right')" title="Move Selected Right">
                                &rarr;
                            </button>
                            <button class="btn btn-outline-secondary control-btn" onclick="moveSelected('left')" title="Move Selected Left">
                                &larr;
                            </button>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-bold small text-success">Enrolled in Class</label>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" id="count-selected">0</span>
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white text-muted">🔍</span>
                            <input type="text" class="form-control" placeholder="Search..." onkeyup="filterList(this, 'selectedList')">
                        </div>
                        <div class="list-group dual-list-box shadow-sm border-success-subtle" id="selectedList">
                            </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light">
                <small class="text-muted me-auto">Select multiple items by clicking them.</small>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" onclick="submitStudentRoster()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Page Config (data attributes for JS) -->
<div id="page-config" data-class-id="<?= $class_id ?>" style="display:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../Assets/JS/admin/admin_utils.js"></script>
<script src="../../../Assets/JS/admin/admin_sidebar.js"></script>
<script src="../../../Assets/JS/admin/admin_common.js"></script>
<script src="../../../Assets/JS/admin/classes_detail.js"></script>
</body>
</html>