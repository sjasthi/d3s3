# D3S3 CareSystem

**Dharma & Dayitva is Divine — Sarada Service Society**

A PHP/MySQL web application for patient care management, built as a capstone project.

## Requirements

- PHP 8.x
- MySQL / MariaDB
- Apache with `mod_rewrite` enabled
- XAMPP (local development) or compatible hosting (e.g. BlueHost)

---

## Local Setup

### Step 1 — Start XAMPP

Open the XAMPP control panel and start both **Apache** and **MySQL**. Confirm they show green / "Running" before proceeding.

### Step 2 — Clone the repo

```bash
cd /Applications/XAMPP/xamppfiles/htdocs   # macOS path; adjust for Windows
git clone <repo-url> d3s3
```

The app must live at `htdocs/d3s3/` so all paths resolve correctly.

### Step 3 — Create the database

1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Click **New** in the left panel and create a database named **`core_app`**
3. Select `core_app`, go to the **Import** tab, and import **`sql/core_app.sql`**

This creates all base tables and inserts the initial SUPER_ADMIN accounts.

### Step 4 — Configure your environment

```bash
cp .env.example .env
```

Edit `.env` with a text editor:

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=core_app
DB_USER=root
DB_PASS=            # leave blank for default XAMPP; set if you added a MySQL password
APP_ENV=development
REGISTRATION_CODE=devcode123   # pick anything — used to gate self-registration
```

### Step 5 — Run all migrations (in order)

Open phpMyAdmin → select the `core_app` database → click the **SQL** tab.

Run each file in **`sql/migrations/`** in the numbered order below. Copy the contents of each file, paste into the SQL tab, and click **Go**. Then repeat for the next file.

```
001_create_events_table.sql
002_create_appointments_table.sql
003_create_user_preferences.sql
004_expand_user_roles.sql
005_add_case_sheet_status.sql
006_add_doctor_review_columns.sql
007_create_case_sheet_audit_log.sql
008_fix_patient_code_sequence.sql
009_add_queue_position.sql
010_add_diagram_columns.sql
011_add_paramedic_education_team_roles.sql
012_create_feedback_table.sql
013_create_messages_table.sql
014_create_tasks_table.sql
015_create_role_permissions_and_log.sql
016_migration_appointments.sql
017_migration_status_appointments.sql
018_create_patient_record_access_log.sql
019_add_appointments_to_role_permissions.sql
020_create_lab_orders.sql
021_add_labwork_to_role_permissions.sql
022_add_thread_id_to_messages.sql
023_migration_audit_snapshots.sql
024_add_notes_to_all_tables.sql
025_add_analytics_to_role_permissions.sql
026_create_patient_portal.sql        ← patient portal tables
027_assets_upgrade.sql               ← asset types, local upload, patient delivery
```

> **Tip:** If you miss a migration or run them out of order, you will get SQL errors on the next one. Run them strictly in sequence.

### Step 6 — Load test data (recommended for development)

There are three test data files. Load them in this order from phpMyAdmin → SQL tab:

| File | What it adds |
|------|-------------|
| `sql/test_data.sql` | Phase 1: 8 staff users (IDs 4–11), 10 patients (IDs 5–14), case sheets, appointments, feedback, messages, assets |
| `sql/test_data_phase2.sql` | Phase 2: 6 more staff (IDs 12–17), 16 more patients (IDs 15–30), richer clinical data, lab orders |
| `sql/test_data_phase3.sql` | Phase 3: additional clinical/lab data, internal messages, **3 patient portal accounts** |

Each file is safe to run multiple times — all inserts use `INSERT IGNORE`.

### Step 7 — Open the app

```
http://localhost/d3s3/
```

This lands on the homepage with links to both the staff portal and the patient portal.

---

## Test Credentials

### Staff accounts

All staff test accounts use the password **`Test1234!`**.

| Name | Email | Username | Role |
|------|-------|----------|------|
| Anita Gupta | `a.gupta@d3s3.com` | `agupta` | ADMIN |
| Priya Desai | `p.desai@d3s3.com` | `pdesai` | DOCTOR |
| Amit Patel | `a.patel@d3s3.com` | `apatel` | DOCTOR |
| Kiran Rao | `k.rao@d3s3.com` | `krao` | DOCTOR |
| Nandita Krishnan | `n.krishnan@d3s3.com` | `nkrishnan` | TRIAGE_NURSE |
| Sunil Varma | `s.varma@d3s3.com` | `svarma` | TRIAGE_NURSE |
| Sneha Kulkarni | `s.kulkarni@d3s3.com` | `skulkarni` | NURSE |
| Rohan Mehta | `r.mehta@d3s3.com` | `rmehta` | NURSE |
| Ravi Shankar | `r.shankar@d3s3.com` | `rshankar` | PARAMEDIC |
| Fatima Siddiqui | `f.siddiqui@d3s3.com` | `fsiddiqui` | GRIEVANCE_OFFICER |
| Chitra Nair | `c.nair@d3s3.com` | `cnair` | EDUCATION_TEAM |
| Meena Rao | `m.rao@d3s3.com` | `mrao` | DATA_ENTRY_OPERATOR |
| Vikram Singh | `v.singh@d3s3.com` | `vsingh` | DATA_ENTRY_OPERATOR |

> **Note:** The SUPER_ADMIN accounts (Andrew Hawkinson / Gary Marks) in the base schema use individual passwords set during initial setup — not the shared test password.

Sign in at: `http://localhost/d3s3/login.php`

