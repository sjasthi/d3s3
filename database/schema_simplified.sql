-- ============================================================
-- D3S3 CareSystem - Simplified Database Schema
-- Date: 2026-02-06
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Table 1: users (Staff/Employees)
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(190) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_e164` varchar(20) DEFAULT NULL,
  `role` enum('SUPER_ADMIN','ADMIN','DOCTOR','NURSE','DATA_ENTRY_OPERATOR') NOT NULL DEFAULT 'DATA_ENTRY_OPERATOR',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Staff/employee accounts who use the system';

-- ============================================================
-- Table 2: patients (merged with patient_accounts)
-- ============================================================
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `patient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_code` char(11) NOT NULL COMMENT 'Format: YYYYMMDDNNN - auto-generated on first insert',
  `first_seen_date` date NOT NULL,

  -- Demographics
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) DEFAULT NULL,
  `sex` enum('MALE','FEMALE','OTHER','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `date_of_birth` date DEFAULT NULL,
  `age_years` smallint(5) unsigned DEFAULT NULL,

  -- Contact
  `phone_e164` varchar(20) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,

  -- Address
  `address_line1` varchar(120) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state_province` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'IN',

  -- Medical
  `blood_group` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `emergency_contact_name` varchar(120) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,

  -- Patient Portal Account (optional - nullable for patients without logins)
  `username` varchar(60) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,

  -- Status
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `uq_patients_code` (`patient_code`),
  UNIQUE KEY `uq_patients_username` (`username`),
  UNIQUE KEY `uq_patients_email` (`email`),
  KEY `idx_patients_name` (`last_name`, `first_name`),
  KEY `idx_patients_phone` (`phone_e164`),
  KEY `idx_patients_first_seen` (`first_seen_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Patient demographics and optional portal account';

-- ============================================================
-- Table 2a: patient_daily_sequence (helper for patient_code)
-- ============================================================
DROP TABLE IF EXISTS `patient_daily_sequence`;
CREATE TABLE `patient_daily_sequence` (
  `seq_date` date NOT NULL,
  `last_n` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`seq_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Helper table to generate daily patient sequence numbers';

-- ============================================================
-- Trigger: Auto-generate patient_code on insert
-- ============================================================
DROP TRIGGER IF EXISTS `trg_patients_before_insert`;

DELIMITER ;;
CREATE TRIGGER `trg_patients_before_insert`
BEFORE INSERT ON `patients`
FOR EACH ROW
BEGIN
  DECLARE v_date DATE;
  DECLARE v_seq INT UNSIGNED;

  -- Use provided first_seen_date or default to today
  SET v_date = IFNULL(NEW.first_seen_date, CURDATE());
  SET NEW.first_seen_date = v_date;

  -- Get next sequence number for this date
  INSERT INTO patient_daily_sequence (seq_date, last_n)
  VALUES (v_date, 1)
  ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1);

  SET v_seq = LAST_INSERT_ID();

  -- Build patient_code: YYYYMMDDNNN (e.g., 20260226005)
  SET NEW.patient_code = CONCAT(
    DATE_FORMAT(v_date, '%Y%m%d'),
    LPAD(v_seq, 3, '0')
  );
END;;
DELIMITER ;

-- ============================================================
-- Table 3: case_sheets (merged with case_closures)
-- ============================================================
DROP TABLE IF EXISTS `case_sheets`;
CREATE TABLE `case_sheets` (
  `case_sheet_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `visit_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `visit_type` enum('CAMP','CLINIC','FOLLOW_UP','EMERGENCY','OTHER') NOT NULL DEFAULT 'CAMP',

  -- Staff assignments
  `created_by_user_id` int(10) unsigned DEFAULT NULL,
  `assigned_doctor_user_id` int(10) unsigned DEFAULT NULL,

  -- Medical Assessment
  `chief_complaint` varchar(255) DEFAULT NULL,
  `history_present_illness` text DEFAULT NULL,
  `vitals_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vitals_json`)),
  `exam_notes` text DEFAULT NULL,
  `assessment` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `plan_notes` text DEFAULT NULL,

  -- Closure/Discharge Information (nullable until case is closed)
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` datetime DEFAULT NULL,
  `closed_by_user_id` int(10) unsigned DEFAULT NULL,
  `closure_type` enum('DISCHARGED','FOLLOW_UP','REFERRAL','PENDING') NOT NULL DEFAULT 'PENDING',
  `disposition` varchar(255) DEFAULT NULL COMMENT 'Brief discharge summary',
  `prescriptions` text DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `referral_to` varchar(255) DEFAULT NULL,
  `referral_reason` text DEFAULT NULL,

  -- Metadata
  `is_locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Lock to prevent edits after finalization',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`case_sheet_id`),
  KEY `idx_case_sheets_patient` (`patient_id`, `visit_datetime`),
  KEY `idx_case_sheets_closed` (`is_closed`, `closed_at`),
  KEY `idx_case_sheets_visit_date` (`visit_datetime`),
  CONSTRAINT `fk_case_sheets_patient` FOREIGN KEY (`patient_id`)
    REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Medical visit records including closure/discharge info';

