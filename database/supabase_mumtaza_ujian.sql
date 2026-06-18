-- Supabase PostgreSQL Migration for mumtaza_ujian Database
-- Migrated from MySQL/MariaDB to PostgreSQL
-- Generated: 2025-11-21
-- Note: All RLS (Row Level Security) policies are DISABLED as requested

-- Drop existing tables and views if they exist (for clean migration)
DROP VIEW IF EXISTS vw_exam_details CASCADE;
DROP VIEW IF EXISTS vw_student_classes CASCADE;
DROP VIEW IF EXISTS vw_teaching_schedule CASCADE;

DROP TABLE IF EXISTS tbl_answers CASCADE;
DROP TABLE IF EXISTS tbl_scores CASCADE;
DROP TABLE IF EXISTS tbl_exam_attempts CASCADE;
DROP TABLE IF EXISTS tbl_questions CASCADE;
DROP TABLE IF EXISTS tbl_exams CASCADE;
DROP TABLE IF EXISTS tbl_class_enrollments CASCADE;
DROP TABLE IF EXISTS tbl_teaching_assignments CASCADE;
DROP TABLE IF EXISTS tbl_classes CASCADE;
DROP TABLE IF EXISTS tbl_subjects CASCADE;
DROP TABLE IF EXISTS tbl_users CASCADE;

-- Create custom ENUM types for PostgreSQL
CREATE TYPE user_role AS ENUM ('admin', 'teacher', 'student');
CREATE TYPE user_status AS ENUM ('active', 'inactive');
CREATE TYPE exam_attempt_status AS ENUM ('in_progress', 'submitted', 'expired', 'graded');
CREATE TYPE question_type AS ENUM ('multiple', 'essay', 'truefalse');

