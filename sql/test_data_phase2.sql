-- ============================================================
-- D3S3 CareSystem – Phase 2 Supplemental Test / Seed Data
-- Generated: 2026-04-08
--
-- Covers tables added after test_data.sql was written:
--   messages   (internal staff-to-staff messaging, migration 013)
--   tasks      (task list, migration 014)
--   appointments (appointment scheduling, migration 016)
--   lab_orders (lab test orders, migration 020)
--
-- Prerequisites:
--   Run core_app.sql, then test_data.sql, then all migrations
--   up through 025, THEN run this file.
--
-- All inserts use INSERT IGNORE — safe to re-run.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- ============================================================
-- 1. INTERNAL MESSAGES  (staff ↔ staff)
--    Users from test_data.sql:
--      4  = Dr. Priya Desai    (DOCTOR)
--      5  = Dr. Amit Patel     (DOCTOR)
--      6  = Sneha Kulkarni     (NURSE)
--      7  = Rohan Mehta        (NURSE)
--      8  = Meena Rao          (DATA_ENTRY_OPERATOR)
--      9  = Vikram Singh       (DATA_ENTRY_OPERATOR)
--      10 = Anita Gupta        (ADMIN)
-- ============================================================
INSERT IGNORE INTO `messages`
  (`message_id`, `sender_user_id`, `recipient_user_id`, `subject`, `body`, `is_read`, `sent_at`)
VALUES

-- Admin → Dr. Desai: scheduling notice
(1, 10, 4,
 'Camp schedule update – Feb 15',
 'Hi Dr. Desai, just a reminder that the Eye Screening Camp on Feb 15 starts at 9 AM sharp. Please confirm by end of day if you can attend. We have registered approx. 60 patients. Thanks, Anita.',
 1, '2026-02-13 09:00:00'),

-- Dr. Desai → Admin: reply
(2, 4, 10,
 'RE: Camp schedule update – Feb 15',
 'Hi Anita, confirmed. I will be there by 8:45. Could you also arrange for a glucometer and BP cuffs — we had a shortage at the last camp. Dr. Desai.',
 1, '2026-02-13 09:30:00'),

-- Admin → Dr. Patel: patient handover note
(3, 10, 5,
 'Patient Mohammed Ansari – follow-up Feb 17',
 'Dear Dr. Patel, Mohammed Ansari (Case #4) is scheduled for follow-up on Feb 17 for his diabetes review. His fasting sugar this morning was 280. Please note he is very anxious about his diet — recommend spending extra time on counselling. Regards, Anita.',
 1, '2026-02-13 10:00:00'),

-- Nurse Sneha → Dr. Desai: clinical question
(4, 6, 4,
 'Vitals concern – Kavita Reddy',
 'Dr. Desai, Kavita Reddy (Case #5) came in today for a recheck. Her temp is still 99.8F and she says the sore throat is only 50% better after 3 days of antibiotics. Should I schedule her for a review today or wait? – Sneha.',
 1, '2026-02-14 10:15:00'),

-- Dr. Desai → Nurse Sneha: reply
(5, 4, 6,
 'RE: Vitals concern – Kavita Reddy',
 'Sneha, bring her in today. Persistent fever after 3 days of Amoxicillin may indicate resistance or a complication. I can see her at 2 PM. Please take a throat swab for culture before she comes in. – Dr. Desai.',
 1, '2026-02-14 10:30:00'),

-- Nurse Rohan → Admin: supplies request
(6, 7, 10,
 'Supplies needed – glucometer strips',
 'Hi Anita, we are running very low on glucometer strips (Accu-Chek). We have maybe 20 left and usage is high with the diabetes patients. Can you place an order ASAP? We will need at least 200 strips. Thanks, Rohan.',
 1, '2026-02-14 11:00:00'),

-- Admin → Nurse Rohan: reply
(7, 10, 7,
 'RE: Supplies needed – glucometer strips',
 'Hi Rohan, I have placed an order — should arrive by Feb 16. In the meantime, please use the backup Contour strips in the supply cabinet (drawer 3). – Anita.',
 0, '2026-02-14 11:45:00'),

