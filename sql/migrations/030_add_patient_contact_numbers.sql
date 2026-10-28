
-- Issue #35: Support Multiple Contact Numbers
-- Add primary and alternate phone information to patients.
-- Run once on databases that do not already have these columns.

ALTER TABLE patients
    ADD COLUMN primary_phone_type
        ENUM('SMARTPHONE', 'BASIC_PHONE')
        NULL AFTER phone_e164,
    ADD COLUMN alternate_phone_e164
        VARCHAR(20)
        NULL AFTER primary_phone_type,
    ADD COLUMN alternate_phone_type
        ENUM('SMARTPHONE', 'BASIC_PHONE')
        NULL AFTER alternate_phone_e164;
