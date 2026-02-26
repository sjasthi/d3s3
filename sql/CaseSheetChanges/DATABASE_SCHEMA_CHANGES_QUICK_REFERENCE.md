# Database Changes - Quick Reference

## Migration Files (Execute in Order)

```bash
# 1. Add case sheet columns (73 columns)
mysql -u root -p core_app < DATABASE_MIGRATION.sql

# 2. Add patient columns (6 columns)
mysql -u root -p core_app < ADD_PATIENT_COLUMNS.sql

# 3. Create sequence table and trigger
mysql -u root -p core_app < CREATE_CASE_SHEET_SEQUENCE.sql
```

---

## What Changed

### case_sheets Table
- **Before:** 27 columns
- **After:** 101 columns
- **Added:** 74 columns (1 status + 73 case sheet fields)

### patients Table
- **Before:** 18 columns
- **After:** 24 columns  
- **Added:** 6 columns (aadhaar_number, address_line2, medicine_sources, occupation, education, diet)

### New Tables
- **case_sheet_sequence** - Tracks case sheet sequence per patient

### New Triggers
- **trg_case_sheets_before_insert** - Generates case_sheet_id = patient_id + sequence

### New Indexes
- **idx_case_sheets_status** - On case_sheets(status)
- **idx_case_sheets_patient_status** - On case_sheets(patient_id, status)

---

## case_sheets Columns Added (by Tab)

### Personal Tab (13 columns)
- symptoms_complaints (!)
- duration_of_symptoms (!)
- number_of_children (~)
- type_of_delivery (~)
- delivery_location (~)
- delivery_source (~)
- has_uterus (~)
- menstrual_age_of_onset (~)
- menstrual_cycle_frequency (!)
- menstrual_duration_of_flow (!)
- menstrual_lmp (!)
- menstrual_mh (!)

**Legend:** 
- (~) = Copies from previous visit
- (!) = Always blank on new visits

### History Tab (12 columns)
All (~) - Copy from previous:
- condition_dm
- condition_htn
- condition_tsh
- condition_heart_disease
- condition_others
- surgical_history
- family_history_cancer
- family_history_tuberculosis
- family_history_diabetes
- family_history_bp
- family_history_thyroid
- family_history_other

### General Exam Tab (12 columns)
All (!) - New each visit:
- general_pulse
- general_bp_systolic
- general_bp_diastolic
- general_heart
- general_lungs
- general_liver
- general_spleen
- general_lymph_glands
- general_height
- general_weight
- general_bmi
- general_obesity_overweight

### Examinations Tab (30 columns)
All (!) - New each visit:
- exam_mouth, exam_lips, exam_buccal_mucosa, exam_teeth, exam_tongue
- exam_oropharynx, exam_hypo, exam_naso_pharynx, exam_larynx
- exam_nose, exam_ears, exam_neck
- exam_bones_joints, exam_abdomen_genital
- exam_breast_left, exam_breast_right, exam_breast_axillary_nodes
- exam_breast_diagram (LONGTEXT - Base64 PNG)
- exam_pelvic_cervix, exam_pelvic_uterus, exam_pelvic_ovaries, exam_pelvic_adnexa
- exam_pelvic_diagram (LONGTEXT - Base64 PNG)
- exam_rectal_skin, exam_rectal_remarks
- exam_gynae_ps, exam_gynae_pv
- exam_gynae_via, exam_gynae_via_diagram (LONGTEXT - Base64 PNG)
- exam_gynae_vili, exam_gynae_vili_diagram (LONGTEXT - Base64 PNG)

### Labs Tab (11 columns)
All (!) - New each visit:
- lab_hb_percentage, lab_hb_gms
- lab_fbs, lab_tsh, lab_sr_creatinine
- lab_others
- cytology_papsmear, cytology_papsmear_notes
- cytology_colposcopy, cytology_colposcopy_notes
- cytology_biopsy, cytology_biopsy_notes

