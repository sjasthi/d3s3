-- ============================================================================
-- Analytics test data – April 2026
-- Populates: case_sheets (CS26–CS46), patient_feedback (FB19–FB28),
--            case_sheet_audit_log (intake timing), appointments (scheduled)
-- Run after core_app.sql + test_data_phase3.sql.
-- ============================================================================

SET NAMES utf8mb4;

-- ── Case sheets ──────────────────────────────────────────────────────────────
-- Spread across April 2026 (the default date range) with varied:
--   visit types, closure types, chief complaints, assessment JSON,
--   vitals JSON (medicine_sources), assigned doctors, and intake staff.
--
-- Doctors:  6 = Hema Sri Ala, 12 = Doctor Test01, 17 = Kiran Rao
-- Intake:   7 = Harshitha Sai Alakunta, 13 = Nurse Test01, 14 = Triage NurseTest01
-- Follow-up compliance:
--   CS28 (Patient 7, due Apr 9) → CS37 return visit on Apr 9  = COMPLIANT
--   CS34 (Patient 13, due Apr 15) → no return                 = non-compliant
--   CS40 (Patient 24, due Apr 19) → no return                 = non-compliant
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO case_sheets
  (case_sheet_id, patient_id, created_by_user_id, assigned_doctor_user_id,
   visit_datetime, visit_type, status,
   chief_complaint, vitals_json, assessment,
   is_closed, closed_at, closed_by_user_id, closure_type,
   follow_up_date, referral_to, referral_reason)
VALUES

-- Apr 1 (Wednesday) ──────────────────────────────────────────────────────────

