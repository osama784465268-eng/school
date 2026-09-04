

-- Disabling foreign key checks to safely recreate tables if running multiple times
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `grades`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (تسجيل الدخول والأدوار)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` ENUM('admin', 'teacher', 'student') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Teachers Table (بيانات المعلمين)
CREATE TABLE `teachers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `specialization` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `hire_date` DATE NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Students Table (بيانات الطلاب)
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `gender` ENUM('male', 'female') NOT NULL,
    `dob` DATE NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `enrollment_date` DATE NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Subjects Table (المواد الدراسية)
CREATE TABLE `subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `subject_name` VARCHAR(100) NOT NULL UNIQUE,
    `subject_code` VARCHAR(20) NOT NULL UNIQUE,
    `teacher_id` INT DEFAULT NULL,
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Grades Table (رصد الدرجات)
CREATE TABLE `grades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `exam_type` VARCHAR(50) NOT NULL, -- 'Midterm', 'Final', 'Activities'
    `grade` DECIMAL(5, 2) NOT NULL,
    `grade_date` DATE NOT NULL,
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Attendance Table (الحضور والغياب اليومي)
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_student_date` (`student_id`, `attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data (بيانات تجريبية افتراضية)
-- --------------------------------------------------------

-- الباسورد الافتراضي لجميع الحسابات التجريبية هو 'password123'
-- الهاش الفعلي لـ 'password123' هو: $2y$10$wOdf6Z8gQ58eUjXQ0yK1eexJ0G1gqSmN2x.M89T06I1bBqXy.Cqeu

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES
(1, 'admin', '$2y$10$wOdf6Z8gQ58eUjXQ0yK1eexJ0G1gqSmN2x.M89T06I1bBqXy.Cqeu', 'مدير النظام العام', 'admin@school.com', 'admin'),
(2, 'teacher1', '$2y$10$wOdf6Z8gQ58eUjXQ0yK1eexJ0G1gqSmN2x.M89T06I1bBqXy.Cqeu', 'أ. أحمد علي المصعبي', 'ahmed@school.com', 'teacher'),
(3, 'teacher2', '$2y$10$wOdf6Z8gQ58eUjXQ0yK1eexJ0G1gqSmN2x.M89T06I1bBqXy.Cqeu', 'أ. فاطمة عمر عثمان', 'fatima@school.com', 'teacher'),
(4, 'student1', '$2y$10$wOdf6Z8gQ58eUjXQ0yK1eexJ0G1gqSmN2x.M89T06I1bBqXy.Cqeu', 'خالد محمد اليزيدي', 'khaled@school.com', 'student'),
(5, 'student2', '$2y$10$wOdf6Z8gQ58eUjXQ0yK1eexJ0G1gqSmN2x.M89T06I1bBqXy.Cqeu', 'آلاء فضل الحميري', 'alaa@school.com', 'student');

INSERT INTO `teachers` (`id`, `user_id`, `specialization`, `phone`, `address`, `hire_date`) VALUES
(1, 2, 'الرياضيات', '777112233', 'صنعاء - الدائري', '2023-09-01'),
(2, 3, 'اللغة الإنجليزية', '777445566', 'صنعاء - حدة', '2024-01-15');

INSERT INTO `students` (`id`, `user_id`, `gender`, `dob`, `phone`, `address`, `enrollment_date`) VALUES
(1, 4, 'male', '2010-05-12', '771234567', 'صنعاء - الأصبحي', '2025-09-01'),
(2, 5, 'female', '2011-08-22', '773210987', 'صنعاء - شيرتون', '2025-09-01');

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `teacher_id`) VALUES
(1, 'الرياضيات العامة', 'MATH-101', 1),
(2, 'الجبر والهندسة', 'MATH-102', 1),
(3, 'اللغة الإنجليزية الأساسية', 'ENG-101', 2);

INSERT INTO `grades` (`student_id`, `subject_id`, `exam_type`, `grade`, `grade_date`) VALUES
(1, 1, 'Midterm', 85.50, '2026-04-10'),
(1, 1, 'Final', 90.00, '2026-06-15'),
(2, 1, 'Midterm', 92.00, '2026-04-10'),
(2, 3, 'Midterm', 88.00, '2026-04-12');

INSERT INTO `attendance` (`student_id`, `attendance_date`, `status`, `remarks`) VALUES
(1, '2026-07-10', 'present', 'حضور بالوقت المحدد'),
(2, '2026-07-10', 'present', 'حضور بالوقت المحدد'),
(1, '2026-07-11', 'absent', 'غائب بدون عذر'),
(2, '2026-07-11', 'late', 'تأخر 15 دقيقة');
