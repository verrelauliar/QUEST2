-- Supabase RPC Function: get_classes_with_details
-- Retrieves all active classes with teachers, subjects, and student enrollment counts
-- Parameters: None
-- Returns: Table with class details, teacher assignments, and enrollment statistics

CREATE OR REPLACE FUNCTION get_classes_with_details()
RETURNS TABLE (
    id_class INTEGER,
    class_name VARCHAR,
    grade_level INTEGER,
    academic_year VARCHAR,
    status VARCHAR,
    teacher_name VARCHAR,
    subject_name VARCHAR,
    id_assignment INTEGER,
    student_count BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        c.id_class,
        c.class_name,
        c.grade_level,
        c.academic_year,
        c.status::VARCHAR,
        u.full_name as teacher_name,
        s.subject_name,
        ta.id_assignment,
        COUNT(DISTINCT ce.student_id) as student_count
    FROM tbl_classes c
    LEFT JOIN tbl_teaching_assignments ta ON c.id_class = ta.class_id
    LEFT JOIN tbl_users u ON ta.teacher_id = u.id_user
    LEFT JOIN tbl_subjects s ON ta.subject_id = s.id_subject
    LEFT JOIN tbl_class_enrollments ce ON c.id_class = ce.class_id
    WHERE c.status = 'active'
    GROUP BY c.id_class, c.class_name, c.grade_level, c.academic_year, c.status,
             u.full_name, s.subject_name, ta.id_assignment
    ORDER BY c.grade_level, c.class_name;
END;
$$ LANGUAGE plpgsql;

-- Example usage:
-- SELECT * FROM get_classes_with_details();