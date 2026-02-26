-- Migration 010: Add examination diagram columns to case_sheets.
--
-- Diagrams are stored as base64-encoded PNG (without the data:image/png;base64,
-- prefix). LONGTEXT is used because a canvas annotation at 800x600 typically
-- produces ~60–200 KB of base64 text.

ALTER TABLE case_sheets
  ADD COLUMN diag_breast LONGTEXT DEFAULT NULL AFTER exam_notes,
  ADD COLUMN diag_pelvic LONGTEXT DEFAULT NULL AFTER diag_breast,
  ADD COLUMN diag_via    LONGTEXT DEFAULT NULL AFTER diag_pelvic,
  ADD COLUMN diag_vili   LONGTEXT DEFAULT NULL AFTER diag_via;