-- Table: tbl_users
-- Stores all system users (admin, teachers, students)
CREATE TABLE tbl_users (
  id_user SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  role user_role NOT NULL,
  status user_status NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create index for role and status lookups
CREATE INDEX idx_users_role_status ON tbl_users(role, status);

-- Disable RLS for tbl_users
ALTER TABLE tbl_users DISABLE ROW LEVEL SECURITY;

-- Table: tbl_classes
-- Stores class information (7A, 8B, 9C, etc.)
CREATE TABLE tbl_classes (
  id_class SERIAL PRIMARY KEY,
  class_name VARCHAR(50) NOT NULL,
  grade_level INTEGER NOT NULL CHECK (grade_level BETWEEN 7 AND 9),
  academic_year VARCHAR(20) NOT NULL,
  status user_status NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT unique_class_year UNIQUE (class_name, academic_year)
);

-- Create index for grade level and academic year lookups
CREATE INDEX idx_classes_grade_year ON tbl_classes(grade_level, academic_year);

-- Disable RLS for tbl_classes
ALTER TABLE tbl_classes DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_subjects
-- Description: Stores subject information
-- ============================================================================
CREATE TABLE tbl_subjects (
  id_subject SERIAL PRIMARY KEY,
  subject_name VARCHAR(100) NOT NULL,
  subject_code VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Disable RLS for tbl_subjects
ALTER TABLE tbl_subjects DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_class_enrollments
-- Description: Links students to their classes
-- ============================================================================
CREATE TABLE tbl_class_enrollments (
  id_enrollment SERIAL PRIMARY KEY,
  student_id INTEGER NOT NULL,
  class_id INTEGER NOT NULL,
  enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT unique_student_class UNIQUE (student_id, class_id),
  CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) 
    REFERENCES tbl_users(id_user) ON DELETE CASCADE,
  CONSTRAINT fk_enrollment_class FOREIGN KEY (class_id) 
    REFERENCES tbl_classes(id_class) ON DELETE CASCADE
);

-- Create index for class lookups
CREATE INDEX idx_class_enrollments_class ON tbl_class_enrollments(class_id);

-- Disable RLS for tbl_class_enrollments
ALTER TABLE tbl_class_enrollments DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_teaching_assignments
-- Description: Links teachers to subjects and classes they teach
-- ============================================================================
CREATE TABLE tbl_teaching_assignments (
  id_assignment SERIAL PRIMARY KEY,
  teacher_id INTEGER NOT NULL,
  subject_id INTEGER NOT NULL,
  class_id INTEGER NOT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT unique_teacher_subject_class UNIQUE (teacher_id, subject_id, class_id),
  CONSTRAINT fk_teaching_teacher FOREIGN KEY (teacher_id) 
    REFERENCES tbl_users(id_user) ON DELETE CASCADE,
  CONSTRAINT fk_teaching_subject FOREIGN KEY (subject_id) 
    REFERENCES tbl_subjects(id_subject) ON DELETE CASCADE,
  CONSTRAINT fk_teaching_class FOREIGN KEY (class_id) 
    REFERENCES tbl_classes(id_class) ON DELETE CASCADE
);

-- Create indexes for lookups
CREATE INDEX idx_teaching_assignments_teacher ON tbl_teaching_assignments(teacher_id);
CREATE INDEX idx_teaching_assignments_subject ON tbl_teaching_assignments(subject_id);
CREATE INDEX idx_teaching_assignments_class ON tbl_teaching_assignments(class_id);

-- Disable RLS for tbl_teaching_assignments
ALTER TABLE tbl_teaching_assignments DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_exams
-- Description: Stores exam/quiz information
-- ============================================================================
CREATE TABLE tbl_exams (
  id_exam SERIAL PRIMARY KEY,
  assignment_id INTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  instructions TEXT,
  duration INTEGER NOT NULL,
  passing_score REAL NOT NULL DEFAULT 60,
  start_time TIMESTAMP NOT NULL,
  end_time TIMESTAMP NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  show_results BOOLEAN NOT NULL DEFAULT TRUE,
  allow_review BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_exam_assignment FOREIGN KEY (assignment_id) 
    REFERENCES tbl_teaching_assignments(id_assignment) ON DELETE CASCADE
);

-- Create indexes for lookups
CREATE INDEX idx_exams_assignment ON tbl_exams(assignment_id);
CREATE INDEX idx_exams_active_time ON tbl_exams(is_active, start_time, end_time);

-- Disable RLS for tbl_exams
ALTER TABLE tbl_exams DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_questions
-- Description: Stores exam questions
-- ============================================================================
CREATE TABLE tbl_questions (
  id_question SERIAL PRIMARY KEY,
  exam_id INTEGER NOT NULL,
  question_text TEXT NOT NULL,
  question_type question_type NOT NULL,
  points REAL NOT NULL DEFAULT 1,
  option_a VARCHAR(255),
  option_b VARCHAR(255),
  option_c VARCHAR(255),
  option_d VARCHAR(255),
  correct_answer CHAR(1),
  display_order INTEGER NOT NULL DEFAULT 0,
  CONSTRAINT fk_question_exam FOREIGN KEY (exam_id) 
    REFERENCES tbl_exams(id_exam) ON DELETE CASCADE
);

-- Create index for exam and display order
CREATE INDEX idx_questions_exam_order ON tbl_questions(exam_id, display_order);

-- Disable RLS for tbl_questions
ALTER TABLE tbl_questions DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_exam_attempts
-- Description: Tracks student exam attempts
-- ============================================================================
CREATE TABLE tbl_exam_attempts (
  id_attempt SERIAL PRIMARY KEY,
  exam_id INTEGER NOT NULL,
  student_id INTEGER NOT NULL,
  started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at TIMESTAMP,
  time_remaining INTEGER,
  status exam_attempt_status NOT NULL DEFAULT 'in_progress',
  CONSTRAINT fk_attempt_exam FOREIGN KEY (exam_id) 
    REFERENCES tbl_exams(id_exam) ON DELETE CASCADE,
  CONSTRAINT fk_attempt_student FOREIGN KEY (student_id) 
    REFERENCES tbl_users(id_user) ON DELETE CASCADE
);

-- Create indexes for lookups
CREATE INDEX idx_exam_attempts_exam_student ON tbl_exam_attempts(exam_id, student_id);
CREATE INDEX idx_exam_attempts_student_status ON tbl_exam_attempts(student_id, status);

-- Disable RLS for tbl_exam_attempts
ALTER TABLE tbl_exam_attempts DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_answers
-- Description: Stores student answers to questions
-- ============================================================================
CREATE TABLE tbl_answers (
  id_answer SERIAL PRIMARY KEY,
  attempt_id INTEGER NOT NULL,
  question_id INTEGER NOT NULL,
  student_answer TEXT,
  is_correct BOOLEAN,
  points_earned REAL NOT NULL DEFAULT 0,
  teacher_feedback TEXT,
  graded_at TIMESTAMP,
  graded_by INTEGER,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT unique_attempt_question UNIQUE (attempt_id, question_id),
  CONSTRAINT fk_answer_attempt FOREIGN KEY (attempt_id) 
    REFERENCES tbl_exam_attempts(id_attempt) ON DELETE CASCADE,
  CONSTRAINT fk_answer_question FOREIGN KEY (question_id) 
    REFERENCES tbl_questions(id_question) ON DELETE CASCADE,
  CONSTRAINT fk_answer_grader FOREIGN KEY (graded_by) 
    REFERENCES tbl_users(id_user) ON DELETE SET NULL
);

-- Create indexes for lookups
CREATE INDEX idx_answers_question ON tbl_answers(question_id);
CREATE INDEX idx_answers_graded_by ON tbl_answers(graded_by);

-- Disable RLS for tbl_answers
ALTER TABLE tbl_answers DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Table: tbl_scores
-- Description: Stores calculated scores for exam attempts
-- ============================================================================
CREATE TABLE tbl_scores (
  id_score SERIAL PRIMARY KEY,
  attempt_id INTEGER NOT NULL UNIQUE,
  total_points REAL NOT NULL DEFAULT 0,
  points_earned REAL NOT NULL DEFAULT 0,
  percentage REAL NOT NULL DEFAULT 0,
  passed BOOLEAN NOT NULL DEFAULT FALSE,
  graded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_score_attempt FOREIGN KEY (attempt_id) 
    REFERENCES tbl_exam_attempts(id_attempt) ON DELETE CASCADE
);

-- Disable RLS for tbl_scores
ALTER TABLE tbl_scores DISABLE ROW LEVEL SECURITY;

-- ============================================================================
-- VIEWS: Replicate MySQL views with PostgreSQL syntax
-- ============================================================================

-- View: vw_exam_details
-- Description: Comprehensive exam information with teacher, subject, class details
CREATE VIEW vw_exam_details AS
SELECT 
  e.id_exam,
  e.title,
  e.duration,
  e.start_time,
  e.end_time,
  e.is_active,
  t.full_name AS teacher_name,
  s.subject_name,
  c.class_name,
  c.grade_level,
  COUNT(q.id_question) AS total_questions,
  SUM(q.points) AS total_points
FROM tbl_exams e
INNER JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
INNER JOIN tbl_users t ON ta.teacher_id = t.id_user
INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
INNER JOIN tbl_classes c ON ta.class_id = c.id_class
LEFT JOIN tbl_questions q ON e.id_exam = q.exam_id
GROUP BY e.id_exam, e.title, e.duration, e.start_time, e.end_time, e.is_active,
         t.full_name, s.subject_name, c.class_name, c.grade_level;

-- View: vw_student_classes
-- Description: Shows which students are enrolled in which classes
CREATE VIEW vw_student_classes AS
SELECT 
  u.id_user,
  u.username,
  u.full_name,
  c.id_class,
  c.class_name,
  c.grade_level,
  c.academic_year
FROM tbl_users u
INNER JOIN tbl_class_enrollments e ON u.id_user = e.student_id
INNER JOIN tbl_classes c ON e.class_id = c.id_class
WHERE u.role = 'student' AND u.status = 'active';

-- View: vw_teaching_schedule
-- Description: Shows teaching assignments (which teacher teaches what subject to which class)
CREATE VIEW vw_teaching_schedule AS
SELECT 
  t.id_user AS teacher_id,
  t.full_name AS teacher_name,
  s.id_subject,
  s.subject_name,
  c.id_class,
  c.class_name,
  c.grade_level,
  ta.id_assignment
FROM tbl_teaching_assignments ta
INNER JOIN tbl_users t ON ta.teacher_id = t.id_user
INNER JOIN tbl_subjects s ON ta.subject_id = s.id_subject
INNER JOIN tbl_classes c ON ta.class_id = c.id_class
WHERE t.status = 'active';

-- ============================================================================
-- TRIGGERS: Auto-update updated_at timestamp
-- ============================================================================

-- Function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply trigger to tables with updated_at column
CREATE TRIGGER update_tbl_users_updated_at
  BEFORE UPDATE ON tbl_users
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tbl_classes_updated_at
  BEFORE UPDATE ON tbl_classes
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tbl_subjects_updated_at
  BEFORE UPDATE ON tbl_subjects
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tbl_exams_updated_at
  BEFORE UPDATE ON tbl_exams
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- NOTE: This script creates EMPTY tables only.
-- Use supabase_seed_data.sql to populate tables with comprehensive test data.

-- MIGRATION COMPLETE - SCHEMA ONLY
-- All tables, indexes, foreign keys, and views have been migrated from MySQL/MariaDB to PostgreSQL (Supabase compatible)
-- Key Changes: int(11)→INTEGER, tinyint(1)→BOOLEAN, float→REAL, enum→Custom ENUM types, AUTO_INCREMENT→SERIAL, current_timestamp()→CURRENT_TIMESTAMP
-- Replaced MySQL backticks with PostgreSQL standard, UPDATE ON CURRENT_TIMESTAMP with triggers, DEFAULT values to TRUE/FALSE
-- All foreign keys, unique constraints, and indexes preserved. RLS policies DISABLED as requested.
-- Run supabase_seed_data.sql next to populate with test data.
