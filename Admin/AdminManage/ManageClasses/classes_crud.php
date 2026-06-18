<?php
/**
 * Classes CRUD API - PDO Implementation
 * 
 * Provides high-performance API endpoints for class management.
 * All operations use prepared statements for security.
 * 
 * @see constitution.md for architecture details
 */
declare(strict_types=1);

session_start();

// Manual RLS: Verify admin role before any operation
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../../Config/db_pdo.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getPDOConnection();
    
    switch ($action) {
        // --- CRUD ---
        case 'create':          createClass($pdo); break;
        case 'read':            readClasses($pdo); break;
        case 'read_one':        readOneClass($pdo); break;
        case 'update':          updateClass($pdo); break;
        case 'delete':          deleteClass($pdo); break;
        
        // --- DATA ---
        case 'get_teachers':    getTeachers($pdo); break;
        case 'get_subjects':    getSubjects($pdo); break;
        
        // --- CURRICULUM ---
        case 'get_curriculum':  getClassCurriculum($pdo); break;
        case 'add_assignment':  addSubjectAssignment($pdo); break;
        case 'update_assignment': updateSubjectAssignment($pdo); break;
        case 'remove_assignment': removeTeachingAssignment($pdo); break;

        // --- STUDENTS & EXAMS ---
        case 'get_roster_data': getRosterData($pdo); break;
        case 'update_roster':   updateClassRoster($pdo); break;
        case 'remove_student':  removeStudentFromClass($pdo); break;
        case 'get_exams_by_class': getExamsByClass($pdo); break;

        default: throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => $e->getMessage()]);
}

// ============================================================================
// CLASS CRUD FUNCTIONS
// ============================================================================

function createClass(PDO $pdo): void
{
    $class_name = trim($_POST['class_name'] ?? '');
    $grade_level = (int)($_POST['grade_level'] ?? 7);
    $academic_year = trim($_POST['academic_year'] ?? '');
    
    if (empty($class_name)) {
        throw new Exception('Class name is required');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO tbl_classes (class_name, grade_level, academic_year, status) 
        VALUES (?, ?, ?, 'active')
        RETURNING id_class
    ");
    $stmt->execute([$class_name, $grade_level, $academic_year]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'id' => $result['id_class']]);
}

