# Issue 1 — Assets list

The web application manages asset metadata. The file or video itself stays where it is hosted.

| Asset | Where it lives | How the app stores it |
| --- | --- | --- |
| Public videos | YouTube | `assets.asset_type = VIDEO`, `storage_type = URL`, `is_public = 1` |
| Private videos | Unlisted or private YouTube links | Same columns, `is_public = 0` |
| Public audio | A public URL | `asset_type = AUDIO`, `storage_type = URL`, `is_public = 1` |
| Private audio | A file under `storage/`, which Apache is not allowed to serve directly | `asset_type = AUDIO`, `storage_type = LOCAL`, `is_public = 0` |

The asset library (`assets.php`) already records title, type, category, public or private, and the URL or local path. The app does not re-host YouTube videos.

# Issue 2 — Stakeholders and roles

| Stakeholder from the issue | Role in CareSystem |
| --- | --- |
| Super Admin | `SUPER_ADMIN` |
| Admin | `ADMIN` |
| Doctors | `DOCTOR` |
| Para Medics | `PARAMEDIC` |
| Triage Nurses | `TRIAGE_NURSE` |
| Patients | Patient portal account, separate from staff users |
| Data Entry Operators | `DATA_ENTRY_OPERATOR` |

Later roles that are also in the permission matrix: `NURSE`, `GRIEVANCE_OFFICER`, and `EDUCATION_TEAM`. Permissions are in `role_permissions` and `app/config/permissions.php`.

# Issue 3 — Requirements

| Requirement | Where it lives now |
| --- | --- |
| Keep the original public site available | The clinical app is the staff and portal system. Public videos and press links are assets, not a replacement of d3saradaservicesociety.org. |
| iPad-style admin screens | AdminLTE cards on the dashboard and admin panel |
| Scan papers into records | Consent, lab-report, and ultrasound uploads on the case sheet |
| Telugu and English | Language toggle (`en` / `te`) |
| Patient records: audio, video, images, formal records | Case sheets, asset library, voice recordings, and uploaded images |
| Admin reports: trends and success | Analytics |
| Visitor-level numbers | Public assets and press page |
| Events, calendar, schedule | Calendar and appointments |
| Contact / triaging | Intake, triage nurse role, messages |
| Stack: HTML, CSS, JS, jQuery, Bootstrap, PHP, MySQL | The running XAMPP application |
| Deployment target | The app runs under Apache and MariaDB and can be deployed to shared PHP hosting |

# Issue 4 — Team composition

Recorded from the project thread on 21 January 2026:

| Name | GitHub | LinkedIn |
| --- | --- | --- |
| Manvith Kumar Reddy | https://github.com/manvith-kumar | https://www.linkedin.com/in/manvith-kumar-reddy/ |
| Harshitha Sai Alakunta | https://github.com/harshitha506 | https://www.linkedin.com/in/harshitha-sai-alakunta |
| Hema Sri Ala | https://github.com/hemasriala | https://www.linkedin.com/in/hema-sri-ala/ |

Thursday 10:00 PM IST was the time each person confirmed.

# Issue 5 — Database tables

The running database `core_app` has the tables the issue asked for.

| Data | Table | Identifier |
| --- | --- | --- |
| Patients | `patients` | `patient_code` is `YYYYMMDDNNN`, assigned by the database trigger. Example: the 5th patient on 4 Feb 2026 is `20260204005`. |
| Case sheets | `case_sheets` | One patient has many case sheets (`patient_id`). The case sheet key is `case_sheet_id`. A follow-up also stores `prior_case_sheet_id`. |
| Assets | `assets` | Metadata only: URL or local path, type, and public/private. |
| Events | `events` | Camps and seminars, shown on the calendar. |
| Users | `users` | Staff accounts and roles. Patients stay in `patients`. |
| Feedback | `feedback` and `patient_feedback` | Text plus an optional audio file. Patient portal feedback is limited to a logged-in patient. |
| Closure | columns on `case_sheets` | `closure_type` is on-the-spot discharge, follow-up, referral, or pending. `is_closed` marks the case finished. |

# Issue 7 — Case sheet reconciled with the paper form

The paper form is `project-resources/project_forms_resources_docs/4. Case Sheet - routinely we fill this.pdf`. The filled example is beside it. The intake screen is that form, stored as follows.

| Paper section | Stored in |
| --- | --- |
| Identity, phone, address | `patients` |
| Visit type and chief complaint | `case_sheets.visit_type`, `chief_complaint` |
| History and background | `case_sheets.assessment` (JSON) |
| Vitals | `case_sheets.vitals_json` |
| Examination, including head and neck and pelvic exam | `case_sheets.exam_notes` (JSON) plus ultrasound images in `case_attachments` |
| Diagrams | `diag_breast`, `diag_pelvic`, `diag_via`, `diag_vili` |
| Labs | `lab_orders` and the labs tab JSON in `diagnosis` |
| Plan, medicines, advice | `plan_notes`, `prescriptions`, `advice`, and the doctor columns |
| Follow-up and referral | `follow_up_date`, `follow_up_notes`, `referral_to`, `prior_case_sheet_id` |
| Closure | `closure_type`, `is_closed`, `closed_at` |