-- Dr. Patel → Dr. Desai: case discussion
(8, 5, 4,
 'Suresh Yadav post-referral update',
 'Priya, just got word from Ruby Hall — Suresh Yadav (Case #8) was diagnosed with acute cholecystitis and underwent laparoscopic cholecystectomy yesterday. He is stable and will be discharged in 2 days. Worth updating his case sheet. – Amit.',
 0, '2026-02-15 08:30:00'),

-- Data Entry → Admin: system question
(9, 8, 10,
 'Patient code for walk-in today',
 'Anita ma\'am, we had a walk-in today who left without registering — should we still create a patient record? Also, the system generated patient code 20260215000 but the patient refused to give Aadhaar. Is that field mandatory? – Meena.',
 1, '2026-02-15 09:15:00'),

-- Admin → Data Entry: reply
(10, 10, 8,
 'RE: Patient code for walk-in today',
 'Hi Meena, yes create the record — Aadhaar is optional, not mandatory. Just leave it blank. For the walk-in who left, mark the case as PENDING and note "Patient left before consultation" in the chief complaint. – Anita.',
 0, '2026-02-15 09:45:00');


-- ============================================================
-- 2. TASKS
--    Mix of statuses, priorities, and assignees.
-- ============================================================
INSERT IGNORE INTO `tasks`
  (`task_id`, `title`, `description`, `status`, `priority`, `assigned_to_user_id`, `created_by_user_id`, `due_date`, `created_at`)
VALUES

-- Admin tasks
(1,  'Order glucometer strips',
     'Accu-Chek strips, qty 200. Supplier: MedSupply India. Also order 50 lancets.',
     'DONE', 'HIGH', 10, 10, '2026-02-16', '2026-02-14 11:50:00'),

(2,  'Set up token system for next camp',
     'Design and print numbered tokens for the Feb 22 camp. Also procure 10 extra chairs for elderly patients based on feedback received.',
     'IN_PROGRESS', 'MEDIUM', 10, 10, '2026-02-20', '2026-02-11 09:00:00'),

(3,  'Update patient intake form template',
     'Add Aadhaar field as optional. Include checkbox for "Patient declined to provide". Share with data entry team once updated.',
     'PENDING', 'LOW', 8, 10, '2026-02-28', '2026-02-14 12:00:00'),

-- Doctor tasks
(4,  'Review Pooja Bhatt lab results',
     'Patient Pooja Bhatt (ID 13, Case #9) — CBC, TSH, Free T4, iron studies ordered Feb 12. Results expected by Feb 16. Call patient to discuss findings.',
     'PENDING', 'HIGH', 4, 4, '2026-02-17', '2026-02-12 10:05:00'),

(5,  'Update Mohammed Ansari case notes',
     'Add post-camp counselling notes to Case #4. Record blood sugar diary review outcomes from the Feb 17 follow-up visit.',
     'DONE', 'MEDIUM', 5, 5, '2026-02-18', '2026-02-13 14:30:00'),

(6,  'Prepare diabetes education handout (Telugu)',
     'Translate the "Understanding Diabetes" PDF into Telugu. Coordinate with education team. Aim for plain language suitable for low-literacy patients.',
     'IN_PROGRESS', 'MEDIUM', 4, 10, '2026-03-01', '2026-02-15 09:00:00'),

-- Nurse tasks
(7,  'Check BP cuff calibration',
     'Both BP cuffs (exam room 1 and camp kit) need calibration check. Log result in equipment register.',
     'DONE', 'HIGH', 6, 7, '2026-02-14', '2026-02-13 08:00:00'),

(8,  'Follow up on Kavita Reddy throat swab culture',
     'Lab result expected Feb 16. If positive for Group A Strep, notify Dr. Desai immediately for antibiotic review.',
     'IN_PROGRESS', 'HIGH', 6, 6, '2026-02-16', '2026-02-14 10:35:00'),

(9,  'Prepare camp supply checklist for Feb 22',
     'Include: BP cuffs x3, glucometers x2, strips, lancets, tongue depressors, thermometers, registration forms, patient info sheets.',
     'PENDING', 'MEDIUM', 7, 10, '2026-02-20', '2026-02-15 10:00:00'),

