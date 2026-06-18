-- Supabase Seed Data for mumtaza_ujian Database
-- Comprehensive test data for production readiness testing
-- Generated: 2025-11-23

-- Clear existing data (in correct order to respect foreign keys)
DELETE FROM tbl_scores;
DELETE FROM tbl_answers;
DELETE FROM tbl_exam_attempts;
DELETE FROM tbl_questions;
DELETE FROM tbl_exams;
DELETE FROM tbl_teaching_assignments;
DELETE FROM tbl_class_enrollments;
DELETE FROM tbl_classes;
DELETE FROM tbl_subjects;
DELETE FROM tbl_users;

-- Reset sequences to start from 1
ALTER SEQUENCE tbl_users_id_user_seq RESTART WITH 1;
ALTER SEQUENCE tbl_subjects_id_subject_seq RESTART WITH 1;
ALTER SEQUENCE tbl_classes_id_class_seq RESTART WITH 1;
ALTER SEQUENCE tbl_class_enrollments_id_enrollment_seq RESTART WITH 1;
ALTER SEQUENCE tbl_teaching_assignments_id_assignment_seq RESTART WITH 1;
ALTER SEQUENCE tbl_exams_id_exam_seq RESTART WITH 1;
ALTER SEQUENCE tbl_questions_id_question_seq RESTART WITH 1;
ALTER SEQUENCE tbl_exam_attempts_id_attempt_seq RESTART WITH 1;
ALTER SEQUENCE tbl_answers_id_answer_seq RESTART WITH 1;
ALTER SEQUENCE tbl_scores_id_score_seq RESTART WITH 1;

-- Users: 1 Admin, 6 Teachers, 30 Students
-- DEFAULT PASSWORD FOR ALL USERS: password123
-- Each user has a unique password hash for better security

-- Admin (username: admin, password: password123)
INSERT INTO tbl_users (username, password, full_name, email, role, status) VALUES
('admin', '$2y$10$ZSfR2wh6Janqu/.xV/cRlOlm5s4Jebc2uVzCrbN1I.Bv.dUiZGV9C', 'System Administrator', 'admin@mumtaza.sch.id', 'admin', 'active');

-- Teachers (password for all: password123) - Each with unique hash
INSERT INTO tbl_users (username, password, full_name, email, role, status) VALUES
('teacher_math', '$2y$10$eBoOCD5vDAQKO6e2RQP.Vuwgbd.JzFpWpXE1KhWtSd8rRDIH3nxr6', 'Ahmad Fauzi', 'ahmad.fauzi@mumtaza.sch.id', 'teacher', 'active'),
('teacher_science', '$2y$10$F6R3pfoPA7wJBsWnaSN6juaYcvw9QB7GRqzLz0BTnpv3aGQzvFrd2', 'Siti Nurhaliza', 'siti.nurhaliza@mumtaza.sch.id', 'teacher', 'active'),
('teacher_english', '$2y$10$/DQSKvK.z86jnbUlcMtc/.pw.BSsENKl.2MWps0hgLHvo6u2CugbO', 'Budi Santoso', 'budi.santoso@mumtaza.sch.id', 'teacher', 'active'),
('teacher_indo', '$2y$10$XArrQ2L50ZWciM/Y2aGlZegmVAFsHykNum15/v5cx1zkgBHadMyXe', 'Dewi Lestari', 'dewi.lestari@mumtaza.sch.id', 'teacher', 'active'),
('teacher_ips', '$2y$10$hXwZM96fESCheM.B15N7k.EIcs14SpzrEQmP/EWj1itwcfD4SyLuW', 'Eko Prasetyo', 'eko.prasetyo@mumtaza.sch.id', 'teacher', 'active'),
('teacher_art', '$2y$10$lpDGECtwxSWBZ3E27OLwM.TMPjI3CzksjizJaWOchQKmxJCGPDH3S', 'Fitri Handayani', 'fitri.handayani@mumtaza.sch.id', 'teacher', 'inactive');

-- Students Grade 7 (password for all: password123) - Each with unique hash
INSERT INTO tbl_users (username, password, full_name, email, role, status) VALUES
('student_7a_01', '$2y$10$r/8F6h3qXHH6qSbUh15nAOcuJlQrOptBV6V6LkNDLhh0iiJ3rsKnW', 'Adi Nugroho', 'adi.nugroho@student.mumtaza.sch.id', 'student', 'active'),
('student_7a_02', '$2y$10$qoAjFtvN5qMf3cGwJz53eOcLSlbwziA.oNo.fsYHaTSL0l/.J/wQa', 'Bella Ayu', 'bella.ayu@student.mumtaza.sch.id', 'student', 'active'),
('student_7a_03', '$2y$10$cNTmlupm7lnZdr3ImWYnAuF4i1AsieowhBMM98vnSq.iFHsp48Vxe', 'Citra Maharani', 'citra.maharani@student.mumtaza.sch.id', 'student', 'active'),
('student_7a_04', '$2y$10$XB.I7lfuQgTwmtJ2kdF7ZOMV1Npvq1QudYJi56IipN/08IUCwJB5O', 'Deni Pratama', 'deni.pratama@student.mumtaza.sch.id', 'student', 'active'),
('student_7a_05', '$2y$10$2f1QDzBNxrkbuLp0aBBBkuH8oxmv.XCBDC17kTPTCKeNlvAUszeCe', 'Eka Putri', 'eka.putri@student.mumtaza.sch.id', 'student', 'active'),
('student_7b_01', '$2y$10$9RQla5Vaf1WTvkx29KEAAOljYyqF.3K1YzfNEWcIWpuhfWYEY1Q1C', 'Fajar Hidayat', 'fajar.hidayat@student.mumtaza.sch.id', 'student', 'active'),
('student_7b_02', '$2y$10$WRvH.O240nMxrZ14329dCudt5RkmFjiORpE5Oy90cWk/AQJvuW3E2', 'Gita Sari', 'gita.sari@student.mumtaza.sch.id', 'student', 'active'),
('student_7b_03', '$2y$10$wBFV6dIJ8LAlkKQtj1JOqOz4U4udah94ma49znUpsbpZSGosXuGQK', 'Hadi Wijaya', 'hadi.wijaya@student.mumtaza.sch.id', 'student', 'active'),
('student_7b_04', '$2y$10$cM7gnABJxBg9Ti5l6R/8lOBin/xzJZEnubMSLsIU4tA8ANqWKB3HO', 'Indah Permata', 'indah.permata@student.mumtaza.sch.id', 'student', 'active'),
('student_7b_05', '$2y$10$jcNjb5vQ1kupL5EkPU3RD.mgrV9e.Rv8GKeQTwyUDLGKhZuYrpavW', 'Joko Susilo', 'joko.susilo@student.mumtaza.sch.id', 'student', 'active');

