# D3S3 CareSystem

**Dharma & Dayitva is Divine — Sarada Service Society**

A PHP/MySQL web application for patient care management, built as a capstone project.

## Requirements

- PHP 8.x
- MySQL / MariaDB
- Apache with `mod_rewrite` enabled
- XAMPP (local development) or compatible hosting (e.g. BlueHost)

## Local Setup

1. **Clone the repo** into your XAMPP `htdocs/` directory:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs
   git clone <repo-url> d3s3
   ```

2. **Create the database:**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a new database named `core_app`
   - Import `sql/core_app.sql` to set up all tables

3. **Configure environment:**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` with your local database credentials:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=core_app
   DB_USER=root
   DB_PASS=
   APP_ENV=development
   REGISTRATION_CODE=<pick any code>
   ```

4. **Access the app** at `http://localhost/d3s3/`

## Project Structure

```
d3s3/
├── index.php, login.php, dashboard.php, ...   # Entry points
├── patients.php, appointments.php             # Patient & scheduling entry points
├── lab_results.php                            # Labwork queue entry point
├── feedback.php, messages.php, tasks.php      # Staff feature entry points
├── assets.php, calendar.php, reports.php      # Resource & planning entry points
├── app/
│   ├── config/
│   │   ├── database.php        # PDO connection (reads .env)
│   │   ├── session.php         # Secure session configuration
│   │   └── permissions.php     # 9-role × 8-resource access matrix + can() helper
│   ├── controllers/
│   │   ├── UserController.php       # Login, profile, registration
│   │   ├── AdminController.php      # Admin dashboard, user management
│   │   ├── ClinicalController.php   # Intake, case sheets, queue
│   │   ├── PatientController.php    # Patient records, profile, access log
│   │   ├── AppointmentController.php# Appointments list, doctor assignment
│   │   ├── LabResultsController.php # Labwork queue and result completion
│   │   ├── FeedbackController.php   # Grievance/feedback tracking
│   │   ├── MessagingController.php  # Internal messaging
│   │   └── TaskController.php       # Task management
│   ├── middleware/
│   │   └── auth.php            # Session guard
│   └── views/
│       ├── login.php
│       ├── profile.php
│       ├── _sidebar.php
│       ├── patients.php         # Patient search / list
│       ├── patient_profile.php  # 4-tab patient profile
│       ├── appointments.php     # Appointments list & assignment
│       ├── lab_results.php      # Labwork queue & result completion
│       ├── feedback.php, feedback_detail.php, feedback_submit.php
│       ├── messages.php
│       ├── tasks.php
│       └── admin/
│           ├── dashboard.php
│           ├── users.php
│           └── emp_register.php
├── assets/                     # CSS, JS, icons
├── sql/                        # Database schema & migrations
├── .env.example                # Environment template
├── .htaccess                   # Apache config & security headers
└── .user.ini                   # PHP-FPM settings (production)
```

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

## Phase 1 Features

- User login / logout with secure session handling
- Role-based access control
- Admin dashboard
- User management (Admin+)
- Employee self-registration (gated by registration code)
- User profile with password change

## Phase 2 Features

### Reporting & Database Backup *(2026-02-11)*
- Reports page (`reports.php`) with on-demand full database backup download
- PHP file-upload configuration for report attachments
- SQL migration file tracking for database versioning

### Case Sheet System *(2026-02-13)*
- Patient intake form with structured case sheet creation
- Auto-generated patient codes in `YYYYMMDDNNN` format (via DB trigger)
- Case sheet status tracking (open, in-review, closed)
- Full audit trail of case sheet changes

### Clinical Dashboard & Queue Management *(2026-02-17–18)*
- Redesigned clinical dashboard with quick-action tiles and live statistics
- Live patient queue with drag-to-reorder for clinical roles
- Doctor queue showing patients awaiting review
- Doctor review workflow with step-by-step audit trail
- Examination diagram editor (canvas-based, stored as stroke JSON)

### Admin Panel & Asset Management *(2026-02-17)*
- Expanded admin panel with controls beyond user management
- Asset library (`assets.php`) for uploading and managing clinical/educational files
- Role-gated access to assets based on the permission matrix

