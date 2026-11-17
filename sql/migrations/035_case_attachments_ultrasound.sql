-- Issue #7: private case attachments (ultrasound images on the pelvic exam).
-- Safe to run more than once.

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
