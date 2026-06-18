# Database Development Constitution

**Version:** 3.0  
**Last Updated:** 2025-01-15

## Overview

This project uses a **Hybrid Database Architecture**:
- **Supabase SDK** for Authentication (login only)
- **Native PDO (PostgreSQL)** for ALL data operations (Student, Teacher, Admin)
- **Session-Cached Auth** to skip Supabase HTTP calls on subsequent requests

This hybrid approach reduces exam-taking latency from ~2.6s (HTTP SDK) to <200ms (TCP PDO).

---

## Architecture Summary

| Module | Connection Type | Why |
|--------|-----------------|-----|
| **Login/Auth** | Supabase SDK | Security (JWT/Password hashing), Low frequency |
| **Admin Dashboard** | **PDO + Cached Auth** | Fast stats loading, Consistent UX |
| **Admin CRUD** | **PDO + Cached Auth** | User/Class/Subject management |
| **Teacher Dashboard** | **PDO + Cached Auth** | Fast quiz listing, Improved UX |
| **Teacher Quiz CRUD** | **PDO + Cached Auth** | Quiz management with transactions |
| **Student Dashboard** | **PDO + Cached Auth** | Fast exam listing, High frequency |
| **Exam Taking** | **PDO + Cached Auth** | Critical latency, Questions/Answers |
| **Submit Answers** | **PDO** | High throughput, Real-time saves |
| **View Results** | **PDO** | Read-heavy, Student-facing |

---

## Core Principles

### 1. **KISS (Keep It Simple, Stupid)**
- Use straightforward methods instead of complex abstractions
- Avoid over-engineering solutions
- Prefer readable code over clever tricks

### 2. **DRY (Don't Repeat Yourself)**
- Use singleton patterns for both connection types
- Extract repeated logic into reusable functions
- Never create multiple connections per request

### 3. **Manual RLS (Row-Level Security)**

---

## Frontend Standardization

### Bootstrap 5.3 Utility Classes
All new UI components must use Bootstrap 5.3 utility classes over custom CSS:

```html
<!-- ✅ CORRECT - Bootstrap utilities -->
<div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3 shadow-sm">
  <h5 class="fw-bold mb-0">Dashboard</h5>
  <button class="btn btn-primary px-4">Create Quiz</button>
</div>

<!-- ❌ WRONG - Custom CSS classes -->
<div class="custom-header">
  <h5>Dashboard</h5>
  <button class="custom-button">Create Quiz</button>
</div>
```

### Standard Responsive Grid
Use the standard responsive grid pattern for consistent layouts:

```html
<!-- Standard responsive grid -->
<div class="row g-3">
  <div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <!-- Card content -->
  </div>
</div>
```

### CSS File Guidelines
- **Custom CSS files should be minimal/empty** after Bootstrap standardization
- Use Bootstrap utilities for 90%+ of styling needs
- Only add custom CSS for brand-specific elements (logos, colors)
- Prefer `rem` and `%` units over fixed `px` for scalability

---

## Connection Patterns

### Supabase SDK (Admin/Teacher/Auth)

```php
<?php
require_once __DIR__ . '/Config/supabase.php';

// Get singleton instance
$supabase = SupabaseConnection::getInstance();

// Use for CRUD operations
$users = $supabase->from('tbl_users')->select('*')->execute();
```

### PDO (Student Module)

```php
<?php
require_once __DIR__ . '/Config/db_pdo.php';

// Get singleton PDO connection
$pdo = getPDOConnection();

// Use prepared statements for all queries
$stmt = $pdo->prepare("
    SELECT id_exam, title, duration 
    FROM tbl_exams 
    WHERE is_active = true AND assignment_id IN (...)
");
$stmt->execute([...]);
$exams = $stmt->fetchAll();
```

### Hybrid Pattern (Student Pages)

```php
<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// STEP 1: Supabase SDK for Authentication
require_once __DIR__ . '/../../Config/supabase.php';
use Core\Services\AuthService;

$supabase = SupabaseConnection::getInstance();
$authService = new AuthService($supabase);

if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

// STEP 2: PDO for Data Fetching
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

// Get student ID from validated session
$student_id = (int)$_SESSION['user']['id'];

// Fetch data with Manual RLS (filter by student_id)
$stmt = $pdo->prepare("
    SELECT * FROM tbl_exam_attempts 
    WHERE student_id = ?
");
$stmt->execute([$student_id]);
$attempts = $stmt->fetchAll();
```