-- Students Grade 8 (password for all: password123) - Each with unique hash
INSERT INTO tbl_users (username, password, full_name, email, role, status) VALUES
('student_8a_01', '$2y$10$me2h1Gz4dJrW7gdPnINDne6qJfwh7PcxWDA3Fo/CzFmMTkINj7t1i', 'Kartika Dewi', 'kartika.dewi@student.mumtaza.sch.id', 'student', 'active'),
('student_8a_02', '$2y$10$YSfOsRqpEGdVtUrab/2EiehSwLAdHJ4PKd/9qibLrkU7sumxdRR2m', 'Lukman Hakim', 'lukman.hakim@student.mumtaza.sch.id', 'student', 'active'),
('student_8a_03', '$2y$10$QyPF9eUeo2Go.bKa5H9DOuLh1es0.5omW7VlTk1sUhEbgIkq4TT8O', 'Maya Sari', 'maya.sari@student.mumtaza.sch.id', 'student', 'active'),
('student_8a_04', '$2y$10$a2AdlcedJb2nCo4TE2Fxc.2z5K1U6Qt3fMsBNbose6awjzY0BN07m', 'Nanda Pratama', 'nanda.pratama@student.mumtaza.sch.id', 'student', 'active'),
('student_8a_05', '$2y$10$LnnazxyTYt86putil/eBZemOa/u1UrXc1vrApRznM1XtisO0MfwO.', 'Olivia Tan', 'olivia.tan@student.mumtaza.sch.id', 'student', 'active'),
('student_8b_01', '$2y$10$YFz1drA7D9.jkE6VLdcxoOC5JTExCSFLN9R2qX8ud4wCunEPQQHVK', 'Putri Ayu', 'putri.ayu@student.mumtaza.sch.id', 'student', 'active'),
('student_8b_02', '$2y$10$5CFDT6ASt1y3oNwflvmyzeplLlIsZpBUH4PNure7IeAkb0oYGltY2', 'Qori Zulfikar', 'qori.zulfikar@student.mumtaza.sch.id', 'student', 'active'),
('student_8b_03', '$2y$10$aqBx4tnxZYQWb6.wlItiJuuNd466K/VRkbQFzVLL2oPAfoeTQHGkG', 'Rina Kusuma', 'rina.kusuma@student.mumtaza.sch.id', 'student', 'active'),
('student_8b_04', '$2y$10$z5aqaLEdBCmMFLgZPNe4GejzgIBaXiv2xBIChKgCJesnNn5Di0q8C', 'Surya Atmaja', 'surya.atmaja@student.mumtaza.sch.id', 'student', 'active'),
('student_8b_05', '$2y$10$yCWRVlqiZEDiTrOwtKQ1MuOr0fla17N75tmhAIG8eImuqM4K.4W4u', 'Tari Wulandari', 'tari.wulandari@student.mumtaza.sch.id', 'student', 'active');

-- Students Grade 9 (password for all: password123) - Each with unique hash
INSERT INTO tbl_users (username, password, full_name, email, role, status) VALUES
('student_9a_01', '$2y$10$sanzgkkKFQf4i1FPjbZWOurH2zfb4MRF/7ptb7QbvGkp5lMevp4xu', 'Umar Faruq', 'umar.faruq@student.mumtaza.sch.id', 'student', 'active'),
('student_9a_02', '$2y$10$qQ8Z7Sj8paLY8biBbB.G9OWbBRfBE8uJ1Ftq5kzDqJPDu9G3TDZaG', 'Vina Melati', 'vina.melati@student.mumtaza.sch.id', 'student', 'active'),
('student_9a_03', '$2y$10$SPGhoLyrs7NgmDHIcLPVJ.Z.elWVEvr8z3MinADA2V0WIwtcJfkP.', 'Wawan Setiawan', 'wawan.setiawan@student.mumtaza.sch.id', 'student', 'active'),
('student_9a_04', '$2y$10$Vz/IGO3GEOTeLTTSL7AUR.QotxvaoSAnIaAGoCHSaJHqv.TC2CPkW', 'Xena Kartika', 'xena.kartika@student.mumtaza.sch.id', 'student', 'active'),
('student_9a_05', '$2y$10$VRNz9VjGKWrekilR3i22Pe5kehVuVPHHEWrUkv.YQu1L4iRBldd66', 'Yoga Aditya', 'yoga.aditya@student.mumtaza.sch.id', 'student', 'active'),
('student_9b_01', '$2y$10$8/DRHDTplek3h156j95lqu8n7MW67gnfKr.d9LAuOJQxksL2zwDRu', 'Zahra Amelia', 'zahra.amelia@student.mumtaza.sch.id', 'student', 'active'),
('student_9b_02', '$2y$10$TkklEzH2IA5z/86h37qHc.UNYKaknCjSbIp/sjO61lpC5P1HGpqH6', 'Andi Rahman', 'andi.rahman@student.mumtaza.sch.id', 'student', 'active'),
('student_9b_03', '$2y$10$dah73zMOmJavurvZ2ZIjU.mW8/HoOymTnS5nEzviXqGbIVFwpjMhm', 'Bunga Citra', 'bunga.citra@student.mumtaza.sch.id', 'student', 'active'),
('student_9b_04', '$2y$10$2b54iPIrVM2HhaLNhoJW0uFd8sGvIGpc62HcfFrnZaj0v7M/Ju9ry', 'Chandra Kusuma', 'chandra.kusuma@student.mumtaza.sch.id', 'student', 'active'),
('student_9b_05', '$2y$10$p2.XwB/kOB8BfXUMWeon3un8R3/5VvnIdl6wBLKU7540PSsHB/fCa', 'Diana Puspita', 'diana.puspita@student.mumtaza.sch.id', 'student', 'inactive');

-- Subjects
INSERT INTO tbl_subjects (subject_name, subject_code, description) VALUES
('Mathematics', 'MTK-001', 'Core mathematics curriculum for grades 7-9'),
('Natural Sciences', 'IPA-001', 'Integrated science including physics, chemistry, and biology'),
('English Language', 'ENG-001', 'English language and literature'),
('Indonesian Language', 'IND-001', 'Indonesian language and literature'),
('Social Studies', 'IPS-001', 'Geography, history, economics, and sociology'),
('Arts and Culture', 'SBD-001', 'Visual arts, music, and cultural studies');

-- Classes
INSERT INTO tbl_classes (class_name, grade_level, academic_year, status) VALUES
('7A', 7, '2024/2025', 'active'),
('7B', 7, '2024/2025', 'active'),
('8A', 8, '2024/2025', 'active'),
('8B', 8, '2024/2025', 'active'),
('9A', 9, '2024/2025', 'active'),
('9B', 9, '2024/2025', 'active');

-- Class Enrollments
-- Grade 7A enrollments (students 8-12)
INSERT INTO tbl_class_enrollments (student_id, class_id) VALUES
(8, 1), (9, 1), (10, 1), (11, 1), (12, 1);

-- Grade 7B enrollments (students 13-17)
INSERT INTO tbl_class_enrollments (student_id, class_id) VALUES
(13, 2), (14, 2), (15, 2), (16, 2), (17, 2);

-- Grade 8A enrollments (students 18-22)
INSERT INTO tbl_class_enrollments (student_id, class_id) VALUES
(18, 3), (19, 3), (20, 3), (21, 3), (22, 3);

-- Grade 8B enrollments (students 23-27)
INSERT INTO tbl_class_enrollments (student_id, class_id) VALUES
(23, 4), (24, 4), (25, 4), (26, 4), (27, 4);

-- Grade 9A enrollments (students 28-32)
INSERT INTO tbl_class_enrollments (student_id, class_id) VALUES
(28, 5), (29, 5), (30, 5), (31, 5), (32, 5);

-- Grade 9B enrollments (students 33-37)
INSERT INTO tbl_class_enrollments (student_id, class_id) VALUES
(33, 6), (34, 6), (35, 6), (36, 6), (37, 6);

-- Teaching Assignments
-- Ahmad Fauzi (teacher_math, id=2) teaches Mathematics to all classes
INSERT INTO tbl_teaching_assignments (teacher_id, subject_id, class_id) VALUES
(2, 1, 1), (2, 1, 2), (2, 1, 3), (2, 1, 4), (2, 1, 5), (2, 1, 6);

-- Siti Nurhaliza (teacher_science, id=3) teaches Natural Sciences to all classes
INSERT INTO tbl_teaching_assignments (teacher_id, subject_id, class_id) VALUES
(3, 2, 1), (3, 2, 2), (3, 2, 3), (3, 2, 4), (3, 2, 5), (3, 2, 6);