-- Data entry task
(10, 'Archive February paper registration forms',
     'Scan and upload Feb 1–13 paper forms to the shared drive. Shred originals after scanning (per policy).',
     'PENDING', 'LOW', 8, 10, '2026-02-28', '2026-02-14 13:00:00');


-- ============================================================
-- 3. APPOINTMENTS
--    Linked to existing case sheets (IDs 1–10) and doctors
--    (user_id 4 = Dr. Desai, 5 = Dr. Patel).
--    Uses follow-up dates from case sheet data where applicable.
-- ============================================================
INSERT IGNORE INTO `appointments`
  (`appointment_id`, `case_sheet_id`, `doctor_user_id`, `scheduled_date`, `scheduled_time`,
   `visit_mode`, `event_id`, `status`, `notes`, `created_by_user_id`, `created_at`)
VALUES

-- Case 1: Sunita Devi — BP follow-up (from case sheet follow_up_date)
(1, 1, 4, '2026-02-24', '10:00:00', 'IN_PERSON', NULL,
 'SCHEDULED',
 'BP follow-up. Review home BP log and medication tolerance.',
 8, '2026-02-10 09:55:00'),

-- Case 3: Lakshmi Iyer — Knee OA follow-up
(2, 3, 5, '2026-02-24', '11:00:00', 'IN_PERSON', NULL,
 'SCHEDULED',
 'Knee OA review. Assess pain response to Paracetamol. Consider X-ray if no improvement.',
 9, '2026-02-10 10:40:00'),

-- Case 4: Mohammed Ansari — Diabetes follow-up (COMPLETED — already happened)
(3, 4, 5, '2026-02-17', '09:30:00', 'IN_PERSON', NULL,
 'COMPLETED',
 'Diabetes review. Blood sugar diary reviewed. HbA1c repeat ordered.',
 8, '2026-02-10 10:40:00'),

-- Case 6: Rajesh Verma — Back pain review
(4, 6, 5, '2026-02-25', '14:00:00', 'IN_PERSON', NULL,
 'SCHEDULED',
 'LBP radiculopathy review. Assess numbness in left foot. Order MRI if no improvement.',
 9, '2026-02-11 10:10:00'),

-- Case 9: Pooja Bhatt — Lab report review
(5, 9, 4, '2026-02-19', '10:30:00', 'IN_PERSON', NULL,
 'COMPLETED',
 'Review CBC, TSH, Free T4, iron studies. Pelvic ultrasound result expected.',
 9, '2026-02-12 10:05:00'),

-- Camp appointment: Sunita Devi at Eye Screening Camp (event 3)
(6, 1, 4, '2026-02-15', '09:30:00', 'CAMP', 3,
 'COMPLETED',
 'Routine eye screening during camp. BP check also done.',
 8, '2026-02-13 09:00:00'),

-- Future: Mohammed Ansari — next diabetes check
(7, 4, 5, '2026-03-03', '09:30:00', 'IN_PERSON', NULL,
 'SCHEDULED',
 'Second follow-up. Review HbA1c result. Adjust medications if needed.',
 8, '2026-02-17 10:00:00'),

-- No-show: Rajesh Verma missed an earlier appointment
(8, 6, 5, '2026-02-18', '11:00:00', 'IN_PERSON', NULL,
 'NO_SHOW',
 'Patient did not attend. Called — said pain had subsided. Rescheduled to Feb 25.',
 9, '2026-02-11 10:15:00');


-- ============================================================
-- 4. LAB ORDERS
--    Ordered from case sheets; mix of PENDING and COMPLETED.
--    completed_by_user_id 6 = Nurse Sneha, 7 = Nurse Rohan.
-- ============================================================
INSERT IGNORE INTO `lab_orders`
  (`order_id`, `case_sheet_id`, `patient_id`, `test_name`, `order_notes`,
   `status`, `ordered_by_user_id`, `ordered_at`,
   `completed_by_user_id`, `completed_at`, `result_notes`)
VALUES