### Calendar / Events *(2026-02-17)*
- Shared calendar (`calendar.php`) for scheduling events and appointments
- Role-gated read/write access per the permission matrix

### User Preferences & Settings *(2026-02-17)*
- Per-user settings page for UI theme and font-size preferences
- Preferences persisted in the database (`user_preferences` table)

### Expanded Role Support *(2026-02-17, 2026-02-23)*
- Added `DOCTOR`, `TRIAGE_NURSE`, `NURSE`, and `GRIEVANCE_OFFICER` roles
- Added `PARAMEDIC` and `EDUCATION_TEAM` roles with appropriate permission scopes

### Centralized Permission System *(2026-02-24)*
- `app/config/permissions.php` — 9-role × 10-resource access matrix
- `can(string $role, string $resource, string $action)` helper used throughout all controllers and views
- DB-backed `role_permissions` table with hardcoded fallback for resilience
- Admin UI for viewing and editing permissions at runtime

### Feedback / Grievance Tracking *(2026-02-24)*
- Feedback submission and tracking system (`feedback.php`)
- `GRIEVANCE_OFFICER`, `ADMIN`, `SUPER_ADMIN` have read/write access; clinical roles have read-only
- Detail view and submission form for structured grievance handling

### Internal Messaging *(2026-02-24)*
- Internal messaging system (`messages.php`) for all staff roles
- Inbox, sent, compose, and thread-view interfaces
- All roles have read/write access

### Task Management *(2026-02-24)*
- To-do / task list (`tasks.php`) available to all roles
- Staff can create, update, and delete their own tasks
- Admins can view all tasks across the system

### Patient Records *(2026-02-26)*
- Patient search and list page (`patients.php`) with live AJAX search by name or patient code
- Four-tab patient profile view:
  - **Personal Info** — demographics and contact details
  - **Medical History** — all case sheets with expandable clinical notes
  - **Grievances** — linked feedback records (gated by `feedback` permission)
  - **Access Log** — full audit trail of who viewed this record (ADMIN / SUPER_ADMIN only)
- Every profile view is automatically written to `patient_record_access_log` (migration 018) with access type, IP address, user agent, and timestamp

### Appointments *(2026-02-26)*
- Appointments page (`appointments.php`) for clinical roles with three tabs: Today, Next 7 Days, and Pending Assignment
- Nurse/Triage Nurse can assign `INTAKE_COMPLETE` case sheets to a specific doctor via a modal
- Doctor view shows their assigned appointments only
- AJAX patient search and doctor list endpoints for the assignment workflow

### Labwork *(2026-03-02)*
- Labwork queue page (`lab_results.php`) showing pending lab orders in FIFO order
- "Order Lab Test" button in the intake form's Labs tab opens a modal with 50+ categorized tests and a notes field; submitted via AJAX to `ClinicalController::orderLabTest()`
- Pending orders are completable via a "Complete" modal that captures result notes; submitted via AJAX to `LabResultsController::completeOrder()`
- `lab_orders` table (migration 020) tracks each ordered test with status `PENDING` → `COMPLETED`
- `labwork` permission resource (migration 021): SUPER_ADMIN / ADMIN / DOCTOR / TRIAGE_NURSE / NURSE = RW; PARAMEDIC = R; others = none
- Sidebar link renamed from "Lab Results" to "Labwork"

### Security Hardening *(2026-03-02)*
- `logout.php` now performs a full three-step session teardown: clears `$_SESSION`, expires the session cookie in the browser via `setcookie()`, then calls `session_destroy()`
- `session.php` now explicitly sets `lifetime` and `path` in `session_set_cookie_params()` instead of relying on php.ini defaults

### Security & Quality Audit *(2026-03-04)*
- `update_patient.php` — added CSRF token validation and a strict field-name whitelist; removed debug `error_reporting` calls
- `.htaccess` — added `Content-Security-Policy`, `Referrer-Policy`, and `Permissions-Policy` response headers
- Message body capped at 10 000 characters; feedback description capped at 5 000 characters
- Clinical dashboard DB queries wrapped in `try-catch` so a missing table never causes a fatal error
- Sidebar Labwork nav link now correctly gates on the `labwork` permission resource (was incorrectly using `patient_data`)
- Reports page access restricted to `SUPER_ADMIN` / `ADMIN` only
- `MessagingController` sets a flash error on silent redirects; inbox now consumes `$_SESSION['messages_error']`
- `patient_profile.php` null-coalescing guard on `first_name` to prevent notices on incomplete records

