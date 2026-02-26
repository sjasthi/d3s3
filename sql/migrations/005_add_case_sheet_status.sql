-- ============================================================
-- Migration 005: Add status column to case_sheets
-- Date: 2026-02-17
-- Description: Adds a workflow status column to track case sheets
--              through the intake pipeline.
-- ============================================================

ALTER TABLE `case_sheets`
  ADD COLUMN `status` enum(
    'INTAKE_IN_PROGRESS',
    'INTAKE_COMPLETE',
    'DOCTOR_REVIEW',
    'CLOSED'
  ) NOT NULL DEFAULT 'INTAKE_IN_PROGRESS'
  AFTER `visit_type`;

ALTER TABLE `case_sheets`
  ADD KEY `idx_case_sheets_status` (`status`, `visit_datetime`);

-- Backfill existing rows
UPDATE `case_sheets` SET `status` = 'CLOSED' WHERE `is_closed` = 1;
UPDATE `case_sheets` SET `status` = 'INTAKE_COMPLETE' WHERE `is_closed` = 0;
