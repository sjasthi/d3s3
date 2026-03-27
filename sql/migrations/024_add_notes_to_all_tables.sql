-- Migration 024: Add generic notes column to all operational tables.
--
-- Adds a nullable TEXT `notes` column to every operational table so
-- stakeholders can capture free-form information when the schema does
-- not have a dedicated field for it.
--
-- Tables skipped intentionally:
--   appointments          – already has `notes`
--   case_sheet_audit_log  – immutable audit log
--   permission_change_log – immutable audit log
--   patient_record_access_log – immutable access log
--
-- Note: ADD COLUMN IF NOT EXISTS requires MySQL 8.0+. BlueHost runs MySQL 5.7,
-- so IF NOT EXISTS is omitted. This migration is a one-time run.

ALTER TABLE `users`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `user_preferences`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `patients`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `case_sheets`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `events`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `patient_feedback`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use (distinct from admin_notes)';

ALTER TABLE `assets`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `feedback`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `messages`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `tasks`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `lab_orders`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `role_permissions`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';

ALTER TABLE `patient_daily_sequence`
  ADD COLUMN `notes` TEXT DEFAULT NULL
    COMMENT 'Free-form notes for stakeholder use';