function readClasses(PDO $pdo): void
{
    $stmt = $pdo->prepare("
        SELECT * FROM tbl_classes 
        ORDER BY grade_level ASC, class_name ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function readOneClass(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM tbl_classes WHERE id_class = ? LIMIT 1");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data ?: []]);
}

function updateClass(PDO $pdo): void
{
    $id = (int)($_POST['id_class'] ?? 0);
    $class_name = trim($_POST['class_name'] ?? '');
    $grade_level = (int)($_POST['grade_level'] ?? 7);
    $academic_year = trim($_POST['academic_year'] ?? '');
    
    $stmt = $pdo->prepare("
        UPDATE tbl_classes 
        SET class_name = ?, grade_level = ?, academic_year = ?
        WHERE id_class = ?
    ");
    $stmt->execute([$class_name, $grade_level, $academic_year, $id]);
    
    echo json_encode(['success' => true]);
}

function deleteClass(PDO $pdo): void
{
    $id = (int)($_POST['id_class'] ?? 0);
    
    // Soft delete - set status to inactive
    $stmt = $pdo->prepare("UPDATE tbl_classes SET status = 'inactive' WHERE id_class = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
}

// ============================================================================
// DATA HELPER FUNCTIONS
// ============================================================================

function getTeachers(PDO $pdo): void
{
    $stmt = $pdo->prepare("
        SELECT id_user, full_name 
        FROM tbl_users 
        WHERE role = 'teacher' AND status = 'active'
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function getSubjects(PDO $pdo): void
{
    $stmt = $pdo->prepare("SELECT id_subject, subject_name FROM tbl_subjects ORDER BY subject_name ASC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}

// ============================================================================
// CURRICULUM FUNCTIONS
// ============================================================================

function getClassCurriculum(PDO $pdo): void
{
    $class_id = (int)($_GET['class_id'] ?? 0);
    
    $stmt = $pdo->prepare("
        SELECT 
            ta.id_assignment, ta.teacher_id, ta.subject_id,
            u.full_name as teacher_name,
            s.subject_name, s.subject_code
        FROM tbl_teaching_assignments ta
        LEFT JOIN tbl_users u ON ta.teacher_id = u.id_user
        LEFT JOIN tbl_subjects s ON ta.subject_id = s.id_subject
        WHERE ta.class_id = ?
        ORDER BY s.subject_name ASC
    ");
    $stmt->execute([$class_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function addSubjectAssignment(PDO $pdo): void
{
    $class_id = (int)($_POST['class_id'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);

    if ($class_id <= 0 || $teacher_id <= 0 || $subject_id <= 0) {
        throw new Exception('Missing required fields');
    }

    // Check for duplicates
    $stmt = $pdo->prepare("
        SELECT id_assignment FROM tbl_teaching_assignments 
        WHERE class_id = ? AND subject_id = ?
        LIMIT 1
    ");
    $stmt->execute([$class_id, $subject_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('Subject already assigned to this class.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_teaching_assignments (class_id, teacher_id, subject_id) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$class_id, $teacher_id, $subject_id]);

    echo json_encode(['success' => true, 'message' => 'Subject assigned successfully']);
}

function updateSubjectAssignment(PDO $pdo): void
{
    $id = (int)($_POST['assignment_id'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);

    if ($id <= 0 || $teacher_id <= 0 || $subject_id <= 0) {
        throw new Exception('Invalid data');
    }

    $stmt = $pdo->prepare("
        UPDATE tbl_teaching_assignments 
        SET teacher_id = ?, subject_id = ?
        WHERE id_assignment = ?
    ");
    $stmt->execute([$teacher_id, $subject_id, $id]);

    echo json_encode(['success' => true, 'message' => 'Assignment updated']);
}

function removeTeachingAssignment(PDO $pdo): void
{
    $id = (int)($_POST['assignment_id'] ?? 0);
    
    $stmt = $pdo->prepare("DELETE FROM tbl_teaching_assignments WHERE id_assignment = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'Assignment removed']);
}

// ============================================================================
// STUDENT ROSTER FUNCTIONS
// ============================================================================

function getRosterData(PDO $pdo): void
{
    $class_id = (int)($_GET['class_id'] ?? 0);
    
    // Get IDs of enrolled students
    $stmt = $pdo->prepare("SELECT student_id FROM tbl_class_enrollments WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $enrolled_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all active students
    $stmt = $pdo->prepare("
        SELECT id_user, full_name, username 
        FROM tbl_users 
        WHERE role = 'student' AND status = 'active'
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $all_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $available = [];
    $enrolled = [];
    
    foreach ($all_students as $s) {
        if (in_array($s['id_user'], $enrolled_ids)) {
            $enrolled[] = $s;
        } else {
            $available[] = $s;
        }
    }
    
    echo json_encode(['success' => true, 'data' => ['available' => $available, 'enrolled' => $enrolled]]);
}

function updateClassRoster(PDO $pdo): void
{
    $class_id = (int)($_POST['class_id'] ?? 0);
    $student_ids = json_decode($_POST['student_ids'] ?? '[]', true);
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Remove all current enrollments
        $stmt = $pdo->prepare("DELETE FROM tbl_class_enrollments WHERE class_id = ?");
        $stmt->execute([$class_id]);
        
        // Add new enrollments
        if (!empty($student_ids)) {
            $stmt = $pdo->prepare("INSERT INTO tbl_class_enrollments (class_id, student_id) VALUES (?, ?)");
            foreach ($student_ids as $sid) {
                $stmt->execute([$class_id, (int)$sid]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function removeStudentFromClass(PDO $pdo): void
{
    $class_id = (int)($_POST['class_id'] ?? 0);
    $student_id = (int)($_POST['student_id'] ?? 0);
    
    $stmt = $pdo->prepare("DELETE FROM tbl_class_enrollments WHERE class_id = ? AND student_id = ?");
    $stmt->execute([$class_id, $student_id]);
    
    echo json_encode(['success' => true]);
}

// ============================================================================
// EXAM FUNCTIONS
// ============================================================================

function getExamsByClass(PDO $pdo): void
{
    $class_id = (int)($_GET['class_id'] ?? 0);
    
    // Get assignments for this class
    $stmt = $pdo->prepare("SELECT id_assignment FROM tbl_teaching_assignments WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $assignment_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($assignment_ids)) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($assignment_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT id_exam, title, start_time, end_time, is_active 
        FROM tbl_exams 
        WHERE assignment_id IN ($placeholders)
        ORDER BY start_time DESC
    ");
    $stmt->execute($assignment_ids);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}