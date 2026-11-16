-- Issue #37: Track doctor access to individual patient cases.

CREATE TABLE IF NOT EXISTS case_doctor_assignments (
    assignment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_sheet_id BIGINT UNSIGNED NOT NULL,
    doctor_user_id INT UNSIGNED NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    discontinued_at DATETIME DEFAULT NULL,
    discontinued_by INT UNSIGNED DEFAULT NULL,

    PRIMARY KEY (assignment_id),
    UNIQUE KEY uq_case_doctor (case_sheet_id, doctor_user_id),
    KEY idx_doctor_status (doctor_user_id, status),

    CONSTRAINT fk_cda_case
        FOREIGN KEY (case_sheet_id)
        REFERENCES case_sheets(case_sheet_id),

    CONSTRAINT fk_cda_doctor
        FOREIGN KEY (doctor_user_id)
        REFERENCES users(user_id),

    CONSTRAINT fk_cda_discontinued_by
        FOREIGN KEY (discontinued_by)
        REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
