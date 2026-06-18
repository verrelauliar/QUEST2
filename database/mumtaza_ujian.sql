-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 05:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mumtaza_ujian`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_answers`
--

CREATE TABLE `tbl_answers` (
  `id_answer` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `student_answer` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL COMMENT 'Auto-graded for multiple/truefalse',
  `points_earned` float NOT NULL DEFAULT 0,
  `teacher_feedback` text DEFAULT NULL COMMENT 'For essay questions',
  `graded_at` timestamp NULL DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL COMMENT 'Teacher who graded essay',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_classes`
--

CREATE TABLE `tbl_classes` (
  `id_class` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL COMMENT 'e.g., 7A, 8B, 9C',
  `grade_level` int(11) NOT NULL COMMENT 'Grade level: 7, 8, or 9',
  `academic_year` varchar(20) NOT NULL COMMENT 'e.g., 2024/2025',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_classes`
--

INSERT INTO `tbl_classes` (`id_class`, `class_name`, `grade_level`, `academic_year`, `status`, `created_at`, `updated_at`) VALUES
(1, '7A', 7, '2024/2025', 'active', '2025-11-14 08:32:23', '2025-11-14 08:32:23'),
(2, '7B', 7, '2024/2025', 'active', '2025-11-14 08:32:23', '2025-11-14 08:32:23'),
(3, '8A', 8, '2024/2025', 'active', '2025-11-14 08:32:23', '2025-11-14 08:32:23'),
(4, '8B', 8, '2024/2025', 'active', '2025-11-14 08:32:23', '2025-11-14 08:32:23'),
(5, 'ASDA', 8, '2024/2025', 'inactive', '2025-11-17 13:20:49', '2025-11-17 13:22:11'),
(6, '7C', 9, '2024/2025', 'inactive', '2025-11-17 14:16:07', '2025-11-17 15:10:46'),
(7, '9A', 9, '2024/2025', 'active', '2025-11-17 15:11:02', '2025-11-17 15:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_class_enrollments`
--

CREATE TABLE `tbl_class_enrollments` (
  `id_enrollment` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_class_enrollments`
--

INSERT INTO `tbl_class_enrollments` (`id_enrollment`, `student_id`, `class_id`, `enrolled_at`) VALUES
(3, 4, 1, '2025-11-17 13:44:40'),
(4, 5, 1, '2025-11-17 13:44:40'),
(5, 4, 6, '2025-11-17 14:16:07'),
(6, 5, 6, '2025-11-17 14:16:07'),
(7, 4, 7, '2025-11-17 15:11:02'),
(8, 5, 7, '2025-11-17 15:11:02'),
(11, 4, 2, '2025-11-17 15:12:10'),
(12, 5, 2, '2025-11-17 15:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_exams`
--

CREATE TABLE `tbl_exams` (
  `id_exam` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL COMMENT 'Links to teacher-subject-class assignment',
  `title` varchar(255) NOT NULL,
  `instructions` text DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `passing_score` float NOT NULL DEFAULT 60,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_results` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Show results to students after submission',
  `allow_review` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Allow students to review answers',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_exam_attempts`
--

CREATE TABLE `tbl_exam_attempts` (
  `id_attempt` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `time_remaining` int(11) DEFAULT NULL COMMENT 'Seconds remaining when submitted',
  `status` enum('in_progress','submitted','expired','graded') NOT NULL DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_questions`
--

CREATE TABLE `tbl_questions` (
  `id_question` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple','essay','truefalse') NOT NULL,
  `points` float NOT NULL DEFAULT 1,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_answer` char(1) DEFAULT NULL COMMENT 'A/B/C/D for multiple, T/F for truefalse, NULL for essay',
  `display_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_scores`
--

CREATE TABLE `tbl_scores` (
  `id_score` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `total_points` float NOT NULL DEFAULT 0,
  `points_earned` float NOT NULL DEFAULT 0,
  `percentage` float NOT NULL DEFAULT 0,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subjects`
--

CREATE TABLE `tbl_subjects` (
  `id_subject` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_subjects`
--

INSERT INTO `tbl_subjects` (`id_subject`, `subject_name`, `subject_code`, `description`, `created_at`, `updated_at`) VALUES
(10, 'Indonesia', 'asa241', 'Asda', '2025-11-14 09:01:55', '2025-11-14 09:01:55'),
(11, 'English', 'ENG-01', 'English for Year 7', '2025-11-17 15:11:35', '2025-11-17 15:11:35');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_teaching_assignments`
--

CREATE TABLE `tbl_teaching_assignments` (
  `id_assignment` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_teaching_assignments`
--

INSERT INTO `tbl_teaching_assignments` (`id_assignment`, `teacher_id`, `subject_id`, `class_id`, `assigned_at`) VALUES
(5, 2, 10, 1, '2025-11-17 13:44:40'),
(7, 2, 10, 7, '2025-11-17 15:11:02'),
(8, 2, 11, 2, '2025-11-17 15:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id_user`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$LqdPlwj8YKmcNnBPQDSfKuTHjFXk3CzDGg6jiJ7vv5CgKH5cKf/6S', 'Administrator', 'admin@school.id', 'admin', 'active', '2025-11-14 08:32:23', '2025-11-14 09:11:15'),
(2, 'pak.budi', '$2y$10$vG3vkfz3SCjsi8BkP5y8kenQVSwEEGmTZVhBiGlOJD5gt/l5mxQxa', 'Budi Santoso', 'budi@school.id', 'teacher', 'active', '2025-11-14 08:32:23', '2025-11-14 09:02:05'),
(3, 'bu.siti', '$2y$10$vG3vkfz3SCjsi8BkP5y8kenQVSwEEGmTZVhBiGlOJD5gt/l5mxQxa', 'Siti Nurhaliza', 'siti@school.id', 'teacher', 'active', '2025-11-14 08:32:23', '2025-11-14 09:02:05'),
(4, 'ahmad', '$2y$10$hdzhUbMuv5jDfJzEm7UjGOiTHgtnmhjmCC6b.Yf7aZYpjeRIIo9/C', 'Ahmad Fauzi', 'ahmad@student.school.id', 'student', 'active', '2025-11-14 08:32:23', '2025-11-14 08:32:23'),
(5, 'rina', '$2y$10$hdzhUbMuv5jDfJzEm7UjGOiTHgtnmhjmCC6b.Yf7aZYpjeRIIo9/C', 'Rina Wati', 'rina@student.school.id', 'student', 'active', '2025-11-14 08:32:23', '2025-11-14 08:32:23'),
(6, 'Shiori', '$2y$10$GIfDGTYxuipy6IlVxXVgqe0r/m3CjXC6aZWJ6owKEA1inylNTub1u', 'Shiori Novella', 'shiorinovella@school.id', 'teacher', 'active', '2025-11-17 16:12:47', '2025-11-17 16:12:47');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_exam_details`
-- (See below for the actual view)
--
CREATE TABLE `vw_exam_details` (
`id_exam` int(11)
,`title` varchar(255)
,`duration` int(11)
,`start_time` datetime
,`end_time` datetime
,`is_active` tinyint(1)
,`teacher_name` varchar(100)
,`subject_name` varchar(100)
,`class_name` varchar(50)
,`grade_level` int(11)
,`total_questions` bigint(21)
,`total_points` double
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_classes`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_classes` (
`id_user` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`id_class` int(11)
,`class_name` varchar(50)
,`grade_level` int(11)
,`academic_year` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_teaching_schedule`
-- (See below for the actual view)
--
CREATE TABLE `vw_teaching_schedule` (
`teacher_id` int(11)
,`teacher_name` varchar(100)
,`id_subject` int(11)
,`subject_name` varchar(100)
,`id_class` int(11)
,`class_name` varchar(50)
,`grade_level` int(11)
,`id_assignment` int(11)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_exam_details`
--
DROP TABLE IF EXISTS `vw_exam_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_exam_details`  AS SELECT `e`.`id_exam` AS `id_exam`, `e`.`title` AS `title`, `e`.`duration` AS `duration`, `e`.`start_time` AS `start_time`, `e`.`end_time` AS `end_time`, `e`.`is_active` AS `is_active`, `t`.`full_name` AS `teacher_name`, `s`.`subject_name` AS `subject_name`, `c`.`class_name` AS `class_name`, `c`.`grade_level` AS `grade_level`, count(`q`.`id_question`) AS `total_questions`, sum(`q`.`points`) AS `total_points` FROM (((((`tbl_exams` `e` join `tbl_teaching_assignments` `ta` on(`e`.`assignment_id` = `ta`.`id_assignment`)) join `tbl_users` `t` on(`ta`.`teacher_id` = `t`.`id_user`)) join `tbl_subjects` `s` on(`ta`.`subject_id` = `s`.`id_subject`)) join `tbl_classes` `c` on(`ta`.`class_id` = `c`.`id_class`)) left join `tbl_questions` `q` on(`e`.`id_exam` = `q`.`exam_id`)) GROUP BY `e`.`id_exam` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_classes`
--
DROP TABLE IF EXISTS `vw_student_classes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_classes`  AS SELECT `u`.`id_user` AS `id_user`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, `c`.`id_class` AS `id_class`, `c`.`class_name` AS `class_name`, `c`.`grade_level` AS `grade_level`, `c`.`academic_year` AS `academic_year` FROM ((`tbl_users` `u` join `tbl_class_enrollments` `e` on(`u`.`id_user` = `e`.`student_id`)) join `tbl_classes` `c` on(`e`.`class_id` = `c`.`id_class`)) WHERE `u`.`role` = 'student' AND `u`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_teaching_schedule`
--
DROP TABLE IF EXISTS `vw_teaching_schedule`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_teaching_schedule`  AS SELECT `t`.`id_user` AS `teacher_id`, `t`.`full_name` AS `teacher_name`, `s`.`id_subject` AS `id_subject`, `s`.`subject_name` AS `subject_name`, `c`.`id_class` AS `id_class`, `c`.`class_name` AS `class_name`, `c`.`grade_level` AS `grade_level`, `ta`.`id_assignment` AS `id_assignment` FROM (((`tbl_teaching_assignments` `ta` join `tbl_users` `t` on(`ta`.`teacher_id` = `t`.`id_user`)) join `tbl_subjects` `s` on(`ta`.`subject_id` = `s`.`id_subject`)) join `tbl_classes` `c` on(`ta`.`class_id` = `c`.`id_class`)) WHERE `t`.`status` = 'active' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_answers`
--
ALTER TABLE `tbl_answers`
  ADD PRIMARY KEY (`id_answer`),
  ADD UNIQUE KEY `unique_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `idx_question` (`question_id`),
  ADD KEY `idx_graded_by` (`graded_by`);

--
-- Indexes for table `tbl_classes`
--
ALTER TABLE `tbl_classes`
  ADD PRIMARY KEY (`id_class`),
  ADD UNIQUE KEY `unique_class_year` (`class_name`,`academic_year`),
  ADD KEY `idx_grade_year` (`grade_level`,`academic_year`);

--
-- Indexes for table `tbl_class_enrollments`
--
ALTER TABLE `tbl_class_enrollments`
  ADD PRIMARY KEY (`id_enrollment`),
  ADD UNIQUE KEY `unique_student_class` (`student_id`,`class_id`),
  ADD KEY `idx_class` (`class_id`);

--
-- Indexes for table `tbl_exams`
--
ALTER TABLE `tbl_exams`
  ADD PRIMARY KEY (`id_exam`),
  ADD KEY `idx_assignment` (`assignment_id`),
  ADD KEY `idx_active_time` (`is_active`,`start_time`,`end_time`);

--
-- Indexes for table `tbl_exam_attempts`
--
ALTER TABLE `tbl_exam_attempts`
  ADD PRIMARY KEY (`id_attempt`),
  ADD KEY `idx_exam_student` (`exam_id`,`student_id`),
  ADD KEY `idx_student_status` (`student_id`,`status`);

--
-- Indexes for table `tbl_questions`
--
ALTER TABLE `tbl_questions`
  ADD PRIMARY KEY (`id_question`),
  ADD KEY `idx_exam_order` (`exam_id`,`display_order`);

--
-- Indexes for table `tbl_scores`
--
ALTER TABLE `tbl_scores`
  ADD PRIMARY KEY (`id_score`),
  ADD UNIQUE KEY `unique_attempt` (`attempt_id`);

--
-- Indexes for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  ADD PRIMARY KEY (`id_subject`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `tbl_teaching_assignments`
--
ALTER TABLE `tbl_teaching_assignments`
  ADD PRIMARY KEY (`id_assignment`),
  ADD UNIQUE KEY `unique_teacher_subject_class` (`teacher_id`,`subject_id`,`class_id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_class` (`class_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role_status` (`role`,`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_answers`
--
ALTER TABLE `tbl_answers`
  MODIFY `id_answer` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_classes`
--
ALTER TABLE `tbl_classes`
  MODIFY `id_class` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_class_enrollments`
--
ALTER TABLE `tbl_class_enrollments`
  MODIFY `id_enrollment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_exams`
--
ALTER TABLE `tbl_exams`
  MODIFY `id_exam` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_exam_attempts`
--
ALTER TABLE `tbl_exam_attempts`
  MODIFY `id_attempt` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_questions`
--
ALTER TABLE `tbl_questions`
  MODIFY `id_question` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_scores`
--
ALTER TABLE `tbl_scores`
  MODIFY `id_score` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  MODIFY `id_subject` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_teaching_assignments`
--
ALTER TABLE `tbl_teaching_assignments`
  MODIFY `id_assignment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_answers`
--
ALTER TABLE `tbl_answers`
  ADD CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `tbl_exam_attempts` (`id_attempt`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answer_grader` FOREIGN KEY (`graded_by`) REFERENCES `tbl_users` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `tbl_questions` (`id_question`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_class_enrollments`
--
ALTER TABLE `tbl_class_enrollments`
  ADD CONSTRAINT `fk_enrollment_class` FOREIGN KEY (`class_id`) REFERENCES `tbl_classes` (`id_class`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `tbl_users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_exams`
--
ALTER TABLE `tbl_exams`
  ADD CONSTRAINT `fk_exam_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `tbl_teaching_assignments` (`id_assignment`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_exam_attempts`
--
ALTER TABLE `tbl_exam_attempts`
  ADD CONSTRAINT `fk_attempt_exam` FOREIGN KEY (`exam_id`) REFERENCES `tbl_exams` (`id_exam`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `tbl_users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_questions`
--
ALTER TABLE `tbl_questions`
  ADD CONSTRAINT `fk_question_exam` FOREIGN KEY (`exam_id`) REFERENCES `tbl_exams` (`id_exam`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_scores`
--
ALTER TABLE `tbl_scores`
  ADD CONSTRAINT `fk_score_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `tbl_exam_attempts` (`id_attempt`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_teaching_assignments`
--
ALTER TABLE `tbl_teaching_assignments`
  ADD CONSTRAINT `fk_teaching_class` FOREIGN KEY (`class_id`) REFERENCES `tbl_classes` (`id_class`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teaching_subject` FOREIGN KEY (`subject_id`) REFERENCES `tbl_subjects` (`id_subject`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teaching_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `tbl_users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
