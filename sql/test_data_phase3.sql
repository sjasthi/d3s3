-- ============================================================
-- D3S3 CareSystem – Phase 3 Comprehensive Test / Seed Data
-- Generated: 2026-04-08
--
-- Covers all tables not fully seeded by test_data.sql or
-- test_data_phase2.sql:
--   users                     (new: TRIAGE_NURSE, PARAMEDIC,
--                              GRIEVANCE_OFFICER, EDUCATION_TEAM)
--   patients                  (16 new patients, March–April 2026)
--   patient_daily_sequence    (sequence rows for new dates)
--   case_sheets               (15 new with all status variants)
--   case_sheet_audit_log      (field-change audit trail)
--   user_preferences          (preferences for staff accounts)
--   feedback                  (staff grievance/feedback system)
--   patient_record_access_log (access audit log)
--   events                    (March–April camps and seminars)
--   assets                    (additional educational materials)
--   patient_feedback          (more patient reviews)
--   appointments              (new follow-ups for new cases)
--   lab_orders                (new orders for new cases)
--   messages                  (more internal staff conversations)
--   tasks                     (more tasks across all new staff)
--
-- Prerequisites: core_app.sql → test_data.sql → all migrations
--   through 025 → test_data_phase2.sql → THEN this file.
--
-- All inserts use INSERT IGNORE — safe to re-run.
-- Passwords (where applicable): Test1234!
--   hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- ============================================================
-- 1. USERS  (fill in missing roles)
--    Existing: 1=Hawkinson/SUPER_ADMIN, 2=admin1/ADMIN(inactive),
--              3=Gary/SUPER_ADMIN, 4=Dr.Desai/DOCTOR,
--              5=Dr.Patel/DOCTOR, 6=Sneha/NURSE, 7=Rohan/NURSE,
--              8=Meena/DEO, 9=Vikram/DEO, 10=Anita/ADMIN,
--              11=Deepak/DOCTOR(inactive)
-- ============================================================
INSERT IGNORE INTO `users`
  (`user_id`, `first_name`, `last_name`, `email`, `phone_e164`, `username`,
   `password_hash`, `role`, `is_active`, `last_login_at`, `created_at`)
VALUES
  (12, 'Nandita',  'Krishnan',  'n.krishnan@d3s3.com',  '+919812345010', 'nkrishnan',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'TRIAGE_NURSE',      1, '2026-03-01 08:30:00', '2026-02-20 09:00:00'),
  (13, 'Sunil',    'Varma',     's.varma@d3s3.com',     '+919812345011', 'svarma',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'TRIAGE_NURSE',      1, '2026-04-07 09:00:00', '2026-02-20 09:00:00'),
  (14, 'Ravi',     'Shankar',   'r.shankar@d3s3.com',   '+919812345012', 'rshankar',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'PARAMEDIC',         1, '2026-04-06 14:00:00', '2026-02-20 09:00:00'),
  (15, 'Fatima',   'Siddiqui',  'f.siddiqui@d3s3.com',  '+919812345013', 'fsiddiqui',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'GRIEVANCE_OFFICER', 1, '2026-04-07 10:15:00', '2026-02-20 09:00:00'),
  (16, 'Chitra',   'Nair',      'c.nair@d3s3.com',      '+919812345014', 'cnair',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'EDUCATION_TEAM',    1, '2026-04-05 11:00:00', '2026-02-20 09:00:00'),
  (17, 'Kiran',    'Rao',       'k.rao@d3s3.com',       '+919812345015', 'krao',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'DOCTOR',            1, '2026-04-08 08:45:00', '2026-03-01 09:00:00');


-- ============================================================
-- 2. PATIENTS  (IDs 15–30, March–April 2026)
-- ============================================================
INSERT IGNORE INTO `patients`
  (`patient_id`, `patient_code`, `first_seen_date`, `first_name`, `last_name`,
   `sex`, `date_of_birth`, `age_years`, `phone_e164`, `email`,
   `address_line1`, `city`, `state_province`, `postal_code`,
   `blood_group`, `allergies`,
   `emergency_contact_name`, `emergency_contact_phone`,
   `username`, `password_hash`, `is_active`, `created_at`)
VALUES
  (15, '20260301000', '2026-03-01', 'Indira',    'Pillai',    'FEMALE', '1965-04-12', 60, '+919900200001', 'indira.p@email.com',   '7 Lotus Nagar',       'Pune',         'Maharashtra', '411008', 'O+',  NULL,                     'Raj Pillai',      '+919900299001', NULL, NULL, 1, '2026-03-01 09:00:00'),
  (16, '20260301001', '2026-03-01', 'Ganesh',    'Bhat',      'MALE',   '1992-09-03', 33, '+919900200002', NULL,                   '22 Green Avenue',     'Pune',         'Maharashtra', '411009', 'A+',  NULL,                     'Uma Bhat',        '+919900299002', NULL, NULL, 1, '2026-03-01 09:30:00'),
  (17, '20260301002', '2026-03-01', 'Deepa',     'Menon',     'FEMALE', '1978-12-25', 47, '+919900200003', 'deepa.m@email.com',    '14 MG Road',          'Ernakulam',    'Kerala',      '682011', 'B+',  'Penicillin',             'Sanjay Menon',    '+919900299003', NULL, NULL, 1, '2026-03-01 10:00:00'),
  (18, '20260305000', '2026-03-05', 'Harish',    'Shetty',    'MALE',   '1950-07-19', 75, '+919900200004', NULL,                   '3 Old Town Road',     'Pune',         'Maharashtra', '411001', 'AB+', 'Aspirin, NSAIDs',         'Preeti Shetty',   '+919900299004', NULL, NULL, 1, '2026-03-05 08:30:00'),
  (19, '20260305001', '2026-03-05', 'Lalitha',   'Subramaniam','FEMALE','1987-02-14', 39, '+919900200005', 'lalitha.s@email.com',  '56 Convent Street',   'Chennai',      'Tamil Nadu',  '600014', 'O-',  NULL,                     'Kumar Subramaniam','+919900299005', NULL, NULL, 1, '2026-03-05 09:15:00'),
  (20, '20260310000', '2026-03-10', 'Mahesh',    'Kulkarni',  'MALE',   '1983-06-30', 42, '+919900200006', NULL,                   '89 Deccan Gymkhana',  'Pune',         'Maharashtra', '411004', 'A-',  NULL,                     'Suneeta Kulkarni','+919900299006', NULL, NULL, 1, '2026-03-10 09:00:00'),
  (21, '20260310001', '2026-03-10', 'Savita',    'Patil',     'FEMALE', '1998-11-08', 27, '+919900200007', 'savita.p@email.com',   '12 Baner Road',       'Pune',         'Maharashtra', '411045', 'B-',  NULL,                     'Santosh Patil',   '+919900299007', NULL, NULL, 1, '2026-03-10 10:00:00'),
  (22, '20260315000', '2026-03-15', 'Balaji',    'Venkatesh', 'MALE',   '1960-03-22', 65, '+919900200008', NULL,                   '77 Anna Nagar',       'Hyderabad',    'Telangana',   '500082', 'O+',  'Sulfonamides',           'Radha Venkatesh', '+919900299008', NULL, NULL, 1, '2026-03-15 08:45:00'),
  (23, '20260315001', '2026-03-15', 'Preethi',   'Raj',       'FEMALE', '2002-08-15', 23, '+919900200009', 'preethi.r@email.com',  '31 Jayanagar',        'Bengaluru',    'Karnataka',   '560011', 'A+',  NULL,                     'Mohan Raj',       '+919900299009', NULL, NULL, 1, '2026-03-15 09:30:00'),
  (24, '20260320000', '2026-03-20', 'Vikrant',   'Deshmukh',  'MALE',   '1975-01-10', 51, '+919900200010', NULL,                   '4 Shivaji Nagar',     'Nashik',       'Maharashtra', '422002', 'B+',  NULL,                     'Kavita Deshmukh', '+919900299010', NULL, NULL, 1, '2026-03-20 09:00:00'),
  (25, '20260320001', '2026-03-20', 'Geeta',     'Joshi',     'FEMALE', '1970-05-05', 55, '+919900200011', 'geeta.j@email.com',    '19 Camp Road',        'Pune',         'Maharashtra', '411001', 'AB-', 'Morphine',               'Desh Joshi',      '+919900299011', NULL, NULL, 1, '2026-03-20 10:15:00'),
  (26, '20260325000', '2026-03-25', 'Aditya',    'Khanna',    'MALE',   '2005-03-01', 21, '+919900200012', 'aditya.k@email.com',   '66 Sector 12',        'Noida',        'Uttar Pradesh','201301', 'O+', NULL,                     'Sunita Khanna',   '+919900299012', NULL, NULL, 1, '2026-03-25 09:00:00'),
  (27, '20260325001', '2026-03-25', 'Shobha',    'Hegde',     'FEMALE', '1955-10-18', 70, '+919900200013', NULL,                   '2 Mangalore Road',    'Mangaluru',    'Karnataka',   '575001', 'A+',  'Latex',                  'Prasad Hegde',    '+919900299013', NULL, NULL, 1, '2026-03-25 10:30:00'),
  (28, '20260401000', '2026-04-01', 'Naresh',    'Sharma',    'MALE',   '1988-07-07', 37, '+919900200014', 'naresh.s@email.com',   '10 Ring Road',        'Pune',         'Maharashtra', '411006', 'O+',  NULL,                     'Anita Sharma',    '+919900299014', NULL, NULL, 1, '2026-04-01 08:30:00'),
  (29, '20260401001', '2026-04-01', 'Rekha',     'Pillai',    'FEMALE', '1963-12-30', 62, '+919900200015', NULL,                   '45 Pettah',           'Thiruvananthapuram','Kerala','695024', 'B+', 'Codeine',               'Suresh Pillai',   '+919900299015', NULL, NULL, 1, '2026-04-01 09:15:00'),
  (30, '20260407000', '2026-04-07', 'Santosh',   'Pawar',     'MALE',   '1945-04-15', 80, '+919900200016', NULL,                   '1 Kasba Peth',        'Pune',         'Maharashtra', '411011', 'A+',  'Ibuprofen',              'Lata Pawar',      '+919900299016', NULL, NULL, 1, '2026-04-07 09:00:00');

