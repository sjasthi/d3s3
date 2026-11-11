
-- Issue #34: Store patient consent form upload information

CREATE TABLE IF NOT EXISTS patient_consent_forms (
    consent_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    case_sheet_id INT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    uploaded_by_user_id INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_consent_patient (patient_id),
    INDEX idx_consent_case (case_sheet_id),

    CONSTRAINT fk_consent_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_consent_case
        FOREIGN KEY (case_sheet_id)
        REFERENCES case_sheets(case_sheet_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_consent_user
        FOREIGN KEY (uploaded_by_user_id)
        REFERENCES users(user_id)
);
