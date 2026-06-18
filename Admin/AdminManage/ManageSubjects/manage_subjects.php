<?php
/**
 * Manage Subjects (Admin Panel) - Hybrid Architecture (Session-Cached Auth + PDO)
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
    $admin_name = $_SESSION['user']['username'] ?? 'Admin';
    session_write_close();
} else {
    require_once __DIR__ . '/../../../Config/supabase.php';
    
    $supabase = SupabaseConnection::getInstance();
    $authService = new AuthService($supabase);
    
    if (!$authService->validateSession()['valid'] || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        session_write_close();
        header("Location: ../../login.php");
        exit();
    }
    
    $_SESSION['auth_verified'] = true;
    $admin_name = $_SESSION['user']['username'] ?? 'Admin';
    session_write_close();
}

// ============================================================================
// PHASE 2: Data Fetching via PDO
// ============================================================================
require_once __DIR__ . '/../../../Config/db_pdo.php';

$subjects = [];

try {
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare("
        SELECT id_subject, subject_name, subject_code, description 
        FROM tbl_subjects 
        ORDER BY id_subject DESC
    ");
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("ManageSubjects PDO Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../admin.css">
    <link rel="stylesheet" href="subjects_style.css?v=4">
</head>
<body>

    <!-- Navbar (Consistent with Dashboard) -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top px-4 bg-white border-bottom" style="height: 72px; z-index: 1030;">
        <div class="d-flex align-items-center w-100">
            <button class="btn btn-light me-3 border" id="sidebarToggle">☰</button>

            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="#">
                <img src="../../../Assets/Images/mumtaza_logo.png" alt="Logo" height="30">
                <span>Manage Subjects</span>
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-secondary d-none d-md-block small">
                    Welcome, <strong><?= htmlspecialchars($admin_name) ?></strong>
                </span>
                <a href="../../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="admin-layout">
        
        <?php require_once __DIR__ . '/../../sidebar.php'; ?>

        <main class="admin-main" data-form-url="subjects_form.php">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Subjects</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="../../dashboard.php" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Manage Subjects</li>
                        </ol>
                    </nav>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openSubjectModal();">
                    <i class="bi bi-plus-lg"></i> New Subject
                </button>
            </div>

            <!-- Content Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3" style="width: 50px;">ID</th>
                                    <th class="px-4 py-3">Subject Name</th>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <div class="fs-1 mb-3"><i class="bi bi-book"></i></div>
                                            <p class="mb-0">No subjects found. Click "New Subject" to create one.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $row): ?>
                                    <tr>
                                        <td class="px-4 fw-bold text-secondary"><?= (int)$row['id_subject']; ?></td>
                                        <td class="px-4 fw-bold text-primary"><?= htmlspecialchars($row['subject_name']); ?></td>
                                        <td class="px-4"><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['subject_code']); ?></span></td>
                                        <td class="px-4 text-muted small"><?= htmlspecialchars(substr($row['description'] ?? '', 0, 50)) . (strlen($row['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                        <td class="px-4 text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openSubjectModal(<?= (int)$row['id_subject']; ?>)">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteSubject(<?= (int)$row['id_subject']; ?>, '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES); ?>', event)">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Bootstrap Modal Container -->
    <div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" id="modalContent">
                <!-- Content loaded via AJAX -->
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../Assets/JS/admin/admin_utils.js"></script>
    <script src="../../../Assets/JS/admin/admin_sidebar.js"></script>
    <script src="../../../Assets/JS/admin/admin_common.js"></script>
    <script src="../../../Assets/JS/admin/manage_subjects.js"></script>
</body>
</html>
