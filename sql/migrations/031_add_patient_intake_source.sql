
-- Issue #25: Source of Patient Intake
-- Record how a patient first came to the hospital.

ALTER TABLE patients
ADD COLUMN intake_source VARCHAR(30) NULL
COMMENT 'Original source of patient intake: Phone Call, Medical Camp, Referral, Google Search, or Other';
