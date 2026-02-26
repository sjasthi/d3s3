-- Migration 006: Add doctor-specific documentation columns to case_sheets
-- Keeps doctor clinical notes separate from nurse-collected JSON blobs.

ALTER TABLE case_sheets
  ADD COLUMN doctor_exam_notes TEXT NULL AFTER plan_notes,
  ADD COLUMN doctor_assessment  TEXT NULL AFTER doctor_exam_notes,
  ADD COLUMN doctor_diagnosis   TEXT NULL AFTER doctor_assessment,
  ADD COLUMN doctor_plan_notes  TEXT NULL AFTER doctor_diagnosis;
