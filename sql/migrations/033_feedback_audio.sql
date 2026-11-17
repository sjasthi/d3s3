-- Issue #12: optional audio attachment on feedback records.
-- Storage: private files under storage/feedback-audio/ (not web-accessible).
-- Retention: audio deleted one year after feedback is closed/resolved.
-- Safe to run more than once on MariaDB 10.3+.

ALTER TABLE feedback
	ADD COLUMN IF NOT EXISTS audio_stored_name VARCHAR(255) NULL AFTER notes,
	ADD COLUMN IF NOT EXISTS audio_original_name VARCHAR(255) NULL AFTER audio_stored_name,
	ADD COLUMN IF NOT EXISTS audio_mime VARCHAR(120) NULL AFTER audio_original_name,
	ADD COLUMN IF NOT EXISTS closed_at DATETIME NULL AFTER audio_mime;