### Session-Cached Auth Pattern (Optimized)

The **Session-Cached Auth** pattern eliminates Supabase HTTP overhead on subsequent requests:

```php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user_data = isset($_SESSION['user']) && isset($_SESSION['user']['role']);

if ($auth_cached && $has_user_data && $_SESSION['user']['role'] === 'student') {
    // FAST PATH: Auth already verified (~0ms overhead)
    $student_id = $_SESSION['user']['id'];
    $student_name = $_SESSION['user']['name'];
    session_write_close();  // Release lock for concurrent requests
} else {
    // SLOW PATH: First request - full Supabase validation
    require_once __DIR__ . '/../../Config/supabase.php';
    
    $authService = new AuthService($supabase);
    $session_result = $authService->validateSession();
    
    if (!$session_result['valid'] || $_SESSION['user']['role'] !== 'student') {
        session_write_close();
        header("Location: ../index.php");
        exit;
    }
    
    // Cache auth for subsequent requests
    $_SESSION['auth_verified'] = true;
    $student_id = $_SESSION['user']['id'];
    $student_name = $_SESSION['user']['name'];
    session_write_close();
}

// ============================================================================
// PHASE 2: High-Performance Data Fetching (no session lock)
// ============================================================================
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

// Your optimized queries here...
```

**Performance Impact:**
| Metric | Without Cache | With Cache | Improvement |
|--------|---------------|------------|-------------|
| Auth Overhead | ~900ms | ~0ms | ∞ |
| Avg Latency | 3,061ms | 194ms | **15.7x faster** |
| RPS | 4.92 | 64.55 | **13x higher** |

**Important:** Ensure `logout.php` clears the cache:
```php
unset($_SESSION['auth_verified']);
```

---

## PDO Helper Functions

The `Config/db_pdo.php` provides these helper functions:

```php
// Get PDO connection singleton
$pdo = getPDOConnection();

// Fetch all results
$rows = pdo_fetch_all("SELECT * FROM tbl_exams WHERE is_active = ?", [true]);

// Fetch single row
$exam = pdo_fetch_one("SELECT * FROM tbl_exams WHERE id_exam = ?", [$exam_id]);

// Execute INSERT/UPDATE/DELETE
$affected = pdo_execute("UPDATE tbl_exams SET title = ? WHERE id_exam = ?", [$title, $id]);

// Transaction support
pdo_begin_transaction();
try {
    // ... operations ...
    pdo_commit();
} catch (Exception $e) {
    pdo_rollback();
    throw $e;
}
```

---

## Manual RLS Security Rules

Since PDO bypasses Supabase's automatic RLS, enforce security manually:

### ✅ CORRECT - Always Filter by User

```php
// Validate session first
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}

$student_id = (int)$_SESSION['user']['id'];

// ALWAYS filter by student_id
$stmt = $pdo->prepare("
    SELECT * FROM tbl_scores 
    WHERE attempt_id IN (
        SELECT id_attempt FROM tbl_exam_attempts WHERE student_id = ?
    )
");
$stmt->execute([$student_id]);
```

### ❌ WRONG - No User Filter

```php
// NEVER do this - exposes all data
$scores = $pdo->query("SELECT * FROM tbl_scores")->fetchAll();
```

### Authorization Checks

```php
// Verify student is enrolled before accessing exam
$stmt = $pdo->prepare("
    SELECT id_enrollment 
    FROM tbl_class_enrollments 
    WHERE student_id = ? AND class_id = ? 
    LIMIT 1
");
$stmt->execute([$student_id, $class_id]);

if (!$stmt->fetch()) {
    $_SESSION['error'] = "You are not enrolled in this class";
    header("Location: student_dashboard.php");
    exit;
}

// For teachers, validate assignment ownership before quiz operations
$stmt = $pdo->prepare("
    SELECT id_assignment 
    FROM tbl_teaching_assignments 
    WHERE id_assignment = ? AND teacher_id = ?
");
$stmt->execute([$assignment_id, $teacher_id]);

if (!$stmt->fetch()) {
    $_SESSION['error'] = "You do not own this assignment";
    header("Location: teacher_dashboard.php");
    exit;
}
```

---

## Supabase SDK Reference

### Basic CRUD (Admin/Teacher)

