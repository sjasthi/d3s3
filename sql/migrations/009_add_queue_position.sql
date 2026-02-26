-- Migration 009: Add queue_position to case_sheets for manual prioritization.
--
-- queue_position is a float so we can insert between two positions without
-- renumbering (e.g. 1, 1.5, 2). When NULL the row is sorted by visit_datetime.
-- Staff set positions explicitly via queue_update.php.

ALTER TABLE case_sheets
  ADD COLUMN queue_position FLOAT UNSIGNED NULL DEFAULT NULL
  AFTER status;

ALTER TABLE case_sheets
  ADD KEY idx_case_sheets_queue (status, queue_position, visit_datetime);

-- Initialise queue positions for all currently-active (non-closed) rows
-- ordered by visit_datetime so existing order is preserved.
SET @pos := 0;
UPDATE case_sheets
   SET queue_position = (@pos := @pos + 1)
 WHERE status IN ('INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', 'DOCTOR_REVIEW')
 ORDER BY visit_datetime ASC;
