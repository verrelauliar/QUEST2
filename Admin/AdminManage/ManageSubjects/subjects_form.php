<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Config/db_pdo.php';

session_start();

// ─────────────────────────────────────────────────────────────
// Session-Cached Auth (Supabase SDK for auth only)
// ─────────────────────────────────────────────────────────────
if (empty($_SESSION['auth_verified']) || empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo '<div class="modal-body text-danger">Unauthorized access</div>';
    exit;
}
session_write_close();

// ─────────────────────────────────────────────────────────────
// PDO Connection + Fetch subject for edit (if id provided)
// ─────────────────────────────────────────────────────────────
$subject = ['subject_name' => '', 'subject_code' => '', 'description' => ''];

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) {
        try {
            $pdo = getPDOConnection();
            $stmt = $pdo->prepare("
                SELECT id_subject, subject_name, subject_code, description 
                FROM tbl_subjects 
                WHERE id_subject = :id 
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $subject = $row;
            }
        } catch (PDOException $e) {
            error_log("Failed to fetch subject data: " . $e->getMessage());
        }
    }
}

$isEdit = !empty($subject['id_subject']);
?>

<div class="modal-header">
    <h5 class="modal-title fw-bold" id="subjectModalLabel">
        <?= $isEdit ? 'Edit Subject' : 'Add New Subject'; ?>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

    <div class="modal-body">
        <form id="subjectForm" action="subjects_crud.php" method="POST" class="needs-validation" novalidate>
        
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($subject['id_subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            
            <!-- Hidden field to store action type (add/update) - more reliable than event.submitter -->
            <input type="hidden" name="action_type" value="<?= $isEdit ? 'update' : 'add'; ?>">

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="subject_name" name="subject_name"
                       value="<?= htmlspecialchars((string)($subject['subject_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Subject Name" required>
                <label for="subject_name">Subject Name</label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="subject_code" name="subject_code"
                       value="<?= htmlspecialchars((string)($subject['subject_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Subject Code" required>
                <label for="subject_code">Subject Code</label>
            </div>

            <div class="form-floating">
                <textarea class="form-control" placeholder="Description" id="description" name="description" style="height: 100px"><?= htmlspecialchars((string)($subject['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                <label for="description">Description (Optional)</label>
            </div>

        </form>
    </div>
    <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="<?= $isEdit ? 'update' : 'add'; ?>" form="subjectForm" class="btn btn-primary px-4">
            <?= $isEdit ? 'Save Changes' : 'Add Subject'; ?>
        </button>
    </div>