### Patient portal accounts

> **Prerequisite:** You must have loaded **`sql/test_data_phase3.sql`** (Step 6 above) before these accounts will exist in the database. If you skipped that file, run it now from phpMyAdmin → SQL tab, then come back here.

All three portal test accounts use the password **`Portal@1234`**.

You can log in with either the **username** or the **email** — both are accepted.

| Account | Username | Email | Password |
|---------|----------|-------|----------|
| Portal account 1 | `patient_priya` | `priya.patient@example.com` | `Portal@1234` |
| Portal account 2 | `patient_rahul` | `rahul.patient@example.com` | `Portal@1234` |
| Portal account 3 | `patient_ananya` | `ananya.patient@example.com` | `Portal@1234` |

**To log in:**

1. Go to `http://localhost/d3s3/patient_login.php`
2. Enter a username (e.g. `patient_priya`) **or** email (e.g. `priya.patient@example.com`)
3. Enter the password: `Portal@1234`
4. Click **Sign In** — you will land on the patient portal dashboard

**What you can do once logged in:**

- **Dashboard** — overview of upcoming appointments, unread messages, and recent resources
- **Appointments** — view upcoming and past clinic visits (read-only)
- **Health Record** — view closed case sheets including diagnosis and treatment plan (read-only)
- **Lab Results** — view completed test results and pending orders (read-only)
- **Messages** — send a message to the clinic and receive replies from staff
- **Feedback** — submit a complaint, suggestion, or positive review
- **Resources** — view educational materials sent by staff or available in the public library
- **Profile** — view personal details; the **Allergies** field is the only editable field

**To see the staff ↔ patient message flow:**

1. Log in as a patient → go to **Messages** → compose a new message
2. Open a new browser tab → log in as a staff member at `http://localhost/d3s3/login.php`
   - Use Anita Gupta (`a.gupta@d3s3.com` / `Test1234!`) or any clinical role
3. Click **Patient Messages** in the sidebar (yellow badge shows unread count)
4. Select the thread → type a reply → **Send**
5. Switch back to the patient tab → refresh Messages → the reply appears with a **New** badge

**Troubleshooting:**

- *"Incorrect email/username or password"* — confirm you loaded `test_data_phase3.sql`. If the accounts exist but login still fails, run this fix query in phpMyAdmin:
  ```sql
  UPDATE patient_accounts
  SET password_hash = '$2y$10$iEbnZFWXi/SriBcbT7FDEepvsVRu9NzL3VYOB5SwT8nHT7jMk930e'
  WHERE patient_id IN (1, 2, 3);
  ```
