# Issue #2 — Stakeholders and roles

Each stakeholder named in the issue maps to a CareSystem role (or to the patient portal).

| Stakeholder (issue) | CareSystem role / account | Login surface | Primary job |
| --- | --- | --- | --- |
| Super Admin | `SUPER_ADMIN` | Staff login → `admin.php` / dashboard | Full configuration; user management; all clinical and ops resources |
| Admin | `ADMIN` | Staff login → admin / dashboard | Day-to-day administration; same resource breadth as Super Admin for app modules |
| Doctors | `DOCTOR` | Staff login → dashboard | Review case sheets, diagnosis/plan, labs, assigned caseload |
| Para Medics | `PARAMEDIC` | Staff login → dashboard | Field / camp intake support; case sheets and patient data; limited appointments/labs |
| Triage Nurses | `TRIAGE_NURSE` | Staff login → dashboard | Intake, triage queue, appointments, case sheets they create |
| Patients | Patient portal account (`patient_accounts`), **not** a `users.role` | Patient portal login → `patient_portal.php` | View own record, messages, feedback, resources |
| Data Entry Operators | `DATA_ENTRY_OPERATOR` | Staff login → dashboard | Enter case-sheet and patient data from paper forms; no user admin / feedback submit |

Staff roles live on `users.role`. Patients are separate tables (`patients` + portal accounts). Permissions are `R` / `RW` / `N` per resource in `role_permissions` and the fallback matrix in `app/config/permissions.php`.

## Roles added after the original issue list

| Role | Purpose |
| --- | --- |
| `NURSE` | Clinical nursing (intake / appointments / labs), distinct from triage |
| `GRIEVANCE_OFFICER` | Own feedback / grievance workflow (`feedback` RW) |
| `EDUCATION_TEAM` | Educational assets and events; no clinical patient chart access |

## Resource cheat sheet (issue stakeholders)

| Resource | SUPER_ADMIN / ADMIN | DOCTOR | PARAMEDIC | TRIAGE_NURSE | DATA_ENTRY_OPERATOR |
| --- | --- | --- | --- | --- | --- |
| users | RW | N | N | N | N |
| case_sheets | RW | RW | RW | RW | RW |
| patient_data | RW | RW | RW | RW | RW |
| appointments | RW | R | R | RW | N |
| labwork | RW | RW | R | RW | N |
| feedback | RW | R | R | R | N |
| analytics | R | R | R* | R* | R* |
| assets / events | RW | R | R | R | R |

\*Analytics visibility may still be scoped or limited by controller rules even when the matrix says `R`.

## Related docs

- Full workflows: [../Role_Based_Access_Control_and_Workflows.md](../Role_Based_Access_Control_and_Workflows.md)
- Test staff accounts: [../getting-started/QUICKSTART.md](../getting-started/QUICKSTART.md)
- Patient portal accounts: [../Patient_Portal_Test_Accounts.md](../Patient_Portal_Test_Accounts.md)
