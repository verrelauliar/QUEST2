<?php
/**
 * Admin Dashboard - Hybrid Architecture (Session-Cached Auth + PDO Data)
 * 
 * Performance: Session-Cached Auth eliminates Supabase HTTP overhead on subsequent requests
 * Data: Native PDO for fast COUNT queries
 * 
 * @see constitution.md for architecture details
 */
declare(strict_types=1);

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

if ($auth_cached && $has_user) {
    // FAST PATH: Auth already verified (~0ms overhead)
    $admin_id = (int)$_SESSION['user']['id'];
    $admin_name = $_SESSION['user']['username'] ?? 'Admin';
    session_write_close(); // Release session lock for concurrent requests
} else {
    // SLOW PATH: First request - full Supabase validation
    require_once __DIR__ . '/../Config/supabase.php';
    
    $supabase = SupabaseConnection::getInstance();
    $authService = new AuthService($supabase);
    
    $validation = $authService->validateSession();
    if (!$validation['valid'] || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        session_write_close();
        header("Location: login.php");
        exit();
    }
    
    // Cache auth for subsequent requests
    $_SESSION['auth_verified'] = true;
    $admin_id = (int)$_SESSION['user']['id'];
    $admin_name = $_SESSION['user']['username'] ?? 'Admin';
    session_write_close();
}

// ============================================================================
// PHASE 2: High-Performance Data Fetching (PDO)
// ============================================================================
require_once __DIR__ . '/../Config/db_pdo.php';

try {
    $pdo = getPDOConnection();
    
    // Fetch dashboard statistics with optimized COUNT queries
    $stats = [
        'total_students' => 0,
        'total_teachers' => 0,
        'total_classes' => 0,
        'total_subjects' => 0,
        'total_exams' => 0,
        'active_exams' => 0
    ];
    
    // Count students
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_users WHERE role = 'student' AND status = 'active'");
    $stmt->execute();
    $stats['total_students'] = (int)$stmt->fetchColumn();
    
    // Count teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_users WHERE role = 'teacher' AND status = 'active'");
    $stmt->execute();
    $stats['total_teachers'] = (int)$stmt->fetchColumn();
    
    // Count active classes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_classes WHERE status = 'active'");
    $stmt->execute();
    $stats['total_classes'] = (int)$stmt->fetchColumn();
    
    // Count subjects
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_subjects");
    $stmt->execute();
    $stats['total_subjects'] = (int)$stmt->fetchColumn();
    
    // Count total exams
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_exams");
    $stmt->execute();
    $stats['total_exams'] = (int)$stmt->fetchColumn();
    
    // Count active exams
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_exams WHERE is_active = true");
    $stmt->execute();
    $stats['active_exams'] = (int)$stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Admin Dashboard PDO Error: " . $e->getMessage());
    $stats = [
        'total_students' => 0,
        'total_teachers' => 0,
        'total_classes' => 0,
        'total_subjects' => 0,
        'total_exams' => 0,
        'active_exams' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top px-4 bg-white border-bottom" style="height: 72px; z-index: 1030;">
        <div class="d-flex align-items-center w-100">
            <button class="btn btn-light me-3 border" id="sidebarToggle">☰</button>

            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="#">
                <img src="../Assets/Images/mumtaza_logo.png" alt="Logo" height="30">
                <span>Admin Panel</span>
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-secondary d-none d-md-block small">
                    Welcome, <strong><?= htmlspecialchars($admin_name) ?></strong>
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="admin-layout" style="margin-top: 72px; display: flex;">
        
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <main class="admin-main flex-grow-1 p-4" style="margin-left: 280px; transition: 0.3s;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Dashboard</h3>
                    <p class="text-muted mb-0">Overview of system management</p>
                </div>
            </div>

            <!-- Statistics Cards Row -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center py-3">
                            <div class="text-primary mb-2"><i class="bi bi-mortarboard fs-3"></i></div>
                            <h3 class="fw-bold mb-0"><?= $stats['total_students'] ?></h3>
                            <small class="text-muted">Students</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center py-3">
                            <div class="text-success mb-2"><i class="bi bi-person-badge fs-3"></i></div>
                            <h3 class="fw-bold mb-0"><?= $stats['total_teachers'] ?></h3>
                            <small class="text-muted">Teachers</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center py-3">
                            <div class="text-info mb-2"><i class="bi bi-building fs-3"></i></div>
                            <h3 class="fw-bold mb-0"><?= $stats['total_classes'] ?></h3>
                            <small class="text-muted">Classes</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center py-3">
                            <div class="text-warning mb-2"><i class="bi bi-book fs-3"></i></div>
                            <h3 class="fw-bold mb-0"><?= $stats['total_subjects'] ?></h3>
                            <small class="text-muted">Subjects</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center py-3">
                            <div class="text-danger mb-2"><i class="bi bi-file-earmark-text fs-3"></i></div>
                            <h3 class="fw-bold mb-0"><?= $stats['total_exams'] ?></h3>
                            <small class="text-muted">Total Exams</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center py-3">
                            <div class="text-success mb-2"><i class="bi bi-check-circle fs-3"></i></div>
                            <h3 class="fw-bold mb-0"><?= $stats['active_exams'] ?></h3>
                            <small class="text-muted">Active Exams</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Cards -->
            <h5 class="fw-bold text-dark mb-3">Quick Actions</h5>
            <div class="row g-4">
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center d-flex flex-column p-4">
                            <div class="mb-3 text-primary fs-1"><i class="bi bi-building"></i></div>
                            <h5 class="card-title fw-bold">Manage Classes</h5>
                            <p class="card-text text-muted small flex-grow-1">Create classes, assign teachers, manage enrollments.</p>
                            <button data-href="AdminManage/ManageClasses/manage_classes.php" class="btn btn-primary mt-auto w-100">
                                <i class="bi bi-arrow-right me-1"></i> Open Classes
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center d-flex flex-column p-4">
                            <div class="mb-3 text-primary fs-1"><i class="bi bi-book"></i></div>
                            <h5 class="card-title fw-bold">Manage Subjects</h5>
                            <p class="card-text text-muted small flex-grow-1">Configure course codes and subject information.</p>
                            <button data-href="AdminManage/ManageSubjects/manage_subjects.php" class="btn btn-primary mt-auto w-100">
                                <i class="bi bi-arrow-right me-1"></i> Open Subjects
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center d-flex flex-column p-4">
                            <div class="mb-3 text-primary fs-1"><i class="bi bi-people"></i></div>
                            <h5 class="card-title fw-bold">Manage Users</h5>
                            <p class="card-text text-muted small flex-grow-1">Add teachers and students, manage accounts.</p>
                            <button data-href="AdminManage/ManageUsers/manage_users.php" class="btn btn-primary mt-auto w-100">
                                <i class="bi bi-arrow-right me-1"></i> Open Users
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/JS/admin/admin_utils.js"></script>
    <script src="../Assets/JS/admin/admin_sidebar.js"></script>
    <script src="../Assets/JS/admin/admin_common.js"></script>
</body>
</html>