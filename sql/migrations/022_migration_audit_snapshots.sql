-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Permanent name snapshots on case_sheets and case_sheet_audit_log
--
-- Rationale: user_id foreign keys become unresolvable when staff leave and
-- accounts are deleted or user_ids are invalid. Snapshotting the name at
-- write time preserves attribution permanently.
-- ─────────────────────────────────────────────────────────────────────────────

-- ── case_sheets ──────────────────────────────────────────────────────────────

ALTER TABLE case_sheets
  ADD COLUMN created_by_name      VARCHAR(121) DEFAULT NULL AFTER created_by_user_id,
  ADD COLUMN assigned_doctor_name VARCHAR(121) DEFAULT NULL AFTER assigned_doctor_user_id;

-- Backfill created_by_name where the user record still exists
UPDATE case_sheets cs
  JOIN users u ON u.user_id = cs.created_by_user_id
   SET cs.created_by_name = CONCAT(u.first_name, ' ', u.last_name)
 WHERE cs.created_by_name IS NULL
   AND cs.created_by_user_id IS NOT NULL;

-- Backfill assigned_doctor_name where the user record still exists
UPDATE case_sheets cs
  JOIN users u ON u.user_id = cs.assigned_doctor_user_id
   SET cs.assigned_doctor_name = CONCAT(u.first_name, ' ', u.last_name)
 WHERE cs.assigned_doctor_name IS NULL
   AND cs.assigned_doctor_user_id IS NOT NULL;

-- ── case_sheet_audit_log ─────────────────────────────────────────────────────

ALTER TABLE case_sheet_audit_log
  ADD COLUMN changed_by_name VARCHAR(121) DEFAULT NULL AFTER user_id;

-- Backfill changed_by_name where the user record still exists
UPDATE case_sheet_audit_log al
  JOIN users u ON u.user_id = al.user_id
   SET al.changed_by_name = CONCAT(u.first_name, ' ', u.last_name)
 WHERE al.changed_by_name IS NULL;