-- Sequence rows for new patient dates
INSERT INTO `patient_daily_sequence` (`seq_date`, `last_n`) VALUES
  ('2026-03-01', 2),
  ('2026-03-05', 1),
  ('2026-03-10', 1),
  ('2026-03-15', 1),
  ('2026-03-20', 1),
  ('2026-03-25', 1),
  ('2026-04-01', 1),
  ('2026-04-07', 0)
ON DUPLICATE KEY UPDATE `last_n` = VALUES(`last_n`);


-- ============================================================
-- 3. CASE SHEETS  (IDs 11–25)
--    Covers all status variants:
--      INTAKE_IN_PROGRESS, INTAKE_COMPLETE, DOCTOR_REVIEW,
--      SCHEDULED, CLOSED
--    created_by_name / assigned_doctor_name columns added by
--    migration 023 — include them here.
-- ============================================================
INSERT IGNORE INTO `case_sheets`
  (`case_sheet_id`, `patient_id`, `visit_datetime`, `visit_type`, `status`,
   `queue_position`,
   `created_by_user_id`, `created_by_name`,
   `assigned_doctor_user_id`, `assigned_doctor_name`,
   `chief_complaint`, `history_present_illness`, `vitals_json`,
   `exam_notes`, `assessment`, `diagnosis`,
   `plan_notes`, `prescriptions`, `advice`,
   `follow_up_date`, `follow_up_notes`,
   `referral_to`, `referral_reason`,
   `doctor_exam_notes`, `doctor_assessment`, `doctor_diagnosis`, `doctor_plan_notes`,
   `is_closed`, `closed_at`, `closed_by_user_id`, `closure_type`,
   `disposition`, `is_locked`, `created_at`)
VALUES

-- Case 11: Indira Pillai — menopause & hypertension, CLOSED/FOLLOW_UP
(11, 15, '2026-03-01 09:05:00', 'CLINIC', 'CLOSED', NULL,
 12, 'Nandita Krishnan', 4, 'Priya Desai',
 'Hot flushes and elevated BP for 2 months',
 'Post-menopausal woman presenting with hot flushes, night sweats, and palpitations for 2 months. BP was 150/95 at home. Previously normotensive.',
 '{"bp_systolic":152,"bp_diastolic":96,"pulse":88,"temperature":37.1,"weight_kg":71,"height_cm":155,"spo2":98}',
 'Alert and oriented. BP elevated bilaterally. No focal neurological deficits. No thyromegaly.',
 'Post-menopausal hypertension, likely vasomotor instability contributing.',
 'Essential hypertension; Menopausal vasomotor symptoms',
 'Start antihypertensive. Refer to gynaecology for hormone evaluation. Lifestyle counselling.',
 'Tab Telmisartan 40mg OD, Tab Vitamin D3 60000IU weekly x 8 weeks',
 'Low-salt diet. Regular moderate exercise. Avoid caffeine and alcohol. Sleep hygiene.',
 '2026-03-15', 'Review BP. Check response to telmisartan.',
 NULL, NULL,
 'BP elevated at arms bilaterally. JVP normal. Heart sounds normal.',
 'Likely essential hypertension with menopausal hormonal changes contributing to vasomotor instability.',
 'Hypertension Stage 1; Menopausal syndrome',
 'Initiate telmisartan 40mg OD. Review in 2 weeks. Refer gynaecology for HRT evaluation.',
 1, '2026-03-01 09:55:00', 4, 'FOLLOW_UP',
 'Antihypertensive started. Referred to gynaecology.', 0, '2026-03-01 09:05:00'),

-- Case 12: Ganesh Bhat — ankle sprain, CLOSED/DISCHARGED
(12, 16, '2026-03-01 09:35:00', 'CLINIC', 'CLOSED', NULL,
 12, 'Nandita Krishnan', 4, 'Priya Desai',
 'Right ankle pain after twisting injury while playing cricket yesterday',
 'Young male, twisted right ankle while running during cricket. Immediate swelling and pain. Unable to bear weight. No prior ankle injuries.',
 '{"bp_systolic":118,"bp_diastolic":76,"pulse":80,"temperature":36.8,"weight_kg":72,"height_cm":175,"spo2":99}',
 'Swelling and tenderness over lateral malleolus. No bony tenderness on Ottawa Ankle Rules. Able to bear weight with assistance.',
 'Lateral ankle sprain, Grade 2. Ottawa rules negative for fracture.',
 'Grade 2 lateral ankle sprain; No fracture on clinical assessment',
 'RICE protocol. Analgesics. Ankle support. Review if not improving in 1 week.',
 'Tab Ibuprofen 400mg TDS x 5 days (after food), Diclofenac gel apply TDS x 7 days',
 'Rest ankle for 48 hours. Ice 20 min every 4 hours for 2 days. Elevate foot above heart level when sitting.',
 NULL, NULL, NULL, NULL,
 'Lateral malleolus tenderness. No crepitus. Mild laxity on anterior drawer test.',
 'Grade 2 lateral ankle ligament sprain. Ottawa negative.',
 'Ankle sprain, Grade 2',
 'RICE + NSAIDs + crepe bandage. No fracture. Can weight-bear as tolerated.',
 1, '2026-03-01 10:10:00', 4, 'DISCHARGED',
 'Grade 2 ankle sprain. Conservative management.', 0, '2026-03-01 09:35:00'),

-- Case 13: Deepa Menon — hypothyroidism follow-up, CLOSED/FOLLOW_UP
(13, 17, '2026-03-01 10:05:00', 'FOLLOW_UP', 'CLOSED', NULL,
 12, 'Nandita Krishnan', 17, 'Kiran Rao',
 'Follow-up for hypothyroidism — on Levothyroxine 50mcg',
 'Known hypothyroid on Levothyroxine 50mcg OD x 3 months. Repeat TSH ordered. Still feels fatigued but slightly better. No weight change.',
 '{"bp_systolic":116,"bp_diastolic":74,"pulse":66,"temperature":36.6,"weight_kg":68,"height_cm":162,"spo2":99}',
 'Thyroid not enlarged. No goitre. Mild pallor. No periorbital oedema.',
 'Hypothyroidism on treatment. Fatigue persisting — check TSH for dose adequacy.',
 'Primary hypothyroidism, on Levothyroxine 50mcg',
 'Repeat TSH result: 4.8 mIU/L — slightly above optimal. Increase dose.',
 'Tab Levothyroxine 75mcg OD (taken on empty stomach, 30 min before breakfast)',
 'Take Levothyroxine on empty stomach, 30 minutes before breakfast. Avoid calcium and iron supplements within 4 hours. Repeat TSH in 6 weeks.',
 '2026-04-15', 'Repeat TSH and Free T4.',
 NULL, NULL,
 'TSH: 4.8 mIU/L — suboptimal on current dose.',
 'Suboptimally controlled hypothyroidism. Increase dose to 75mcg.',
 'Primary hypothyroidism, undertreated',
 'Increase Levothyroxine to 75mcg. Repeat TSH in 6 weeks.',
 1, '2026-03-01 10:30:00', 17, 'FOLLOW_UP',
 'Dose increased to 75mcg. TSH recheck in 6 weeks.', 0, '2026-03-01 10:05:00'),

-- Case 14: Harish Shetty — COPD exacerbation, CLOSED/REFERRAL
(14, 18, '2026-03-05 08:35:00', 'EMERGENCY', 'CLOSED', NULL,
 13, 'Sunil Varma', 5, 'Amit Patel',
 'Breathlessness and productive cough for 3 days, getting worse',
 'Known COPD (GOLD Stage 3) on Tiotropium inhaler. Increased breathlessness, productive yellow sputum, and low-grade fever for 3 days. Two previous hospital admissions for COPD exacerbation.',
 '{"bp_systolic":140,"bp_diastolic":88,"pulse":102,"temperature":38.2,"weight_kg":60,"height_cm":162,"spo2":88,"respiratory_rate":28}',
 'Barrel chest. Use of accessory muscles. Bilateral rhonchi and crepitations. SpO2 88% on room air — placed on 2L O2.',
 'Acute exacerbation of COPD. SpO2 88% — needs hospital admission and IV antibiotics.',
 'Acute exacerbation COPD; Suspected pneumonia superimposed',
 'O2 therapy. Nebulisation. Antibiotics. Urgent hospital referral.',
 'Inj Methylprednisolone 125mg IV stat, Salbutamol 2.5mg nebulisation, Ipratropium nebulisation',
 'Immediate hospital admission required. Nothing by mouth. Oxygen at all times.',
 NULL, NULL,
 'Sassoon General Hospital, Pulmonology', 'Acute COPD exacerbation with SpO2 88%. Needs admission, IV steroids, antibiotics, and possible NIV.',
 'Barrel chest, hyperresonant percussion, bilateral rhonchi.',
 'Acute COPD exacerbation, severe. Needs admission.',
 'Acute exacerbation COPD, GOLD Stage 3',
 'O2, nebulisation, systemic steroids, antibiotics — urgent referral.',
 1, '2026-03-05 09:30:00', 5, 'REFERRAL',
 'Emergency referral to pulmonology for COPD exacerbation.', 1, '2026-03-05 08:35:00'),

