-- Migration 007: Create audit log table for case sheet changes.
-- Every field change is recorded with who changed it, when, and what the
-- previous value was. Required for medical record integrity.

CREATE TABLE IF NOT EXISTS case_sheet_audit_log (
  audit_id      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  case_sheet_id BIGINT(20) UNSIGNED NOT NULL,
  user_id       INT(10) UNSIGNED    NOT NULL,
  field_name    VARCHAR(100)        NOT NULL,
  old_value     LONGTEXT            NULL,
  new_value     LONGTEXT            NULL,
  changed_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (audit_id),
  KEY idx_audit_case_sheet (case_sheet_id, changed_at),
  KEY idx_audit_user       (user_id),
  CONSTRAINT fk_audit_case_sheet
    FOREIGN KEY (case_sheet_id) REFERENCES case_sheets (case_sheet_id) ON DELETE CASCADE,
  CONSTRAINT fk_audit_user
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
