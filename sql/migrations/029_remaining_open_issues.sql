-- Issues 12, 13, 16, and case file uploads (28, 29, 32, 34).
-- Safe to run more than once.

ALTER TABLE patients
	ADD COLUMN IF NOT EXISTS created_by_user_id INT UNSIGNED NULL AFTER notes;

ALTER TABLE case_sheets
	ADD COLUMN IF NOT EXISTS prior_case_sheet_id BIGINT UNSIGNED NULL AFTER patient_id;

ALTER TABLE feedback
	ADD COLUMN IF NOT EXISTS audio_stored_name VARCHAR(255) NULL AFTER notes,
	ADD COLUMN IF NOT EXISTS audio_original_name VARCHAR(255) NULL AFTER audio_stored_name,
	ADD COLUMN IF NOT EXISTS audio_mime VARCHAR(120) NULL AFTER audio_original_name,
	ADD COLUMN IF NOT EXISTS closed_at DATETIME NULL AFTER audio_mime;

CREATE TABLE IF NOT EXISTS case_attachments (
	attachment_id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	case_sheet_id       BIGINT UNSIGNED NULL,
	patient_id          INT UNSIGNED    NOT NULL,
	lab_order_id        BIGINT UNSIGNED NULL,
	kind                VARCHAR(32)     NOT NULL,
	stored_name         VARCHAR(255)    NOT NULL,
	original_name       VARCHAR(255)    NOT NULL,
	mime_type           VARCHAR(120)    NOT NULL,
	file_size           INT UNSIGNED    NOT NULL,
	locked              TINYINT(1)      NOT NULL DEFAULT 0,
	uploaded_by_user_id INT UNSIGNED    NULL,
	created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (attachment_id),
	KEY idx_case_attachments_case (case_sheet_id, kind),
	KEY idx_case_attachments_patient (patient_id, kind)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attribute existing patients to the person who opened their first case sheet.
UPDATE patients p
JOIN case_sheets cs ON cs.case_sheet_id = (
	SELECT MIN(cs2.case_sheet_id) FROM case_sheets cs2 WHERE cs2.patient_id = p.patient_id
)
SET p.created_by_user_id = cs.created_by_user_id
WHERE p.created_by_user_id IS NULL
  AND cs.created_by_user_id IS NOT NULL;