-- Case 15: Lalitha Subramaniam — antenatal visit, CLOSED/FOLLOW_UP
(15, 19, '2026-03-05 09:20:00', 'CLINIC', 'CLOSED', NULL,
 13, 'Sunil Varma', 17, 'Kiran Rao',
 'Routine antenatal check-up — 28 weeks pregnant',
 'G2P1, 28 weeks gestation. Previous uncomplicated vaginal delivery. Mild ankle swelling noted for 1 week. No headache, no visual disturbances, no epigastric pain.',
 '{"bp_systolic":124,"bp_diastolic":78,"pulse":84,"temperature":36.9,"weight_kg":72,"height_cm":161,"spo2":99}',
 'Uterus 28-week size. FHR 148 bpm. Mild bilateral pedal oedema. No proteinuria on dipstick.',
 'Normal 28-week antenatal visit. Physiological oedema. No signs of pre-eclampsia.',
 '28 weeks gestation, G2P1, normal progress; Physiological pedal oedema',
 'Routine antenatal advice. Iron and calcium supplements. Growth scan in 4 weeks.',
 'Tab Ferrous Fumarate 200mg OD, Tab Calcium 500mg BD, Folic Acid 5mg OD',
 'Elevate feet when resting. Avoid prolonged standing. Return immediately if severe headache, visual changes, or decreased fetal movements.',
 '2026-03-19', 'Growth scan. Repeat urine dipstick. BP review.',
 NULL, NULL,
 'FHR 148 bpm, regular. Fundal height 28 cm. Presentation cephalic.',
 'Normal antenatal progress at 28 weeks. Physiological oedema only.',
 'Normal antenatal, 28 weeks',
 'Continue supplements. Growth scan at 32 weeks. Revisit in 2 weeks.',
 1, '2026-03-05 09:55:00', 17, 'FOLLOW_UP',
 'Normal antenatal visit. Follow-up in 2 weeks.', 0, '2026-03-05 09:20:00'),

-- Case 16: Mahesh Kulkarni — chest pain evaluation, DOCTOR_REVIEW
(16, 20, '2026-03-10 09:05:00', 'CLINIC', 'DOCTOR_REVIEW', NULL,
 12, 'Nandita Krishnan', 5, 'Amit Patel',
 'Chest tightness and shortness of breath on climbing stairs for 1 week',
 'Non-smoker, no prior cardiac history. Exertional chest tightness for 1 week — stops with rest. Associated mild breathlessness. No pleuritic pain.',
 '{"bp_systolic":142,"bp_diastolic":90,"pulse":92,"temperature":37.0,"weight_kg":88,"height_cm":172,"spo2":97}',
 'Alert. No cyanosis. BP 142/90. Mild obesity. Heart sounds S1S2 normal. No murmurs. Lungs clear.',
 'Exertional chest tightness — need to evaluate for angina vs other cause.',
 'Suspected stable angina — workup pending',
 'ECG, echo, treadmill test. Aspirin and Atorvastatin pending clinical review.',
 NULL, NULL,
 '2026-03-17', 'Review ECG and investigations.',
 NULL, NULL,
 NULL, NULL, NULL, NULL,
 0, NULL, NULL, 'PENDING', NULL, 0, '2026-03-10 09:05:00'),

-- Case 17: Savita Patil — dysmenorrhoea, CLOSED/DISCHARGED
(17, 21, '2026-03-10 10:05:00', 'CLINIC', 'CLOSED', NULL,
 12, 'Nandita Krishnan', 4, 'Priya Desai',
 'Severe menstrual cramps since start of period yesterday',
 'Young woman with severe lower abdominal cramps on day 2 of menstruation. Pain 8/10, radiates to lower back. Has had this for 3 cycles but getting worse. No fever, no vaginal discharge.',
 '{"bp_systolic":108,"bp_diastolic":68,"pulse":88,"temperature":36.7,"weight_kg":54,"height_cm":157,"spo2":99}',
 'Lower abdominal tenderness, midline. No guarding or rebound. Uterus normal size on bimanual. No adnexal tenderness.',
 'Primary dysmenorrhoea. Rule out endometriosis if NSAIDs fail.',
 'Primary dysmenorrhoea',
 'NSAIDs starting 1-2 days before expected period. Pelvic ultrasound to rule out secondary cause.',
 'Tab Mefenamic Acid 500mg TDS x 5 days from day 1 of period, Tab Hyoscine Butylbromide 10mg TDS x 2 days SOS',
 'Heat application on lower abdomen. Start Mefenamic Acid from day before expected period. If no improvement in 2 cycles, ultrasound will be done.',
 NULL, NULL, NULL, NULL,
 'Uterus anteverted, normal size. No adnexal masses.',
 'Primary dysmenorrhoea. Endometriosis possible if NSAIDs fail.',
 'Primary dysmenorrhoea',
 'NSAIDs as prescribed. Pelvic ultrasound at next visit if inadequate response.',
 1, '2026-03-10 10:45:00', 4, 'DISCHARGED',
 'Primary dysmenorrhoea. NSAIDs prescribed.', 0, '2026-03-10 10:05:00'),

-- Case 18: Balaji Venkatesh — Type 2 DM with foot ulcer, CLOSED/REFERRAL
(18, 22, '2026-03-15 08:50:00', 'CAMP', 'CLOSED', NULL,
 14, 'Ravi Shankar', 17, 'Kiran Rao',
 'Non-healing wound on right foot for 3 weeks',
 'Known T2DM for 15 years on oral medications. Wound on plantar aspect right foot, started as a blister, now ulcerated. No pain (peripheral neuropathy). Blood sugar at home 250-300 mg/dL.',
 '{"bp_systolic":138,"bp_diastolic":86,"pulse":84,"temperature":37.5,"weight_kg":76,"height_cm":168,"spo2":96,"blood_sugar":286}',
 'Wagner Grade 2 diabetic foot ulcer, right foot plantar surface. 3cm x 2cm. Sloughy base. Surrounding erythema. Faint peripheral pulses bilaterally.',
 'Diabetic foot ulcer Grade 2. Glycaemic control poor. High risk for amputation without intervention.',
 'Diabetic foot ulcer, Wagner Grade 2; Poorly controlled T2DM; Peripheral arterial disease suspected',
 'Wound debridement. IV antibiotics. Urgent vascular surgery referral. Intensify glycaemic control.',
 'Tab Metformin 1000mg BD, Tab Glimepiride 4mg OD, Inj Ceftriaxone 1g IV stat',
 'Non-weight bearing on right foot. Wound dressing daily. Go to hospital immediately.',
 NULL, NULL,
 'KEM Hospital, Vascular Surgery', 'Wagner Grade 2 diabetic foot ulcer. Needs urgent vascular assessment and IV antibiotics to prevent amputation.',
 'Wagner Grade 2 ulcer, 3x2cm, sloughy base, surrounding cellulitis.',
 'Diabetic foot ulcer, high risk. Urgent referral required.',
 'Diabetic foot ulcer Wagner Grade 2; Uncontrolled T2DM',
 'Urgent vascular referral. Start IV antibiotics. Non-weight bearing.',
 1, '2026-03-15 09:45:00', 17, 'REFERRAL',
 'Urgent referral for diabetic foot ulcer.', 1, '2026-03-15 08:50:00'),

-- Case 19: Preethi Raj — urinary tract infection, CLOSED/DISCHARGED
(19, 23, '2026-03-15 09:35:00', 'CLINIC', 'CLOSED', NULL,
 13, 'Sunil Varma', 4, 'Priya Desai',
 'Burning urination and lower abdominal pain for 2 days',
 'Young woman with dysuria, frequency, and suprapubic discomfort. No fever. Sexually active. No prior UTI history. Last menstrual period 2 weeks ago. No vaginal discharge.',
 '{"bp_systolic":112,"bp_diastolic":70,"pulse":76,"temperature":36.9,"weight_kg":55,"height_cm":163,"spo2":99}',
 'Mild suprapubic tenderness. No costovertebral angle tenderness. Urine dipstick: nitrites positive, leukocytes ++.',
 'Acute uncomplicated cystitis.',
 'Acute cystitis; Urine dipstick positive for nitrites and leukocytes',
 'Short-course antibiotics. Adequate hydration. Urine culture if recurring.',
 'Tab Nitrofurantoin 100mg BD x 5 days',
 'Drink at least 2.5 litres of water daily. Void after intercourse. Complete full antibiotic course.',
 NULL, NULL, NULL, NULL,
 'Suprapubic tenderness on deep palpation. No renal angle tenderness.',
 'Acute uncomplicated cystitis.',
 'Acute cystitis',
 'Nitrofurantoin 5-day course. Urine culture if symptoms recur.',
 1, '2026-03-15 10:05:00', 4, 'DISCHARGED',
 'Uncomplicated UTI. Antibiotics prescribed.', 0, '2026-03-15 09:35:00'),

-- Case 20: Vikrant Deshmukh — hypertension review, INTAKE_COMPLETE
(20, 24, '2026-03-20 09:05:00', 'FOLLOW_UP', 'INTAKE_COMPLETE', 1.0,
 13, 'Sunil Varma', 5, 'Amit Patel',
 'Hypertension 3-month review — on Amlodipine 5mg',
 'Known hypertensive on Amlodipine 5mg OD x 3 months. BP diary shows 140-150/88-92 at home. Feels well. No side effects.',
 '{"bp_systolic":144,"bp_diastolic":90,"pulse":78,"temperature":36.8,"weight_kg":80,"height_cm":170,"spo2":98}',
 'Alert. No papilledema. Heart sounds S1S2 normal. No peripheral oedema.',
 'BP not at target (<130/80). Medication intensification needed.',
 'Stage 1 hypertension, suboptimally controlled on Amlodipine 5mg',
 'Awaiting doctor review for medication adjustment.',
 NULL, NULL,
 '2026-03-27', 'BP recheck after medication change.',
 NULL, NULL,
 NULL, NULL, NULL, NULL,
 0, NULL, NULL, 'PENDING', NULL, 0, '2026-03-20 09:05:00'),

-- Case 21: Geeta Joshi — breast lump evaluation, DOCTOR_REVIEW
(21, 25, '2026-03-20 10:20:00', 'CLINIC', 'DOCTOR_REVIEW', 2.0,
 12, 'Nandita Krishnan', 4, 'Priya Desai',
 'Painless lump in left breast noticed 3 weeks ago',
 'Post-menopausal woman. Noticed left breast lump 3 weeks ago. No pain, no skin changes, no nipple discharge. No family history of breast cancer. Not on HRT.',
 '{"bp_systolic":128,"bp_diastolic":80,"pulse":80,"temperature":36.7,"weight_kg":67,"height_cm":158,"spo2":98}',
 'Left breast: 2cm firm, mobile, non-tender lump at 2 o\'clock position. No skin tethering. No peau d\'orange. No axillary lymphadenopathy. Right breast normal.',
 'Breast lump — needs urgent imaging and biopsy. Cannot exclude malignancy.',
 'Breast lump, left — nature to be determined',
 'Urgent mammogram and ultrasound. FNAC/biopsy if needed. Surgical oncology referral.',
 NULL, NULL,
 '2026-03-27', 'Review imaging results. Discuss FNAC plan.',
 NULL, NULL,
 NULL, NULL, NULL, NULL,
 0, NULL, NULL, 'PENDING', NULL, 0, '2026-03-20 10:20:00'),