```php
// SELECT
$result = $supabase
    ->from('tbl_users')
    ->select('id_user, username, full_name')
    ->eq('role', 'student')
    ->execute();
$users = $result->data ?? [];

// INSERT
$result = $supabase
    ->from('tbl_exams')
    ->insert([
        'title' => 'Math Quiz',
        'duration' => 60,
        'is_active' => true
    ])
    ->execute();

// UPDATE
$supabase
    ->from('tbl_exams')
    ->update(['title' => 'Updated Title'])
    ->eq('id_exam', $exam_id)
    ->execute();

// DELETE
$supabase
    ->from('tbl_exams')
    ->delete()
    ->eq('id_exam', $exam_id)
    ->execute();
```

### Advanced Filters

```php
->in('role', ['student', 'teacher'])    // IN clause
->gt('score', 70)                        // Greater than
->gte('score', 70)                       // Greater or equal
->lt('age', 18)                          // Less than
->ilike('name', '%john%')                // Case-insensitive LIKE
->order('created_at', ['ascending' => false])
->limit(10)
```

---

## PDO Reference

### Basic CRUD (Student Module)

```php
// SELECT with prepared statement
$stmt = $pdo->prepare("
    SELECT id_exam, title, duration, start_time, end_time 
    FROM tbl_exams 
    WHERE is_active = true AND assignment_id = ?
");
$stmt->execute([$assignment_id]);
$exams = $stmt->fetchAll();

// INSERT with RETURNING (PostgreSQL)
$stmt = $pdo->prepare("
    INSERT INTO tbl_exam_attempts (exam_id, student_id, status, started_at) 
    VALUES (?, ?, 'in_progress', ?) 
    RETURNING *
");
$stmt->execute([$exam_id, $student_id, date('Y-m-d H:i:s')]);
$attempt = $stmt->fetch();

// UPDATE
$stmt = $pdo->prepare("
    UPDATE tbl_exam_attempts 
    SET status = 'submitted', submitted_at = ? 
    WHERE id_attempt = ? AND student_id = ?
");
$stmt->execute([date('Y-m-d H:i:s'), $attempt_id, $student_id]);

// UPSERT (PostgreSQL ON CONFLICT)
$stmt = $pdo->prepare("
    INSERT INTO tbl_answers (attempt_id, question_id, student_answer) 
    VALUES (?, ?, ?) 
    ON CONFLICT (attempt_id, question_id) 
    DO UPDATE SET student_answer = EXCLUDED.student_answer
");
$stmt->execute([$attempt_id, $question_id, $answer]);
```

### Batch Operations

```php
// Efficient IN clause with dynamic placeholders
$question_ids = [1, 2, 3, 4, 5];
$placeholders = implode(',', array_fill(0, count($question_ids), '?'));

$stmt = $pdo->prepare("
    SELECT id_question, question_text, question_type, points 
    FROM tbl_questions 
    WHERE id_question IN ($placeholders)
");
$stmt->execute($question_ids);
$questions = $stmt->fetchAll();
```

---

## File Structure

```
Config/
  ├── supabase.php          ← Supabase SDK singleton (Admin/Auth)
  ├── db_pdo.php            ← PDO singleton (Student + Teacher Modules)
  ├── db.php                ← Legacy MySQL fallback
  └── auth.php              ← Legacy auth functions

MainApp/
  ├── MainStudent/          ← Uses Hybrid (Session-Cached Auth + PDO Data)
  │   ├── student_dashboard.php
  │   ├── exam.php
  │   ├── view-results.php
  │   └── my-results.php
  └── MainTeacher/          ← Uses Hybrid (Session-Cached Auth + PDO Data)
      ├── teacher_dashboard.php   ← Refactored: PDO JOINs
      ├── quiz_create.php         ← Refactored: PDO Transaction
      ├── quiz_view.php           ← Refactored: Manual RLS
      ├── quiz_edit.php           ← Refactored: Manual RLS
      ├── quiz_delete.php         ← Refactored: Manual RLS
      └── manage_subject.php      ← Refactored: PDO SSR

Admin/                      ← Uses Session-Cached Auth + PDO

Assets/
  └── JS/
      ├── admin/              ← Admin module external JS (SoC)
      │   ├── admin_sidebar.js
      │   ├── admin_common.js
      │   ├── manage_subjects.js
      │   ├── manage_users.js
      │   ├── manage_classes.js
      │   └── classes_detail.js
      └── student/
          └── exam_logic.js  ← Decoupled exam JavaScript
```

---

## Module-Specific Guidelines

### Student Pages (Hybrid)