- *Portal accounts show no appointments or health records* — accounts 1–3 are early test patients with minimal seed data. For richer data, create a portal account for a patient from the Phase 2 or 3 batch (IDs 15–30) via the staff UI: log in as ADMIN → Patients → open a patient profile → Patient Portal Account card → Create Portal Account.

---

## Project Structure

```
d3s3/
├── index.php                                  # Landing page (staff + patient sign-in)
├── login.php, dashboard.php, logout.php       # Staff auth entry points
├── patient_login.php, patient_portal.php      # Patient portal entry points
├── portal_messages.php                        # Staff view of patient portal messages
├── patients.php, appointments.php             # Patient & scheduling entry points
├── lab_results.php                            # Labwork queue entry point
├── feedback.php, messages.php, tasks.php      # Staff feature entry points
├── assets.php, calendar.php, reports.php      # Resource & planning entry points
├── analytics.php                              # Usage analytics entry point
├── app/
│   ├── config/
│   │   ├── database.php        # PDO connection (reads .env)
│   │   ├── session.php         # Secure session configuration
│   │   └── permissions.php     # 9-role × 10-resource access matrix + can() helper
│   ├── controllers/
│   │   ├── UserController.php          # Login, profile, registration
│   │   ├── AdminController.php         # Admin dashboard, user management
│   │   ├── ClinicalController.php      # Intake, case sheets, queue
│   │   ├── PatientController.php       # Patient records, profile, access log
│   │   ├── PatientPortalController.php # All patient portal actions (patient + staff)
│   │   ├── AppointmentController.php   # Appointments list, doctor assignment
│   │   ├── LabResultsController.php    # Labwork queue and result completion
│   │   ├── AssetController.php         # Asset library, file upload, send-to-patient
│   │   ├── FeedbackController.php      # Grievance/feedback tracking
│   │   ├── MessagingController.php     # Internal messaging
│   │   └── TaskController.php          # Task management
│   ├── middleware/
│   │   ├── auth.php            # Staff session guard
│   │   └── patient_auth.php    # Patient portal session guard
│   └── views/
│       ├── login.php
│       ├── profile.php
│       ├── _sidebar.php
│       ├── patients.php         # Patient search / list
│       ├── patient_profile.php  # 4-tab patient profile + portal account management
│       ├── appointments.php     # Appointments list & assignment
│       ├── lab_results.php      # Labwork queue & result completion
│       ├── portal_messages.php  # Staff inbox for patient portal messages
│       ├── feedback.php, feedback_detail.php, feedback_submit.php
│       ├── messages.php
│       ├── tasks.php
│       ├── portal/              # Patient-facing portal views
│       │   ├── _nav.php, _nav_close.php
│       │   ├── login.php, dashboard.php
│       │   ├── appointments.php, health_record.php
│       │   ├── lab_results.php, messages.php
│       │   ├── feedback.php, profile.php
│       └── admin/
│           ├── dashboard.php
│           ├── users.php
│           ├── emp_register.php
│           └── assets.php       # Asset library UI
├── uploads/assets/              # Locally uploaded asset files (auto-created)
├── assets/                      # CSS, JS, icons
├── sql/                         # Database schema & migrations
├── .env.example                 # Environment template
├── .htaccess                    # Apache config & security headers
└── .user.ini                    # PHP-FPM settings (production)
```

---

## User Roles

| Role | Access |
|------|--------|
| `SUPER_ADMIN` | Full access — manage all users, settings, and system configuration |
| `ADMIN` | User management, admin dashboard, and full feature access |
| `DOCTOR` | Clinical access — case sheets, patient data, queue management |
| `TRIAGE_NURSE` | Clinical access — patient intake, triage, and queue management |
| `NURSE` | Clinical access — patient data and case sheet support |
| `PARAMEDIC` | Clinical access — patient data and case sheet support |
| `GRIEVANCE_OFFICER` | Feedback/grievance management (read/write) |
| `EDUCATION_TEAM` | Read access to clinical and patient data for training purposes |
| `DATA_ENTRY_OPERATOR` | Dashboard, profile, and limited data entry access |

---

## Patient Portal

