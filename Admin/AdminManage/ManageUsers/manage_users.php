<?php
/**
 * Manage Users (Admin Panel) - Hybrid Architecture (Session-Cached Auth + PDO)
 * 
 * @see constitution.md for architecture details
 */
declare(strict_types=1);

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../../vendor/autoload.php';

use Core\Services\AuthService;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->safeLoad();

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

if ($auth_cached && $has_user) {
    // FAST PATH: Auth already verified
    $admin_id = (int)$_SESSION['user']['id'];
    session_write_close();
} else {
    // SLOW PATH: First request - full Supabase validation
    require_once __DIR__ . '/../../../Config/supabase.php';
    
    $supabase = SupabaseConnection::getInstance();
    $authService = new AuthService($supabase);
    
    $validation = $authService->validateSession();
    if (!$validation['valid'] || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        session_write_close();
        header('Location: ../../login.php');
        exit();
    }
    
    $_SESSION['auth_verified'] = true;
    $admin_id = (int)$_SESSION['user']['id'];
    session_write_close();
}

// ============================================================================
// PHASE 2: Include CRUD Functions (PDO-based)
// ============================================================================
require_once __DIR__ . '/users_crud.php';

// Re-open session for flash messages
session_start();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$isError = $_SESSION['flash_error'] ?? false;
unset($_SESSION['flash_error']);
session_write_close();

// --- Logic: POST Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['add_user'])) {
        $flash = addUser($_POST);
    } elseif (isset($_POST['update_user'])) {
        $flash = updateUser((int)$_POST['id'], $_POST);
    } elseif (isset($_POST['delete_user'])) {
        $flash = deleteUser((int)$_POST['id'], (int)$_SESSION['user']['id']);
    } elseif (isset($_POST['bulk_import'])) {
    // Call the updated function which returns an array
    $result = bulkImportUsers($_FILES['bulk_file']);
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Use the array keys from the new function
    $_SESSION['flash'] = $result['message'];
    $_SESSION['new_user_ids'] = $result['newIds']; 
    
    // Determine if it was an error based on the 'success' key
    $_SESSION['flash_error'] = !($result['success']);
    
    header('Location: manage_users.php');
    exit();
}
}

// --- Logic: Fetch Data ---
$editing = isset($_GET['edit']) ? getUserById((int)$_GET['edit']) : null;
$user_rows = getAllUsers();
$is_new_mode = isset($_GET['new']);

// Calculate totals
$role_totals = ['all' => 0, 'admin' => 0, 'teacher' => 0, 'student' => 0];
foreach ($user_rows as $u) {
    $role_totals['all']++;
    $r = $u['role'] ?? 'student';
    if (isset($role_totals[$r])) $role_totals[$r]++;
}

