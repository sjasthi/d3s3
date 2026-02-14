-- ============================================================================
-- Migration: Case Sheets Enhancement & Patient Fields Extension
-- Date: 2026-02-14
-- Description: Adds comprehensive gynecological case sheet fields, auto-generated
--              case sheet IDs, and additional patient tracking fields.
-- Safe to run multiple times (uses IF NOT EXISTS checks).
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- ============================================================================
-- PART 1: Create case_sheet_sequence table
-- ============================================================================

CREATE TABLE IF NOT EXISTS `case_sheet_sequence` (
  `patient_id` int(10) UNSIGNED NOT NULL,
  `last_sequence` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tracks the last case sheet sequence number for each patient';

-- ============================================================================
-- PART 2: Modify case_sheets table structure
-- ============================================================================

-- Change visit_type from ENUM to VARCHAR to allow flexible visit types
ALTER TABLE `case_sheets` 
  MODIFY COLUMN `visit_type` varchar(255) DEFAULT NULL;

-- Add status field for case sheet workflow
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `status` enum('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN' 
  AFTER `is_locked`;

-- Add new case sheet fields (grouped by category)

-- Visit and symptoms
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `symptoms_complaints` text DEFAULT NULL AFTER `visit_type`,
  ADD COLUMN IF NOT EXISTS `duration_of_symptoms` varchar(255) DEFAULT NULL AFTER `symptoms_complaints`;

-- Obstetric history
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `number_of_children` tinyint(3) UNSIGNED DEFAULT NULL AFTER `duration_of_symptoms`,
  ADD COLUMN IF NOT EXISTS `type_of_delivery` varchar(50) DEFAULT NULL AFTER `number_of_children`,
  ADD COLUMN IF NOT EXISTS `delivery_location` varchar(255) DEFAULT NULL AFTER `type_of_delivery`,
  ADD COLUMN IF NOT EXISTS `delivery_source` varchar(50) DEFAULT NULL AFTER `delivery_location`,
  ADD COLUMN IF NOT EXISTS `has_uterus` tinyint(1) DEFAULT NULL AFTER `delivery_source`;

-- Menstrual history
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `menstrual_age_of_onset` tinyint(3) UNSIGNED DEFAULT NULL AFTER `has_uterus`,
  ADD COLUMN IF NOT EXISTS `menstrual_cycle_frequency` tinyint(3) UNSIGNED DEFAULT NULL AFTER `menstrual_age_of_onset`,
  ADD COLUMN IF NOT EXISTS `menstrual_duration_of_flow` tinyint(3) UNSIGNED DEFAULT NULL AFTER `menstrual_cycle_frequency`,
  ADD COLUMN IF NOT EXISTS `menstrual_lmp` date DEFAULT NULL AFTER `menstrual_duration_of_flow`,
  ADD COLUMN IF NOT EXISTS `menstrual_mh` varchar(20) DEFAULT NULL AFTER `menstrual_lmp`;

-- Medical conditions
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `condition_dm` varchar(20) DEFAULT NULL AFTER `menstrual_mh`,
  ADD COLUMN IF NOT EXISTS `condition_htn` varchar(20) DEFAULT NULL AFTER `condition_dm`,
  ADD COLUMN IF NOT EXISTS `condition_tsh` varchar(20) DEFAULT NULL AFTER `condition_htn`,
  ADD COLUMN IF NOT EXISTS `condition_heart_disease` varchar(20) DEFAULT NULL AFTER `condition_tsh`,
  ADD COLUMN IF NOT EXISTS `condition_others` text DEFAULT NULL AFTER `condition_heart_disease`;

-- Surgical history
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `surgical_history` text DEFAULT NULL AFTER `condition_others`;

-- Family history (boolean flags)
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `family_history_cancer` tinyint(1) DEFAULT 0 AFTER `surgical_history`,
  ADD COLUMN IF NOT EXISTS `family_history_tuberculosis` tinyint(1) DEFAULT 0 AFTER `family_history_cancer`,
  ADD COLUMN IF NOT EXISTS `family_history_diabetes` tinyint(1) DEFAULT 0 AFTER `family_history_tuberculosis`,
  ADD COLUMN IF NOT EXISTS `family_history_bp` tinyint(1) DEFAULT 0 AFTER `family_history_diabetes`,
  ADD COLUMN IF NOT EXISTS `family_history_thyroid` tinyint(1) DEFAULT 0 AFTER `family_history_bp`,
  ADD COLUMN IF NOT EXISTS `family_history_other` varchar(255) DEFAULT NULL AFTER `family_history_thyroid`;

-- General physical examination
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `general_pulse` smallint(5) UNSIGNED DEFAULT NULL AFTER `family_history_other`,
  ADD COLUMN IF NOT EXISTS `general_bp_systolic` smallint(5) UNSIGNED DEFAULT NULL AFTER `general_pulse`,
  ADD COLUMN IF NOT EXISTS `general_bp_diastolic` smallint(5) UNSIGNED DEFAULT NULL AFTER `general_bp_systolic`,
  ADD COLUMN IF NOT EXISTS `general_heart` varchar(255) DEFAULT NULL AFTER `general_bp_diastolic`,
  ADD COLUMN IF NOT EXISTS `general_lungs` varchar(255) DEFAULT NULL AFTER `general_heart`,
  ADD COLUMN IF NOT EXISTS `general_liver` varchar(255) DEFAULT NULL AFTER `general_lungs`,
  ADD COLUMN IF NOT EXISTS `general_spleen` varchar(255) DEFAULT NULL AFTER `general_liver`,
  ADD COLUMN IF NOT EXISTS `general_lymph_glands` varchar(255) DEFAULT NULL AFTER `general_spleen`,
  ADD COLUMN IF NOT EXISTS `general_height` decimal(5,2) DEFAULT NULL AFTER `general_lymph_glands`,
  ADD COLUMN IF NOT EXISTS `general_weight` decimal(5,2) DEFAULT NULL AFTER `general_height`,
  ADD COLUMN IF NOT EXISTS `general_bmi` decimal(4,2) DEFAULT NULL AFTER `general_weight`,
  ADD COLUMN IF NOT EXISTS `general_obesity_overweight` tinyint(1) DEFAULT NULL AFTER `general_bmi`;

-- Oral and ENT examination
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `exam_mouth` varchar(255) DEFAULT NULL AFTER `general_obesity_overweight`,
  ADD COLUMN IF NOT EXISTS `exam_lips` varchar(255) DEFAULT NULL AFTER `exam_mouth`,
  ADD COLUMN IF NOT EXISTS `exam_buccal_mucosa` varchar(255) DEFAULT NULL AFTER `exam_lips`,
  ADD COLUMN IF NOT EXISTS `exam_teeth` varchar(255) DEFAULT NULL AFTER `exam_buccal_mucosa`,
  ADD COLUMN IF NOT EXISTS `exam_tongue` varchar(255) DEFAULT NULL AFTER `exam_teeth`,
  ADD COLUMN IF NOT EXISTS `exam_oropharynx` varchar(255) DEFAULT NULL AFTER `exam_tongue`,
  ADD COLUMN IF NOT EXISTS `exam_hypo` varchar(255) DEFAULT NULL AFTER `exam_oropharynx`,
  ADD COLUMN IF NOT EXISTS `exam_naso_pharynx` varchar(255) DEFAULT NULL AFTER `exam_hypo`,
  ADD COLUMN IF NOT EXISTS `exam_larynx` varchar(255) DEFAULT NULL AFTER `exam_naso_pharynx`,
  ADD COLUMN IF NOT EXISTS `exam_nose` varchar(255) DEFAULT NULL AFTER `exam_larynx`,
  ADD COLUMN IF NOT EXISTS `exam_ears` varchar(255) DEFAULT NULL AFTER `exam_nose`,
  ADD COLUMN IF NOT EXISTS `exam_neck` varchar(255) DEFAULT NULL AFTER `exam_ears`;

-- Musculoskeletal and abdominal examination
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `exam_bones_joints` varchar(255) DEFAULT NULL AFTER `exam_neck`,
  ADD COLUMN IF NOT EXISTS `exam_abdomen_genital` varchar(255) DEFAULT NULL AFTER `exam_bones_joints`;

-- Breast examination
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `exam_breast_left` varchar(255) DEFAULT NULL AFTER `exam_abdomen_genital`,
  ADD COLUMN IF NOT EXISTS `exam_breast_right` varchar(255) DEFAULT NULL AFTER `exam_breast_left`,
  ADD COLUMN IF NOT EXISTS `exam_breast_axillary_nodes` varchar(255) DEFAULT NULL AFTER `exam_breast_right`,
  ADD COLUMN IF NOT EXISTS `exam_breast_diagram` longtext DEFAULT NULL AFTER `exam_breast_axillary_nodes`;

-- Pelvic examination
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `exam_pelvic_cervix` varchar(255) DEFAULT NULL AFTER `exam_breast_diagram`,
  ADD COLUMN IF NOT EXISTS `exam_pelvic_uterus` varchar(255) DEFAULT NULL AFTER `exam_pelvic_cervix`,
  ADD COLUMN IF NOT EXISTS `exam_pelvic_ovaries` varchar(255) DEFAULT NULL AFTER `exam_pelvic_uterus`,
  ADD COLUMN IF NOT EXISTS `exam_pelvic_adnexa` varchar(255) DEFAULT NULL AFTER `exam_pelvic_ovaries`,
  ADD COLUMN IF NOT EXISTS `exam_pelvic_diagram` longtext DEFAULT NULL AFTER `exam_pelvic_adnexa`;

-- Rectal examination
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `exam_rectal_skin` varchar(255) DEFAULT NULL AFTER `exam_pelvic_diagram`,
  ADD COLUMN IF NOT EXISTS `exam_rectal_remarks` varchar(255) DEFAULT NULL AFTER `exam_rectal_skin`;

-- Gynecological examination
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `exam_gynae_ps` varchar(255) DEFAULT NULL AFTER `exam_rectal_remarks`,
  ADD COLUMN IF NOT EXISTS `exam_gynae_pv` varchar(255) DEFAULT NULL AFTER `exam_gynae_ps`,
  ADD COLUMN IF NOT EXISTS `exam_gynae_via` varchar(255) DEFAULT NULL AFTER `exam_gynae_pv`,
  ADD COLUMN IF NOT EXISTS `exam_gynae_via_diagram` longtext DEFAULT NULL AFTER `exam_gynae_via`,
  ADD COLUMN IF NOT EXISTS `exam_gynae_vili` varchar(255) DEFAULT NULL AFTER `exam_gynae_via_diagram`,
  ADD COLUMN IF NOT EXISTS `exam_gynae_vili_diagram` longtext DEFAULT NULL AFTER `exam_gynae_vili`;

-- Laboratory tests
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `lab_hb_percentage` smallint(5) UNSIGNED DEFAULT NULL AFTER `exam_gynae_vili_diagram`,
  ADD COLUMN IF NOT EXISTS `lab_hb_gms` decimal(4,1) DEFAULT NULL AFTER `lab_hb_percentage`,
  ADD COLUMN IF NOT EXISTS `lab_fbs` smallint(5) UNSIGNED DEFAULT NULL AFTER `lab_hb_gms`,
  ADD COLUMN IF NOT EXISTS `lab_tsh` decimal(5,2) DEFAULT NULL AFTER `lab_fbs`,
  ADD COLUMN IF NOT EXISTS `lab_sr_creatinine` decimal(4,2) DEFAULT NULL AFTER `lab_tsh`,
  ADD COLUMN IF NOT EXISTS `lab_others` text DEFAULT NULL AFTER `lab_sr_creatinine`;

-- Cytology and pathology
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `cytology_papsmear` varchar(20) DEFAULT 'NONE' AFTER `lab_others`,
  ADD COLUMN IF NOT EXISTS `cytology_papsmear_notes` text DEFAULT NULL AFTER `cytology_papsmear`,
  ADD COLUMN IF NOT EXISTS `cytology_colposcopy` varchar(20) DEFAULT 'NONE' AFTER `cytology_papsmear_notes`,
  ADD COLUMN IF NOT EXISTS `cytology_colposcopy_notes` text DEFAULT NULL AFTER `cytology_colposcopy`,
  ADD COLUMN IF NOT EXISTS `cytology_biopsy` varchar(20) DEFAULT 'NONE' AFTER `cytology_colposcopy_notes`,
  ADD COLUMN IF NOT EXISTS `cytology_biopsy_notes` text DEFAULT NULL AFTER `cytology_biopsy`;

-- Summary and final assessment
ALTER TABLE `case_sheets` 
  ADD COLUMN IF NOT EXISTS `summary_risk_level` text DEFAULT NULL AFTER `cytology_biopsy_notes`,
  ADD COLUMN IF NOT EXISTS `summary_referral` text DEFAULT NULL AFTER `summary_risk_level`,
  ADD COLUMN IF NOT EXISTS `summary_patient_acceptance` text DEFAULT NULL AFTER `summary_referral`,
  ADD COLUMN IF NOT EXISTS `summary_doctor_summary` text DEFAULT NULL AFTER `summary_patient_acceptance`;

-- ============================================================================
-- PART 3: Add indexes for case_sheets
-- ============================================================================

-- Check and add status index
SET @index_exists = (
  SELECT COUNT(1) 
  FROM information_schema.statistics 
  WHERE table_schema = DATABASE() 
    AND table_name = 'case_sheets' 
    AND index_name = 'idx_case_sheets_status'
);

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `case_sheets` ADD KEY `idx_case_sheets_status` (`status`)',
  'SELECT "Index idx_case_sheets_status already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add patient_status composite index
SET @index_exists = (
  SELECT COUNT(1) 
  FROM information_schema.statistics 
  WHERE table_schema = DATABASE() 
    AND table_name = 'case_sheets' 
    AND index_name = 'idx_case_sheets_patient_status'
);

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `case_sheets` ADD KEY `idx_case_sheets_patient_status` (`patient_id`, `status`)',
  'SELECT "Index idx_case_sheets_patient_status already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 4: Create case_sheets trigger for auto-generated IDs
-- ============================================================================

DROP TRIGGER IF EXISTS `trg_case_sheets_before_insert`;

DELIMITER $$
CREATE TRIGGER `trg_case_sheets_before_insert` BEFORE INSERT ON `case_sheets` FOR EACH ROW 
BEGIN
    DECLARE v_sequence INT UNSIGNED;
    DECLARE v_exists INT;
    
    -- Check if patient already has a sequence record
    SELECT COUNT(*) INTO v_exists 
    FROM case_sheet_sequence 
    WHERE patient_id = NEW.patient_id;
    
    IF v_exists = 0 THEN
        -- First case sheet for this patient - start at 1
        INSERT INTO case_sheet_sequence (patient_id, last_sequence)
        VALUES (NEW.patient_id, 1);
        SET v_sequence = 1;
    ELSE
        -- Increment the sequence
        UPDATE case_sheet_sequence 
        SET last_sequence = last_sequence + 1
        WHERE patient_id = NEW.patient_id;
        
        -- Get the new sequence number
        SELECT last_sequence INTO v_sequence
        FROM case_sheet_sequence
        WHERE patient_id = NEW.patient_id;
    END IF;
    
    -- Generate case_sheet_id using multiplier to prevent collisions
    -- Formula: case_sheet_id = (patient_id × 1000) + sequence
    -- 
    -- Examples:
    --   Patient 1, Visit 1:  (1 × 1000) + 1  = 1001
    --   Patient 1, Visit 11: (1 × 1000) + 11 = 1011
    --   Patient 11, Visit 1: (11 × 1000) + 1 = 11001  ← No collision!
    --   Patient 250, Visit 5: (250 × 1000) + 5 = 250005
    --
    -- This assumes:
    --   - No patient will have > 999 case sheets (safe assumption)
    --   - Patient IDs won't exceed 4.2 million (BIGINT supports this)
    SET NEW.case_sheet_id = (NEW.patient_id * 1000) + v_sequence;
END$$
DELIMITER ;

-- ============================================================================
-- PART 5: Modify patients table
-- ============================================================================

-- Add new patient tracking fields
ALTER TABLE `patients` 
  ADD COLUMN IF NOT EXISTS `aadhaar_number` varchar(12) DEFAULT NULL AFTER `last_name`,
  ADD COLUMN IF NOT EXISTS `address_line2` varchar(120) DEFAULT NULL AFTER `address_line1`,
  ADD COLUMN IF NOT EXISTS `medicine_sources` varchar(50) DEFAULT 'NONE' AFTER `allergies`,
  ADD COLUMN IF NOT EXISTS `occupation` varchar(100) DEFAULT NULL AFTER `medicine_sources`,
  ADD COLUMN IF NOT EXISTS `education` varchar(100) DEFAULT NULL AFTER `occupation`,
  ADD COLUMN IF NOT EXISTS `diet` varchar(50) DEFAULT NULL AFTER `education`;

-- ============================================================================
-- PART 6: Verification queries (optional - comment out in production)
-- ============================================================================

-- Verify case_sheet_sequence table exists
SELECT 'case_sheet_sequence table' AS verification, 
       COUNT(*) AS exists_count 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
  AND table_name = 'case_sheet_sequence';

-- Verify trigger exists
SELECT 'trg_case_sheets_before_insert trigger' AS verification, 
       COUNT(*) AS exists_count 
FROM information_schema.triggers 
WHERE trigger_schema = DATABASE() 
  AND trigger_name = 'trg_case_sheets_before_insert';

-- Count new columns in case_sheets
SELECT 'New case_sheets columns' AS verification, 
       COUNT(*) AS column_count 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'case_sheets' 
  AND column_name IN (
    'status', 'symptoms_complaints', 'duration_of_symptoms',
    'number_of_children', 'type_of_delivery', 'has_uterus',
    'menstrual_lmp', 'condition_dm', 'surgical_history',
    'family_history_cancer', 'general_pulse', 'general_bmi',
    'exam_mouth', 'exam_breast_diagram', 'exam_pelvic_diagram',
    'lab_hb_percentage', 'cytology_papsmear', 'summary_risk_level'
  );

-- Count new columns in patients
SELECT 'New patients columns' AS verification, 
       COUNT(*) AS column_count 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'patients' 
  AND column_name IN (
    'aadhaar_number', 'address_line2', 'medicine_sources',
    'occupation', 'education', 'diet'
  );

COMMIT;

-- ============================================================================
-- Migration complete!
-- ============================================================================