The patient portal is a separate, green-themed interface that patients use to access their own data. Patients have **no access** to any other patient's records, and **cannot modify** any clinical data (except their own allergy information in their profile).

The portal is visually distinct from the staff interface — green colour scheme vs the staff blue — to make it immediately clear which side of the system you are on.

### URLs at a glance

| URL | Purpose |
|-----|---------|
| `index.php` | Landing page — links to both staff and patient sign-in |
| `patient_login.php` | Patient login (email or username + password) |
| `patient_portal.php` | Patient portal home (routes via `?page=`) |
| `portal_messages.php` | Staff inbox for patient-initiated messages |

### Portal pages

| Page | URL | What the patient sees |
|------|-----|-----------------------|
| Dashboard | `patient_portal.php` (default) | Alerts for unread messages / new resources / next appointment; quick-link cards |
| Appointments | `?page=appointments` | Upcoming and past appointments (read-only) |
| Health Record | `?page=health_record` | Closed case sheets — diagnosis, treatment plan, prescriptions, advice |
| Lab Results | `?page=lab_results` | Completed results (with notes) and pending tests |
| Messages | `?page=messages` | Threaded inbox — compose a new message or reply to staff |
| Feedback | `?page=feedback` | Submit grievance / complaint / suggestion / positive feedback |
| Resources | `?page=resources` | Assets sent by staff + public asset library |
| Profile | `?page=profile` | Read-only demographics; **editable allergy field** |

### Setting up patient portal test accounts

**Option 1 — via the staff UI (recommended for one or two accounts):**

1. Log in as ADMIN or SUPER_ADMIN at `login.php`
2. Go to **Patients** in the sidebar → open any patient's profile
3. Scroll to the **Patient Portal Account** card → click **Create Portal Account**
4. Enter an email, optional username, and password → submit

**Option 2 — via the bulk-create script (for multiple accounts quickly):**

1. Open `create_test_portal_accounts.php` in a text editor
2. Edit the `$accounts` array — set `patient_id`, `email`, `username`, and `password` for each account you want to create. The `patient_id` must match an existing row in the `patients` table.
3. Visit `http://localhost/d3s3/create_test_portal_accounts.php` in your browser
4. The page will show a success or error result for each account
5. **Delete the file immediately after use** — it creates accounts without authentication

**Option 3 — via test_data_phase3.sql (fastest for a clean setup):**

Load `sql/test_data_phase3.sql` as described in Step 6 above. This creates three ready-to-use portal accounts (see [Patient portal accounts](#patient-portal-accounts) above). Password for all three: `Portal@1234`.

### Allergy pre-population (staff side)

When a patient updates their allergies in the portal Profile page, those entries carry into the intake form the first time a nurse opens a new case sheet for that patient. The nurse sees the structured allergy rows pre-filled in the History tab and can confirm or edit them before saving.

### Staff: replying to patient messages

1. Log in as any role with `patient_data` read access
2. Click **Patient Messages** in the sidebar (yellow badge = unread threads)
3. Select a thread → type a reply → **Send**
4. The patient sees the reply in their portal Messages inbox with a **New** badge

### Staff: managing portal accounts

SUPER_ADMIN and ADMIN can manage portal accounts from the patient profile page (`patients.php?action=view&id=X`):

| Action | What it does |
|--------|-------------|
| **Create** | Sets email, optional username, and initial password |
| **Reset password** | Issues a new password without deactivating the account |
| **Deactivate** | Blocks portal access without deleting data |
| **Activate** | Restores a previously deactivated account |

---

## Features

**Phase 1** — Authentication, role-based access control, admin dashboard, user management, employee self-registration, and user profiles.

**Phase 2** — Case sheet system, patient records, appointments, labwork queue, internal messaging, task management, feedback/grievance tracking, shared calendar, asset library (with file upload and patient delivery), analytics dashboard, patient-facing portal (appointments, health record, lab results, messaging, feedback, resources), and security hardening. Multilingual support (English / Telugu) throughout.

See [CHANGELOG.md](CHANGELOG.md) for a full history of changes.

---

## License

MIT — see [LICENSE](LICENSE) for details.