// Get admin ID for session check in template
session_start();
$current_admin_id = (int)$_SESSION['user']['id'];
$new_user_ids = $_SESSION['new_user_ids'] ?? []; // Capture the IDs
unset($_SESSION['new_user_ids']); // Clear immediately
session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../admin.css">
    <link rel="stylesheet" href="users_style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top px-4 bg-white border-bottom" style="height: 72px; z-index: 1030;">
        <div class="d-flex align-items-center w-100">
            <button class="btn btn-light me-3 border" id="sidebarToggle">☰</button>
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="#">
                <img src="../../../Assets/Images/mumtaza_logo.png" alt="Logo" height="30">
                <span>Manage Users</span>
            </a>
            <div class="ms-auto">
                <a href="../../dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="admin-layout" style="margin-top: 72px; display: flex;">
        <?php require_once __DIR__ . '/../../sidebar.php'; ?>

        <main class="admin-main flex-grow-1 p-4" style="margin-left: 280px; transition: 0.3s;">
            <?php if ($flash): ?>
        <div class="alert <?= $isError ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
            <div class="manage-users-wrapper">
                
                <div class="section-lead mb-4">
                    <p class="eyebrow">Directory</p>
                    <h3>User Overview</h3>
                    <p class="text-muted">Add, edit, and manage system access.</p>
                </div>

                <div class="panel-grid" id="panelGrid">

                    <section class="panel form-panel" id="formPanel">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="eyebrow mb-0"><?= $editing ? 'Update' : 'New' ?></p>
                                <h4 class="mb-0"><?= $editing ? 'Edit User' : 'Add User' ?></h4>
                            </div>
                            <button type="button" class="btn btn-sm btn-light" onclick="closeForm()"><i class="bi bi-x-lg"></i></button>
                        </div>

                        <form id="userForm" class="d-flex flex-column gap-3">
                            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
                            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id_user'] ?>"><?php endif; ?>
                            
                            <div>
                                <label>Username</label>
                                <input type="text" name="username" value="<?= htmlspecialchars($editing['username'] ?? '') ?>" <?= $editing ? 'readonly' : 'required' ?>>
                            </div>
                            <div>
                                <label>Password <?= $editing ? '<small class="text-muted fw-normal">(Optional)</small>' : '' ?></label>
                                <input type="password" name="password" placeholder="••••••" <?= !$editing ? 'required' : '' ?>>
                            </div>
                            <div>
                                <label>Full Name</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($editing['full_name'] ?? '') ?>" required>
                            </div>
                            <div>
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($editing['email'] ?? '') ?>" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label>Role</label>
                                    <select name="role" required>
                                        <option value="student" <?= ($editing['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="teacher" <?= ($editing['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                        <option value="admin"   <?= ($editing['role'] ?? '') === 'admin'   ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label>Status</label>
                                    <select name="status" required>
                                        <option value="active"   <?= ($editing['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2 text-end">
                                <button type="button" class="btn btn-light btn-sm me-2" onclick="closeForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi <?= $editing ? 'bi-check-lg' : 'bi-plus-lg' ?> me-1"></i>
                                    <?= $editing ? 'Save Changes' : 'Create User' ?>
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="panel table-panel">
                        <div class="panel-content">
                            <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">All Users</h4>
        <small class="text-muted"><?= count($user_rows) ?> registered accounts</small>
    </div>
    
    <!-- Right-aligned button group -->
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="handleAddNew()">
            <i class="bi bi-plus-lg me-1"></i>Add New
        </button>
        
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="document.getElementById('bulkFile').click()">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Import CSV
        </button>
        
        <a href="users_crud.php?download_template=1" class="btn btn-outline-secondary btn-sm rounded-pill px-3" download>
            <i class="bi bi-download me-1"></i>Download Template
        </a>
    </div>
</div>

<!-- Hidden form for file upload (moved outside) -->
<form id="bulkImportForm" action="manage_users.php" method="POST" enctype="multipart/form-data" style="display:none;">
    <input type="file" id="bulkFile" name="bulk_file" accept=".csv" onchange="this.form.submit()">
    <input type="hidden" name="bulk_import" value="1">
</form>
                            </div>

                            <div class="role-nav">
                                <?php foreach($role_totals as $role => $count): ?>
                                    <button type="button" class="role-btn <?= $role === 'all' ? 'active' : '' ?>" data-role="<?= $role ?>">
                                        <?= ucfirst($role) ?> <span class="count-pill"><?= $count ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name / Username</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_rows as $u): ?>
                                    <tr data-id="<?= (int)$u['id_user'] ?>" data-role="<?= htmlspecialchars($u['role'] ?? 'student') ?>">
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name'] ?? 'Unknown') ?></div>
                                            <div class="small text-muted">@<?= htmlspecialchars($u['username'] ?? '-') ?></div>
                                        </td>
                                        <td><span class="role-chip role-<?= htmlspecialchars($u['role'] ?? 'student') ?>"><?= ucfirst(htmlspecialchars($u['role'] ?? '-')) ?></span></td>
                                        <td><span class="status-badge status-<?= htmlspecialchars($u['status'] ?? 'active') ?>"><?= ucfirst(htmlspecialchars($u['status'] ?? 'active')) ?></span></td>
                                        <td class="text-end">
                                            <a href="?edit=<?= (int)$u['id_user'] ?>" class="table-action"><i class="bi bi-pencil me-1"></i>Edit</a>
                                            <?php if ((int)$u['id_user'] !== $current_admin_id): ?>
                                            <button type="button" class="table-action danger" onclick="deleteUser(<?= (int)$u['id_user'] ?>, '<?= htmlspecialchars($u['full_name'] ?? 'Unknown', ENT_QUOTES) ?>')">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <!-- Page Config (data attributes for JS) -->
    <div id="page-config" 
         data-is-editing="<?= $editing ? 'true' : 'false' ?>" 
         data-is-new-mode="<?= $is_new_mode ? 'true' : 'false' ?>" 
         style="display:none;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Pass the highlighted IDs from PHP to JS (already cleared from session)
window.highlightedUserIds = <?= json_encode($new_user_ids) ?>;
</script>
    <script src="/Assets/JS/admin/admin_utils.js"></script>
    <script src="/Assets/JS/admin/admin_sidebar.js"></script>
    <script src="/Assets/JS/admin/admin_common.js"></script>
    <script src="/Assets/JS/admin/manage_users.js"></script>
</body>
</html>