### Multilingual Support (i18n) *(2026-03-20)*
- `load_language()` helper in `app/helpers/i18n.php` — loads a PHP array of key → string translations at runtime; falls back to English on missing keys
- Language files under `lang/en/` (English) and `lang/te/` (Telugu) — currently covering the full intake form
- `__('key')` wrapper used throughout `app/views/intake.php` for all user-visible strings
- `user_preferences` table stores per-user language selection; preference loaded into `$_SESSION['language']` on every authenticated request
- All intake form tabs and labels fully translated into Telugu (Phase 1 + Phase 2)

### UX Improvements — Gear Settings Panel *(2026-03-20)*
- Replaced the inline "Dark mode" toggle in every page's navbar with a gear icon (⚙) button
- Clicking the gear opens a slide-down settings panel containing:
  - Dark mode toggle (persisted to server via `settings.php?ajax=theme`)
  - Language switcher (English / తెలుగు) for all pages — preference saved via `settings.php?ajax=language` and session-persisted
- Panel CSS and JS moved to shared `assets/css/theme.css` and `assets/js/theme-toggle.js`; zero per-page duplication
- Unread message count badge (red) added to the Messages link in the sidebar
- Fixed 9 views that were referencing the non-existent `theme.js` — now correctly point to `theme-toggle.js`

### Admin Dashboard Enhancements *(2026-03-20)*
- **Messages tile** — shows live unread count (red badge) for the logged-in admin; links to `messages.php`

### Internal Messaging — Multi-Recipient & Reply All *(2026-03-24)*
- Migration 022 adds `thread_id` to `messages`; existing rows backfilled so each legacy message forms its own thread
- Compose now supports up to 20 recipients via a multi-select list; self silently removed
- **Reply** pre-selects the sender; **Reply All** pre-selects all thread participants except self
- View page shows full recipient list and Reply / Reply All buttons side-by-side for threads
- Inbox rows are now fully clickable (replaced eye-icon button)

### i18n — CSV Migration & Sidebar Localisation *(2026-03-24)*
- Language files consolidated into `lang/labels_en.csv` and `lang/labels_te.csv`; per-page PHP arrays removed
- All sidebar navigation and role labels run through `__()` so they translate when the user switches language

### Calendar — Event Creation, Self-Hosted Assets & Visual Polish *(2026-03-24 – 2026-03-25)*
- **New Event** modal (admin/write-access roles): AJAX POST with CSRF; new event added to live calendar without page reload
- **Smart `initialDate`**: calendar opens on the nearest upcoming event (or most recent past event) instead of always defaulting to the current month
- **Self-hosted FullCalendar 5.11.5** (`assets/js/fullcalendar.min.js`, `assets/css/fullcalendar.min.css`) — no CDN dependency
- **Brand-integrated styling**: toolbar buttons use `--brand-primary`, today cell rendered as a filled teal circle, borders use `--border-soft`; full dark-mode support
- **Custom event content**: type-specific FontAwesome icons (medical camp, seminar, meeting, etc.) on each event pill
- **Tablet-optimised**: 44 px tap targets, scale-on-tap feedback, `fixedWeekCount: false`, `dayMaxEvents: 3`, `nowIndicator: true`, simplified 3-view toolbar (Month / Week / List)
- **Dashboard widget**: compact calendar card embedded on the dashboard; uses the same self-hosted assets and brand-integrated CSS as `calendar.php`; month-grid and list views are both accessible via a toolbar toggle

### Messaging — Tom Select Recipient Input *(2026-03-25)*
- Replaced the custom chip-widget recipient input with the **Tom Select** library for a polished, accessible multi-recipient selector
- Selected recipients render as removable chips with consistent styling; keyboard navigation and search work out of the box
- Self-hosted (`assets/js/tom-select.complete.min.js`, `assets/css/tom-select.bootstrap4.min.css`) — no CDN dependency

