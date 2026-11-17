# Issue #7 — Case sheet reconciled with the paper form

Paper form: `project-resources/project_forms_resources_docs/4. Case Sheet - routinely we fill this.pdf`  
Filled example: `project-resources/project_forms_resources_docs/Filled case sheet.pdf`

The intake / doctor review screens are that form. Storage mapping:

| Paper section | Stored in |
| --- | --- |
| Identity, phone, address | `patients` |
| Visit type and chief complaint | `case_sheets.visit_type`, `chief_complaint` |
| History and background | `case_sheets.assessment` (JSON) |
| Vitals | `case_sheets.vitals_json` |
| Examination (head & neck, breast, pelvic, rectal, gynae) | `case_sheets.exam_notes` (JSON) |
| USG findings (text) | `case_sheets.exam_notes` → `exam_usg_findings` |
| Ultrasound image | `case_attachments` with `kind = 'ultrasound'` (private under `storage/case-files/`) |
| Diagrams | `diag_breast`, `diag_pelvic`, `diag_via`, `diag_vili` |
| Labs | `lab_orders` and labs JSON in `diagnosis` |
| Plan, medicines, advice | `plan_notes`, `prescriptions`, `advice`, and the doctor columns |
| Follow-up and referral | `follow_up_date`, `follow_up_notes`, `referral_to` |
| Closure | `closure_type`, `is_closed`, `closed_at` |

Ultrasound images are not web-accessible by direct URL. Playback / view goes through `case_file.php?action=view&id=…` after a logged-in ACL check.
