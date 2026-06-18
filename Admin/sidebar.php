<?php
/**
 * Admin Sidebar - Hybrid Architecture (PDO for Data)
 * 
 * Uses PDO for fast data fetching (subjects, users, classes counts)
 * Session is already validated by parent page (dashboard.php, manage_*.php)
 */

// 1. Dynamic Base Path Calculation
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));

$webPath = '';
if (!empty($documentRoot) && !empty($scriptFilename) && strpos($scriptFilename, $documentRoot) === 0) {
    $webPath = substr($scriptFilename, strlen($documentRoot));
}
if (empty($webPath)) {
    $webPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
}

$adminPos = strpos($webPath, '/Admin/');
if ($adminPos !== false) {
    $adminBasePath = substr($webPath, 0, $adminPos + 7);
} else {
    $adminBasePath = '/Admin/';
}

// 2. Data Fetching via PDO (High Performance)
$sidebar_subjects = [];
$sidebar_classes  = [];
$sidebar_user_count = 0;

try {
    // Include PDO if not already included
    if (!function_exists('getPDOConnection')) {
        require_once __DIR__ . '/../Config/db_pdo.php';
    }
    $pdo = getPDOConnection();
    
    // Fetch subjects
    $stmt = $pdo->prepare("SELECT id_subject, subject_name FROM tbl_subjects ORDER BY subject_name ASC");
    $stmt->execute();
    $sidebar_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch active classes
    $stmt = $pdo->prepare("SELECT id_class, class_name FROM tbl_classes WHERE status = 'active' ORDER BY class_name ASC");
    $stmt->execute();
    $sidebar_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count active users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_users WHERE status = 'active'");
    $stmt->execute();
    $sidebar_user_count = (int)$stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Sidebar PDO Error: " . $e->getMessage());
}
?>

<!-- Config element for JS to read admin base path -->
<div id="admin-config" data-admin-base="<?= htmlspecialchars($adminBasePath) ?>" style="display:none;"></div>

<nav class="admin-sidebar d-flex flex-column bg-white border-end">
    <ul class="nav flex-column nav-pills w-100 p-2 gap-1">
        
        <div class="small fw-bold text-uppercase text-muted mt-3 mb-1 px-3" style="font-size: 0.75rem;">Overview</div>
        <li class="nav-item">
            <a href="#" data-href="dashboard.php" class="nav-link text-secondary d-flex align-items-center">
                <span class="me-2">📊</span> Dashboard
            </a>
        </li>

        <div class="small fw-bold text-uppercase text-muted mt-3 mb-1 px-3" style="font-size: 0.75rem;">Management</div>

        <li class="nav-item">
            <a class="nav-link text-secondary d-flex justify-content-between align-items-center collapsed" 
               href="#" 
               data-bs-toggle="collapse" 
               data-bs-target="#menu-subjects"
               aria-expanded="false">
                <div><span class="me-2">📚</span> Subjects</div>
                <span class="badge bg-light text-primary rounded-pill"><?= count($sidebar_subjects) ?></span>
            </a>
            <div class="collapse" id="menu-subjects">
                <ul class="list-unstyled fw-normal pb-1 small">
                    <li><a href="#" data-href="AdminManage/ManageSubjects/manage_subjects.php" class="nav-link ps-4 py-1 text-primary fw-bold">All Subjects</a></li>
                    <?php foreach ($sidebar_subjects as $s): ?>
                    <li><a href="#" data-href="AdminManage/ManageSubjects/manage_subjects.php?edit=<?= $s['id_subject'] ?>" class="nav-link ps-4 py-1 text-muted"><?= htmlspecialchars($s['subject_name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link text-secondary d-flex justify-content-between align-items-center collapsed" 
               href="#" 
               data-bs-toggle="collapse" 
               data-bs-target="#menu-users"
               aria-expanded="false">
                <div><span class="me-2">👥</span> Users</div>
                <span class="badge bg-light text-primary rounded-pill"><?= $sidebar_user_count ?></span>
            </a>
            <div class="collapse" id="menu-users">
                <ul class="list-unstyled fw-normal pb-1 small">
                    <li><a href="#" data-href="AdminManage/ManageUsers/manage_users.php" class="nav-link ps-4 py-1 text-primary fw-bold">Manage Users</a></li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link text-secondary d-flex justify-content-between align-items-center collapsed" 
               href="#" 
               data-bs-toggle="collapse" 
               data-bs-target="#menu-classes"
               aria-expanded="false">
                <div><span class="me-2">🏫</span> Classes</div>
                <span class="badge bg-light text-primary rounded-pill"><?= count($sidebar_classes) ?></span>
            </a>
            <div class="collapse" id="menu-classes">
                <ul class="list-unstyled fw-normal pb-1 small">
                    <li><a href="#" data-href="AdminManage/ManageClasses/manage_classes.php" class="nav-link ps-4 py-1 text-primary fw-bold">All Classes</a></li>
                    <?php foreach ($sidebar_classes as $c): ?>
                    <li><a href="#" data-href="AdminManage/ManageClasses/classes_detail.php?id=<?= $c['id_class'] ?>" class="nav-link ps-4 py-1 text-muted"><?= htmlspecialchars($c['class_name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </li>
    </ul>
</nav>