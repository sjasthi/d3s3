-- Migration 029
-- Issue #27: WhatsApp Phone Number Security
-- Adds verification status for patient phone numbers.

ALTER TABLE patients
ADD COLUMN phone_verified TINYINT(1) NOT NULL DEFAULT 0
    AFTER phone_e164,
ADD COLUMN phone_verified_at DATETIME NULL
    AFTER phone_verified;
