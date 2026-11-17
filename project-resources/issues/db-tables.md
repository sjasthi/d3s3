# Issue #5 — Database tables

The CareSystem schema (`core_app`) covers the data domains from the issue.

| Data from the issue | Table(s) | Identifier / notes |
| --- | --- | --- |
| Patient registration | `patients` | `patient_code` = `YYYYMMDDNNN` via DB trigger. Example: 5th patient on 4 Feb 2026 → `20260204005`. |
| Case sheets (1 patient → N sheets) | `case_sheets` | PK `case_sheet_id` (bigint). Display reference `patient_code_NN` (e.g. `20260204005_01`). Follow-ups store `prior_case_sheet_id`. |
| Assets (metadata only) | `assets` | URL or local path; `is_public` defaults public; staff can mark private. |
| Events (camps, seminars) | `events` | Shown on the calendar. |
| Staff users | `users` | Separate from patients; roles in `role` / `role_permissions`. |
| Patient feedback | `patient_feedback` (+ staff `feedback`) | Portal feedback requires a logged-in patient. |
| Case closure | columns on `case_sheets` | `closure_type`: `DISCHARGED` (on-the-spot), `FOLLOW_UP`, `REFERRAL`, `PENDING`. Also `is_closed`, `closed_at`. |

## Case sheet display codes

Issue text suggested IDs like `20260204005_01`. Those are **display labels** built as `{patient_code}_{sequence}`, not a second primary key. Sequence is the patient’s case sheets ordered by `case_sheet_id`. Helper: `app/config/case_sheet_code.php`.

## Follow-up visits

Creating a case sheet with visit type `FOLLOW_UP` stores `prior_case_sheet_id` (chosen prior sheet, or most recent). Intake shows the link on the open chart.
