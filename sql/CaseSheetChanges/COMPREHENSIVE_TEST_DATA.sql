-- ============================================================================
-- Comprehensive Test Patient and Case Sheets
-- ============================================================================
-- Patient ID: 1
-- Case Sheet 1001 (CLOSED) - Complete initial visit with ALL fields
-- Case Sheet 1002 (OPEN) - Follow-up visit with ~ fields copied
-- ============================================================================

USE core_app;

-- ============================================================================
-- STEP 1: Create Patient 1 - ALL fields filled
-- ============================================================================

INSERT INTO `patients` (
    `patient_id`,
    `patient_code`,
    `first_seen_date`,
    `first_name`,
    `last_name`,
    `aadhaar_number`,
    `sex`,
    `date_of_birth`,
    `age_years`,
    `phone_e164`,
    `email`,
    `address_line1`,
    `address_line2`,
    `city`,
    `state_province`,
    `postal_code`,
    `blood_group`,
    `allergies`,
    `medicine_sources`,
    `occupation`,
    `education`,
    `diet`,
    `emergency_contact_name`,
    `emergency_contact_phone`,
    `is_active`,
    `created_at`,
    `updated_at`
) VALUES (
    1,
    '20260210000',
    '2026-02-10',
    'Priya',
    'Kumar',
    '987654321012',
    'FEMALE',
    '1985-03-20',
    40,
    '+919123456789',
    'priya.kumar@example.com',
    '45 Park Street',
    'Near Central Mall',
    'Mumbai',
    'Maharashtra',
    '400001',
    'A+',
    'Penicillin',
    'GOVERNMENT',
    'Teacher',
    'Post Graduate',
    'Vegetarian',
    'Amit Kumar (husband)',
    '+919123456790',
    1,
    '2026-02-10 10:00:00',
    '2026-02-10 10:00:00'
);

-- ============================================================================
-- STEP 2: Case Sheet 1001 (CLOSED) - Complete Initial Visit
-- ============================================================================
-- ALL fields filled with valid values based on actual dropdown options

