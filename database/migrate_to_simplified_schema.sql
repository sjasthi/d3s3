-- ============================================================
-- Migration: Convert to Simplified Schema
-- Date: 2026-02-06
-- IMPORTANT: Backup your database before running!
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Step 1: Merge patient_accounts into patients
-- ============================================================

-- Add new columns to patients table for portal account
ALTER TABLE `patients`
  ADD COLUMN `email` varchar(190) DEFAULT NULL AFTER `phone_e164`,
  ADD COLUMN `username` varchar(60) DEFAULT NULL AFTER `emergency_contact_phone`,
  ADD COLUMN `password_hash` varchar(255) DEFAULT NULL AFTER `username`,
  ADD COLUMN `last_login_at` datetime DEFAULT NULL AFTER `password_hash`,
  ADD UNIQUE KEY `uq_patients_username` (`username`),
  ADD UNIQUE KEY `uq_patients_email` (`email`);

-- Copy data from patient_accounts to patients
UPDATE `patients` p
INNER JOIN `patient_accounts` pa ON p.patient_id = pa.patient_id
SET
  p.username = pa.username,
  p.password_hash = pa.password_hash,
  p.last_login_at = pa.last_login_at,
  p.email = pa.email;

-- Update foreign keys in patient_feedback to reference patient_id directly
-- (patient_account_id is no longer needed)
ALTER TABLE `patient_feedback`
  DROP FOREIGN KEY `fk_feedback_patient_account`,
  DROP KEY `fk_feedback_patient_account`,
  DROP COLUMN `patient_account_id`;

-- Drop patient_accounts table
DROP TABLE IF EXISTS `patient_accounts`;

-- ============================================================
-- Step 2: Merge case_closures into case_sheets
-- ============================================================

-- Add closure columns to case_sheets
ALTER TABLE `case_sheets`
  ADD COLUMN `is_closed` tinyint(1) NOT NULL DEFAULT 0 AFTER `follow_up_notes`,
  ADD COLUMN `closed_at` datetime DEFAULT NULL AFTER `is_closed`,
  ADD COLUMN `closed_by_user_id` int(10) unsigned DEFAULT NULL AFTER `closed_at`,
  ADD COLUMN `closure_type` enum('DISCHARGED','FOLLOW_UP','REFERRAL','PENDING') NOT NULL DEFAULT 'PENDING' AFTER `closed_by_user_id`,
  ADD COLUMN `disposition` varchar(255) DEFAULT NULL AFTER `closure_type`,
  ADD COLUMN `advice` text DEFAULT NULL AFTER `prescriptions`,
  ADD COLUMN `follow_up_date` date DEFAULT NULL AFTER `advice`,
  ADD COLUMN `referral_to` varchar(255) DEFAULT NULL AFTER `follow_up_date`,
  ADD COLUMN `referral_reason` text DEFAULT NULL AFTER `referral_to`,
  ADD KEY `idx_case_sheets_closed` (`is_closed`, `closed_at`);

-- Copy data from case_closures to case_sheets
UPDATE `case_sheets` cs
INNER JOIN `case_closures` cc ON cs.case_sheet_id = cc.case_sheet_id
SET
  cs.is_closed = IF(cc.closure_type != 'PENDING', 1, 0),
  cs.closed_at = cc.closed_at,
  cs.closed_by_user_id = cc.closed_by_user_id,
  cs.closure_type = CASE cc.closure_type
    WHEN 'ON_THE_SPOT_DISPOSITION' THEN 'DISCHARGED'
    WHEN 'MEDICAL_ADVICE_FOLLOW_UP' THEN 'FOLLOW_UP'
    WHEN 'REFERRAL' THEN 'REFERRAL'
    ELSE 'PENDING'
  END,
  cs.disposition = cc.closure_summary,
  cs.advice = CONCAT_WS('\n\n', cc.advice_text, cc.prescriptions_text),
  cs.follow_up_date = cc.follow_up_date,
  cs.referral_to = cc.referral_to_name,
  cs.referral_reason = cc.referral_reason;

-- Drop the trigger that auto-creates case_closures
DROP TRIGGER IF EXISTS `trg_case_sheets_after_insert_create_closure`;

-- Drop case_closures table
DROP TABLE IF EXISTS `case_closures`;

-- ============================================================
-- Step 3: Simplify messages (remove threads)
-- ============================================================

