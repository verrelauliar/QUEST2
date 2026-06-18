<?php
// Centralized table name mapping for the database schema.
// Use constants to keep queries DRY and make future schema changes easier.
define('TBL_USERS', 'tbl_users');
define('TBL_SUBJECTS', 'tbl_subjects');
define('TBL_EXAMS', 'tbl_exams');
define('TBL_QUESTIONS', 'tbl_questions');
define('TBL_SCORES', 'tbl_scores');
define('TBL_ANSWERS', 'tbl_answers');
define('TBL_EXAM_ATTEMPTS', 'tbl_exam_attempts');

// Additional tables for class and assignment management
define('TBL_TEACHING_ASSIGNMENTS', 'tbl_teaching_assignments');
define('TBL_CLASSES', 'tbl_classes');
define('TBL_CLASS_ENROLLMENTS', 'tbl_class_enrollments');

// Deprecated/legacy aliases for backward compatibility in code (use the TBL_* constants going forward)
define('USERS', TBL_USERS);
define('SUBJECTS', TBL_SUBJECTS);
define('EXAMS', TBL_EXAMS);

?>
