-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 07, 2026 at 02:30 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `core_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `asset_type` enum('VIDEO','PDF','IMAGE','DOCUMENT','OTHER') NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `storage_type` enum('URL','LOCAL','S3','OTHER') NOT NULL DEFAULT 'URL',
  `resource_url` varchar(1024) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`asset_id`, `title`, `description`, `asset_type`, `category`, `file_name`, `file_size_bytes`, `storage_type`, `resource_url`, `is_public`, `is_active`, `uploaded_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 'How to Measure Blood Pressure', NULL, 'VIDEO', 'hypertension', NULL, NULL, 'URL', 'https://example.org/videos/bp_measurement.mp4', 1, 1, NULL, '2026-02-04 20:12:21', '2026-02-04 20:12:21'),
(2, 'Nurse Triage Checklist', NULL, 'PDF', 'triage', NULL, NULL, 'URL', '/internal/training/triage_checklist.pdf', 0, 1, NULL, '2026-02-04 20:12:29', '2026-02-04 20:12:29');

-- --------------------------------------------------------

--
-- Table structure for table `case_sheets`
--

CREATE TABLE `case_sheets` (
  `case_sheet_id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `visit_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `visit_type` enum('CAMP','CLINIC','FOLLOW_UP','EMERGENCY','OTHER') NOT NULL DEFAULT 'CAMP',
  `created_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `assigned_doctor_user_id` int(10) UNSIGNED DEFAULT NULL,
  `chief_complaint` varchar(255) DEFAULT NULL,
  `history_present_illness` text DEFAULT NULL,
  `vitals_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vitals_json`)),
  `exam_notes` text DEFAULT NULL,
  `assessment` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `plan_notes` text DEFAULT NULL,
  `prescriptions` text DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `referral_to` varchar(255) DEFAULT NULL,
  `referral_reason` text DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` datetime DEFAULT NULL,
  `closed_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `closure_type` enum('DISCHARGED','FOLLOW_UP','REFERRAL','PENDING') NOT NULL DEFAULT 'PENDING',
  `disposition` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `event_type` enum('MEDICAL_CAMP','EDUCATIONAL_SEMINAR','TRAINING','MEETING','OTHER') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state_province` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `status` enum('DRAFT','SCHEDULED','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_type`, `title`, `description`, `start_datetime`, `end_datetime`, `location_name`, `address`, `city`, `state_province`, `postal_code`, `status`, `is_active`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 'MEDICAL_CAMP', 'Health Camp - Community Center', NULL, '2026-02-10 09:00:00', '2026-02-10 16:00:00', 'Ward 12 Community Center', NULL, 'Pune', 'Maharashtra', NULL, 'SCHEDULED', 1, NULL, '2026-02-04 20:14:30', '2026-02-04 20:14:30'),
(2, 'EDUCATIONAL_SEMINAR', 'Diabetes Prevention Seminar', NULL, '2026-02-12 18:00:00', NULL, 'Library Meeting Room A', NULL, 'St. Paul', 'MN', NULL, 'SCHEDULED', 1, NULL, '2026-02-04 20:14:36', '2026-02-04 20:14:36');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `sender_type` enum('PATIENT','STAFF') NOT NULL,
  `sender_user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL if sent by patient',
  `subject` varchar(255) DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `case_sheet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(10) UNSIGNED NOT NULL,
  `patient_code` char(11) NOT NULL,
  `first_seen_date` date NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) DEFAULT NULL,
  `sex` enum('MALE','FEMALE','OTHER','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `date_of_birth` date DEFAULT NULL,
  `age_years` smallint(5) UNSIGNED DEFAULT NULL,
  `phone_e164` varchar(20) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `address_line1` varchar(120) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state_province` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `allergies` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(120) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `username` varchar(60) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `patient_code`, `first_seen_date`, `first_name`, `last_name`, `sex`, `date_of_birth`, `age_years`, `phone_e164`, `email`, `address_line1`, `city`, `state_province`, `postal_code`, `blood_group`, `allergies`, `emergency_contact_name`, `emergency_contact_phone`, `username`, `password_hash`, `last_login_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '20260204000', '2026-02-04', 'Rahul', 'Sharma', 'MALE', NULL, NULL, '+919876543210', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-04 20:07:09', '2026-02-04 20:07:09'),
(2, '20260206000', '2026-02-06', 'Test', 'Patient1', 'MALE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-06 19:26:14', '2026-02-06 19:26:14'),
(3, '20260206001', '2026-02-06', 'Test', 'Patient2', 'MALE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-06 19:26:14', '2026-02-06 19:26:14'),
(4, '20260206002', '2026-02-06', 'Test', 'Patient3', 'MALE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-02-06 19:26:14', '2026-02-06 19:26:14');

--
-- Triggers `patients`
--
DELIMITER $$
CREATE TRIGGER `trg_patients_before_insert` BEFORE INSERT ON `patients` FOR EACH ROW BEGIN
  DECLARE v_date DATE;
  DECLARE v_n   INT UNSIGNED;

  
  SET v_date = IFNULL(NEW.first_seen_date, CURDATE());
  SET NEW.first_seen_date = v_date;

  
  INSERT INTO patient_daily_sequence (seq_date, last_n)
  VALUES (v_date, 0)
  ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1);

  SET v_n = LAST_INSERT_ID();

  
  SET NEW.patient_code = CONCAT(DATE_FORMAT(v_date, '%Y%m%d'), LPAD(v_n, 3, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_daily_sequence`
--

CREATE TABLE `patient_daily_sequence` (
  `seq_date` date NOT NULL,
  `last_n` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient_daily_sequence`
--

INSERT INTO `patient_daily_sequence` (`seq_date`, `last_n`) VALUES
('2026-02-04', 0),
('2026-02-06', 2);

-- --------------------------------------------------------

--
-- Table structure for table `patient_feedback`
--

CREATE TABLE `patient_feedback` (
  `feedback_id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `related_user_id` int(10) UNSIGNED DEFAULT NULL,
  `feedback_type` enum('POSITIVE','COMPLAINT','SUGGESTION') NOT NULL,
  `rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `feedback_text` text DEFAULT NULL,
  `status` enum('NEW','REVIEWED','ACTIONED','CLOSED') NOT NULL DEFAULT 'NEW',
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone_e164` varchar(20) DEFAULT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('SUPER_ADMIN','ADMIN','DOCTOR','NURSE','DATA_ENTRY_OPERATOR') NOT NULL DEFAULT 'DATA_ENTRY_OPERATOR',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `phone_e164`, `username`, `password_hash`, `role`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Andrew', 'Hawkinson', 'hawk@d3s3.com', NULL, 'Hawkinson', '$2y$10$lO.z3dxebDVH.eVN9UATbO4WmZXs9gOw8X23BGpEKffE1aSaAmXF.', 'SUPER_ADMIN', 1, '2026-02-04 20:47:10', '2026-02-04 14:41:37', '2026-02-06 18:32:33'),
(2, 'Admin', 'Account', 'admin1@d3s3.com', NULL, 'admin1', '$2y$10$wH/SJPGCqIxx1wmYUrPzCe7i6JOgeFBk.6a.xrQ4Nl.MuEEUxoASG', 'ADMIN', 0, NULL, '2026-02-04 14:49:41', '2026-02-06 18:32:55'),
(3, 'Gary', 'Marks', 'g.marks@d3s3.com', NULL, 'gmarks', '$2y$10$SW6AIjBtzZU8SkQXsy0EOO84g6Ffe5XlEtKYmm/yGm0QKZUWLxCAa', 'SUPER_ADMIN', 1, NULL, '2026-02-04 18:26:01', '2026-02-06 18:34:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`asset_id`),
  ADD KEY `idx_assets_type` (`asset_type`),
  ADD KEY `idx_assets_public` (`is_public`),
  ADD KEY `idx_assets_category` (`category`);

--
-- Indexes for table `case_sheets`
--
ALTER TABLE `case_sheets`
  ADD PRIMARY KEY (`case_sheet_id`),
  ADD KEY `idx_case_sheets_patient` (`patient_id`,`visit_datetime`),
  ADD KEY `idx_case_sheets_closed` (`is_closed`,`closed_at`),
  ADD KEY `idx_case_sheets_visit_date` (`visit_datetime`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_events_type` (`event_type`),
  ADD KEY `idx_events_status` (`status`),
  ADD KEY `idx_events_start` (`start_datetime`),
  ADD KEY `idx_events_city` (`city`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_messages_patient` (`patient_id`,`sent_at`),
  ADD KEY `idx_messages_unread` (`is_read`,`sent_at`),
  ADD KEY `idx_messages_case` (`case_sheet_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `uq_patients_patient_code` (`patient_code`),
  ADD UNIQUE KEY `uq_patients_username` (`username`),
  ADD UNIQUE KEY `uq_patients_email` (`email`),
  ADD KEY `idx_patients_name` (`last_name`,`first_name`),
  ADD KEY `idx_patients_phone` (`phone_e164`),
  ADD KEY `idx_patients_first_seen` (`first_seen_date`);

--
-- Indexes for table `patient_daily_sequence`
--
ALTER TABLE `patient_daily_sequence`
  ADD PRIMARY KEY (`seq_date`);

--
-- Indexes for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `idx_feedback_patient` (`patient_id`,`created_at`),
  ADD KEY `idx_feedback_status` (`status`),
  ADD KEY `idx_feedback_type` (`feedback_type`),
  ADD KEY `idx_feedback_related_user` (`related_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `asset_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `case_sheets`
--
ALTER TABLE `case_sheets`
  MODIFY `case_sheet_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  MODIFY `feedback_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `case_sheets`
--
ALTER TABLE `case_sheets`
  ADD CONSTRAINT `fk_case_sheets_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_case` FOREIGN KEY (`case_sheet_id`) REFERENCES `case_sheets` (`case_sheet_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE;

--
-- Constraints for table `patient_feedback`
--
ALTER TABLE `patient_feedback`
  ADD CONSTRAINT `fk_feedback_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
