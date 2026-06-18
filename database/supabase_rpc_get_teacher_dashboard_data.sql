-- Supabase RPC Function: get_teacher_dashboard_data
-- Retrieves comprehensive dashboard data for a specific teacher
-- Parameters: teacher_id_param INTEGER - The ID of the teacher
-- Returns: Table with exam details, scores, student counts, and progress metrics

CREATE OR REPLACE FUNCTION get_teacher_dashboard_data(teacher_id_param INTEGER)
RETURNS TABLE (
    id_exam INTEGER,
    title VARCHAR,
    instructions TEXT,
    duration INTEGER,
    passing_score REAL,
    start_time TIMESTAMP,
    end_time TIMESTAMP,
    is_active BOOLEAN,
    show_results BOOLEAN,
    allow_review BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    avg_score REAL,
    total_students BIGINT,
    progress REAL,
    num_questions BIGINT,
    subject_name VARCHAR,
    class_name VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.id_exam,
        e.title,
        e.instructions,
        e.duration,
        e.passing_score,
        e.start_time,
        e.end_time,
        e.is_active,
        e.show_results,
        e.allow_review,
        e.created_at,
        e.updated_at,
        COALESCE(AVG(s.percentage), 0) AS avg_score,
        COUNT(DISTINCT ce.student_id) AS total_students,
        ROUND(COALESCE((SUM(CASE WHEN s.percentage >= e.passing_score THEN 1 ELSE 0 END)::REAL / NULLIF(COUNT(DISTINCT ce.student_id), 0)) * 100, 0), 0) AS progress,
        COUNT(DISTINCT q.id_question) AS num_questions,
        sub.subject_name,
        c.class_name
    FROM tbl_exams e
    LEFT JOIN tbl_teaching_assignments ta ON e.assignment_id = ta.id_assignment
    LEFT JOIN tbl_class_enrollments ce ON ta.class_id = ce.class_id
    LEFT JOIN tbl_exam_attempts ea ON e.id_exam = ea.exam_id
    LEFT JOIN tbl_scores s ON ea.id_attempt = s.attempt_id
    LEFT JOIN tbl_questions q ON e.id_exam = q.exam_id
    LEFT JOIN tbl_subjects sub ON ta.subject_id = sub.id_subject
    LEFT JOIN tbl_classes c ON ta.class_id = c.id_class
    WHERE ta.teacher_id = teacher_id_param
    GROUP BY e.id_exam, e.title, e.instructions, e.duration, e.passing_score,
             e.start_time, e.end_time, e.is_active, e.show_results, e.allow_review,
             e.created_at, e.updated_at, sub.subject_name, c.class_name
    ORDER BY e.created_at DESC;
END;
$$ LANGUAGE plpgsql;

-- Example usage:
-- SELECT * FROM get_teacher_dashboard_data(1);