-- Case 22: Aditya Khanna — asthma attack, CLOSED/DISCHARGED
(22, 26, '2026-03-25 09:05:00', 'EMERGENCY', 'CLOSED', NULL,
 13, 'Sunil Varma', 17, 'Kiran Rao',
 'Sudden breathlessness and wheezing — known asthmatic',
 'Young male, known asthmatic since childhood. Sudden wheeze and dyspnoea 45 minutes ago triggered by dust exposure at construction site. Used Salbutamol inhaler twice without relief.',
 '{"bp_systolic":120,"bp_diastolic":78,"pulse":108,"temperature":37.0,"weight_kg":68,"height_cm":178,"spo2":92,"respiratory_rate":24}',
 'Bilateral wheeze, moderate. Accessory muscles used. SpO2 92%. Speaking in sentences.',
 'Acute moderate asthma attack. Needs nebulisation and observation.',
 'Acute exacerbation of bronchial asthma, moderate severity',
 'Salbutamol nebulisation x2. Oral prednisolone. Observe for 2 hours. Discharge if SpO2 >95% stable.',
 'Salbutamol 5mg nebulisation x2, Tab Prednisolone 40mg OD x 5 days',
 'Avoid triggers (dust, smoke, pets). Use Budesonide inhaler twice daily. Carry Salbutamol at all times. Go to hospital if inhaler gives no relief.',
 '2026-04-08', 'Review asthma control. Check preventer inhaler technique.',
 NULL, NULL,
 'Post-nebulisation: SpO2 97%, wheeze reduced significantly.',
 'Moderate asthma attack, responded to nebulisation.',
 'Acute asthma exacerbation, responded to treatment',
 'Discharge on oral steroids + preventer. Follow-up in 2 weeks.',
 1, '2026-03-25 10:30:00', 17, 'DISCHARGED',
 'Asthma attack, responded to nebulisation. Discharged.', 0, '2026-03-25 09:05:00'),

-- Case 23: Shobha Hegde — back pain, INTAKE_IN_PROGRESS (active in queue)
(23, 27, '2026-04-07 09:05:00', 'CLINIC', 'INTAKE_IN_PROGRESS', 1.0,
 12, 'Nandita Krishnan', 17, 'Kiran Rao',
 'Low back pain for 5 days',
 'Elderly woman with lower back pain after lifting a water bucket. Pain constant, worse on movement. No radiation to legs. No bladder/bowel symptoms.',
 '{"bp_systolic":136,"bp_diastolic":84,"pulse":76,"temperature":36.8,"weight_kg":62,"height_cm":152,"spo2":97}',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, NULL,
 0, NULL, NULL, 'PENDING', NULL, 0, '2026-04-07 09:05:00'),

-- Case 24: Naresh Sharma — Type 2 DM new diagnosis, INTAKE_IN_PROGRESS
(24, 28, '2026-04-01 08:35:00', 'CLINIC', 'INTAKE_IN_PROGRESS', 2.0,
 13, 'Sunil Varma', 5, 'Amit Patel',
 'Fatigue, excessive thirst and frequent urination for 1 month',
 'No prior medical history. Symptoms of polyuria, polydipsia, and fatigue for 1 month. Father has T2DM. BMI 29. Office worker, sedentary lifestyle.',
 '{"bp_systolic":130,"bp_diastolic":84,"pulse":82,"temperature":36.9,"weight_kg":84,"height_cm":170,"spo2":99,"blood_sugar":318}',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL, NULL,
 0, NULL, NULL, 'PENDING', NULL, 0, '2026-04-01 08:35:00'),

-- Case 25: Santosh Pawar — elderly with fall injury, INTAKE_COMPLETE
(25, 30, '2026-04-07 09:05:00', 'CLINIC', 'INTAKE_COMPLETE', 3.0,
 14, 'Ravi Shankar', 5, 'Amit Patel',
 'Hip pain after a fall at home this morning',
 'Elderly male, fell from standing height in bathroom. Right hip pain, unable to bear weight. No loss of consciousness. No head injury. History of osteoporosis.',
 '{"bp_systolic":148,"bp_diastolic":88,"pulse":92,"temperature":36.9,"weight_kg":58,"height_cm":163,"spo2":97}',
 'Right hip: tenderness at greater trochanter and inguinal region. Leg shortened and externally rotated. Passive ROM severely painful. Neurovascular intact distally.',
 'Right hip — possible neck of femur fracture. Needs urgent X-ray.',
 'Suspected right NOF fracture post-fall',
 'X-ray right hip. IV access. Pain management. Likely hospital referral for surgical intervention.',
 'Inj Morphine 2mg IV for pain, NS 500ml IV for hydration',
 NULL,
 NULL, NULL, NULL, NULL,
 NULL, NULL, NULL, NULL,
 0, NULL, NULL, 'PENDING', NULL, 0, '2026-04-07 09:05:00');


-- ============================================================
-- 4. CASE SHEET AUDIT LOG
--    Documents field changes: status transitions, prescription
--    updates, and doctor additions.
-- ============================================================
INSERT IGNORE INTO `case_sheet_audit_log`
  (`audit_id`, `case_sheet_id`, `user_id`, `changed_by_name`,
   `field_name`, `old_value`, `new_value`, `changed_at`)
VALUES