### Bug Fixes *(2026-03-25)*
- Patient profile is now correctly reachable from the "My Active Reviews" queue on the doctor dashboard (broken link resolved)
- `MessagingController` list queries wrapped in `try-catch` to prevent a fatal error when the messages table is missing or unreachable

### Intake — Sticky Patient Header *(2026-03-26)*
- Patient name and patient code are now pinned in the fixed top navbar while editing a case sheet, so the identity of the patient being worked on is always visible regardless of scroll position or which tab is active

### Appointments — Improved New Appointment Flow *(2026-03-26)*
- The case sheet picker is now hidden when a patient has zero or one open case sheet (the vast majority of cases) — it only appears when there is genuine ambiguity (two or more open sheets)
- With one open sheet it is auto-selected silently; with zero open sheets the server automatically reuses the most recent open sheet or creates a new `INTAKE_IN_PROGRESS` stub — the user never has to make an unnecessary selection

### Pre-Deployment Security & Bug Fixes *(2026-03-26)*
- **CSV import MIME validation** (`AdminController`): file content is now verified with `finfo` in addition to the extension check, preventing a file named `malware.php.csv` from bypassing the filter
- **LIKE wildcard escaping** (`PatientController`): `%` and `_` characters in patient search input are now escaped before being interpolated into LIKE patterns, so a literal `%` searches correctly instead of matching everything
- **Lab test name length cap** (`ClinicalController`): test names longer than 255 characters are now silently skipped before the DB insert, preventing truncation errors on oversized AJAX payloads
- **Backup directory** (`backups/.htaccess`): confirmed deny-all rule is in place, blocking direct web access to timestamped CSV exports

### Dashboard Calendar Widget *(2026-03-26)*
- Calendar widget now appears for **all roles with events access** — previously only rendered for clinical roles (`case_sheets = RW`); now correctly gated on `can($role, 'events')` so non-clinical roles such as `EDUCATION_TEAM` see the widget on their dashboard landing
- Widget uses the same self-hosted FullCalendar assets and polished CSS as `calendar.php` (local `assets/js/fullcalendar.min.js` / `assets/css/fullcalendar.min.css` — no CDN dependency)
- Matches `calendar.php` config: custom event content with type-specific FontAwesome icons, `fixedWeekCount: false`, `dayMaxEvents: 3`, `height: auto`, month/list view toggle in toolbar
- Default view is **list** (`listMonth`); month-grid view accessible via toolbar toggle
- Appointment overlay events (clinical roles only) include the patient name and doctor name, coloured purple to distinguish from scheduled events

### Universal Notes Column *(2026-03-27)*
- Migration 024 adds a `notes TEXT DEFAULT NULL` column to every operational table (`users`, `user_preferences`, `patients`, `case_sheets`, `events`, `patient_feedback`, `assets`, `feedback`, `messages`, `tasks`, `lab_orders`, `role_permissions`, `patient_daily_sequence`)
- Provides a catch-all field for stakeholders to record information not covered by the existing schema
- Intentionally excluded: `case_sheet_audit_log`, `permission_change_log`, and `patient_record_access_log` — these are append-only audit logs whose integrity depends on rows never being modified after insert
- `appointments` already had a `notes` column prior to this migration

### UX & Completeness Fixes *(2026-03-26)*
- **Admin panel tiles**: Patient Management tile now links to `patients.php`; Messages tile now links to `messages.php`; the dead Help tile (no page exists) has been removed
- **case-sheet.php — Save & Exit**: removed the fake `setTimeout` stub; since all fields are auto-saved on change via `update_case_sheet.php`, the button now redirects immediately
- **case-sheet.php — Final Submit**: now POSTs to `intake.php?action=complete-intake`, setting the case sheet status to `INTAKE_COMPLETE` and moving it into the doctor queue; previously a non-functional stub
- **Dashboard**: removed the "Clinical alerts coming soon" placeholder card from the right column for clinical users
- **Sidebar brand link**: clicking the D3S3 CareSystem logo now navigates to `admin.php` (admin roles) or `dashboard.php` (all other roles) instead of `href="#"`

## License

MIT — see [LICENSE](LICENSE) for details.
