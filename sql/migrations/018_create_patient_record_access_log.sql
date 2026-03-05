-- Migration 018: Create patient record access log table.
-- Every time a staff member views a patient's profile, this table records
-- who viewed it, when, and from which IP. Required for medical-privacy
-- compliance: only clinicians with a clinical reason should be accessing
-- patient records, and this log enables audit / investigation.

CREATE TABLE IF NOT EXISTS patient_record_access_log (
  log_id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  patient_id          INT UNSIGNED     NOT NULL,
  accessed_by_user_id INT UNSIGNED     NOT NULL,
  access_type         ENUM('VIEW_PROFILE','VIEW_CASE_SHEET') NOT NULL DEFAULT 'VIEW_PROFILE',
  ip_address          VARCHAR(45)      DEFAULT NULL,
  user_agent          VARCHAR(500)     DEFAULT NULL,
  accessed_at         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY idx_access_log_patient (patient_id, accessed_at),
  KEY idx_access_log_user    (accessed_by_user_id, accessed_at),
  CONSTRAINT fk_access_log_patient
    FOREIGN KEY (patient_id)          REFERENCES patients (patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_access_log_user
    FOREIGN KEY (accessed_by_user_id) REFERENCES users    (user_id)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
