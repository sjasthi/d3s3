-- ============================================================
-- Migration 011: Add PARAMEDIC and EDUCATION_TEAM roles
-- Date: 2026-02-18
-- Description: Adds PARAMEDIC and EDUCATION_TEAM to the
--              users.role ENUM to support additional staff types.
-- ============================================================

ALTER TABLE `users`
  MODIFY COLUMN `role` enum(
    'SUPER_ADMIN',
    'ADMIN',
    'DOCTOR',
    'TRIAGE_NURSE',
    'NURSE',
    'PARAMEDIC',
    'GRIEVANCE_OFFICER',
    'EDUCATION_TEAM',
    'DATA_ENTRY_OPERATOR'
  ) NOT NULL DEFAULT 'DATA_ENTRY_OPERATOR';
