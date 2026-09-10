-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS `plrs` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `plrs`;

-- 2. Users Table (Handles both Students and Faculty)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'faculty') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB;

-- 3. Student Requests Table (Linked to User ID)
CREATE TABLE IF NOT EXISTS `student_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `weak_topic` VARCHAR(255),
    `learning_goal` TEXT,
    `study_hours` INT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_requests_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Resources Table (Linked to both the Request and the Faculty who uploaded it)
CREATE TABLE IF NOT EXISTS `resources` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT UNSIGNED,
    `faculty_id` INT UNSIGNED,
    `faculty_name` VARCHAR(255), -- Optional: Consider fetching this from 'users' via JOIN instead
    `subject` VARCHAR(255),
    `sub_topic` VARCHAR(255),
    `title` VARCHAR(255),
    `resource_type` ENUM('pdf', 'youtube'),
    `resource_link` TEXT,
    `file_path` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_resources_request` FOREIGN KEY (`request_id`) 
        REFERENCES `student_requests`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resources_faculty` FOREIGN KEY (`faculty_id`) 
        REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Feedback Table (Communication between Student and Faculty)
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `request_id` INT UNSIGNED NOT NULL,
    `faculty_name` VARCHAR(255),
    `feedback` TEXT,
    `faculty_reply` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_feedback_request` FOREIGN KEY (`request_id`) 
        REFERENCES `student_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Performance Table (Tracking progress)
CREATE TABLE IF NOT EXISTS `performance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `final_score` INT,
    `study_hours` INT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_performance_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;