-- CS26: Sunita Devi (F/40) – diabetes management
(26, 5, 14, 6, '2026-04-01 09:00:00', 'CLINIC', 'CLOSED',
 'Uncontrolled blood sugar',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"CURRENT","condition_htn":"","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"1","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-01 09:45:00', 6, 'DISCHARGED', NULL, NULL, NULL),

-- CS27: Arun Kumar (M/53) – hypertension
(27, 6, 13, 12, '2026-04-01 10:15:00', 'CLINIC', 'CLOSED',
 'High blood pressure – routine follow-up',
 '{"medicine_sources":"PRIVATE"}',
 '{"condition_dm":"","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"PAST","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-01 11:00:00', 12, 'DISCHARGED', NULL, NULL, NULL),

-- CS28: Lakshmi Iyer (F/35) – thyroid, scheduled for follow-up Apr 9
(28, 7, 7, 17, '2026-04-01 11:30:00', 'CAMP', 'CLOSED',
 'Thyroid medication review',
 '{"medicine_sources":"PRIVATE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"CURRENT","condition_heart_disease":"","condition_others":"","family_history_cancer":"1","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"0","family_history_thyroid":"1","family_history_other":""}',
 1, '2026-04-01 12:00:00', 17, 'FOLLOW_UP', '2026-04-09', NULL, NULL),

-- Apr 3 (Friday) ─────────────────────────────────────────────────────────────

-- CS29: Mohammed Ansari (M/58) – referred to cardiology
(29, 8, 14, 6, '2026-04-03 09:00:00', 'CLINIC', 'CLOSED',
 'Chest pain and breathlessness',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"CURRENT","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"1","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-03 10:00:00', 6, 'REFERRAL', NULL,
 'Cardiology Dept. – District Hospital',
 'Chest pain and breathlessness requiring cardiac evaluation'),

-- CS30: Kavita Reddy (F/30) – thyroid + anemia
(30, 9, 13, 12, '2026-04-03 10:30:00', 'CLINIC', 'CLOSED',
 'Fatigue and hair loss',
 '{"medicine_sources":"NONE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"CURRENT","condition_heart_disease":"","condition_others":"Anemia","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"1","family_history_bp":"0","family_history_thyroid":"1","family_history_other":""}',
 1, '2026-04-03 11:05:00', 12, 'DISCHARGED', NULL, NULL, NULL),

-- Apr 7 (Tuesday) ────────────────────────────────────────────────────────────

-- CS31: Rajesh Verma (M/45) – hypertension
(31, 10, 7, 17, '2026-04-07 11:00:00', 'CLINIC', 'CLOSED',
 'Persistent headache',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-07 11:40:00', 17, 'DISCHARGED', NULL, NULL, NULL),

-- CS32: Anjali Nair (F/25) – asthma
(32, 11, 14, 6, '2026-04-07 12:00:00', 'CAMP', 'CLOSED',
 'Skin rash and itching',
 '{"medicine_sources":"PRIVATE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"","condition_heart_disease":"","condition_others":"Asthma","family_history_cancer":"0","family_history_tuberculosis":"1","family_history_diabetes":"0","family_history_bp":"0","family_history_thyroid":"0","family_history_other":"Asthma"}',
 1, '2026-04-07 12:35:00', 6, 'DISCHARGED', NULL, NULL, NULL),

-- CS33: Suresh Yadav (M/70) – DM + HTN + heart, referred to pulmonology
(33, 12, 13, 12, '2026-04-07 13:30:00', 'CLINIC', 'CLOSED',
 'Worsening breathlessness and ankle swelling',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"CURRENT","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"CURRENT","condition_others":"COPD","family_history_cancer":"0","family_history_tuberculosis":"1","family_history_diabetes":"1","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-07 14:30:00', 12, 'REFERRAL', NULL,
 'Pulmonology – General Hospital',
 'COPD exacerbation requiring specialist care'),

-- Apr 8 (Wednesday) ──────────────────────────────────────────────────────────

-- CS34: Pooja Bhatt (F/37) – follow-up due Apr 15
(34, 13, 7, 17, '2026-04-08 09:00:00', 'CLINIC', 'CLOSED',
 'Lower back pain',
 '{"medicine_sources":"PRIVATE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"1","family_history_bp":"0","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-08 09:35:00', 17, 'FOLLOW_UP', '2026-04-15', NULL, NULL),

-- CS35: Ramesh Chauhan (M/51) – emergency, chest pain
(35, 14, 14, 6, '2026-04-08 10:30:00', 'EMERGENCY', 'CLOSED',
 'Chest pain and breathlessness',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"PAST","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"1","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-08 12:00:00', 6, 'DISCHARGED', NULL, NULL, NULL),

-- CS36: Mahesh Kulkarni (M/42) – fever
(36, 20, 13, 12, '2026-04-08 14:00:00', 'CLINIC', 'CLOSED',
 'Fever and body aches',
 '{"medicine_sources":"NONE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"1","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"0","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-08 14:50:00', 12, 'DISCHARGED', NULL, NULL, NULL),

-- Apr 9 (Thursday) ───────────────────────────────────────────────────────────

-- CS37: Lakshmi Iyer (F/35) – returns for Apr 9 follow-up → compliance met
(37, 7, 7, 17, '2026-04-09 09:30:00', 'FOLLOW_UP', 'CLOSED',
 'Thyroid follow-up – medication check',
 '{"medicine_sources":"PRIVATE"}',
 NULL,
 1, '2026-04-09 10:00:00', 17, 'DISCHARGED', NULL, NULL, NULL),

-- CS38: Savita Patil (F/27) – thyroid + anemia
(38, 21, 14, 6, '2026-04-09 11:00:00', 'CAMP', 'CLOSED',
 'Fatigue and irregular menstrual cycle',
 '{"medicine_sources":"NONE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"CURRENT","condition_heart_disease":"","condition_others":"Anemia","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"0","family_history_thyroid":"1","family_history_other":""}',
 1, '2026-04-09 11:40:00', 6, 'DISCHARGED', NULL, NULL, NULL),

-- Apr 10 (Friday) ────────────────────────────────────────────────────────────

-- CS39: Balaji Venkatesh (M/65) – DM + HTN + heart, referred
(39, 22, 13, 12, '2026-04-10 09:00:00', 'CLINIC', 'CLOSED',
 'Palpitations and chest tightness',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"CURRENT","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"CURRENT","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"1","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-10 10:15:00', 12, 'REFERRAL', NULL,
 'Cardiac Care Centre',
 'Palpitations and heart disease requiring specialist management'),

-- CS40: Vikrant Deshmukh (M/51) – HTN, follow-up due Apr 19
(40, 24, 7, 17, '2026-04-10 11:00:00', 'CAMP', 'CLOSED',
 'High blood pressure – routine follow-up',
 '{"medicine_sources":"PRIVATE"}',
 '{"condition_dm":"","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-10 11:35:00', 17, 'FOLLOW_UP', '2026-04-19', NULL, NULL),

-- Apr 14 (Tuesday) ───────────────────────────────────────────────────────────

-- CS41: Geeta Joshi (F/55) – DM + HTN + thyroid history
(41, 25, 14, 6, '2026-04-14 09:30:00', 'CLINIC', 'CLOSED',
 'Joint pain in both knees',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"CURRENT","condition_htn":"CURRENT","condition_tsh":"PAST","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"1","family_history_bp":"1","family_history_thyroid":"1","family_history_other":""}',
 1, '2026-04-14 10:15:00', 6, 'DISCHARGED', NULL, NULL, NULL),

-- CS42: Aditya Khanna (M/21) – no conditions
(42, 26, 13, 12, '2026-04-14 11:00:00', 'CLINIC', 'CLOSED',
 'Cough and sore throat',
 '{"medicine_sources":"NONE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"0","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-14 11:35:00', 12, 'DISCHARGED', NULL, NULL, NULL),

-- Apr 15 (Wednesday) ─────────────────────────────────────────────────────────

-- CS43: Preethi Raj (F/23) – thyroid
(43, 23, 7, 17, '2026-04-15 09:00:00', 'CLINIC', 'CLOSED',
 'Unexplained weight gain and fatigue',
 '{"medicine_sources":"PRIVATE"}',
 '{"condition_dm":"","condition_htn":"","condition_tsh":"CURRENT","condition_heart_disease":"","condition_others":"","family_history_cancer":"1","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"0","family_history_thyroid":"1","family_history_other":""}',
 1, '2026-04-15 09:40:00', 17, 'DISCHARGED', NULL, NULL, NULL),

-- CS44: Shobha Hegde (F/70) – HTN + asthma
(44, 27, 14, 6, '2026-04-15 10:30:00', 'CAMP', 'CLOSED',
 'Shortness of breath and leg swelling',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"PAST","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"PAST","condition_others":"Asthma","family_history_cancer":"0","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"1","family_history_thyroid":"0","family_history_other":"Stroke"}',
 1, '2026-04-15 11:15:00', 6, 'DISCHARGED', NULL, NULL, NULL),

-- Apr 16 (Thursday) ──────────────────────────────────────────────────────────

-- CS45: Rekha Pillai (F/62) – HTN
(45, 29, 13, 12, '2026-04-16 09:00:00', 'CLINIC', 'CLOSED',
 'High blood pressure – routine follow-up',
 '{"medicine_sources":"GOVERNMENT"}',
 '{"condition_dm":"","condition_htn":"CURRENT","condition_tsh":"","condition_heart_disease":"","condition_others":"","family_history_cancer":"1","family_history_tuberculosis":"0","family_history_diabetes":"0","family_history_bp":"1","family_history_thyroid":"0","family_history_other":""}',
 1, '2026-04-16 09:50:00', 12, 'DISCHARGED', NULL, NULL, NULL),

-- CS46: Santosh Pawar (M/80) – still in review
(46, 30, 7, 17, '2026-04-16 11:00:00', 'CLINIC', 'DOCTOR_REVIEW',
 'Uncontrolled blood sugar',
 NULL,
 NULL,
 0, NULL, NULL, 'PENDING', NULL, NULL, NULL);


-- ── Audit log – intake completion times (for Caseload intake timing) ─────────
-- Represents the nurse completing intake, logged as status → INTAKE_COMPLETE.
-- TIMESTAMPDIFF(MINUTE, visit_datetime, changed_at) = intake duration in minutes.

INSERT INTO case_sheet_audit_log
  (audit_id, case_sheet_id, user_id, changed_by_name, field_name, old_value, new_value, changed_at)
VALUES
(45, 26, 14, 'Triage NurseTest01', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-01 09:12:00'),
(46, 27, 13, 'Nurse Test01',       'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-01 10:28:00'),
(47, 28,  7, 'Harshitha Sai Alakunta', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-01 11:43:00'),
(48, 29, 14, 'Triage NurseTest01', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-03 09:14:00'),
(49, 30, 13, 'Nurse Test01',       'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-03 10:42:00'),
(50, 31,  7, 'Harshitha Sai Alakunta', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-07 11:13:00'),
(51, 32, 14, 'Triage NurseTest01', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-07 12:11:00'),
(52, 33, 13, 'Nurse Test01',       'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-07 13:44:00'),
(53, 34,  7, 'Harshitha Sai Alakunta', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-08 09:11:00'),
(54, 35, 14, 'Triage NurseTest01', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-08 10:46:00'),
(55, 36, 13, 'Nurse Test01',       'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-08 14:09:00'),
(56, 37,  7, 'Harshitha Sai Alakunta', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-09 09:40:00'),
(57, 38, 14, 'Triage NurseTest01', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-09 11:12:00'),
(58, 39, 13, 'Nurse Test01',       'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-10 09:15:00'),
(59, 40,  7, 'Harshitha Sai Alakunta', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-10 11:12:00'),
(60, 41, 14, 'Triage NurseTest01', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-14 09:42:00'),
(61, 42, 13, 'Nurse Test01',       'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-14 11:13:00'),
(62, 43,  7, 'Harshitha Sai Alakunta', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-15 09:10:00'),
(63, 44, 14, 'Triage NurseTest01', 'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-15 10:42:00'),
(64, 45, 13, 'Nurse Test01',       'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE', '2026-04-16 09:11:00');


-- ── Appointments – links case sheets to scheduled visits ─────────────────────
-- Provides "scheduled vs walk-in" data for the Caseload tab.

INSERT INTO appointments
  (appointment_id, case_sheet_id, doctor_user_id, scheduled_date, scheduled_time,
   visit_mode, status, created_by_user_id)
VALUES
(19, 27, 12, '2026-04-01', '10:15:00', 'IN_PERSON', 'COMPLETED', 13),
(20, 31, 17, '2026-04-07', '11:00:00', 'IN_PERSON', 'COMPLETED',  7),
(21, 34, 17, '2026-04-08', '09:00:00', 'IN_PERSON', 'COMPLETED',  7),
(22, 41,  6, '2026-04-14', '09:30:00', 'IN_PERSON', 'COMPLETED', 14),
(23, 43, 17, '2026-04-15', '09:00:00', 'IN_PERSON', 'COMPLETED',  7);


-- ── Patient feedback (April 2026) ─────────────────────────────────────────────
-- Covers the Satisfaction tab: feedback type mix, ratings, staff breakdown,
-- complaint pipeline (NEW → REVIEWED → ACTIONED → CLOSED).

INSERT INTO patient_feedback
  (feedback_id, patient_id, related_user_id, feedback_type, rating,
   feedback_text, status, admin_notes, created_at)
VALUES
(19,  5,  6, 'POSITIVE',    5,
 'The doctor was very thorough and explained everything clearly. Felt reassured.',
 'REVIEWED', NULL, '2026-04-01 10:30:00'),

(20,  8,  6, 'COMPLAINT',   2,
 'Waited over an hour despite having an appointment. No explanation was given for the delay.',
 'NEW', NULL, '2026-04-03 11:30:00'),

(21, 10, 17, 'POSITIVE',    4,
 'Dr. Rao was helpful and the treatment has been working well.',
 'NEW', NULL, '2026-04-07 12:45:00'),

(22, 12, 12, 'COMPLAINT',   1,
 'Prescribed medication was not available and staff did not suggest an alternative.',
 'REVIEWED', 'Escalated to pharmacy team for follow-up.', '2026-04-07 15:00:00'),

(23,  7, 17, 'POSITIVE',    5,
 'Follow-up was handled quickly. My thyroid levels are much better now.',
 'NEW', NULL, '2026-04-09 10:30:00'),

(24, 22, 12, 'SUGGESTION',  NULL,
 'It would help to have a printed summary of the visit for the patient to take home.',
 'NEW', NULL, '2026-04-10 11:00:00'),

(25, 25,  6, 'POSITIVE',    4,
 'Camp was well organised and the staff were friendly and patient.',
 'NEW', NULL, '2026-04-14 11:00:00'),

(26, 26, 12, 'POSITIVE',    5,
 'Quick and professional. Happy with the care received.',
 'NEW', NULL, '2026-04-14 12:00:00'),

(27, 23, 17, 'SUGGESTION',  NULL,
 'A reminder SMS before the appointment would be very useful.',
 'ACTIONED', 'Added to feature request list for patient portal.', '2026-04-15 10:15:00'),

(28, 29, 12, 'COMPLAINT',   2,
 'The camp location was not clearly marked and I had difficulty finding it.',
 'CLOSED', 'Signage improved for next camp. Apology sent to patient.', '2026-04-16 10:30:00');
