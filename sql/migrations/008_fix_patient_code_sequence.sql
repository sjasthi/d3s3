-- Migration 008: Fix patient_code sequence starting at 001 instead of 000.
--
-- Root cause: the trigger inserted last_n = 0 on first patient of each day,
-- then used LAST_INSERT_ID() which returned 0 because LAST_INSERT_ID(expr)
-- only sets the session value when ON DUPLICATE KEY UPDATE fires (i.e. on
-- the second+ patient). Fix: use LAST_INSERT_ID(1) in the INSERT VALUES so
-- the session LAST_INSERT_ID is set to 1 for the very first patient of any day.

-- Step 1: Rebuild the trigger with the corrected initial value.
DROP TRIGGER IF EXISTS trg_patients_before_insert;

DELIMITER $$
CREATE TRIGGER trg_patients_before_insert
BEFORE INSERT ON patients
FOR EACH ROW
BEGIN
  DECLARE v_date DATE;
  DECLARE v_n    INT UNSIGNED;

  SET v_date = IFNULL(NEW.first_seen_date, CURDATE());
  SET NEW.first_seen_date = v_date;

  -- LAST_INSERT_ID(1) sets the session LAST_INSERT_ID on a fresh insert so
  -- the first patient of any day gets sequence number 1 (not 0).
  INSERT INTO patient_daily_sequence (seq_date, last_n)
  VALUES (v_date, LAST_INSERT_ID(1))
  ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1);

  SET v_n = LAST_INSERT_ID();

  SET NEW.patient_code = CONCAT(DATE_FORMAT(v_date, '%Y%m%d'), LPAD(v_n, 3, '0'));
END$$
DELIMITER ;

-- Step 2: Shift ALL existing patient codes up by 1 on affected dates,
-- updating from highest code to lowest to avoid unique key conflicts.
UPDATE patients
   SET patient_code = CONCAT(LEFT(patient_code, 8), LPAD(CAST(SUBSTRING(patient_code, 9) AS UNSIGNED) + 1, 3, '0'))
 WHERE patient_code LIKE '%000'
    OR first_seen_date IN (
         SELECT seq_date FROM patient_daily_sequence WHERE seq_date < CURDATE()
       )
 ORDER BY patient_code DESC;

-- Step 3: Advance the sequence table by 1 for all affected dates so it
-- stays consistent with the corrected patient codes.
UPDATE patient_daily_sequence
   SET last_n = last_n + 1
 WHERE seq_date < CURDATE();