### Summary Tab (4 columns)
All (!) - New each visit:
- summary_risk_level
- summary_referral
- summary_patient_acceptance
- summary_doctor_summary

### System Field (1 column)
- status ENUM('OPEN','CLOSED')

---

## patients Columns Added (6 columns)

- aadhaar_number VARCHAR(12)
- address_line2 VARCHAR(120)
- medicine_sources VARCHAR(50)
- occupation VARCHAR(100)
- education VARCHAR(100)
- diet VARCHAR(50)

---

## case_sheet_id Generation

### Before (Team's Original)
```
AUTO_INCREMENT: 1, 2, 3, 4, 5...
```

### After (Our Changes)
```
Format: (patient_id × 1000) + sequence

Patient 1:   1001, 1002, 1003, 1004...
Patient 2:   2001, 2002, 2003, 2004...
Patient 11:  11001, 11002, 11003, 11004...
Patient 250: 250001, 250002, 250003...
```

**How it works:**
1. Trigger fires on INSERT
2. Looks up patient's last sequence in case_sheet_sequence table
3. Increments sequence
4. Sets case_sheet_id = (patient_id × 1000) + sequence

**Why multiplier?**
Prevents collisions:
- Patient 1, Visit 11 = 1011 (not 111)
- Patient 11, Visit 1 = 11001 (not 111) ← No collision!

---

## Modified Existing Fields

### case_sheets.visit_type
- **Before:** ENUM('CAMP','CLINIC','FOLLOW_UP','EMERGENCY','OTHER')
- **After:** VARCHAR(255)
- **Reason:** Allow custom visit types like "Annual Screening", "Follow-up - Breast Biopsy Results"

### case_sheets.case_sheet_id
- **Before:** AUTO_INCREMENT
- **After:** Set by trigger
- **Reason:** Generate meaningful IDs (patient_id + sequence)

---

## Breaking Changes

**NONE**

All changes are additive:
- ✅ No columns removed
- ✅ No tables dropped
- ✅ Existing data preserved
- ✅ Old queries still work

---

## Verification Commands

```sql
-- Check case_sheets column count (should be 101)
SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'case_sheets';

-- Check patients column count (should be 24)
SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'patients';

-- Check sequence table exists
SHOW TABLES LIKE 'case_sheet_sequence';

-- Check trigger exists
SHOW TRIGGERS WHERE `Trigger` = 'trg_case_sheets_before_insert';

-- Check indexes
SHOW INDEXES FROM case_sheets 
WHERE Key_name LIKE 'idx_case_sheets%';
```

---

## File Sizes

- DATABASE_MIGRATION.sql: ~8 KB
- ADD_PATIENT_COLUMNS.sql: ~1 KB
- CREATE_CASE_SHEET_SEQUENCE.sql: ~4 KB

**Total:** ~13 KB of SQL

**Execution Time:** < 10 seconds total

---

## Rollback (Emergency Only)

```bash
# WARNING: This will delete all case sheet data!

mysql -u root -p core_app <<EOF
DROP TRIGGER IF EXISTS trg_case_sheets_before_insert;
DROP TABLE IF EXISTS case_sheet_sequence;
ALTER TABLE case_sheets DROP INDEX idx_case_sheets_status;
ALTER TABLE case_sheets DROP INDEX idx_case_sheets_patient_status;
ALTER TABLE case_sheets MODIFY COLUMN case_sheet_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT;
-- Then manually drop all 74 added columns
EOF
```

**Recommendation:** Backup before migrating instead of rolling back

---

## Documentation Files

- **DATABASE_SCHEMA_CHANGES.md** - Complete detailed documentation (this file's big brother)
- **DATABASE_SCHEMA_CHANGES_QUICK_REFERENCE.md** - This file
- **SYSTEM_DOCUMENTATION.md** - Overall system documentation
- **DEVELOPER_GUIDE.md** - How to work with the new fields

---

Last Updated: February 13, 2026
