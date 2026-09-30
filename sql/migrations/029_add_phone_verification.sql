-- Add phone verification support for patient contact numbers.
-- A phone number must be verified before it can be trusted for
-- WhatsApp or other sensitive patient communications.

ALTER TABLE patients
    ADD COLUMN phone_verified_at DATETIME NULL AFTER phone_e164;

-- Existing phone numbers remain unverified.
-- When a patient's phone number changes, application logic must
-- reset phone_verified_at to NULL.
