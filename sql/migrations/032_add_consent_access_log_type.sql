-- Issue #34: Track consent document access

ALTER TABLE patient_record_access_log
MODIFY COLUMN access_type
ENUM('VIEW_PROFILE', 'VIEW_CASE_SHEET', 'VIEW_CONSENT')
NOT NULL DEFAULT 'VIEW_PROFILE';