INSERT INTO `case_sheets` (
    `patient_id`,
    `visit_datetime`,
    `visit_type`,
    `status`,
    `created_by_user_id`,
    
    -- Personal Tab (13 fields) --
    `symptoms_complaints`,
    `duration_of_symptoms`,
    `number_of_children`,
    `type_of_delivery`,
    `delivery_location`,
    `delivery_source`,
    `has_uterus`,
    `menstrual_age_of_onset`,
    `menstrual_cycle_frequency`,
    `menstrual_duration_of_flow`,
    `menstrual_lmp`,
    `menstrual_mh`,
    
    -- History Tab (12 fields) --
    `condition_dm`,
    `condition_htn`,
    `condition_tsh`,
    `condition_heart_disease`,
    `condition_others`,
    `surgical_history`,
    `family_history_cancer`,
    `family_history_tuberculosis`,
    `family_history_diabetes`,
    `family_history_bp`,
    `family_history_thyroid`,
    `family_history_other`,
    
    -- General Exam Tab (12 fields) --
    `general_pulse`,
    `general_bp_systolic`,
    `general_bp_diastolic`,
    `general_heart`,
    `general_lungs`,
    `general_liver`,
    `general_spleen`,
    `general_lymph_glands`,
    `general_height`,
    `general_weight`,
    `general_bmi`,
    `general_obesity_overweight`,
    
    -- Examinations Tab (27 text fields) --
    `exam_mouth`,
    `exam_lips`,
    `exam_buccal_mucosa`,
    `exam_teeth`,
    `exam_tongue`,
    `exam_oropharynx`,
    `exam_hypo`,
    `exam_naso_pharynx`,
    `exam_larynx`,
    `exam_nose`,
    `exam_ears`,
    `exam_neck`,
    `exam_bones_joints`,
    `exam_abdomen_genital`,
    `exam_breast_left`,
    `exam_breast_right`,
    `exam_breast_axillary_nodes`,
    `exam_pelvic_cervix`,
    `exam_pelvic_uterus`,
    `exam_pelvic_ovaries`,
    `exam_pelvic_adnexa`,
    `exam_rectal_skin`,
    `exam_rectal_remarks`,
    `exam_gynae_ps`,
    `exam_gynae_pv`,
    `exam_gynae_via`,
    `exam_gynae_vili`,
    
    -- Labs Tab (11 fields) --
    `lab_hb_percentage`,
    `lab_hb_gms`,
    `lab_fbs`,
    `lab_tsh`,
    `lab_sr_creatinine`,
    `lab_others`,
    `cytology_papsmear`,
    `cytology_papsmear_notes`,
    `cytology_colposcopy`,
    `cytology_colposcopy_notes`,
    `cytology_biopsy`,
    `cytology_biopsy_notes`,
    
    -- Summary Tab (4 fields) --
    `summary_risk_level`,
    `summary_referral`,
    `summary_patient_acceptance`,
    `summary_doctor_summary`,
    
    -- System fields --
    `created_at`,
    `updated_at`
) VALUES (
    1,
    '2026-02-10 11:00:00',
    'Annual Screening',
    'CLOSED',
    1,
    
    -- Personal Tab --
    'Routine annual screening. Patient reports occasional fatigue and mild headaches over the past 3 months.',
    '3 months',
    2,
    'LSCS',
    'Sion Hospital Mumbai',
    'GH',
    1,
    13,
    28,
    5,
    '2026-01-20',
    'REGULAR',
    
    -- History Tab --
    'NO',
    'CURRENT',
    'NO',
    'NO',
    'Mild anemia noted in previous visit',
    'Appendectomy (2010), LSCS x2 (2015, 2018)',
    1,
    0,
    1,
    1,
    0,
    'Maternal aunt had ovarian cancer at age 55',
    
    -- General Exam Tab --
    72,
    138,
    86,
    'S1S2 heard, no murmurs',
    'Clear bilateral air entry, no wheeze',
    'Palpable 2cm below costal margin, non-tender',
    'Not palpable',
    'No lymphadenopathy',
    162.00,
    58.00,
    22.10,
    0,
    
    -- Examinations Tab (27 fields with realistic findings) --
    'Mucosa pink, no ulcers',
    'Normal color and texture',
    'Normal mucosa, no lesions',
    'Dental caries in lower right molar',
    'Normal papillae, no coating',
    'Normal, no inflammation',
    'No masses palpable',
    'Normal mucosa',
    'Normal vocal cords on examination',
    'Nasal septum deviated to right, no discharge',
    'Bilateral tympanic membranes intact',
    'No thyroid enlargement, no nodes palpable',
    'Full range of motion, no tenderness',
    'Soft, non-tender, no masses',
    'No masses palpable',
    'No masses palpable',
    'No palpable axillary nodes',
    'Nulliparous cervix, no lesions',
    'Normal size, anteverted',
    'Not palpable',
    'No masses',
    'Normal perianal skin',
    'Hemorrhoids grade 1 noted',
    'Normal external genitalia',
    'Multiparous cervix, no discharge',
    'Acetic acid test negative',
    'Lugol iodine test negative',
    
    -- Labs Tab --
    78,
    11.8,
    92,
    2.40,
    0.90,
    'Urine routine: NAD',
    'ADVISED',
    'Patient counseled on importance of screening, agreed to schedule',
    'NONE',
    NULL,
    'NONE',
    NULL,
    
    -- Summary Tab --
    'Moderate risk due to: HTN (138/86), Family history of cancer and diabetes, Mild anemia',
    'Referred to cardiology for BP management. Advised Pap smear screening. Iron supplementation recommended.',
    'Patient agrees with referrals and will schedule appointments. Understands importance of BP control.',
    'Patient presented for routine annual screening. BP elevated at 138/86 (HTN noted). Mild anemia with Hb 11.8 g/dL. Significant family history of cancer (ovarian) and diabetes. All systems examination largely unremarkable except for grade 1 hemorrhoids and dental caries. Pap smear advised and patient counseled. Plan: Cardiology referral for HTN management, iron supplementation, dental referral, schedule Pap smear. Follow-up in 4 weeks for BP check.',
    
    '2026-02-10 11:00:00',
    '2026-02-10 12:15:00'
);

-- ============================================================================
-- STEP 3: Case Sheet 1002 (OPEN) - Follow-up Visit
-- ============================================================================
-- ~ fields copied from 1001, ! fields blank

