-- Issue #5: link a follow-up case sheet to an earlier visit (1:N patients → case_sheets).
-- Safe to run more than once.

ALTER TABLE case_sheets
	ADD COLUMN IF NOT EXISTS prior_case_sheet_id BIGINT UNSIGNED NULL AFTER patient_id;