-- ============================================================
-- Table 4: events
-- ============================================================
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` enum('MEDICAL_CAMP','EDUCATIONAL_SEMINAR','TRAINING','MEETING','OTHER') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,

  -- Location
  `location_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state_province` varchar(80) DEFAULT NULL,

  -- Status
  `status` enum('DRAFT','SCHEDULED','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`event_id`),
  KEY `idx_events_type` (`event_type`),
  KEY `idx_events_status` (`status`),
  KEY `idx_events_start` (`start_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Scheduled events like medical camps and seminars';

-- ============================================================
-- Table 5: messages (simplified - direct messaging only)
-- ============================================================
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,

  -- Sender (either patient or staff)
  `sender_type` enum('PATIENT','STAFF') NOT NULL,
  `sender_user_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL if sent by patient',

  -- Message content
  `subject` varchar(255) DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,

  -- Optional: link to related case
  `case_sheet_id` bigint(20) unsigned DEFAULT NULL,

  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),

  PRIMARY KEY (`message_id`),
  KEY `idx_messages_patient` (`patient_id`, `sent_at`),
  KEY `idx_messages_unread` (`is_read`, `sent_at`),
  KEY `idx_messages_case` (`case_sheet_id`),
  CONSTRAINT `fk_messages_patient` FOREIGN KEY (`patient_id`)
    REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_case` FOREIGN KEY (`case_sheet_id`)
    REFERENCES `case_sheets` (`case_sheet_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Direct messages between patients and staff';

-- ============================================================
-- Table 6: patient_feedback
-- ============================================================
DROP TABLE IF EXISTS `patient_feedback`;
CREATE TABLE `patient_feedback` (
  `feedback_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `case_sheet_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Optional link to specific visit',
  `related_user_id` int(10) unsigned DEFAULT NULL COMMENT 'Staff member this feedback is about',

  -- Feedback content
  `feedback_type` enum('POSITIVE','COMPLAINT','SUGGESTION') NOT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL COMMENT '1-5 star rating',
  `feedback_text` text DEFAULT NULL,

  -- Admin tracking
  `status` enum('NEW','REVIEWED','RESOLVED','CLOSED') NOT NULL DEFAULT 'NEW',
  `admin_notes` text DEFAULT NULL,

  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`feedback_id`),
  KEY `idx_feedback_patient` (`patient_id`, `created_at`),
  KEY `idx_feedback_status` (`status`),
  KEY `idx_feedback_type` (`feedback_type`),
  KEY `idx_feedback_user` (`related_user_id`),
  CONSTRAINT `fk_feedback_patient` FOREIGN KEY (`patient_id`)
    REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_case` FOREIGN KEY (`case_sheet_id`)
    REFERENCES `case_sheets` (`case_sheet_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Patient feedback, complaints, and suggestions';

-- ============================================================
-- Table 7: assets
-- ============================================================
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `asset_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `asset_type` enum('VIDEO','PDF','IMAGE','DOCUMENT','OTHER') NOT NULL,
  `category` varchar(80) DEFAULT NULL,

  -- File info
  `file_name` varchar(255) DEFAULT NULL,
  `file_size_bytes` bigint(20) unsigned DEFAULT NULL,
  `storage_type` enum('URL','LOCAL','S3') NOT NULL DEFAULT 'LOCAL',
  `resource_url` varchar(1024) DEFAULT NULL,

  -- Access control
  `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=public, 0=staff only',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by_user_id` int(10) unsigned DEFAULT NULL,

  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`asset_id`),
  KEY `idx_assets_type` (`asset_type`),
  KEY `idx_assets_category` (`category`),
  KEY `idx_assets_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Educational materials, documents, and media files';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Sample Data
-- ============================================================

-- Add a test user
INSERT INTO `users` (`first_name`, `last_name`, `email`, `username`, `password_hash`, `role`)
VALUES ('Admin', 'User', 'admin@d3s3.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SUPER_ADMIN')
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Add a test patient (patient_code will be auto-generated by trigger)
INSERT INTO `patients` (`first_name`, `last_name`, `sex`, `phone_e164`, `first_seen_date`)
VALUES ('John', 'Doe', 'MALE', '+919876543210', '2026-02-06')
ON DUPLICATE KEY UPDATE patient_id=patient_id;