```php
<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Auth via Supabase SDK
require_once __DIR__ . '/../../Config/supabase.php';
use Core\Services\AuthService;

$authService = new AuthService(SupabaseConnection::getInstance());
if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

// Data via PDO
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

$student_id = (int)$_SESSION['user']['id'];

// All queries must filter by student_id (Manual RLS)
$stmt = $pdo->prepare("SELECT ... WHERE student_id = ?");
$stmt->execute([$student_id]);
```

### Teacher Pages (Hybrid - Session-Cached Auth + PDO)

```php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher';

if ($auth_cached && $has_user) {
    // FAST PATH: Auth already verified (~0ms)
    $teacher_id = (int)$_SESSION['user']['id'];
    $username = $_SESSION['user']['name'] ?? 'Teacher';
    session_write_close();
} else {
    // SLOW PATH: First request - full Supabase validation
    require_once __DIR__ . '/../../Config/supabase.php';
    $authService = new AuthService($supabase);
    
    if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'teacher') {
        session_write_close();
        header("Location: ../index.php");
        exit;
    }
    
    $_SESSION['auth_verified'] = true;
    $teacher_id = (int)$_SESSION['user']['id'];
    $username = $_SESSION['user']['name'] ?? 'Teacher';
    session_write_close();
}

// ============================================================================
// PHASE 2: PDO Data Fetching with Manual RLS
// ============================================================================
require_once __DIR__ . '/../../Config/db_pdo.php';
$pdo = getPDOConnection();

// Single JOIN query replaces multiple SDK calls
$stmt = $pdo->prepare("
    SELECT e.id_exam, e.title, e.duration, e.is_active,
           s.subject_name, c.class_name,
           COUNT(q.id_question) as question_count
    FROM tbl_exams e
    INNER JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
    INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
    INNER JOIN tbl_classes c ON ta.class_id = c.id_class
    LEFT JOIN tbl_questions q ON e.id_exam = q.exam_id
    WHERE ta.teacher_id = ?  -- Manual RLS
    GROUP BY e.id_exam, s.subject_name, c.class_name
    ORDER BY e.created_at DESC
");
$stmt->execute([$teacher_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Admin Pages (Session-Cached Auth + PDO)

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Core\Services\AuthService;

// ============================================================================
// PHASE 1: Session-Cached Authentication
// ============================================================================
session_start();

$auth_cached = isset($_SESSION['auth_verified']) && $_SESSION['auth_verified'] === true;
$has_user = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

if ($auth_cached && $has_user) {
    // FAST PATH: Auth already verified (~0ms)
    $admin_id = (int)$_SESSION['user']['id'];
    session_write_close();
} else {
    // SLOW PATH: First request - full Supabase validation
    require_once __DIR__ . '/../Config/supabase.php';
    $authService = new AuthService($supabase);
    
    if (!$authService->validateSession()['valid'] || $_SESSION['user']['role'] !== 'admin') {
        session_write_close();
        header('Location: login.php');
        exit;
    }
    
    $_SESSION['auth_verified'] = true;
    $admin_id = (int)$_SESSION['user']['id'];
    session_write_close();
}

// ============================================================================
// PHASE 2: PDO Data Fetching
// ============================================================================
require_once __DIR__ . '/../Config/db_pdo.php';
$pdo = getPDOConnection();

// All data operations via PDO
$stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE role != 'admin'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

---

## JavaScript Decoupling

### Best Practice: External JS Files

Pass PHP data via `data-*` attributes:

```php
<!-- PHP: Pass config to JS -->
<div id="exam-app" 
     data-exam-id="<?= $exam_id ?>" 
     data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
     data-time-remaining="<?= $time_remaining ?>">
```

```javascript
// JS: Read config from data attributes
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('exam-app');
    const config = {
        examId: app.dataset.examId,
        csrfToken: app.dataset.csrfToken,
        timeRemaining: parseInt(app.dataset.timeRemaining, 10)
    };
    // Initialize exam logic...
});
```

---

## What to AVOID

### ❌ SDK in Student High-Frequency Operations

```php
// DON'T use SDK for exam questions - too slow (2.6s latency)
$result = $supabase
    ->from('tbl_questions')
    ->select('*')
    ->eq('exam_id', $exam_id)
    ->execute();  // ❌ BAD - Use PDO instead
```

### ❌ PDO Without Manual RLS

```php
// NEVER fetch without user filter
$stmt = $pdo->query("SELECT * FROM tbl_answers");  // ❌ BAD - No student filter
```

### ❌ Mixed Connections in Same Query Flow

```php
// DON'T mix SDK and PDO for related data in complex ways
// Keep it simple: Auth via SDK, Data via PDO
```

### ❌ Raw SQL Injection

```php
// NEVER concatenate user input
$pdo->query("SELECT * FROM tbl_exams WHERE id = " . $_GET['id']);  // ❌ BAD

