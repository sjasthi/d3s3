-- ============================================================
-- Migration 004: Expand users.role ENUM to include all staff roles
-- Date: 2026-02-17
-- Description: The role column was originally limited to
--              SUPER_ADMIN, ADMIN, DATA_ENTRY_OPERATOR.
--              This adds DOCTOR, TRIAGE_NURSE, NURSE, and
--              GRIEVANCE_OFFICER to match the application code.
-- ============================================================

ALTER TABLE `users`
  MODIFY COLUMN `role` enum(
    'SUPER_ADMIN',
    'ADMIN',
    'DOCTOR',
    'TRIAGE_NURSE',
    'NURSE',
    'GRIEVANCE_OFFICER',
    'DATA_ENTRY_OPERATOR'
  ) NOT NULL DEFAULT 'DATA_ENTRY_OPERATOR';