-- Budi Santoso (teacher_english, id=4) teaches English to grades 7 and 8
INSERT INTO tbl_teaching_assignments (teacher_id, subject_id, class_id) VALUES
(4, 3, 1), (4, 3, 2), (4, 3, 3), (4, 3, 4);

-- Dewi Lestari (teacher_indo, id=5) teaches Indonesian to grades 8 and 9
INSERT INTO tbl_teaching_assignments (teacher_id, subject_id, class_id) VALUES
(5, 4, 3), (5, 4, 4), (5, 4, 5), (5, 4, 6);

-- Eko Prasetyo (teacher_ips, id=6) teaches Social Studies to grade 9
INSERT INTO tbl_teaching_assignments (teacher_id, subject_id, class_id) VALUES
(6, 5, 5), (6, 5, 6);

-- Exams - Spread across one month (Nov 22 - Dec 22, 2025)
-- Multiple exams per class to ensure at least 3 exams per student

-- ==================== GRADE 7A EXAMS (Students 8-12) ====================
-- Math exams for 7A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(1, 'Mathematics Quiz - Integers', 'Basic quiz on integer operations. Show your work.', 30, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(1, 'Mathematics Mid-Term Exam', 'Answer all questions carefully. No calculators allowed.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(1, 'Mathematics Final Review', 'Comprehensive review covering all semester topics.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Science exams for 7A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(7, 'Science Lab Safety Quiz', 'Answer all questions about laboratory safety procedures.', 20, 80, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(7, 'Natural Sciences - Matter & Energy', 'Quiz covering properties of matter and energy forms.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(7, 'Science Semester Assessment', 'Comprehensive science exam for semester evaluation.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- English exams for 7A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(13, 'English Grammar Basics', 'Test your understanding of basic English grammar.', 30, 60, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(13, 'English Reading Comprehension', 'Read passages carefully and answer questions.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(13, 'English Writing Skills Test', 'Demonstrate your English writing abilities.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- ==================== GRADE 7B EXAMS (Students 13-17) ====================
-- Math exams for 7B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(2, 'Mathematics Quick Quiz', 'Quick assessment of basic concepts.', 30, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(2, 'Mathematics Chapter Test', 'Test covering algebra and geometry basics.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(2, 'Mathematics Practice Exam', 'Practice exam to prepare for finals.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Science exams for 7B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(8, 'Science Pop Quiz', 'Short quiz on recent topics.', 20, 60, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(8, 'Natural Sciences - Living Things', 'Assessment on characteristics of living organisms.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(8, 'Science Mid-Term Exam', 'Comprehensive mid-term examination.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- English exams for 7B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(14, 'English Vocabulary Test', 'Choose the correct answer for each question. Read carefully.', 30, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(14, 'English Listening & Speaking', 'Assess your listening and speaking skills.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(14, 'English Literature Analysis', 'Analyze literary works and themes.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- ==================== GRADE 8A EXAMS (Students 18-22) ====================
-- Math exams for 8A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(3, 'Mathematics Algebra Quiz', 'Quick quiz on algebraic expressions.', 30, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(3, 'Mathematics Functions Test', 'Test your understanding of functions and graphs.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(3, 'Mathematics Advanced Problems', 'Challenging problems for advanced learners.', 90, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Science exams for 8A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(9, 'Natural Sciences Quiz - Chapter 3', 'This quiz covers photosynthesis and cellular respiration.', 45, 60, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(9, 'Science - Chemical Reactions', 'Understanding chemical reactions and equations.', 60, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(9, 'Science Comprehensive Exam', 'Full semester comprehensive examination.', 120, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- English exams for 8A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(15, 'English Advanced Grammar', 'Advanced grammar concepts and usage.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(15, 'English Essay Writing', 'Write essays on given topics. Be creative.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(15, 'English Literature & Culture', 'Explore literature and cultural contexts.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Indonesian exams for 8A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(19, 'Indonesian Language Basics', 'Basic Indonesian language skills assessment.', 30, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(19, 'Indonesian Reading Skills', 'Reading comprehension in Indonesian.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(19, 'Indonesian Writing Test', 'Demonstrate Indonesian writing proficiency.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- ==================== GRADE 8B EXAMS (Students 23-27) ====================
-- Math exams for 8B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(4, 'Mathematics Geometry Quiz', 'Basic geometry concepts and formulas.', 30, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(4, 'Mathematics Problem Solving', 'Applied mathematics and word problems.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(4, 'Mathematics Final Assessment', 'Comprehensive final mathematics assessment.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Science exams for 8B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(10, 'Science Quick Check', 'Quick check of understanding.', 20, 60, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(10, 'Natural Sciences - Physics', 'Understanding motion, force, and energy.', 60, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(10, 'Science Year-End Exam', 'Year-end comprehensive science examination.', 120, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- English exams for 8B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(16, 'English Communication Skills', 'Practical communication in English.', 30, 60, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(16, 'English Creative Writing', 'Express yourself through creative writing.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(16, 'English Comprehensive Test', 'Complete English skills assessment.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Indonesian exams for 8B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(20, 'Indonesian Grammar Check', 'Assessment of Indonesian grammar knowledge.', 30, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(20, 'Indonesian Literature Study', 'Study of Indonesian literary works.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(20, 'Indonesian Final Exam', 'Final examination for Indonesian language.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- ==================== GRADE 9A EXAMS (Students 28-32) ====================
-- Math exams for 9A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(5, 'Mathematics Trigonometry Quiz', 'Quick assessment of trigonometry basics.', 45, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(5, 'Mathematics Advanced Algebra', 'Advanced algebraic concepts and applications.', 90, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(5, 'Mathematics Final Exam', 'Comprehensive final exam covering all topics. Calculator permitted for section B only.', 120, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Science exams for 9A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(11, 'Science Electricity & Magnetism', 'Understanding electrical and magnetic phenomena.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(11, 'Natural Sciences - Ecology', 'Study of ecosystems and environmental science.', 60, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(11, 'Science Graduation Exam', 'Final graduation examination for science.', 120, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Indonesian exams for 9A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(21, 'Indonesian Critical Reading', 'Critical reading and analysis skills.', 45, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(21, 'Indonesian Advanced Writing', 'Advanced writing techniques and styles.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(21, 'Indonesian National Exam Prep', 'Preparation for national examination.', 90, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Social Studies exams for 9A
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(21, 'Social Studies - Geography', 'Geographic concepts and world geography.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(21, 'Social Studies - History', 'Historical events and their significance.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(21, 'Social Studies Final Exam', 'Comprehensive social studies examination.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- ==================== GRADE 9B EXAMS (Students 33-37) ====================
-- Math exams for 9B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(6, 'Mathematics Statistics Quiz', 'Data analysis and statistical concepts.', 45, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(6, 'Mathematics Probability Test', 'Probability theory and applications.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(6, 'Mathematics Comprehensive Final', 'Complete final examination for graduation.', 120, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Science exams for 9B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(12, 'Science Atomic Structure', 'Understanding atoms and molecular structure.', 45, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(12, 'Natural Sciences - Biotechnology', 'Modern biotechnology and applications.', 60, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(12, 'Science Final Assessment', 'Final comprehensive science assessment.', 120, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Indonesian exams for 9B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(20, 'Indonesian Poetry Analysis', 'Analyze Indonesian poetry and literary devices.', 45, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(20, 'Indonesian Literature Analysis', 'Analyze provided text and answer essay questions. Write clearly.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(20, 'Indonesian Graduation Exam', 'Final Indonesian language graduation exam.', 90, 75, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Social Studies exams for 9B
INSERT INTO tbl_exams (assignment_id, title, instructions, duration, passing_score, start_time, end_time, is_active, show_results, allow_review) VALUES
(22, 'Social Studies - Economics', 'Economic principles and applications.', 45, 65, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(22, 'Social Studies - Sociology', 'Society, culture, and social interactions.', 60, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE),
(22, 'Social Studies Graduation Test', 'Final graduation test for social studies.', 90, 70, '2025-11-20 08:00:00', '2025-12-29 23:59:59', TRUE, TRUE, TRUE);

-- Questions - Sample questions for each exam
-- Note: Adding questions only for first few exams as examples
-- In production, all exams should have appropriate questions

-- Questions for Exam 1 (Math Quiz - Integers, Grade 7A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(1, 'What is -5 + 8?', 'multiple', 10, '3', '-3', '13', '-13', 'A', 1),
(1, 'Multiply: (-4) × (-6) = ?', 'multiple', 10, '-24', '24', '-10', '10', 'B', 2),
(1, 'All integers are whole numbers.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'B', 3);

-- Questions for Exam 2 (Math Mid-Term, Grade 7A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(2, 'What is the result of 15 + 27?', 'multiple', 10, '32', '42', '52', '62', 'B', 1),
(2, 'Solve for x: 2x + 5 = 15', 'multiple', 10, 'x = 3', 'x = 5', 'x = 7', 'x = 10', 'B', 2),
(2, 'What is 25% of 80?', 'multiple', 10, '15', '20', '25', '30', 'B', 3),
(2, 'Which of the following is a prime number?', 'multiple', 10, '12', '15', '17', '20', 'C', 4),
(2, 'Explain the Pythagorean theorem and provide an example of its application.', 'essay', 20, NULL, NULL, NULL, NULL, NULL, 5);

-- Questions for Exam 3 (Math Final Review, Grade 7A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(3, 'Simplify: 3(x + 2) - 2(x - 1)', 'multiple', 10, 'x + 4', 'x + 8', '5x + 4', 'x + 6', 'B', 1),
(3, 'What is the volume of a cube with side length 5 cm?', 'multiple', 10, '25 cm³', '50 cm³', '125 cm³', '150 cm³', 'C', 2),
(3, 'A fraction is a type of rational number.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'A', 3);

-- Questions for Exam 4 (Science Lab Safety, Grade 7A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(4, 'You should always wear safety goggles in the lab.', 'truefalse', 5, 'True', 'False', NULL, NULL, 'A', 1),
(4, 'What should you do if chemicals spill on your skin?', 'multiple', 5, 'Wipe with cloth', 'Rinse with water immediately', 'Apply cream', 'Ignore it', 'B', 2),
(4, 'It is safe to eat or drink in the laboratory.', 'truefalse', 5, 'True', 'False', NULL, NULL, 'B', 3),
(4, 'What is the first step when entering a laboratory?', 'multiple', 5, 'Start experiment', 'Read safety rules', 'Open chemicals', 'Turn on equipment', 'B', 4);

-- Questions for Exam 7 (English Grammar Basics, Grade 7A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(7, 'Which word is a noun?', 'multiple', 10, 'Run', 'Beautiful', 'Table', 'Quickly', 'C', 1),
(7, 'Choose the correct form: She ___ to school every day.', 'multiple', 10, 'go', 'goes', 'going', 'gone', 'B', 2),
(7, 'An adverb describes a noun.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'B', 3);

-- Questions for Exam 10 (Math Quick Quiz, Grade 7B)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(10, 'What is 12 × 8?', 'multiple', 10, '84', '96', '104', '88', 'B', 1),
(10, 'Divide: 144 ÷ 12 = ?', 'multiple', 10, '10', '11', '12', '13', 'C', 2),
(10, 'Zero is a natural number.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'B', 3);

-- Questions for Exam 13 (Science Pop Quiz, Grade 7B)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(13, 'Water boils at 100°C at sea level.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'A', 1),
(13, 'Which state of matter has a definite shape and volume?', 'multiple', 10, 'Gas', 'Liquid', 'Solid', 'Plasma', 'C', 2);

-- Questions for Exam 16 (English Vocabulary Test, Grade 7B)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(16, 'What is the meaning of "benevolent"?', 'multiple', 10, 'Harmful', 'Kind and generous', 'Angry', 'Confused', 'B', 1),
(16, 'Choose the synonym for "ancient":', 'multiple', 10, 'Modern', 'New', 'Old', 'Future', 'C', 2),
(16, 'The word "transparent" means "difficult to see through".', 'truefalse', 10, 'True', 'False', NULL, NULL, 'B', 3);

-- Questions for Exam 19 (Math Algebra Quiz, Grade 8A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(19, 'Solve: 3x - 7 = 14', 'multiple', 10, 'x = 5', 'x = 6', 'x = 7', 'x = 8', 'C', 1),
(19, 'Simplify: 2a + 3a - a', 'multiple', 10, '3a', '4a', '5a', '6a', 'B', 2),
(19, 'An equation always has an equals sign.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'A', 3);

-- Questions for Exam 22 (Science Quiz - Photosynthesis, Grade 8A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(22, 'Photosynthesis occurs in which part of the plant cell?', 'multiple', 10, 'Nucleus', 'Mitochondria', 'Chloroplast', 'Cell wall', 'C', 1),
(22, 'What is the main product of photosynthesis?', 'multiple', 10, 'Carbon dioxide', 'Oxygen', 'Water', 'Nitrogen', 'B', 2),
(22, 'The process of cellular respiration occurs in all living organisms.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'A', 3),
(22, 'Plants perform photosynthesis only during the day.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'A', 4),
(22, 'Describe the relationship between photosynthesis and cellular respiration.', 'essay', 15, NULL, NULL, NULL, NULL, NULL, 5);

-- Questions for Exam 37 (Math Geometry Quiz, Grade 8B)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(37, 'What is the sum of interior angles in a quadrilateral?', 'multiple', 10, '180°', '270°', '360°', '450°', 'C', 1),
(37, 'The perimeter of a square with side 6 cm is?', 'multiple', 10, '12 cm', '18 cm', '24 cm', '36 cm', 'C', 2),
(37, 'All rectangles are squares.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'B', 3);

-- Questions for Exam 46 (Math Trigonometry Quiz, Grade 9A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(46, 'What is sin(90°)?', 'multiple', 15, '0', '0.5', '1', '√3/2', 'C', 1),
(46, 'In a right triangle, which ratio represents cosine?', 'multiple', 15, 'opposite/hypotenuse', 'adjacent/hypotenuse', 'opposite/adjacent', 'hypotenuse/adjacent', 'B', 2),
(46, 'The Pythagorean theorem applies only to right triangles.', 'truefalse', 15, 'True', 'False', NULL, NULL, 'A', 3);

-- Questions for Exam 47 (Math Advanced Algebra, Grade 9A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(47, 'Solve: (2x - 3)² = ?', 'multiple', 10, '4x² - 9', '4x² - 12x + 9', '4x² + 12x - 9', '2x² - 6x + 9', 'B', 1),
(47, 'Factor: x² - 9', 'multiple', 10, '(x-3)(x-3)', '(x+3)(x+3)', '(x-3)(x+3)', 'Cannot factor', 'C', 2),
(47, 'What is the slope of a line passing through points (2,3) and (4,7)?', 'multiple', 10, '1', '2', '3', '4', 'B', 3),
(47, 'A quadratic equation can have three real solutions.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'B', 4),
(47, 'Explain the quadratic formula and how to use it to solve x² + 5x + 6 = 0', 'essay', 20, NULL, NULL, NULL, NULL, NULL, 5);

-- Questions for Exam 48 (Math Final Exam, Grade 9A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(48, 'Solve: (3x + 2)(2x - 1) = ?', 'multiple', 10, '6x² - x + 2', '6x² + x - 2', '6x² - x - 2', '6x² + 4x - 2', 'B', 1),
(48, 'Calculate: √(64) + √(36)', 'multiple', 10, '10', '12', '14', '16', 'C', 2),
(48, 'The sum of angles in any triangle is always 180 degrees.', 'truefalse', 10, 'True', 'False', NULL, NULL, 'A', 3),
(48, 'Calculate the area of a circle with radius 7 cm (use π = 3.14)', 'multiple', 10, '49 cm²', '153.86 cm²', '43.96 cm²', '21.98 cm²', 'B', 4),
(48, 'What is the value of x if 2^x = 32?', 'multiple', 10, '3', '4', '5', '6', 'C', 5),
(48, 'Prove that the sum of any two consecutive odd numbers is always even. Provide examples.', 'essay', 20, NULL, NULL, NULL, NULL, NULL, 6);

-- Questions for Exam 49 (Science Electricity & Magnetism, Grade 9A)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(49, 'The unit of electrical resistance is?', 'multiple', 12, 'Ampere', 'Volt', 'Ohm', 'Watt', 'C', 1),
(49, 'Like magnetic poles attract each other.', 'truefalse', 12, 'True', 'False', NULL, NULL, 'B', 2),
(49, 'What is the formula for Ohm''s Law?', 'multiple', 12, 'V = I × R', 'V = I / R', 'V = R / I', 'V = I + R', 'A', 3),
(49, 'Explain how a simple electric motor works.', 'essay', 24, NULL, NULL, NULL, NULL, NULL, 4);

-- Questions for Exam 55 (Math Statistics Quiz, Grade 9B)
INSERT INTO tbl_questions (exam_id, question_text, question_type, points, option_a, option_b, option_c, option_d, correct_answer, display_order) VALUES
(55, 'What is the median of: 3, 7, 9, 12, 15?', 'multiple', 15, '7', '9', '12', '9.2', 'B', 1),
(55, 'The mode is the most frequently occurring value in a dataset.', 'truefalse', 15, 'True', 'False', NULL, NULL, 'A', 2),
(55, 'Calculate the mean of: 10, 20, 30, 40, 50', 'multiple', 15, '25', '30', '35', '40', 'B', 3);

-- Exam Attempts - Sample attempts showing various states

-- Exam 1 attempts (Math Quiz - Integers, Grade 7A) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(1, 8, '2025-11-20 08:05:00', '2025-11-20 08:28:00', NULL, 'graded'),
(1, 9, '2025-11-20 08:02:00', '2025-11-20 08:25:00', NULL, 'graded'),
(1, 10, '2025-11-20 08:00:00', '2025-11-20 08:30:00', NULL, 'graded');

-- Exam 4 attempts (Science Lab Safety, Grade 7A) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(4, 8, '2025-11-19 10:05:00', '2025-11-19 10:18:00', NULL, 'graded'),
(4, 9, '2025-11-19 10:03:00', '2025-11-19 10:20:00', NULL, 'graded'),
(4, 11, '2025-11-19 10:07:00', '2025-11-19 10:22:00', NULL, 'graded');

-- Exam 10 attempts (Math Quick Quiz, Grade 7B) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(10, 13, '2025-11-21 08:05:00', '2025-11-21 08:27:00', NULL, 'graded'),
(10, 14, '2025-11-21 08:02:00', '2025-11-21 08:25:00', NULL, 'graded'),
(10, 15, '2025-11-21 08:00:00', NULL, 0, 'expired');

-- Exam 13 attempts (Science Pop Quiz, Grade 7B) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(13, 13, '2025-11-22 10:05:00', '2025-11-22 10:18:00', NULL, 'graded'),
(13, 14, '2025-11-22 10:03:00', '2025-11-22 10:20:00', NULL, 'graded'),
(13, 16, '2025-11-22 10:02:00', '2025-11-22 10:19:00', NULL, 'graded');

-- Exam 16 attempts (English Vocabulary Test, Grade 7B) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(16, 13, '2025-11-23 10:05:00', '2025-11-23 10:32:00', NULL, 'graded'),
(16, 14, '2025-11-23 10:02:00', '2025-11-23 10:28:00', NULL, 'graded'),
(16, 15, '2025-11-23 10:00:00', '2025-11-23 10:29:00', NULL, 'graded');

-- Exam 19 attempts (Math Algebra Quiz, Grade 8A) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(19, 18, '2025-11-24 08:05:00', '2025-11-24 08:28:00', NULL, 'graded'),
(19, 19, '2025-11-24 08:02:00', '2025-11-24 08:30:00', NULL, 'graded'),
(19, 20, '2025-11-24 08:07:00', '2025-11-24 08:32:00', NULL, 'graded');

-- Exam 22 attempts (Science - Photosynthesis, Grade 8A) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(22, 18, '2025-11-22 09:05:00', '2025-11-22 09:45:00', NULL, 'graded'),
(22, 19, '2025-11-22 09:10:00', '2025-11-22 09:48:00', NULL, 'graded'),
(22, 20, '2025-11-22 09:02:00', '2025-11-22 09:40:00', NULL, 'graded');

-- Exam 37 attempts (Math Geometry Quiz, Grade 8B) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(37, 23, '2025-11-25 08:05:00', '2025-11-25 08:28:00', NULL, 'graded'),
(37, 24, '2025-11-25 08:03:00', '2025-11-25 08:30:00', NULL, 'graded'),
(37, 25, '2025-11-25 08:00:00', '2025-11-25 08:27:00', NULL, 'graded');

-- Exam 46 attempts (Math Trigonometry Quiz, Grade 9A) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(46, 28, '2025-11-20 08:05:00', '2025-11-20 08:42:00', NULL, 'graded'),
(46, 29, '2025-11-20 08:02:00', '2025-11-20 08:45:00', NULL, 'graded'),
(46, 30, '2025-11-20 08:07:00', '2025-11-20 08:48:00', NULL, 'graded');

-- Exam 47 attempts (Math Advanced Algebra, Grade 9A) - SUBMITTED/IN_PROGRESS
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(47, 28, '2025-11-29 08:05:00', '2025-11-29 09:30:00', NULL, 'submitted'),
(47, 29, '2025-11-29 08:10:00', '2025-11-29 09:45:00', NULL, 'submitted'),
(47, 30, '2025-11-29 08:02:00', NULL, 3200, 'in_progress');

-- Exam 49 attempts (Science Electricity, Grade 9A) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(49, 28, '2025-11-24 09:05:00', '2025-11-24 09:55:00', NULL, 'graded'),
(49, 29, '2025-11-24 09:02:00', '2025-11-24 10:00:00', NULL, 'graded'),
(49, 31, '2025-11-24 09:10:00', '2025-11-24 09:58:00', NULL, 'graded');

-- Exam 55 attempts (Math Statistics Quiz, Grade 9B) - COMPLETED/GRADED
INSERT INTO tbl_exam_attempts (exam_id, student_id, started_at, submitted_at, time_remaining, status) VALUES
(55, 33, '2025-11-21 08:05:00', '2025-11-21 08:42:00', NULL, 'graded'),
(55, 34, '2025-11-21 08:02:00', '2025-11-21 08:45:00', NULL, 'graded'),
(55, 35, '2025-11-21 08:07:00', '2025-11-21 08:50:00', NULL, 'graded');

-- Answers - Sample answers for various exam attempts

-- Answers for Attempt 1 (student 8, exam 1 - Math Quiz Integers)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(1, 1, 'A', TRUE, 10, 'Correct!', 2, '2025-11-20 10:00:00', '2025-11-20 08:15:00'),
(1, 2, 'B', TRUE, 10, 'Good work', 2, '2025-11-20 10:00:00', '2025-11-20 08:20:00'),
(1, 3, 'B', TRUE, 10, 'Perfect', 2, '2025-11-20 10:00:00', '2025-11-20 08:25:00');

-- Answers for Attempt 2 (student 9, exam 1)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(2, 1, 'A', TRUE, 10, NULL, 2, '2025-11-20 10:00:00', '2025-11-20 08:12:00'),
(2, 2, 'B', TRUE, 10, NULL, 2, '2025-11-20 10:00:00', '2025-11-20 08:18:00'),
(2, 3, 'A', FALSE, 0, 'Integers include negative numbers, but not all integers are whole numbers', 2, '2025-11-20 10:00:00', '2025-11-20 08:23:00');

-- Answers for Attempt 3 (student 10, exam 1)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(3, 1, 'A', TRUE, 10, NULL, 2, '2025-11-20 10:00:00', '2025-11-20 08:10:00'),
(3, 2, 'A', FALSE, 0, 'Remember: negative × negative = positive', 2, '2025-11-20 10:00:00', '2025-11-20 08:16:00'),
(3, 3, 'B', TRUE, 10, NULL, 2, '2025-11-20 10:00:00', '2025-11-20 08:28:00');

-- Answers for Attempt 4 (student 8, exam 4 - Science Lab Safety)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(4, 12, 'A', TRUE, 5, 'Correct', 3, '2025-11-19 11:00:00', '2025-11-19 10:08:00'),
(4, 13, 'B', TRUE, 5, 'Good', 3, '2025-11-19 11:00:00', '2025-11-19 10:11:00'),
(4, 14, 'B', TRUE, 5, 'Perfect', 3, '2025-11-19 11:00:00', '2025-11-19 10:14:00'),
(4, 15, 'B', TRUE, 5, 'Excellent', 3, '2025-11-19 11:00:00', '2025-11-19 10:17:00');

-- Answers for Attempt 5 (student 9, exam 4)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(5, 12, 'A', TRUE, 5, NULL, 3, '2025-11-19 11:00:00', '2025-11-19 10:07:00'),
(5, 13, 'B', TRUE, 5, NULL, 3, '2025-11-19 11:00:00', '2025-11-19 10:12:00'),
(5, 14, 'A', FALSE, 0, 'Never eat or drink in lab', 3, '2025-11-19 11:00:00', '2025-11-19 10:16:00'),
(5, 15, 'B', TRUE, 5, NULL, 3, '2025-11-19 11:00:00', '2025-11-19 10:19:00');

-- Answers for Attempt 6 (student 11, exam 4)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(6, 12, 'A', TRUE, 5, NULL, 3, '2025-11-19 11:00:00', '2025-11-19 10:10:00'),
(6, 13, 'B', TRUE, 5, NULL, 3, '2025-11-19 11:00:00', '2025-11-19 10:14:00'),
(6, 14, 'B', TRUE, 5, NULL, 3, '2025-11-19 11:00:00', '2025-11-19 10:18:00'),
(6, 15, 'B', TRUE, 5, NULL, 3, '2025-11-19 11:00:00', '2025-11-19 10:21:00');

-- Answers for Attempt 7 (student 13, exam 10 - Math Quick Quiz)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(7, 19, 'B', TRUE, 10, 'Correct', 2, '2025-11-21 10:00:00', '2025-11-21 08:12:00'),
(7, 20, 'C', TRUE, 10, 'Good work', 2, '2025-11-21 10:00:00', '2025-11-21 08:18:00'),
(7, 21, 'B', TRUE, 10, 'Perfect', 2, '2025-11-21 10:00:00', '2025-11-21 08:25:00');

-- Answers for Attempt 8 (student 14, exam 10)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(8, 19, 'B', TRUE, 10, NULL, 2, '2025-11-21 10:00:00', '2025-11-21 08:10:00'),
(8, 20, 'C', TRUE, 10, NULL, 2, '2025-11-21 10:00:00', '2025-11-21 08:16:00'),
(8, 21, 'A', FALSE, 0, 'Zero is not a natural number', 2, '2025-11-21 10:00:00', '2025-11-21 08:23:00');

-- Answers for Attempt 10 (student 13, exam 13 - Science Pop Quiz)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(10, 22, 'A', TRUE, 10, 'Correct', 3, '2025-11-22 12:00:00', '2025-11-22 10:10:00'),
(10, 23, 'C', TRUE, 10, 'Good', 3, '2025-11-22 12:00:00', '2025-11-22 10:16:00');

-- Answers for Attempt 11 (student 14, exam 13)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(11, 22, 'A', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 10:08:00'),
(11, 23, 'C', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 10:18:00');

-- Answers for Attempt 12 (student 16, exam 13)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(12, 22, 'B', FALSE, 0, 'Water boils at 100°C at sea level', 3, '2025-11-22 12:00:00', '2025-11-22 10:06:00'),
(12, 23, 'C', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 10:17:00');

-- Answers for Attempt 13 (student 13, exam 16 - English Vocabulary)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(13, 24, 'B', TRUE, 10, 'Correct', 4, '2025-11-23 14:00:00', '2025-11-23 10:15:00'),
(13, 25, 'C', TRUE, 10, 'Good', 4, '2025-11-23 14:00:00', '2025-11-23 10:20:00'),
(13, 26, 'B', TRUE, 10, 'Perfect', 4, '2025-11-23 14:00:00', '2025-11-23 10:28:00');

-- Answers for Attempt 14 (student 14, exam 16)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(14, 24, 'B', TRUE, 10, NULL, 4, '2025-11-23 14:00:00', '2025-11-23 10:10:00'),
(14, 25, 'C', TRUE, 10, NULL, 4, '2025-11-23 14:00:00', '2025-11-23 10:16:00'),
(14, 26, 'A', FALSE, 0, 'Transparent means easy to see through', 4, '2025-11-23 14:00:00', '2025-11-23 10:25:00');

-- Answers for Attempt 15 (student 15, exam 16)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(15, 24, 'B', TRUE, 10, NULL, 4, '2025-11-23 14:00:00', '2025-11-23 10:08:00'),
(15, 25, 'C', TRUE, 10, NULL, 4, '2025-11-23 14:00:00', '2025-11-23 10:15:00'),
(15, 26, 'B', TRUE, 10, NULL, 4, '2025-11-23 14:00:00', '2025-11-23 10:27:00');

-- Answers for Attempt 16 (student 18, exam 19 - Math Algebra Quiz)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(16, 27, 'C', TRUE, 10, 'Correct', 2, '2025-11-24 10:00:00', '2025-11-24 08:12:00'),
(16, 28, 'B', TRUE, 10, 'Good', 2, '2025-11-24 10:00:00', '2025-11-24 08:18:00'),
(16, 29, 'A', TRUE, 10, 'Perfect', 2, '2025-11-24 10:00:00', '2025-11-24 08:26:00');

-- Answers for Attempt 17 (student 19, exam 19)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(17, 27, 'C', TRUE, 10, NULL, 2, '2025-11-24 10:00:00', '2025-11-24 08:10:00'),
(17, 28, 'B', TRUE, 10, NULL, 2, '2025-11-24 10:00:00', '2025-11-24 08:16:00'),
(17, 29, 'A', TRUE, 10, NULL, 2, '2025-11-24 10:00:00', '2025-11-24 08:28:00');

-- Answers for Attempt 18 (student 20, exam 19)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(18, 27, 'B', FALSE, 0, 'Solve carefully: 3x = 21, so x = 7', 2, '2025-11-24 10:00:00', '2025-11-24 08:15:00'),
(18, 28, 'B', TRUE, 10, NULL, 2, '2025-11-24 10:00:00', '2025-11-24 08:22:00'),
(18, 29, 'A', TRUE, 10, NULL, 2, '2025-11-24 10:00:00', '2025-11-24 08:30:00');

-- Answers for Attempt 19 (student 18, exam 22 - Science Photosynthesis)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(19, 30, 'C', TRUE, 10, 'Correct', 3, '2025-11-22 12:00:00', '2025-11-22 09:12:00'),
(19, 31, 'B', TRUE, 10, 'Good', 3, '2025-11-22 12:00:00', '2025-11-22 09:18:00'),
(19, 32, 'A', TRUE, 10, 'Perfect', 3, '2025-11-22 12:00:00', '2025-11-22 09:25:00'),
(19, 33, 'A', TRUE, 10, 'Excellent', 3, '2025-11-22 12:00:00', '2025-11-22 09:32:00'),
(19, 34, 'Photosynthesis produces oxygen and glucose while cellular respiration uses them. They are complementary processes in nature.', TRUE, 13, 'Good understanding. Could be more detailed.', 3, '2025-11-22 12:00:00', '2025-11-22 09:42:00');

-- Answers for Attempt 20 (student 19, exam 22)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(20, 30, 'C', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 09:16:00'),
(20, 31, 'B', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 09:22:00'),
(20, 32, 'A', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 09:28:00'),
(20, 33, 'B', FALSE, 0, 'Plants do photosynthesize during the day', 3, '2025-11-22 12:00:00', '2025-11-22 09:35:00'),
(20, 34, 'They are related biological processes in plants and animals.', TRUE, 10, 'Too brief. Needs more detail about their relationship.', 3, '2025-11-22 12:00:00', '2025-11-22 09:46:00');

-- Answers for Attempt 21 (student 20, exam 22)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(21, 30, 'C', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 09:08:00'),
(21, 31, 'B', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 09:14:00'),
(21, 32, 'A', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 09:20:00'),
(21, 33, 'A', TRUE, 10, NULL, 3, '2025-11-22 12:00:00', '2025-11-22 09:26:00'),
(21, 34, 'Photosynthesis and cellular respiration are opposite processes. Photosynthesis stores energy in glucose using sunlight and produces oxygen, while respiration releases that energy for cell functions and uses oxygen. They form a cycle where the products of one are the reactants of the other, maintaining balance in ecosystems.', TRUE, 15, 'Excellent explanation! Very thorough understanding.', 3, '2025-11-22 12:00:00', '2025-11-22 09:38:00');

-- Answers for Attempt 22 (student 23, exam 37 - Math Geometry)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(22, 35, 'C', TRUE, 10, 'Correct', 2, '2025-11-25 10:00:00', '2025-11-25 08:12:00'),
(22, 36, 'C', TRUE, 10, 'Good', 2, '2025-11-25 10:00:00', '2025-11-25 08:18:00'),
(22, 37, 'B', TRUE, 10, 'Perfect', 2, '2025-11-25 10:00:00', '2025-11-25 08:26:00');

-- Answers for Attempt 23 (student 24, exam 37)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(23, 35, 'C', TRUE, 10, NULL, 2, '2025-11-25 10:00:00', '2025-11-25 08:10:00'),
(23, 36, 'C', TRUE, 10, NULL, 2, '2025-11-25 10:00:00', '2025-11-25 08:16:00'),
(23, 37, 'A', FALSE, 0, 'All rectangles are NOT squares', 2, '2025-11-25 10:00:00', '2025-11-25 08:28:00');

-- Answers for Attempt 24 (student 25, exam 37)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(24, 35, 'C', TRUE, 10, NULL, 2, '2025-11-25 10:00:00', '2025-11-25 08:08:00'),
(24, 36, 'B', FALSE, 0, 'Perimeter = 4 × side = 24 cm', 2, '2025-11-25 10:00:00', '2025-11-25 08:14:00'),
(24, 37, 'B', TRUE, 10, NULL, 2, '2025-11-25 10:00:00', '2025-11-25 08:25:00');

-- Answers for Attempt 25 (student 28, exam 46 - Math Trigonometry)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(25, 38, 'C', TRUE, 15, 'Correct', 2, '2025-11-20 11:00:00', '2025-11-20 08:18:00'),
(25, 39, 'B', TRUE, 15, 'Good work', 2, '2025-11-20 11:00:00', '2025-11-20 08:28:00'),
(25, 40, 'A', TRUE, 15, 'Perfect', 2, '2025-11-20 11:00:00', '2025-11-20 08:40:00');

-- Answers for Attempt 26 (student 29, exam 46)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(26, 38, 'C', TRUE, 15, NULL, 2, '2025-11-20 11:00:00', '2025-11-20 08:15:00'),
(26, 39, 'A', FALSE, 0, 'Cosine = adjacent/hypotenuse', 2, '2025-11-20 11:00:00', '2025-11-20 08:26:00'),
(26, 40, 'A', TRUE, 15, NULL, 2, '2025-11-20 11:00:00', '2025-11-20 08:42:00');

-- Answers for Attempt 27 (student 30, exam 46)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(27, 38, 'C', TRUE, 15, NULL, 2, '2025-11-20 11:00:00', '2025-11-20 08:20:00'),
(27, 39, 'B', TRUE, 15, NULL, 2, '2025-11-20 11:00:00', '2025-11-20 08:32:00'),
(27, 40, 'A', TRUE, 15, NULL, 2, '2025-11-20 11:00:00', '2025-11-20 08:46:00');

-- Answers for Attempt 28 (student 28, exam 47 - Math Advanced Algebra) - SUBMITTED
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(28, 41, 'B', NULL, 0, NULL, NULL, NULL, '2025-11-29 08:25:00'),
(28, 42, 'C', NULL, 0, NULL, NULL, NULL, '2025-11-29 08:38:00'),
(28, 43, 'B', NULL, 0, NULL, NULL, NULL, '2025-11-29 09:05:00'),
(28, 44, 'B', NULL, 0, NULL, NULL, NULL, '2025-11-29 09:28:00'),
(28, 45, 'x = (-b ± √(b²-4ac)) / 2a for ax² + bx + c = 0. For x² + 5x + 6 = 0, we have a=1, b=5, c=6. x = (-5 ± √(25-24)) / 2 = (-5 ± 1) / 2 = -2 or -3.', NULL, 0, NULL, NULL, NULL, '2025-11-29 09:28:00');

-- Answers for Attempt 29 (student 29, exam 47) - SUBMITTED
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(29, 41, 'B', NULL, 0, NULL, NULL, NULL, '2025-11-29 08:30:00'),
(29, 42, 'C', NULL, 0, NULL, NULL, NULL, '2025-11-29 08:45:00'),
(29, 43, 'B', NULL, 0, NULL, NULL, NULL, '2025-11-29 09:00:00'),
(29, 44, 'B', NULL, 0, NULL, NULL, NULL, '2025-11-29 09:15:00'),
(29, 45, 'x = (-b ± √(b²-4ac)) / 2a for ax² + bx + c = 0. Plugging in: x = (-5 ± 1) / 2 = -2 or -3.', NULL, 0, NULL, NULL, NULL, '2025-11-29 09:42:00');

-- Answers for Attempt 30 (student 30, exam 47) - IN PROGRESS (partial answers)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, submitted_at) VALUES
(30, 41, 'B', NULL, 0, '2025-11-29 08:20:00'),
(30, 42, 'C', NULL, 0, '2025-11-29 08:35:00');

-- Answers for Attempt 31 (student 28, exam 49 - Science Electricity)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(31, 52, 'C', TRUE, 12, 'Correct', 3, '2025-11-24 11:30:00', '2025-11-24 09:18:00'),
(31, 53, 'B', TRUE, 12, 'Good', 3, '2025-11-24 11:30:00', '2025-11-24 09:26:00'),
(31, 54, 'A', TRUE, 12, 'Perfect', 3, '2025-11-24 11:30:00', '2025-11-24 09:38:00'),
(31, 55, 'An electric motor converts electrical energy to mechanical energy using electromagnetic induction. When current flows through a coil in a magnetic field, it experiences a force that causes rotation. The commutator reverses current direction to maintain continuous rotation.', TRUE, 20, 'Excellent explanation!', 3, '2025-11-24 11:30:00', '2025-11-24 09:52:00');

-- Answers for Attempt 32 (student 29, exam 49)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(32, 52, 'C', TRUE, 12, NULL, 3, '2025-11-24 11:30:00', '2025-11-24 09:15:00'),
(32, 53, 'A', FALSE, 0, 'Like poles repel, opposite poles attract', 3, '2025-11-24 11:30:00', '2025-11-24 09:24:00'),
(32, 54, 'A', TRUE, 12, NULL, 3, '2025-11-24 11:30:00', '2025-11-24 09:42:00'),
(32, 55, 'It uses magnets and electricity to make things spin.', TRUE, 10, 'Too brief. Needs more technical detail.', 3, '2025-11-24 11:30:00', '2025-11-24 09:58:00');

-- Answers for Attempt 33 (student 31, exam 49)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(33, 52, 'C', TRUE, 12, NULL, 3, '2025-11-24 11:30:00', '2025-11-24 09:22:00'),
(33, 53, 'B', TRUE, 12, NULL, 3, '2025-11-24 11:30:00', '2025-11-24 09:30:00'),
(33, 54, 'A', TRUE, 12, NULL, 3, '2025-11-24 11:30:00', '2025-11-24 09:44:00'),
(33, 55, 'Electric motors use electromagnetic force. Current in coil creates magnetic field that interacts with permanent magnets causing rotation.', TRUE, 16, 'Good explanation with correct concepts.', 3, '2025-11-24 11:30:00', '2025-11-24 09:56:00');

-- Answers for Attempt 34 (student 33, exam 55 - Math Statistics)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(34, 56, 'B', TRUE, 15, 'Correct', 2, '2025-11-21 10:30:00', '2025-11-21 08:20:00'),
(34, 57, 'A', TRUE, 15, 'Good', 2, '2025-11-21 10:30:00', '2025-11-21 08:28:00'),
(34, 58, 'B', TRUE, 15, 'Perfect', 2, '2025-11-21 10:30:00', '2025-11-21 08:40:00');

-- Answers for Attempt 35 (student 34, exam 55)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(35, 56, 'B', TRUE, 15, NULL, 2, '2025-11-21 10:30:00', '2025-11-21 08:16:00'),
(35, 57, 'A', TRUE, 15, NULL, 2, '2025-11-21 10:30:00', '2025-11-21 08:26:00'),
(35, 58, 'A', FALSE, 0, 'Mean = sum / count = 150 / 5 = 30', 2, '2025-11-21 10:30:00', '2025-11-21 08:43:00');

-- Answers for Attempt 36 (student 35, exam 55)
INSERT INTO tbl_answers (attempt_id, question_id, student_answer, is_correct, points_earned, teacher_feedback, graded_by, graded_at, submitted_at) VALUES
(36, 56, 'A', FALSE, 0, 'Median is the middle value: 9', 2, '2025-11-21 10:30:00', '2025-11-21 08:18:00'),
(36, 57, 'A', TRUE, 15, NULL, 2, '2025-11-21 10:30:00', '2025-11-21 08:28:00'),
(36, 58, 'B', TRUE, 15, NULL, 2, '2025-11-21 10:30:00', '2025-11-21 08:48:00');

-- Scores - Calculated scores for completed exam attempts

INSERT INTO tbl_scores (attempt_id, total_points, points_earned, percentage, passed, graded_at) VALUES
-- Exam 1 scores (Math Quiz - Integers)
(1, 30, 30, 100, TRUE, '2025-11-20 10:00:00'),
(2, 30, 20, 66.67, TRUE, '2025-11-20 10:00:00'),
(3, 30, 20, 66.67, TRUE, '2025-11-20 10:00:00'),

-- Exam 4 scores (Science Lab Safety)
(4, 20, 20, 100, TRUE, '2025-11-19 11:00:00'),
(5, 20, 15, 75, TRUE, '2025-11-19 11:00:00'),
(6, 20, 20, 100, TRUE, '2025-11-19 11:00:00'),

-- Exam 10 scores (Math Quick Quiz, 7B)
(7, 30, 30, 100, TRUE, '2025-11-21 10:00:00'),
(8, 30, 20, 66.67, TRUE, '2025-11-21 10:00:00'),

-- Exam 13 scores (Science Pop Quiz, 7B)
(10, 20, 20, 100, TRUE, '2025-11-22 12:00:00'),
(11, 20, 20, 100, TRUE, '2025-11-22 12:00:00'),
(12, 20, 10, 50, FALSE, '2025-11-22 12:00:00'),

-- Exam 16 scores (English Vocabulary, 7B)
(13, 30, 30, 100, TRUE, '2025-11-23 14:00:00'),
(14, 30, 20, 66.67, TRUE, '2025-11-23 14:00:00'),
(15, 30, 30, 100, TRUE, '2025-11-23 14:00:00'),

-- Exam 19 scores (Math Algebra Quiz, 8A)
(16, 30, 30, 100, TRUE, '2025-11-24 10:00:00'),
(17, 30, 30, 100, TRUE, '2025-11-24 10:00:00'),
(18, 30, 20, 66.67, TRUE, '2025-11-24 10:00:00'),

-- Exam 22 scores (Science Photosynthesis, 8A)
(19, 55, 53, 96.36, TRUE, '2025-11-22 12:00:00'),
(20, 55, 40, 72.73, TRUE, '2025-11-22 12:00:00'),
(21, 55, 55, 100, TRUE, '2025-11-22 12:00:00'),

-- Exam 37 scores (Math Geometry, 8B)
(22, 30, 30, 100, TRUE, '2025-11-25 10:00:00'),
(23, 30, 20, 66.67, TRUE, '2025-11-25 10:00:00'),
(24, 30, 20, 66.67, TRUE, '2025-11-25 10:00:00'),

-- Exam 46 scores (Math Trigonometry, 9A)
(25, 45, 45, 100, TRUE, '2025-11-20 11:00:00'),
(26, 45, 30, 66.67, FALSE, '2025-11-20 11:00:00'),
(27, 45, 45, 100, TRUE, '2025-11-20 11:00:00'),

-- Exam 49 scores (Science Electricity, 9A)
(31, 60, 56, 93.33, TRUE, '2025-11-24 11:30:00'),
(32, 60, 34, 56.67, FALSE, '2025-11-24 11:30:00'),
(33, 60, 52, 86.67, TRUE, '2025-11-24 11:30:00'),

-- Exam 55 scores (Math Statistics, 9B)
(34, 45, 45, 100, TRUE, '2025-11-21 10:30:00'),
(35, 45, 30, 66.67, FALSE, '2025-11-21 10:30:00'),
(36, 45, 30, 66.67, FALSE, '2025-11-21 10:30:00');

-- Data Population Complete
-- Summary: 37 users (1 admin, 6 teachers, 30 students), 6 subjects, 6 classes, 30 enrollments, 24 teaching assignments, 72 exams, 61 questions, 36 attempts, 27 scores
-- Testing Period: November 20 - December 22, 2025. Each student has access to at least 9-12 exams.