-- Create new simplified messages table
CREATE TABLE `messages_new` (
  `message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `sender_type` enum('PATIENT','STAFF') NOT NULL,
  `sender_user_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL if sent by patient',
  `subject` varchar(255) DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `case_sheet_id` bigint(20) unsigned DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_patient` (`patient_id`, `sent_at`),
  KEY `idx_messages_unread` (`is_read`, `sent_at`),
  KEY `idx_messages_case` (`case_sheet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing messages to new structure
INSERT INTO `messages_new`
  (`message_id`, `patient_id`, `sender_type`, `sender_user_id`, `subject`, `message_text`, `is_read`, `sent_at`)
SELECT
  m.message_id,
  mt.patient_id,
  IF(m.sender_type = 'PATIENT', 'PATIENT', 'STAFF') as sender_type,
  m.sender_user_id,
  mt.subject,
  m.message_text,
  m.is_read,
  m.sent_at
FROM `messages` m
INNER JOIN `message_threads` mt ON m.thread_id = mt.thread_id;

-- Drop old tables
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `message_threads`;

-- Rename new table
RENAME TABLE `messages_new` TO `messages`;

-- Add foreign keys
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_patient` FOREIGN KEY (`patient_id`)
    REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_case` FOREIGN KEY (`case_sheet_id`)
    REFERENCES `case_sheets` (`case_sheet_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================
-- Step 4: Simplify other tables
-- ============================================================

-- Simplify users table (remove unused fields)
ALTER TABLE `users`
  DROP COLUMN IF EXISTS `display_name`,
  DROP COLUMN IF EXISTS `address_line1`,
  DROP COLUMN IF EXISTS `address_line2`,
  DROP COLUMN IF EXISTS `city`,
  DROP COLUMN IF EXISTS `state_province`,
  DROP COLUMN IF EXISTS `postal_code`,
  DROP COLUMN IF EXISTS `country_code`,
  DROP COLUMN IF EXISTS `whatsapp_e164`;

-- Simplify patients table (remove unused fields)
ALTER TABLE `patients`
  DROP COLUMN IF EXISTS `whatsapp_e164`,
  DROP COLUMN IF EXISTS `address_line2`,
  DROP COLUMN IF EXISTS `country_code`;

-- Simplify events table (remove UUIDs and unused fields)
ALTER TABLE `events`
  DROP COLUMN IF EXISTS `event_uuid`,
  DROP COLUMN IF EXISTS `timezone`,
  DROP COLUMN IF EXISTS `address_line1`,
  DROP COLUMN IF EXISTS `address_line2`,
  DROP COLUMN IF EXISTS `country_code`,
  DROP COLUMN IF EXISTS `latitude`,
  DROP COLUMN IF EXISTS `longitude`,
  DROP COLUMN IF EXISTS `is_public`;

-- Add simplified address field to events
ALTER TABLE `events`
  ADD COLUMN `address` varchar(255) DEFAULT NULL AFTER `location_name`;

-- Simplify patient_feedback (remove sentiment analysis and UUIDs)
-- First drop foreign keys that depend on columns we're removing
ALTER TABLE `patient_feedback`
  DROP FOREIGN KEY IF EXISTS `fk_feedback_thread`;

ALTER TABLE `patient_feedback`
  DROP COLUMN IF EXISTS `feedback_uuid`,
  DROP COLUMN IF EXISTS `thread_id`,
  DROP COLUMN IF EXISTS `content_type`,
  DROP COLUMN IF EXISTS `language_code`,
  DROP COLUMN IF EXISTS `voice_asset_id`,
  DROP COLUMN IF EXISTS `sentiment_label`,
  DROP COLUMN IF EXISTS `sentiment_score`,
  DROP COLUMN IF EXISTS `sentiment_summary`,
  DROP COLUMN IF EXISTS `analyzed_at`;

-- Rename feedback_text to just text, add admin_notes
ALTER TABLE `patient_feedback`
  CHANGE COLUMN `feedback_text` `feedback_text` text DEFAULT NULL,
  ADD COLUMN `admin_notes` text DEFAULT NULL AFTER `status`;

-- Update feedback_type enum to simpler values
ALTER TABLE `patient_feedback`
  MODIFY COLUMN `feedback_type` enum('POSITIVE','COMPLAINT','SUGGESTION') NOT NULL;

-- Simplify assets table (remove UUIDs and unused fields)
ALTER TABLE `assets`
  DROP COLUMN IF EXISTS `asset_uuid`,
  DROP COLUMN IF EXISTS `language_code`,
  DROP COLUMN IF EXISTS `file_format`,
  DROP COLUMN IF EXISTS `duration_seconds`,
  DROP COLUMN IF EXISTS `page_count`;

-- Update asset_type enum
ALTER TABLE `assets`
  MODIFY COLUMN `asset_type` enum('VIDEO','PDF','IMAGE','DOCUMENT','OTHER') NOT NULL;

-- ============================================================
-- Step 5: Update user role enum (remove overly specific roles)
-- ============================================================
ALTER TABLE `users`
  MODIFY COLUMN `role` enum('SUPER_ADMIN','ADMIN','DOCTOR','NURSE','DATA_ENTRY_OPERATOR') NOT NULL DEFAULT 'DATA_ENTRY_OPERATOR';

-- ============================================================
-- Step 6: Update case_sheets (add visit_type option)
-- ============================================================
ALTER TABLE `case_sheets`
  MODIFY COLUMN `visit_type` enum('CAMP','CLINIC','FOLLOW_UP','EMERGENCY','OTHER') NOT NULL DEFAULT 'CAMP';

-- ============================================================
-- Step 7: Clean up indexes
-- ============================================================

-- Add missing indexes for performance
ALTER TABLE `case_sheets`
  ADD KEY IF NOT EXISTS `idx_case_sheets_visit_date` (`visit_datetime`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verification Queries
-- ============================================================
SELECT 'Migration complete!' AS status;
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('users', 'patients', 'case_sheets', 'events', 'messages', 'patient_feedback', 'assets', 'patient_daily_sequence')
ORDER BY TABLE_NAME;