-- Case 11: Indira Pillai — status transitions
(1,  11, 12, 'Nandita Krishnan', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-03-01 09:20:00'),
(2,  11, 4,  'Priya Desai',      'status', 'INTAKE_COMPLETE',     'DOCTOR_REVIEW',   '2026-03-01 09:35:00'),
(3,  11, 4,  'Priya Desai',      'diagnosis',    NULL, 'Essential hypertension; Menopausal vasomotor symptoms', '2026-03-01 09:40:00'),
(4,  11, 4,  'Priya Desai',      'prescriptions', NULL, 'Tab Telmisartan 40mg OD, Tab Vitamin D3 60000IU weekly x 8 weeks', '2026-03-01 09:45:00'),
(5,  11, 4,  'Priya Desai',      'closure_type', 'PENDING',        'FOLLOW_UP',       '2026-03-01 09:55:00'),
(6,  11, 4,  'Priya Desai',      'status',       'DOCTOR_REVIEW',  'CLOSED',          '2026-03-01 09:55:00'),

-- Case 14: Harish Shetty — COPD emergency
(7,  14, 13, 'Sunil Varma',      'status',       'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-03-05 08:55:00'),
(8,  14, 5,  'Amit Patel',       'status',       'INTAKE_COMPLETE',    'DOCTOR_REVIEW',   '2026-03-05 09:05:00'),
(9,  14, 5,  'Amit Patel',       'vitals_json',
     '{"bp_systolic":140,"bp_diastolic":88,"pulse":102,"temperature":38.2,"weight_kg":60,"height_cm":162,"spo2":88,"respiratory_rate":28}',
     '{"bp_systolic":140,"bp_diastolic":88,"pulse":102,"temperature":38.2,"weight_kg":60,"height_cm":162,"spo2":92,"respiratory_rate":22}',
     '2026-03-05 09:10:00'),
(10, 14, 5,  'Amit Patel',       'closure_type', 'PENDING',        'REFERRAL',        '2026-03-05 09:28:00'),
(11, 14, 5,  'Amit Patel',       'status',       'DOCTOR_REVIEW',  'CLOSED',          '2026-03-05 09:30:00'),

-- Case 16: Mahesh Kulkarni — intake to doctor review
(12, 16, 12, 'Nandita Krishnan', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE',  '2026-03-10 09:25:00'),
(13, 16, 5,  'Amit Patel',       'status', 'INTAKE_COMPLETE',     'DOCTOR_REVIEW',    '2026-03-10 10:15:00'),

-- Case 4 (prior): Mohammed Ansari — prescription update during follow-up
(14, 4,  5,  'Amit Patel',  'prescriptions',
     'Tab Metformin 1000mg BD, Tab Glimepiride 2mg OD before breakfast, Tab Methylcobalamin 1500mcg OD',
     'Tab Metformin 1000mg BD, Tab Glimepiride 4mg OD before breakfast, Tab Methylcobalamin 1500mcg OD, Tab Jardiance 10mg OD',
     '2026-02-17 09:55:00'),
(15, 4,  5,  'Amit Patel',  'follow_up_date', '2026-02-17', '2026-03-03', '2026-02-17 10:00:00'),

-- Case 22: Aditya Khanna — asthma attack status changes
(16, 22, 13, 'Sunil Varma',  'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE',  '2026-03-25 09:20:00'),
(17, 22, 17, 'Kiran Rao',    'status', 'INTAKE_COMPLETE',    'DOCTOR_REVIEW',    '2026-03-25 09:35:00'),
(18, 22, 17, 'Kiran Rao',    'vitals_json',
     '{"bp_systolic":120,"bp_diastolic":78,"pulse":108,"temperature":37.0,"weight_kg":68,"height_cm":178,"spo2":92,"respiratory_rate":24}',
     '{"bp_systolic":118,"bp_diastolic":76,"pulse":88,"temperature":37.0,"weight_kg":68,"height_cm":178,"spo2":97,"respiratory_rate":16}',
     '2026-03-25 10:10:00'),
(19, 22, 17, 'Kiran Rao',    'closure_type', 'PENDING', 'DISCHARGED',       '2026-03-25 10:28:00'),
(20, 22, 17, 'Kiran Rao',    'status', 'DOCTOR_REVIEW', 'CLOSED',           '2026-03-25 10:30:00');


-- ============================================================
-- 5. USER PREFERENCES
-- ============================================================
INSERT IGNORE INTO `user_preferences`
  (`pref_id`, `account_type`, `account_id`, `theme`, `language`,
   `font_size`, `date_format`, `session_timeout_minutes`, `email_notifications`)
VALUES
  (1,  'STAFF', 1,  'dark',   'en', 'normal', 'MM/DD/YYYY', 60, 1),
  (2,  'STAFF', 4,  'light',  'en', 'large',  'DD/MM/YYYY', 30, 1),
  (3,  'STAFF', 5,  'system', 'en', 'normal', 'DD/MM/YYYY', 30, 1),
  (4,  'STAFF', 6,  'light',  'te', 'normal', 'DD/MM/YYYY', 30, 1),
  (5,  'STAFF', 7,  'light',  'te', 'large',  'DD/MM/YYYY', 30, 0),
  (6,  'STAFF', 8,  'system', 'te', 'normal', 'DD/MM/YYYY', 30, 1),
  (7,  'STAFF', 10, 'dark',   'en', 'normal', 'DD/MM/YYYY', 60, 1),
  (8,  'STAFF', 12, 'light',  'en', 'normal', 'DD/MM/YYYY', 30, 1),
  (9,  'STAFF', 14, 'system', 'en', 'normal', 'DD/MM/YYYY', 30, 0),
  (10, 'STAFF', 15, 'light',  'en', 'large',  'DD/MM/YYYY', 30, 1),
  (11, 'STAFF', 16, 'dark',   'en', 'normal', 'DD/MM/YYYY', 45, 1),
  (12, 'STAFF', 17, 'system', 'en', 'normal', 'DD/MM/YYYY', 30, 1);


-- ============================================================
-- 6. FEEDBACK  (staff grievance / internal feedback system)
--    migration 012: submitted_by_user_id, assigned_to_user_id
-- ============================================================
INSERT IGNORE INTO `feedback`
  (`feedback_id`, `subject`, `description`, `status`,
   `submitted_by_user_id`, `assigned_to_user_id`, `created_at`)
VALUES

(1, 'Request for additional glucometer strips at camps',
 'During the Feb 10 and March 15 camps we ran out of glucometer strips by midday. With the number of diabetic patients attending, we need at least 400 strips per camp day. Current allocation of 100 is inadequate. Please review supply quantities before the next camp.',
 'RESOLVED', 7, 10, '2026-02-14 11:30:00'),

(2, 'Lack of privacy curtains in exam area at community camp',
 'At the March 15 camp at Chaitanya Hall, the examination area had no privacy curtains. Female patients were visibly uncomfortable and some refused the clinical examination. We need portable partition screens for all future camp setups. This is both a patient dignity and compliance issue.',
 'UNDER_REVIEW', 6, 10, '2026-03-16 10:00:00'),

(3, 'Request for interpreter for Telugu-speaking elderly patients',
 'Several elderly patients at recent clinics spoke only Telugu and had difficulty communicating symptoms. The form fields in English are also inaccessible to them. We have the Telugu language option but it is not well known to patients. Suggest printing patient information forms in Telugu and having a designated Telugu interpreter at each camp.',
 'OPEN', 12, 15, '2026-03-20 09:00:00'),

(4, 'ECG machine needs maintenance — inconsistent trace readings',
 'The ECG machine in Exam Room 2 has been giving inconsistent readings for the past 2 weeks. Electrode contact is confirmed, but the trace is noisy and not diagnostic. Case 16 (chest pain patient) may have had an inconclusive ECG as a result. This needs urgent biomedical engineering review before we use it for any cardiac patients.',
 'UNDER_REVIEW', 5, 1, '2026-03-21 14:00:00'),

(5, 'Shift handover documentation needs standardisation',
 'There is currently no standard handover template when nursing shifts change. Three times in the past month, open INTAKE_IN_PROGRESS cases were not handed over properly and patients waited additional time before anyone picked up their case. Recommend implementing a printed/digital shift handover checklist.',
 'OPEN', 13, 10, '2026-03-28 16:00:00'),

(6, 'Recognition for triage team performance at March 15 camp',
 'I want to formally commend the triage team (Nandita Krishnan, Sunil Varma, Ravi Shankar) for their exceptional performance at the March 15 Chaitanya Hall camp. Despite 80+ patient registrations, triage was completed for all patients within 45 minutes and zero cases were missed. This deserves formal recognition.',
 'CLOSED', 4, 1, '2026-03-18 11:00:00'),

(7, 'Request for standing desk in data entry area',
 'The data entry staff (myself and Vikram) sit for 6-8 hours at a time. The current chairs and desk height are not ergonomically suitable. I have been experiencing wrist and neck pain. Requesting ergonomic review and consideration of a standing desk option.',
 'OPEN', 8, 10, '2026-03-25 10:00:00'),

(8, 'Training needed on new labwork module',
 'The new Labwork module was deployed but no formal training was given to nursing staff. Rohan and I had to figure it out on our own. There is confusion about when to mark orders as COMPLETED versus when the doctor should do it. Please schedule a 30-minute walkthrough session.',
 'RESOLVED', 6, 1, '2026-03-10 09:00:00');


-- ============================================================
-- 7. PATIENT RECORD ACCESS LOG
--    Simulates staff accessing patient profiles over time.
-- ============================================================
INSERT IGNORE INTO `patient_record_access_log`
  (`log_id`, `patient_id`, `accessed_by_user_id`, `access_type`,
   `ip_address`, `user_agent`, `accessed_at`)
VALUES
-- Doctors reviewing their patients
(1,  5,  4,  'VIEW_PROFILE',    '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-12 09:00:00'),
(2,  8,  5,  'VIEW_PROFILE',    '192.168.1.11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-13 10:00:00'),
(3,  13, 4,  'VIEW_PROFILE',    '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-15 10:30:00'),
(4,  6,  5,  'VIEW_PROFILE',    '192.168.1.11', 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Safari/605.1',   '2026-02-18 11:00:00'),
(5,  10, 5,  'VIEW_CASE_SHEET', '192.168.1.11', 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Safari/605.1',   '2026-02-25 14:05:00'),
-- Admin reviewing patient records
(6,  12, 10, 'VIEW_PROFILE',    '192.168.1.20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-12 15:00:00'),
(7,  6,  10, 'VIEW_PROFILE',    '192.168.1.20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', '2026-02-12 15:05:00'),
-- SUPER_ADMIN audit access
(8,  5,  1,  'VIEW_PROFILE',    '192.168.1.1',  'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/121.0',   '2026-02-20 10:00:00'),
(9,  8,  1,  'VIEW_PROFILE',    '192.168.1.1',  'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/121.0',   '2026-02-20 10:02:00'),
-- New cases — March 2026
(10, 15, 4,  'VIEW_PROFILE',    '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0', '2026-03-01 09:30:00'),
(11, 18, 17, 'VIEW_PROFILE',    '192.168.1.15', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/604.1',  '2026-03-15 09:00:00'),
(12, 20, 5,  'VIEW_PROFILE',    '192.168.1.11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0', '2026-03-20 09:30:00'),
(13, 25, 4,  'VIEW_PROFILE',    '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0', '2026-03-20 10:25:00'),
(14, 22, 17, 'VIEW_PROFILE',    '192.168.1.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0', '2026-03-25 09:10:00'),
(15, 30, 5,  'VIEW_PROFILE',    '192.168.1.11', 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/123.0',   '2026-04-07 09:10:00');


-- ============================================================
-- 8. EVENTS  (March–April 2026)
-- ============================================================
INSERT IGNORE INTO `events`
  (`event_id`, `event_type`, `title`, `description`,
   `start_datetime`, `end_datetime`,
   `location_name`, `address`, `city`, `state_province`, `postal_code`,
   `status`, `is_active`, `created_by_user_id`, `created_at`)
VALUES
  (8,  'MEDICAL_CAMP', 'Chaitanya Hall Health Screening Camp',
   'General health screening, diabetes and BP checks, eye screening for 80+ registered patients.',
   '2026-03-15 08:00:00', '2026-03-15 16:00:00',
   'Chaitanya Community Hall', 'Erandwane', 'Pune', 'Maharashtra', '411004',
   'COMPLETED', 1, 10, '2026-03-05 10:00:00'),

  (9,  'TRAINING', 'Labwork Module Staff Training',
   'Hands-on walkthrough of the new Labwork module for nursing and data entry staff. Covers ordering tests, marking completions, and viewing pending queues.',
   '2026-03-20 14:00:00', '2026-03-20 15:00:00',
   'D3S3 Training Room', 'Office Campus', 'Pune', 'Maharashtra', '411001',
   'COMPLETED', 1, 1, '2026-03-12 09:00:00'),

  (10, 'EDUCATIONAL_SEMINAR', 'Maternal and Child Health Awareness Week',
   'Week-long seminar series on antenatal care, breastfeeding, child immunisation, and nutrition. Open to community members.',
   '2026-03-22 10:00:00', '2026-03-28 16:00:00',
   'Kothrud Community Health Center', 'Kothrud', 'Pune', 'Maharashtra', '411038',
   'COMPLETED', 1, 16, '2026-03-10 11:00:00'),

  (11, 'MEDICAL_CAMP', 'Rural Diabetic Foot Care Camp — Mulshi',
   'Specialised camp for diabetic patients targeting foot care education and wound screening. Collaboration with podiatry volunteer group.',
   '2026-04-05 09:00:00', '2026-04-05 15:00:00',
   'Mulshi Gram Panchayat Hall', 'Mulshi Village', 'Mulshi', 'Maharashtra', '412108',
   'COMPLETED', 1, 10, '2026-03-25 10:00:00'),

  (12, 'MEETING', 'Quarterly Clinical Review Q1 2026',
   'Review of Q1 patient outcomes, camp statistics, staff performance, and planning for Q2. All senior staff required.',
   '2026-04-07 16:00:00', '2026-04-07 18:00:00',
   'D3S3 Conference Room', 'Office Campus', 'Pune', 'Maharashtra', '411001',
   'ACTIVE', 1, 1, '2026-03-28 09:00:00'),

  (13, 'MEDICAL_CAMP', 'April Wellness Camp — Hadapsar',
   'General wellness screening, BP and blood sugar checks, dental referrals, and eye testing. Registration open.',
   '2026-04-20 08:30:00', '2026-04-20 16:00:00',
   'Hadapsar Community Ground', 'Hadapsar', 'Pune', 'Maharashtra', '411028',
   'SCHEDULED', 1, 10, '2026-04-05 11:00:00');


-- ============================================================
-- 9. ASSETS  (additional educational and operational materials)
-- ============================================================
INSERT IGNORE INTO `assets`
  (`asset_id`, `title`, `description`, `asset_type`, `category`,
   `file_name`, `file_size_bytes`, `storage_type`, `resource_url`,
   `is_public`, `is_active`, `uploaded_by_user_id`, `created_at`)
VALUES
  (9,  'Diabetic Foot Care – Telugu',
       'Patient-friendly guide for diabetic foot inspection and care, translated into Telugu.',
       'PDF', 'diabetes', 'diabetic_foot_care_te.pdf', 910000, 'LOCAL',
       '/assets/documents/diabetic_foot_care_te.pdf', 1, 1, 16, '2026-03-15 09:00:00'),

  (10, 'Antenatal Care Guide – English & Telugu',
       'Bilingual guide covering diet, exercise, warning signs, and fetal movement counting for pregnant women.',
       'PDF', 'maternal', 'antenatal_guide_en_te.pdf', 1800000, 'LOCAL',
       '/assets/documents/antenatal_guide_en_te.pdf', 1, 1, 16, '2026-03-20 10:00:00'),

  (11, 'Hypertension Medication Adherence Video',
       'Short video (4 min) on the importance of taking BP medications daily, with common patient concerns addressed.',
       'VIDEO', 'hypertension', NULL, NULL, 'URL',
       'https://example.org/videos/bp_adherence.mp4', 1, 1, 4, '2026-03-01 11:00:00'),

  (12, 'Asthma Action Plan Template',
       'Customisable asthma action plan for patients to fill with their doctor. Covers green/yellow/red zones.',
       'PDF', 'respiratory', 'asthma_action_plan.pdf', 480000, 'LOCAL',
       '/assets/documents/asthma_action_plan.pdf', 1, 1, 17, '2026-03-28 09:00:00'),

  (13, 'Camp Registration and Consent Form (Updated)',
       'Updated bilingual (English/Telugu) camp registration form with updated consent language per legal review.',
       'PDF', 'admin', 'camp_reg_form_v2.pdf', 390000, 'LOCAL',
       '/assets/documents/camp_reg_form_v2.pdf', 0, 1, 10, '2026-04-01 09:00:00'),

  (14, 'COPD Patient Education Card',
       'Pocket card explaining COPD triggers, inhaler technique, and when to seek emergency care.',
       'IMAGE', 'respiratory', 'copd_patient_card.png', 220000, 'LOCAL',
       '/assets/images/copd_patient_card.png', 1, 1, 5, '2026-03-10 14:00:00'),

  (15, 'Wound Dressing Protocol – Nursing Staff',
       'Step-by-step wound dressing protocol for diabetic foot ulcers and post-surgical wounds. Internal staff use only.',
       'PDF', 'wound-care', 'wound_dressing_protocol.pdf', 760000, 'LOCAL',
       '/assets/documents/wound_dressing_protocol.pdf', 0, 1, 6, '2026-03-18 10:00:00');


-- ============================================================
-- 10. PATIENT FEEDBACK  (more reviews — IDs 9–20)
-- ============================================================
INSERT IGNORE INTO `patient_feedback`
  (`feedback_id`, `patient_id`, `related_user_id`, `feedback_type`,
   `rating`, `feedback_text`, `status`, `admin_notes`, `created_at`)
VALUES
  (9,  15, 4,  'POSITIVE', 5,
   'Dr. Desai listened to all my concerns patiently and explained everything about my blood pressure and menopause. I felt very well cared for. The nurse (Nandita) was also very professional.',
   'NEW', NULL, '2026-03-02 11:00:00'),

  (10, 17, 17, 'POSITIVE', 4,
   'Dr. Kiran Rao was thorough with my thyroid follow-up and explained why the dose needed to change. Only suggestion is that the waiting area could use better signage so patients know where to go.',
   'REVIEWED', 'Good feedback for new doctor. Signage improvement noted — will discuss with admin.', '2026-03-03 10:00:00'),

  (11, 18, NULL, 'COMPLAINT', 1,
   'I came with a serious wound on my foot and was seen at a camp. The camp did not have proper wound care supplies — only basic bandages. For a diabetic foot camp there should be proper dressing materials available. I had to be told to go to hospital anyway. Why hold a diabetic foot camp without proper supplies?',
   'UNDER_REVIEW', 'Legitimate complaint — coordinating with camp logistics team to review supply checklist for diabetic foot camps.', '2026-03-16 14:00:00'),

  (12, 19, 4,  'POSITIVE', 5,
   'Very quick and helpful visit. Dr. Desai was kind and the treatment worked well. My infection cleared up in 4 days. Thank you!',
   'NEW', NULL, '2026-03-20 09:30:00'),

  (13, 22, 17, 'POSITIVE', 5,
   'I came in struggling to breathe and the team acted quickly. Dr. Rao and the triage nurse were calm and efficient. After the nebulisation I felt much better. Grateful for the fast response.',
   'NEW', NULL, '2026-03-26 08:00:00'),

  (14, 26, NULL, 'SUGGESTION', NULL,
   'It would be very helpful if patients could get a text message confirmation after an appointment is booked. I was not sure if my follow-up was confirmed and called the clinic twice to check.',
   'NEW', NULL, '2026-03-26 10:00:00'),

  (15, 16, NULL, 'POSITIVE', 4,
   'Good service. My ankle sprain was managed well. Clear instructions were given about what to do at home. Recovered quickly following the advice.',
   'REVIEWED', NULL, '2026-03-08 12:00:00'),

  (16, 21, 4,  'POSITIVE', 5,
   'Dr. Desai handled a very sensitive situation (breast lump) with great care and professionalism. She explained every step clearly and did not cause unnecessary alarm while still being appropriately serious about it. I felt safe and informed.',
   'ACTIONED', 'Shared with Dr. Desai. Outstanding patient communication noted.', '2026-03-22 10:00:00'),

  (17, 23, NULL, 'COMPLAINT', 2,
   'I was in a lot of pain with my back and had to wait almost 40 minutes after triage before anyone saw me. The waiting area chairs are very uncomfortable for someone with back pain. Could there be cots or reclining chairs available for patients with pain?',
   'NEW', NULL, '2026-04-08 10:00:00'),

  (18, 25, NULL, 'SUGGESTION', NULL,
   'For elderly patients visiting for follow-ups, it would help to have someone assist them from the entrance to the registration desk. My mother has difficulty walking and there was no one to help her navigate the clinic.',
   'NEW', NULL, '2026-04-02 09:30:00');


-- ============================================================
-- 11. APPOINTMENTS  (IDs 9–20 — for new cases)
-- ============================================================
INSERT IGNORE INTO `appointments`
  (`appointment_id`, `case_sheet_id`, `doctor_user_id`,
   `scheduled_date`, `scheduled_time`, `visit_mode`, `event_id`,
   `status`, `notes`, `created_by_user_id`, `created_at`)
VALUES

-- Case 11: Indira Pillai — BP follow-up (COMPLETED)
(9,  11, 4, '2026-03-15', '10:00:00', 'IN_PERSON', NULL,
 'COMPLETED', 'BP review after starting Telmisartan. BP 138/86 — improving.', 12, '2026-03-01 10:00:00'),

-- Case 13: Deepa Menon — TSH recheck (SCHEDULED)
(10, 13, 17, '2026-04-15', '11:00:00', 'IN_PERSON', NULL,
 'SCHEDULED', 'TSH + Free T4 recheck after dose increase to Levothyroxine 75mcg.', 12, '2026-03-01 10:35:00'),

-- Case 15: Lalitha Subramaniam — 32-week antenatal (SCHEDULED)
(11, 15, 17, '2026-04-02', '10:00:00', 'IN_PERSON', NULL,
 'COMPLETED', '32-week growth scan completed. Normal fetal biometry. No complications.', 13, '2026-03-05 10:00:00'),

-- Case 16: Mahesh Kulkarni — ECG review (SCHEDULED)
(12, 16, 5, '2026-03-17', '09:30:00', 'IN_PERSON', NULL,
 'COMPLETED', 'Reviewed ECG — normal sinus rhythm. Treadmill test scheduled at Deenanath Hospital.', 12, '2026-03-10 10:30:00'),

-- Case 18: Balaji Venkatesh — post-hospital discharge follow-up (SCHEDULED)
(13, 18, 17, '2026-04-10', '09:00:00', 'IN_PERSON', NULL,
 'SCHEDULED', 'Post-referral follow-up. Review KEM Hospital discharge summary. Wound assessment.', 14, '2026-03-20 10:00:00'),

-- Case 20: Vikrant Deshmukh — BP recheck (SCHEDULED)
(14, 20, 5, '2026-03-27', '10:00:00', 'IN_PERSON', NULL,
 'SCHEDULED', 'BP review after medication adjustment. Repeat urine albumin.', 13, '2026-03-20 09:30:00'),

-- Case 21: Geeta Joshi — imaging review (SCHEDULED)
(15, 21, 4, '2026-03-27', '11:00:00', 'IN_PERSON', NULL,
 'SCHEDULED', 'Review mammogram and ultrasound results. Discuss FNAC plan.', 12, '2026-03-20 10:30:00'),

-- Case 22: Aditya Khanna — asthma follow-up (SCHEDULED)
(16, 22, 17, '2026-04-08', '10:00:00', 'IN_PERSON', NULL,
 'SCHEDULED', 'Asthma control review. Check preventer inhaler technique. Peak flow measurement.', 13, '2026-03-25 10:35:00'),

-- Indira Pillai — second BP follow-up (SCHEDULED)
(17, 11, 4, '2026-04-15', '10:00:00', 'IN_PERSON', NULL,
 'SCHEDULED', 'Second BP follow-up. Assess telmisartan dose adequacy. Target <130/80.', 12, '2026-03-16 09:00:00'),

-- Lalitha Subramaniam — 36-week antenatal (SCHEDULED)
(18, 15, 17, '2026-04-30', '10:00:00', 'IN_PERSON', NULL,
 'SCHEDULED', '36-week antenatal. Birth plan discussion. Presentation check.', 13, '2026-04-02 10:30:00');


-- ============================================================
-- 12. LAB ORDERS  (IDs 11–22 — for new cases)
-- ============================================================
INSERT IGNORE INTO `lab_orders`
  (`order_id`, `case_sheet_id`, `patient_id`, `test_name`, `order_notes`,
   `status`, `ordered_by_user_id`, `ordered_at`,
   `completed_by_user_id`, `completed_at`, `result_notes`)
VALUES

-- Case 16: Mahesh Kulkarni — cardiac workup
(11, 16, 20, 'Resting ECG (12-lead)',
     'Rule out ischaemia. Patient has exertional chest tightness.',
     'COMPLETED', 5, '2026-03-10 10:20:00',
     7, '2026-03-10 11:00:00',
     'Normal sinus rhythm. Rate 86 bpm. No ST changes. No Q waves. Report: Normal ECG. Treadmill stress test recommended.'),

(12, 16, 20, 'Lipid Profile',
     'Baseline lipids for cardiovascular risk assessment.',
     'COMPLETED', 5, '2026-03-10 10:20:00',
     7, '2026-03-12 09:30:00',
     'Total Cholesterol: 218 mg/dL (borderline). LDL: 142 mg/dL (high). HDL: 38 mg/dL (low). Triglycerides: 190 mg/dL (borderline). Start Atorvastatin — advise Dr. Patel.'),

(13, 16, 20, 'Fasting Blood Sugar',
     'Rule out diabetes as cardiovascular risk factor.',
     'COMPLETED', 5, '2026-03-10 10:20:00',
     7, '2026-03-12 09:45:00',
     'FBS: 102 mg/dL. Pre-diabetic range (100-125 mg/dL). Lifestyle counselling indicated. Recheck HbA1c.'),

-- Case 21: Geeta Joshi — breast lump
(14, 21, 25, 'Bilateral Mammogram',
     'Evaluate left breast lump, 2cm at 2 o\'clock position. Age 55, post-menopausal.',
     'COMPLETED', 4, '2026-03-20 10:35:00',
     6, '2026-03-24 10:00:00',
     'Left breast: 2.1cm spiculated mass at 2 o\'clock position, 3cm from nipple. BIRADS 4C — high suspicion for malignancy. Biopsy strongly recommended. Right breast: no abnormality.'),

(15, 21, 25, 'Breast Ultrasound',
     'Supplement mammogram findings. Evaluate vascular supply and margins of left breast lump.',
     'COMPLETED', 4, '2026-03-20 10:35:00',
     6, '2026-03-24 10:30:00',
     'Left breast: hypoechoic mass with irregular margins and posterior acoustic shadowing, 2.2 x 1.8 cm. BIRADS 4C. FNAC or core biopsy required urgently. Axillary nodes: no significant lymphadenopathy.'),

-- Case 13: Deepa Menon — thyroid follow-up
(16, 13, 17, 'TSH (Thyroid Stimulating Hormone)',
     'Repeat TSH 6 weeks after increasing Levothyroxine from 50mcg to 75mcg.',
     'PENDING', 17, '2026-03-01 10:35:00',
     NULL, NULL, NULL),

-- Case 20: Vikrant Deshmukh — hypertension
(17, 20, 24, 'Urine Microalbumin / Creatinine Ratio',
     'Screen for early renal involvement in uncontrolled hypertension.',
     'PENDING', 5, '2026-03-20 09:30:00',
     NULL, NULL, NULL),

(18, 20, 24, 'Renal Function Test (Serum Creatinine, BUN)',
     'Baseline renal function. BP suboptimally controlled.',
     'PENDING', 5, '2026-03-20 09:30:00',
     NULL, NULL, NULL),

-- Case 24: Naresh Sharma — new DM diagnosis
(19, 24, 28, 'HbA1c',
     'New presentation with RBS 318 mg/dL. Confirm T2DM and assess 3-month glucose burden.',
     'PENDING', 5, '2026-04-01 09:00:00',
     NULL, NULL, NULL),

(20, 24, 28, 'Fasting Lipid Profile',
     'Assess cardiovascular risk at DM diagnosis.',
     'PENDING', 5, '2026-04-01 09:00:00',
     NULL, NULL, NULL),

(21, 24, 28, 'Urine Routine and Microscopy',
     'Rule out glycosuria and proteinuria at diabetes diagnosis.',
     'PENDING', 5, '2026-04-01 09:00:00',
     NULL, NULL, NULL),

-- Case 25: Santosh Pawar — fall
(22, 25, 30, 'X-ray Right Hip (AP and Lateral views)',
     'Suspected neck of femur fracture after fall. Patient unable to weight-bear.',
     'PENDING', 5, '2026-04-07 09:20:00',
     NULL, NULL, NULL);


-- ============================================================
-- 13. INTERNAL MESSAGES  (IDs 11–22 — new staff conversations)
-- ============================================================
INSERT IGNORE INTO `messages`
  (`message_id`, `sender_user_id`, `recipient_user_id`,
   `subject`, `body`, `is_read`, `sent_at`)
VALUES

-- New doctor (Kiran) getting oriented by Admin
(11, 10, 17,
 'Welcome to D3S3 — orientation checklist',
 'Hi Dr. Rao, welcome to the team! A few things to get you set up: (1) Your login is krao / Test1234! — please change your password on first login via Profile → Settings. (2) The labwork module and patient intake flow are accessible from your dashboard. (3) Nandita (triage nurse) will brief you on the camp workflow this Friday. Any questions, I am always reachable. — Anita.',
 1, '2026-03-01 08:00:00'),

-- Dr. Rao replying
(12, 17, 10,
 'RE: Welcome to D3S3 — orientation checklist',
 'Hi Anita, thank you for the warm welcome! I have changed my password and explored the system briefly. The intake and case sheet flow is clear. One question — where do I access previously closed case sheets for patient history? Looking forward to working with the team. — Dr. Kiran Rao.',
 1, '2026-03-01 08:45:00'),

-- Admin to Dr. Rao answering question
(13, 10, 17,
 'RE: RE: Welcome to D3S3 — orientation checklist',
 'Hi Dr. Rao, to access closed cases, go to Patients → search the patient → click their name → Medical History tab. You will see all prior case sheets in reverse chronological order with full details. Let me know if you need anything else. — Anita.',
 1, '2026-03-01 09:00:00'),

-- Grievance officer flagging a complaint
(14, 15, 10,
 'Complaint Case #11 — Camp supply deficiency (Balaji Venkatesh)',
 'Anita, I have reviewed the complaint from Balaji Venkatesh regarding inadequate wound care supplies at the March 15 camp. This is a valid complaint. The camp pack had only standard gauze and bandages — no saline, no iodoform, no collagen dressings that are required for diabetic foot wounds. I recommend we create a separate "diabetic foot camp supply kit" checklist. Can we discuss at the next team meeting? — Fatima.',
 1, '2026-03-17 10:30:00'),

-- Admin to Grievance Officer
(15, 10, 15,
 'RE: Complaint Case #11 — Camp supply deficiency',
 'Hi Fatima, agreed — I have added this to the April 7th quarterly review agenda. Please prepare a brief proposal (2-3 items) on what a diabetic foot camp kit should include and estimated cost. I will get it approved and implemented before the Hadapsar camp on April 20. — Anita.',
 1, '2026-03-17 11:00:00'),

-- Education team to Admin — asset upload request
(16, 16, 10,
 'New bilingual patient guides ready for upload',
 'Anita, I have completed the Telugu translations for the diabetic foot care guide and the antenatal guide. Both are ready to upload as assets. Files are in the shared drive under /UPLOADS/MARCH2026/. Once uploaded, can you mark them as public so patients can access them? Let me know if the format is OK. — Chitra.',
 1, '2026-03-21 09:00:00'),

-- Paramedic to nursing team re camp logistics
(17, 14, 6,
 'Camp logistics for April 5 — Mulshi',
 'Hi Sneha, for the April 5 Mulshi diabetic foot camp, I will bring the extended wound care kit per the new checklist (saline, iodoform gauze, collagen dressings, measuring tape). Can you confirm how many nursing staff will be attending? Also, do we have a referral letter template for patients needing hospital follow-up from camps? — Ravi.',
 0, '2026-03-28 10:00:00'),

-- Dr. Patel to Dr. Desai — case discussion on Geeta Joshi
(18, 5, 4,
 'Geeta Joshi BIRADS 4C — urgent discussion needed',
 'Priya, the imaging results for Geeta Joshi (Case #21) came back as BIRADS 4C bilaterally. This is a high suspicion lesion and needs urgent FNAC/biopsy referral. I can coordinate with Deenanath\'s oncology team if you want. She has an appointment on March 27 — can we ensure she is seen same day and given a clear referral letter? — Amit.',
 1, '2026-03-24 11:00:00'),

-- Dr. Desai reply
(19, 4, 5,
 'RE: Geeta Joshi BIRADS 4C — urgent discussion needed',
 'Amit, yes please do coordinate. I will prepare the referral letter before March 27. She must not leave that appointment without a confirmed biopsy date. I will also call her today to emotionally prepare her for the conversation — BIRADS 4C will be very frightening news. Appreciate you flagging this. — Priya.',
 0, '2026-03-24 11:30:00'),

-- Triage nurse → Grievance officer re patient complaint
(20, 12, 15,
 'Walk-in patient complaint — waiting time April 7',
 'Fatima, heads up — a patient today (Shobha Hegde, elderly with back pain) complained about a 40-minute wait after triage. She was INTAKE_IN_PROGRESS and Case 23 was in the queue but Dr. Rao was finishing Case 25 (emergency hip fracture). We did our best but she was in pain. She has filed written feedback. Just letting you know before it comes through formally. — Nandita.',
 0, '2026-04-07 11:00:00'),

-- SUPER_ADMIN to team — quarterly review notice
(21, 1, 10,
 'Q1 2026 Quarterly Review — April 7, 4 PM',
 'Anita, please circulate to the full team: the Q1 2026 quarterly review will be held today (April 7) at 4 PM in the conference room. Agenda items: camp statistics, patient outcome summary, staff feedback highlights, Q2 planning. All senior staff are required. Please bring your department notes. — Andrew.',
 1, '2026-04-07 09:00:00'),

-- Admin circulating the above
(22, 10, 4,
 'FWD: Q1 2026 Quarterly Review — April 7, 4 PM',
 'Hi everyone, as communicated by Andrew — please join us today at 4 PM for the Q1 quarterly review in the conference room. Bring notes on your department. See you there. — Anita.',
 0, '2026-04-07 09:15:00');


-- ============================================================
-- 14. TASKS  (IDs 11–22 — new staff and new cases)
-- ============================================================
INSERT IGNORE INTO `tasks`
  (`task_id`, `title`, `description`, `status`, `priority`,
   `assigned_to_user_id`, `created_by_user_id`, `due_date`, `created_at`)
VALUES

(11, 'Prepare diabetic foot camp supply kit checklist',
     'Document complete supply list for diabetic foot camps: saline sachets, iodoform gauze, collagen dressings, wound measuring tape, referral letter template, glucose testing strips. Get costed and submit to Admin for approval.',
     'IN_PROGRESS', 'HIGH', 15, 10, '2026-04-01', '2026-03-17 11:30:00'),

(12, 'Refer Geeta Joshi to surgical oncology — FNAC',
     'Patient with BIRADS 4C mammogram (Case #21). Coordinate referral to Deenanath Hospital Oncology. Prepare referral letter with imaging reports attached. Confirm appointment date before patient visit on March 27.',
     'DONE', 'HIGH', 4, 5, '2026-03-27', '2026-03-24 11:00:00'),

(13, 'Schedule TSH recheck — Deepa Menon',
     'Patient increased to Levothyroxine 75mcg on March 1. TSH recheck due week of April 12. Call patient to confirm she will come fasting. Ensure lab order is in the system.',
     'PENDING', 'MEDIUM', 17, 17, '2026-04-12', '2026-03-01 10:40:00'),

(14, 'Translate asthma action plan to Telugu',
     'The English asthma action plan (Asset #12) needs to be translated to Telugu for patients at rural camps. Coordinate with Chitra Nair (Education Team). Target: ready before April 20 Hadapsar camp.',
     'IN_PROGRESS', 'MEDIUM', 16, 10, '2026-04-18', '2026-03-28 09:30:00'),

(15, 'Compile Q1 2026 camp statistics report',
     'Aggregate: number of camps held (5), total patients seen, case closure breakdown (discharged/follow-up/referral), top diagnoses, feedback summary. Present at April 7 quarterly review.',
     'DONE', 'HIGH', 10, 1, '2026-04-07', '2026-03-28 10:00:00'),

(16, 'Update triage protocol for emergency walk-ins',
     'Draft a written triage protocol that ensures all EMERGENCY-flagged walk-ins are assessed within 5 minutes. Include visual triage flag system (coloured stickers). Review with nursing team and submit to Dr. Patel for clinical sign-off.',
     'PENDING', 'HIGH', 13, 10, '2026-04-15', '2026-03-29 09:00:00'),

(17, 'Follow up Santosh Pawar X-ray result',
     'Elderly patient (Case #25) presented with suspected neck of femur fracture on April 7. X-ray ordered. Follow up result same day. If fracture confirmed, prepare urgent referral to Sassoon General Orthopaedics.',
     'IN_PROGRESS', 'HIGH', 5, 5, '2026-04-07', '2026-04-07 09:25:00'),

(18, 'Mahesh Kulkarni — follow up treadmill stress test booking',
     'Patient needs treadmill stress test at Deenanath Hospital (Case #16). ECG normal but clinical suspicion remains. Confirm booking was made at the March 17 visit. Call patient by April 10 if no confirmation received.',
     'PENDING', 'MEDIUM', 5, 5, '2026-04-10', '2026-03-17 10:00:00'),

(19, 'Ergonomic review for data entry workstation',
     'Meena Rao has reported wrist and neck pain (Feedback #7). Arrange ergonomic assessment for the data entry area. Minimum: adjust monitor height, provide wrist rest, advise on 20-20-20 rule for screen breaks.',
     'PENDING', 'LOW', 10, 10, '2026-04-20', '2026-03-26 11:00:00'),

(20, 'Organise Labwork module refresher for nursing staff',
     'Following Feedback #8, schedule a 30-minute hands-on refresher. Cover: how to mark an order COMPLETED, viewing the pending queue, understanding who is responsible for entering result notes. Confirm with Dr. Patel on clinical responsibilities.',
     'DONE', 'MEDIUM', 1, 1, '2026-03-20', '2026-03-12 09:30:00'),

(21, 'Print April 20 camp registration forms (bilingual)',
     'Use the updated Camp Registration Form v2 (Asset #13). Print 150 copies — 100 English, 50 Telugu. Laminate 5 copies for display boards. Coordinate with Vikram Singh for printing.',
     'PENDING', 'MEDIUM', 8, 10, '2026-04-17', '2026-04-05 10:00:00'),

(22, 'Grievance officer onboarding — system access and workflow',
     'Fatima Siddiqui is new to the system. Walk her through: viewing feedback submissions, updating status, adding admin notes, and messaging relevant staff. Ensure she has reviewed the GRIEVANCE_OFFICER permissions. Schedule 1-hour session.',
     'DONE', 'LOW', 15, 1, '2026-02-25', '2026-02-20 10:00:00');


-- ============================================================
-- Patient Portal seed data (Migration 026)
-- Run AFTER migration 026 has been applied.
-- Creates portal accounts for 3 test patients (IDs 1, 2, 3).
-- Password for all three: Portal@1234
-- ============================================================

-- patient_accounts
-- password_hash below = password_hash('Portal@1234', PASSWORD_DEFAULT)
INSERT IGNORE INTO patient_accounts
    (patient_id, username, email, password_hash, is_active, created_by_user_id)
VALUES
    (1, 'patient_priya',  'priya.patient@example.com',
     '$2y$10$iEbnZFWXi/SriBcbT7FDEepvsVRu9NzL3VYOB5SwT8nHT7jMk930e', 1, 1),
    (2, 'patient_rahul',  'rahul.patient@example.com',
     '$2y$10$iEbnZFWXi/SriBcbT7FDEepvsVRu9NzL3VYOB5SwT8nHT7jMk930e', 1, 1),
    (3, 'patient_ananya', 'ananya.patient@example.com',
     '$2y$10$iEbnZFWXi/SriBcbT7FDEepvsVRu9NzL3VYOB5SwT8nHT7jMk930e', 1, 1);

-- portal_message_threads (patient 1 sent a question)
INSERT IGNORE INTO portal_message_threads
    (thread_id, patient_account_id, subject, last_message_at, patient_unread, staff_unread)
VALUES
    (1, 1, 'Question about my follow-up appointment', '2026-04-07 10:30:00', 0, 1),
    (2, 2, 'Side effects from my prescription', '2026-04-06 15:00:00', 1, 0);

-- portal_messages
INSERT IGNORE INTO portal_messages
    (portal_message_id, thread_id, sender_type, body, sent_at)
VALUES
    (1, 1, 'PATIENT',
     'Hello, I was told to come in for a follow-up next month but I cannot find the exact date in my discharge paperwork. Could you please confirm my appointment date and time? Thank you.',
     '2026-04-07 10:30:00'),
    (2, 2, 'PATIENT',
     'I started the new medication three days ago and I am experiencing mild nausea and dizziness. Is this normal? Should I continue taking it or stop and come in?',
     '2026-04-06 14:45:00'),
    (3, 2, 'STAFF',
     'Thank you for letting us know. Mild nausea and dizziness can be common in the first few days. Please continue taking the medication with food. If symptoms worsen or you experience chest pain or difficulty breathing, come in immediately or call us. We will follow up with you in a few days.',
     '2026-04-06 15:00:00');

-- portal_feedback
INSERT IGNORE INTO portal_feedback
    (portal_feedback_id, patient_account_id, feedback_type, subject, description, rating, status)
VALUES
    (1, 1, 'POSITIVE', 'Excellent care at the March camp',
     'The nurses were very kind and patient. The doctor explained everything clearly. I felt well looked after. Thank you to the whole team.',
     5, 'NEW'),
    (2, 2, 'SUGGESTION', 'Longer clinic hours would help working patients',
     'It is difficult to attend morning-only clinics when working. An evening slot or Saturday clinic would be very helpful for patients who cannot take time off work.',
     NULL, 'NEW');

COMMIT;

-- ============================================================
-- Summary of test data added by this script:
-- ============================================================
-- users:                    6 new  (IDs 12-17)   → Total 17
-- patients:                16 new  (IDs 15-30)   → Total 30
-- patient_daily_sequence:   8 new date rows
-- case_sheets:             15 new  (IDs 11-25)   → Total 25
-- case_sheet_audit_log:    20 new  (IDs 1-20)
-- user_preferences:        12 rows (IDs 1-12)
-- feedback (staff):         8 rows (IDs 1-8)
-- patient_record_access_log: 15 rows (IDs 1-15)
-- events:                   6 new  (IDs 8-13)    → Total 13
-- assets:                   7 new  (IDs 9-15)    → Total 15
-- patient_feedback:        10 new  (IDs 9-18)    → Total 18
-- appointments:            10 new  (IDs 9-18)    → Total 18 (phase2+3)
-- lab_orders:              12 new  (IDs 11-22)   → Total 22 (phase2+3)
-- messages (internal):     12 new  (IDs 11-22)   → Total 22 (phase2+3)
-- tasks:                   12 new  (IDs 11-22)   → Total 22 (phase2+3)
-- ============================================================
