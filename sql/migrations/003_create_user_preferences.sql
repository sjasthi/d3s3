-- ============================================================
-- Migration 003: Create user_preferences table
-- Date: 2026-02-13
-- Description: Stores per-user settings (theme, language, etc.)
--              for both staff (users table) and patients.
-- ============================================================

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `pref_id`                  bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_type`             enum('STAFF','PATIENT') NOT NULL,
  `account_id`               int(10) UNSIGNED NOT NULL,
  `theme`                    enum('light','dark','system') NOT NULL DEFAULT 'system',
  `language`                 enum('en','te') NOT NULL DEFAULT 'en',
  `font_size`                enum('normal','large') NOT NULL DEFAULT 'normal',
  `date_format`              enum('DD/MM/YYYY','MM/DD/YYYY') NOT NULL DEFAULT 'DD/MM/YYYY',
  `session_timeout_minutes`  smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `email_notifications`      tinyint(1) NOT NULL DEFAULT 1,
  `created_at`               datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at`               datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`pref_id`),
  UNIQUE KEY `uq_prefs_account` (`account_type`, `account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