INSERT INTO `case_sheets` (
    `patient_id`,
    `visit_datetime`,
    `visit_type`,
    `status`,
    `created_by_user_id`,
    
    -- Personal Tab: ~ fields (COPIED) --
    `number_of_children`,
    `type_of_delivery`,
    `delivery_location`,
    `delivery_source`,
    `has_uterus`,
    `menstrual_age_of_onset`,
    -- menstrual cycle details (!) NOT copied - doctor will enter current
    
    -- Personal Tab: ! fields (NEW) --
    `symptoms_complaints`,
    `duration_of_symptoms`,
    
    -- History Tab: ALL ~ fields (COPIED) --
    `condition_dm`,
    `condition_htn`,
    `condition_tsh`,
    `condition_heart_disease`,
    `condition_others`,
    `surgical_history`,
    `family_history_cancer`,
    `family_history_tuberculosis`,
    `family_history_diabetes`,
    `family_history_bp`,
    `family_history_thyroid`,
    `family_history_other`,
    
    -- All other tabs (!) left NULL - doctor will fill during visit
    
    `created_at`,
    `updated_at`
) VALUES (
    1,
    '2026-02-13 15:00:00',
    'Follow-up - BP Check',
    'OPEN',
    1,
    
    -- Personal: ~ fields copied --
    2,
    'LSCS',
    'Sion Hospital Mumbai',
    'GH',
    1,
    13,
    
    -- Personal: ! fields new --
    'Follow-up for blood pressure management. Started on medication 2 weeks ago.',
    '2 weeks',
    
    -- History: ALL ~ fields copied --
    'NO',
    'CURRENT',
    'NO',
    'NO',
    'Mild anemia noted in previous visit',
    'Appendectomy (2010), LSCS x2 (2015, 2018)',
    1,
    0,
    1,
    1,
    0,
    'Maternal aunt had ovarian cancer at age 55',
    
    '2026-02-13 15:00:00',
    '2026-02-13 15:00:00'
);

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT '=== Patient Data ===' as section;
SELECT patient_id, first_name, last_name, patient_code, sex, age_years, medicine_sources
FROM patients 
WHERE patient_id = 1;

SELECT '=== Case Sheets ===' as section;
SELECT 
    case_sheet_id,
    patient_id,
    visit_type,
    status,
    number_of_children,
    type_of_delivery,
    delivery_source,
    condition_htn,
    general_pulse,
    lab_hb_gms
FROM case_sheets 
WHERE patient_id = 1
ORDER BY case_sheet_id;

-- Expected Results:
-- Case Sheet 1001:
--   - status: CLOSED
--   - number_of_children: 2
--   - type_of_delivery: LSCS (NOT 'Normal'!)
--   - delivery_source: GH
--   - condition_htn: CURRENT
--   - general_pulse: 72
--   - lab_hb_gms: 11.8
--
-- Case Sheet 1002:
--   - status: OPEN
--   - number_of_children: 2 (copied from 1001)
--   - type_of_delivery: LSCS (copied from 1001)
--   - delivery_source: GH (copied from 1001)
--   - condition_htn: CURRENT (copied from 1001)
--   - general_pulse: NULL (not copied - ! field)
--   - lab_hb_gms: NULL (not copied - ! field)

-- ============================================================================
-- TESTING CHECKLIST
-- ============================================================================
--
-- [ ] Case 1001 (CLOSED):
--     URL: case-sheet.php?patient_id=1&case_sheet_id=1001
--     - Red "CLOSED" badge visible
--     - All fields read-only
--     - Personal tab: symptoms filled, delivery = LSCS, source = GH
--     - History tab: HTN = CURRENT, family history checked
--     - General tab: BP = 138/86, pulse = 72, Hb = 11.8
--     - Exams tab: All exam fields have realistic findings
--     - Labs tab: Pap smear = ADVISED
--     - Summary tab: Complete doctor's summary present
--
-- [ ] Case 1002 (OPEN):
--     URL: case-sheet.php?patient_id=1&case_sheet_id=1002
--     - Green "OPEN" badge visible
--     - Fields editable
--     - Personal tab: number_of_children = 2 (pre-filled)
--     - Personal tab: delivery = LSCS, source = GH (pre-filled)
--     - Personal tab: Symptoms filled (new visit info)
--     - Personal tab: Menstrual cycle fields BLANK (ready for doctor)
--     - History tab: HTN = CURRENT (pre-filled)
--     - History tab: "Last General Exam" section shows data from 1001
--     - History tab: Family history checkboxes checked
--     - General tab: ALL fields blank (ready for doctor)
--     - Exams tab: ALL fields blank
--     - Labs tab: ALL fields blank
--     - Summary tab: ALL fields blank
--
-- [ ] Create Case 1003 via intake:
--     - Go to intake.php
--     - Patient ID: 1
--     - Visit Type: "Routine Check"
--     - Click "Create New Case"
--     - Should create case_sheet_id = 1003
--     - Should have ~ fields from 1002
--
-- ============================================================================