-- Case 4: Mohammed Ansari — diabetes workup (COMPLETED)
(1,  4, 8, 'Fasting Blood Sugar (FBS)',
     'Patient on Metformin + Glimepiride. Check fasting glucose for Feb 17 review.',
     'COMPLETED', 5, '2026-02-10 10:50:00',
     6, '2026-02-16 09:30:00',
     'FBS: 186 mg/dL. Improved from 320 at presentation but still above target (<130). Continue current medications. Review HbA1c at next visit.'),

(2,  4, 8, 'HbA1c',
     'Repeat HbA1c to assess 3-month glucose control.',
     'COMPLETED', 5, '2026-02-10 10:50:00',
     6, '2026-02-16 09:45:00',
     'HbA1c: 8.4%. Down from 9.2% three months ago. Trending in right direction. Target <7%. Intensify lifestyle modifications.'),

(3,  4, 8, 'Serum Creatinine',
     'Baseline renal function before intensifying medications.',
     'COMPLETED', 5, '2026-02-10 10:50:00',
     6, '2026-02-16 10:00:00',
     'Creatinine: 1.1 mg/dL. Within normal range (0.7–1.3). No evidence of diabetic nephropathy at this stage.'),

-- Case 9: Pooja Bhatt — menstrual irregularity workup (COMPLETED)
(4,  9, 13, 'Complete Blood Count (CBC)',
     'Rule out anemia secondary to heavy menstrual bleeding.',
     'COMPLETED', 4, '2026-02-12 10:00:00',
     7, '2026-02-15 10:15:00',
     'Hb: 9.8 g/dL (low). MCV: 72 fL (microcytic). Iron-deficiency anemia confirmed. Continue ferrous fumarate supplementation.'),

(5,  9, 13, 'TSH (Thyroid Stimulating Hormone)',
     'Rule out hypothyroidism causing menstrual irregularity and fatigue.',
     'COMPLETED', 4, '2026-02-12 10:00:00',
     7, '2026-02-15 10:30:00',
     'TSH: 7.2 mIU/L (elevated, normal 0.5–4.5). Subclinical hypothyroidism confirmed. Start Levothyroxine 50mcg OD — advise Dr. Desai.'),

(6,  9, 13, 'Iron Studies (Serum Iron, TIBC, Ferritin)',
     'Confirm iron-deficiency etiology for microcytic anemia.',
     'COMPLETED', 4, '2026-02-12 10:00:00',
     7, '2026-02-15 10:45:00',
     'Serum Iron: 42 mcg/dL (low). TIBC: 480 mcg/dL (high). Ferritin: 6 ng/mL (very low). Classic iron-deficiency pattern. Continue supplementation, recheck in 6 weeks.'),

-- Case 6: Rajesh Verma — back pain (PENDING — awaiting results)
(7,  6, 10, 'MRI Lumbar Spine',
     'Persistent left L5-S1 radiculopathy with foot numbness. Evaluate disc herniation severity.',
     'PENDING', 5, '2026-02-25 14:30:00',
     NULL, NULL, NULL),

-- Case 1: Sunita Devi — hypertension monitoring (PENDING)
(8,  1, 5, 'Lipid Profile',
     'Baseline lipid panel for new hypertensive patient. Rule out dyslipidemia.',
     'PENDING', 4, '2026-02-24 10:15:00',
     NULL, NULL, NULL),

(9,  1, 5, 'Renal Function Test (RFT)',
     'Baseline renal function before long-term antihypertensive use.',
     'PENDING', 4, '2026-02-24 10:15:00',
     NULL, NULL, NULL),

-- Case 10: Ramesh Chauhan — UTI (PENDING)
(10, 10, 14, 'Urine Culture & Sensitivity',
     'Confirm acute cystitis diagnosis and guide antibiotic selection if empirical treatment fails.',
     'PENDING', 4, '2026-02-13 08:25:00',
     NULL, NULL, NULL);


COMMIT;

-- ============================================================
-- Summary of test data added by this script:
-- ============================================================
-- messages (internal):   10 rows  (IDs 1-10)
-- tasks:                 10 rows  (IDs 1-10)
-- appointments:           8 rows  (IDs 1-8)
-- lab_orders:            10 rows  (IDs 1-10)
-- ============================================================
