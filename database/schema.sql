-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: core_app
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `asset_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_uuid` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `asset_type` enum('VIDEO','AUDIO','PDF','BROCHURE','IMAGE','OTHER') NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `language_code` char(5) NOT NULL DEFAULT 'en',
  `file_name` varchar(255) DEFAULT NULL,
  `file_format` varchar(20) DEFAULT NULL,
  `file_size_bytes` bigint(20) unsigned DEFAULT NULL,
  `duration_seconds` int(10) unsigned DEFAULT NULL,
  `page_count` int(10) unsigned DEFAULT NULL,
  `storage_type` enum('URL','LOCAL','S3','OTHER') NOT NULL DEFAULT 'URL',
  `resource_url` varchar(1024) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by_employee_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`asset_id`),
  UNIQUE KEY `uq_assets_uuid` (`asset_uuid`),
  KEY `idx_assets_type` (`asset_type`),
  KEY `idx_assets_public` (`is_public`),
  KEY `idx_assets_category` (`category`),
  KEY `idx_assets_language` (`language_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `case_closures`
--

DROP TABLE IF EXISTS `case_closures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_closures` (
  `case_closure_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_sheet_id` bigint(20) unsigned NOT NULL,
  `closure_type` enum('ON_THE_SPOT_DISPOSITION','MEDICAL_ADVICE_FOLLOW_UP','REFERRAL','PENDING') NOT NULL DEFAULT 'PENDING',
  `closure_summary` varchar(500) DEFAULT NULL,
  `closure_notes` text DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `closed_by_employee_id` int(10) unsigned DEFAULT NULL,
  `advice_text` text DEFAULT NULL,
  `prescriptions_text` text DEFAULT NULL,
  `follow_up_needed` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `referral_to_name` varchar(255) DEFAULT NULL,
  `referral_to_type` enum('SPECIALIST','HOSPITAL','CLINIC','LAB','OTHER') DEFAULT NULL,
  `referral_reason` text DEFAULT NULL,
  `referral_contact` varchar(255) DEFAULT NULL,
  `referral_status` enum('RECOMMENDED','SCHEDULED','COMPLETED','DECLINED','UNKNOWN') DEFAULT NULL,
  `disposition_type` enum('DISCHARGED','OBSERVED','TREATED_ON_SITE','TRANSFERRED','OTHER') DEFAULT NULL,
  `pending_reason` varchar(255) DEFAULT NULL,
  `pending_next_step` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`case_closure_id`),
  UNIQUE KEY `uq_case_closures_case_sheet` (`case_sheet_id`),
  KEY `idx_case_closures_type` (`closure_type`),
  KEY `idx_case_closures_followup` (`follow_up_needed`,`follow_up_date`),
  KEY `idx_case_closures_closed_at` (`closed_at`),
  CONSTRAINT `fk_case_closures_case_sheet` FOREIGN KEY (`case_sheet_id`) REFERENCES `case_sheets` (`case_sheet_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `case_sheets`
--

DROP TABLE IF EXISTS `case_sheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_sheets` (
  `case_sheet_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `visit_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `visit_type` enum('CAMP','CLINIC','FOLLOW_UP','OTHER') NOT NULL DEFAULT 'CAMP',
  `created_by_employee_id` int(10) unsigned DEFAULT NULL,
  `assigned_doctor_employee_id` int(10) unsigned DEFAULT NULL,
  `chief_complaint` varchar(255) DEFAULT NULL,
  `history_present_illness` text DEFAULT NULL,
  `vitals_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vitals_json`)),
  `exam_notes` text DEFAULT NULL,
  `assessment` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `plan_notes` text DEFAULT NULL,
  `prescriptions` text DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`case_sheet_id`),
  KEY `idx_case_sheets_patient` (`patient_id`,`visit_datetime`),
  CONSTRAINT `fk_case_sheets_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_case_sheets_after_insert_create_closure
AFTER INSERT ON case_sheets
FOR EACH ROW
BEGIN
  INSERT INTO case_closures (case_sheet_id, closure_type)
  VALUES (NEW.case_sheet_id, 'PENDING');
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `employee_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `display_name` varchar(140) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone_e164` varchar(20) DEFAULT NULL,
  `whatsapp_e164` varchar(20) DEFAULT NULL,
  `address_line1` varchar(120) DEFAULT NULL,
  `address_line2` varchar(120) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state_province` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'IN',
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('SUPER_ADMIN','ADMIN','DOCTOR','PARAMEDIC','TRIAGE_NURSE','REGISTERED_NURSE','DATA_ENTRY_OPERATOR') NOT NULL DEFAULT 'DATA_ENTRY_OPERATOR',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `uq_employees_email` (`email`),
  UNIQUE KEY `uq_employees_username` (`username`),
  KEY `idx_employees_role` (`role`),
  KEY `idx_employees_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_uuid` char(36) NOT NULL,
  `event_type` enum('MEDICAL_CAMP','EDUCATIONAL_SEMINAR','TRAINING','MEETING','OTHER') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'Asia/Kolkata',
  `location_name` varchar(255) DEFAULT NULL,
  `address_line1` varchar(120) DEFAULT NULL,
  `address_line2` varchar(120) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state_province` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'IN',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('DRAFT','SCHEDULED','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_employee_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `uq_events_uuid` (`event_uuid`),
  KEY `idx_events_type` (`event_type`),
  KEY `idx_events_status` (`status`),
  KEY `idx_events_start` (`start_datetime`),
  KEY `idx_events_city` (`city`),
  KEY `idx_events_country` (`country_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message_threads`
--

DROP TABLE IF EXISTS `message_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_threads` (
  `thread_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_uuid` char(36) NOT NULL,
  `patient_id` int(10) unsigned NOT NULL,
  `created_by_patient_account_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `status` enum('OPEN','IN_PROGRESS','RESOLVED','CLOSED') NOT NULL DEFAULT 'OPEN',
  `priority` enum('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  `assigned_employee_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`thread_id`),
  UNIQUE KEY `uq_message_threads_uuid` (`thread_uuid`),
  KEY `idx_threads_patient` (`patient_id`,`status`),
  KEY `idx_threads_assigned` (`assigned_employee_id`,`status`),
  KEY `fk_threads_created_by_patient_account` (`created_by_patient_account_id`),
  CONSTRAINT `fk_threads_created_by_patient_account` FOREIGN KEY (`created_by_patient_account_id`) REFERENCES `patient_accounts` (`patient_account_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_threads_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned NOT NULL,
  `sender_type` enum('PATIENT','EMPLOYEE') NOT NULL,
  `sender_patient_account_id` bigint(20) unsigned DEFAULT NULL,
  `sender_employee_id` int(10) unsigned DEFAULT NULL,
  `content_type` enum('TEXT','VOICE') NOT NULL DEFAULT 'TEXT',
  `language_code` char(5) NOT NULL DEFAULT 'en',
  `message_text` text DEFAULT NULL,
  `voice_asset_id` bigint(20) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_thread` (`thread_id`,`sent_at`),
  KEY `idx_messages_sender_patient` (`sender_patient_account_id`),
  KEY `idx_messages_sender_employee` (`sender_employee_id`),
  CONSTRAINT `fk_messages_sender_patient_account` FOREIGN KEY (`sender_patient_account_id`) REFERENCES `patient_accounts` (`patient_account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_thread` FOREIGN KEY (`thread_id`) REFERENCES `message_threads` (`thread_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_accounts`
--

DROP TABLE IF EXISTS `patient_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_accounts` (
  `patient_account_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `username` varchar(60) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone_e164` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`patient_account_id`),
  UNIQUE KEY `uq_patient_accounts_patient` (`patient_id`),
  UNIQUE KEY `uq_patient_accounts_username` (`username`),
  UNIQUE KEY `uq_patient_accounts_email` (`email`),
  UNIQUE KEY `uq_patient_accounts_phone` (`phone_e164`),
  CONSTRAINT `fk_patient_accounts_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_daily_sequence`
--

DROP TABLE IF EXISTS `patient_daily_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_daily_sequence` (
  `seq_date` date NOT NULL,
  `last_n` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`seq_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_feedback`
--

DROP TABLE IF EXISTS `patient_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_feedback` (
  `feedback_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feedback_uuid` char(36) NOT NULL,
  `patient_id` int(10) unsigned NOT NULL,
  `patient_account_id` bigint(20) unsigned NOT NULL,
  `thread_id` bigint(20) unsigned DEFAULT NULL,
  `related_employee_id` int(10) unsigned DEFAULT NULL,
  `feedback_type` enum('GRIEVANCE','COMPLAINT','POSITIVE') NOT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL,
  `content_type` enum('TEXT','VOICE') NOT NULL DEFAULT 'TEXT',
  `language_code` char(5) NOT NULL DEFAULT 'en',
  `feedback_text` text DEFAULT NULL,
  `voice_asset_id` bigint(20) unsigned DEFAULT NULL,
  `sentiment_label` enum('POSITIVE','NEUTRAL','NEGATIVE','MIXED','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `sentiment_score` decimal(5,4) DEFAULT NULL,
  `sentiment_summary` varchar(500) DEFAULT NULL,
  `analyzed_at` datetime DEFAULT NULL,
  `status` enum('NEW','REVIEWED','ACTIONED','CLOSED') NOT NULL DEFAULT 'NEW',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  UNIQUE KEY `uq_feedback_uuid` (`feedback_uuid`),
  KEY `idx_feedback_patient` (`patient_id`,`created_at`),
  KEY `idx_feedback_status` (`status`),
  KEY `idx_feedback_type` (`feedback_type`),
  KEY `idx_feedback_related_employee` (`related_employee_id`),
  KEY `fk_feedback_patient_account` (`patient_account_id`),
  KEY `fk_feedback_thread` (`thread_id`),
  CONSTRAINT `fk_feedback_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_patient_account` FOREIGN KEY (`patient_account_id`) REFERENCES `patient_accounts` (`patient_account_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_thread` FOREIGN KEY (`thread_id`) REFERENCES `message_threads` (`thread_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `patient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_code` char(11) NOT NULL,
  `first_seen_date` date NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) DEFAULT NULL,
  `sex` enum('MALE','FEMALE','OTHER','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `date_of_birth` date DEFAULT NULL,
  `age_years` smallint(5) unsigned DEFAULT NULL,
  `phone_e164` varchar(20) DEFAULT NULL,
  `whatsapp_e164` varchar(20) DEFAULT NULL,
  `address_line1` varchar(120) DEFAULT NULL,
  `address_line2` varchar(120) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state_province` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'IN',
  `blood_group` varchar(5) DEFAULT NULL,
  `allergies` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(120) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `uq_patients_patient_code` (`patient_code`),
  KEY `idx_patients_name` (`last_name`,`first_name`),
  KEY `idx_patients_phone` (`phone_e164`),
  KEY `idx_patients_first_seen` (`first_seen_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_patients_before_insert
BEFORE INSERT ON patients
FOR EACH ROW
BEGIN
  DECLARE v_date DATE;
  DECLARE v_n   INT UNSIGNED;

  -- Use provided first_seen_date or default to today
  SET v_date = IFNULL(NEW.first_seen_date, CURDATE());
  SET NEW.first_seen_date = v_date;

  -- Increment the per-day counter safely
  INSERT INTO patient_daily_sequence (seq_date, last_n)
  VALUES (v_date, 0)
  ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1);

  SET v_n = LAST_INSERT_ID();

  -- Build patient_code: YYYYMMDD + 3-digit sequence
  SET NEW.patient_code = CONCAT(DATE_FORMAT(v_date, '%Y%m%d'), LPAD(v_n, 3, '0'));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-06 18:43:16