// ALWAYS use prepared statements
$stmt = $pdo->prepare("SELECT * FROM tbl_exams WHERE id = ?");
$stmt->execute([$_GET['id']]);  // ✅ GOOD
```

---

## Performance Comparison

| Operation | SDK (HTTP) | PDO (TCP) | Improvement |
|-----------|------------|-----------|-------------|
| Fetch 1 record | ~260ms | ~15ms | 17x faster |
| Fetch 10 records | ~2,600ms | ~50ms | 52x faster |
| Insert 1 record | ~300ms | ~20ms | 15x faster |
| Page with 5 queries | ~1,500ms | ~100ms | 15x faster |

---

## Quick Reference Card

### Supabase SDK (Admin/Teacher)

| Operation | Method |
|-----------|--------|
| Select | `->from('table')->select('*')->execute()` |
| Insert | `->from('table')->insert([...])->execute()` |
| Update | `->from('table')->update([...])->eq('id', 1)->execute()` |
| Delete | `->from('table')->delete()->eq('id', 1)->execute()` |

### PDO (Student Module)

| Operation | Method |
|-----------|--------|
| Select | `$pdo->prepare("SELECT ...")->execute([$params])` |
| Insert | `$pdo->prepare("INSERT ... RETURNING *")->execute([$params])` |
| Update | `$pdo->prepare("UPDATE ... WHERE ...")->execute([$params])` |
| Upsert | `$pdo->prepare("INSERT ... ON CONFLICT DO UPDATE")->execute([$params])` |

---

## Summary

**Hybrid Architecture Rules:**
1. **Auth**: Supabase SDK with Session-Caching (secure, fast on subsequent requests)
2. **Admin**: Supabase SDK (simpler, lower volume)
3. **Teacher Data**: PDO with Manual RLS (optimized for quiz management)
4. **Student Data**: PDO with Manual RLS (fast, critical latency)
5. **Prepared Statements**: Never concatenate user input

**Golden Rules:**
- Student module = Session-Cached Auth + PDO Data
- Teacher module = Session-Cached Auth + PDO Data + Transactions
- Admin module = Session-Cached Auth + PDO Data + SoC JavaScript
- Always validate session before database operations
- Always filter by user ID (Manual RLS) for teacher and student queries
- All JavaScript must be in external files (Assets/JS/<module>/) - no inline scripts

---

## Optimization Status Matrix

All modules have been optimized with Session-Cached Auth + PDO pattern:

| Module | Status | Pattern | Notes |
|--------|--------|---------|-------|
| Student Dashboard | ✅ Done | Session-Cached Auth + PDO | High frequency, critical |
| Student Exam | ✅ Done | Session-Cached Auth + PDO | Real-time, latency critical |
| Teacher Dashboard | ✅ Done | Session-Cached Auth + PDO | Quiz listing optimized |
| Teacher Quiz CRUD | ✅ Done | PDO + Transactions | Manual RLS for ownership |
| Admin Dashboard | ✅ Done | Session-Cached Auth + PDO | Stats via JOINs |
| Admin CRUD | ✅ Done | Session-Cached Auth + PDO | SoC JavaScript pattern |

### Testing Teacher Module

The Teacher module has a complete test suite:
```bash
# Run all Teacher tests
python Tests/run_all_tests.py --role teacher --run

# Individual tests
python Tests/Teacher/test_auth.py
python Tests/Teacher/Dashboard/test_dashboard_sanity.py
python Tests/Teacher/Dashboard/test_load_dashboard.py --users 20
python Tests/Teacher/Quiz/test_create_quiz_flow.py
php Tests/Teacher/Quiz/test_rls_security.php
```

### Testing Admin Module

The Admin module has a complete test suite:
```bash
# Run all Admin tests
python Tests/run_all_tests.py --role admin --run

# Individual tests
python Tests/Admin/test_auth.py
python Tests/Admin/Dashboard/test_dashboard_sanity.py
python Tests/Admin/Audit/audit_admin_latency.py
python Tests/Admin/Audit/audit_admin_load.py --users 20
```

---

*For examples, see `MainApp/MainStudent/student_dashboard.php`, `MainApp/MainTeacher/teacher_dashboard.php`, and `Admin/dashboard.php`.*
