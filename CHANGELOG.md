# Changelog

### Analytics — Patient Trends MySQL Compatibility Fix *(2026-04-21)*
- `dataTrends()` in `AnalyticsController` was using `JSON_EXTRACT()` / `JSON_UNQUOTE()` SQL functions (requires MySQL 5.7.8+) which are unavailable on the BlueHost production MySQL version, causing the Patient Trends tab to return a 500 error on the live site while working fine locally (MariaDB)
- All five affected queries (medicine sources, medical conditions, other conditions, family history, other family history) replaced with a single `SELECT assessment, vitals_json` fetch; JSON parsing and aggregation now performed in PHP using `json_decode()`, which is version-agnostic

### Analytics — Admin Redirect Bug Fix *(2026-04-21)*
- `index.php` redirect for authenticated admins was reading `$_SESSION['role']` (undefined) instead of `$_SESSION['user_role']`, so admins were always falling through to `dashboard.php` instead of `admin.php`

### Analytics Test Data *(2026-04-21)*
- Added `sql/test_data_analytics.sql` — 21 case sheets spread across April 2026 with varied visit types, chief complaints, closure types, assessment JSON (medical conditions, family history), and vitals JSON (medicine sources); 20 audit log entries for intake timing; 5 scheduled appointments; 10 patient feedback entries covering all feedback types and complaint pipeline statuses

## Phase 1 *(through 2026-02-09)*

- User login / logout with secure session handling
- Role-based access control
- Admin dashboard
- User management (Admin+)
- Employee self-registration (gated by registration code)
- User profile with password change

---

## Phase 2 *(from 2026-02-10)*

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
- The case sheet picker is now hidden when a patient has zero or one open case sheet — it only appears when there is genuine ambiguity (two or more open sheets)
- With one open sheet it is auto-selected silently; with zero open sheets the server automatically reuses the most recent open sheet or creates a new `INTAKE_IN_PROGRESS` stub

### Pre-Deployment Security & Bug Fixes *(2026-03-26)*
- **CSV import MIME validation** (`AdminController`): file content is now verified with `finfo` in addition to the extension check, preventing a file named `malware.php.csv` from bypassing the filter
- **LIKE wildcard escaping** (`PatientController`): `%` and `_` characters in patient search input are now escaped before being interpolated into LIKE patterns
- **Lab test name length cap** (`ClinicalController`): test names longer than 255 characters are now silently skipped before the DB insert
- **Backup directory** (`backups/.htaccess`): confirmed deny-all rule is in place, blocking direct web access to timestamped CSV exports

### Dashboard Calendar Widget *(2026-03-26)*
- Calendar widget now appears for **all roles with events access** — previously only rendered for clinical roles; now correctly gated on `can($role, 'events')`
- Default view is **list** (`listMonth`); month-grid view accessible via toolbar toggle
- Appointment overlay events (clinical roles only) include the patient name and doctor name, coloured purple to distinguish from scheduled events

### UX & Completeness Fixes *(2026-03-26)*
- **Admin panel tiles**: Patient Management tile now links to `patients.php`; Messages tile now links to `messages.php`; the dead Help tile has been removed
- **case-sheet.php — Save & Exit**: removed the fake `setTimeout` stub; button now redirects immediately since all fields are auto-saved on change
- **case-sheet.php — Final Submit**: now POSTs to `intake.php?action=complete-intake`, setting status to `INTAKE_COMPLETE` and moving it into the doctor queue; previously a non-functional stub
- **Dashboard**: removed the "Clinical alerts coming soon" placeholder card
- **Sidebar brand link**: clicking the D3S3 CareSystem logo now navigates to `admin.php` (admin roles) or `dashboard.php` (all other roles)

### Session Timeout *(2026-03-27)*
- Idle session timeout enforced in `app/middleware/auth.php` — inactive sessions are destroyed and the user is redirected to the login page with an explanatory message
- Timeout duration defined as a single constant (`SESSION_TIMEOUT`) in `app/config/session.php`
- Currently set to **6 hours** (testing; no real patient data). Change to `900` (15 minutes) for HIPAA-compliant production deployment

### Universal Notes Column *(2026-03-27)*
- Migration 024 adds a `notes TEXT DEFAULT NULL` column to every operational table
- Intentionally excluded: `case_sheet_audit_log`, `permission_change_log`, and `patient_record_access_log` — append-only audit logs

### Profile — Phone Number Field *(2026-03-27)*
- Optional Indian phone number field (`phone_e164`) added to all user profiles; accepts 10 digits (first digit 6–9), with optional `+91`/`91`/`0` prefix

### i18n — Intake Comparison Panels & Enum Labels *(2026-03-29)*
- Vitals and menstrual comparison panels in `intake.php` fully localised — card headings, legend badges, column headers, row labels, and empty-state strings all replaced with `__()` calls
- `$_mhEnumMap` (PHP) and `_intakeEnumDisplayMap` (JS) added so `REGULAR`/`IRREGULAR` MH enum values display in the active language both on page load and when changed live via dropdown
- Patient selection card now uses `_sexDisplayMap` in JS to translate `MALE`/`FEMALE`/`OTHER` when a patient is chosen from search, and replaces the hardcoded "years" string with `__('years')`
- 10 new keys added to `lang/labels_en.csv` and `lang/labels_te.csv`

### i18n — Doctor Review Page Localisation *(2026-03-29)*
- `app/views/review.php` fully localised — all user-visible strings replaced with `__()` calls across all 9 tabs (Patient, History, General, Examinations, Labs, Assessment, Treatment Plan, Follow-up, Audit)
- Vitals comparison, lab comparison, and menstrual comparison panels in `review.php` use the same `vitalsCompare()` helper and `__()` labels as the intake view
- 71 new keys added to `lang/labels_en.csv` and `lang/labels_te.csv`

### Doctor Review — Sticky Patient Header & Lab Order Modal *(2026-04-11)*
- Patient name and patient code are now pinned in the fixed top navbar of the doctor review page (`review.php`), matching the behaviour already in place on the intake form
- "Order Lab Test" modal added to the Labs tab of the doctor review page — full 50+ categorised test list with live filter, AJAX POST to `intake.php?action=order-lab-test`, and auto-reload of the lab orders table on success; consistent with the identical modal in `intake.php`

### README Overhaul *(2026-04-11)*
- Expanded setup guide: step-by-step XAMPP start, database creation, `.env` configuration, ordered migration list, and test data loading instructions
- Added full test credentials table (staff accounts + patient portal accounts)
- Added Patient Portal section documenting URLs, portal pages, and account management

### Developer Utility *(2026-04-11)*
- `create_test_portal_accounts.php` — one-off localhost-only script to seed patient portal test accounts; intended to be deleted after use

### Security Fixes *(2026-04-01)*
- **Stored XSS — patient search dropdown** (`intake.php`): patient data fields (`first_name`, `last_name`, `patient_code`, `sex`, `age_years`, `phone_e164`) are now HTML-escaped via jQuery's `$('<span>').text(v).html()` before being inserted into the search results list; previously these were concatenated into a raw HTML string and injectable
- **Flash message escaping** (`intake.php`): `$flashSuccess` now passes through `htmlspecialchars()` at the output point, consistent with all other flash/error messages in the codebase

### Patient Portal *(2026-04-08 – 2026-04-11)*
- **Migration 026** creates `patient_accounts` (1:1 with patients, portal auth), `portal_message_threads`, `portal_messages` (threaded patient↔staff messaging), and `portal_feedback`
- **Patient login** — `patient_login.php` → `PatientPortalController::login()`; separate from staff login; authenticates via email or username; session keys (`patient_account_id`, `patient_id`, `patient_name`) are distinct from staff keys
- **Portal router** — `patient_portal.php?page=X` routes to: dashboard, appointments, health_record, lab_results, messages, feedback, profile
- **Patient middleware** — `app/middleware/patient_auth.php` guards all portal pages
- **Portal views** (`app/views/portal/`) — dashboard with next appointment, unread message count, and recent labs; appointments list; read-only health record (closed case sheets); lab results; threaded messaging; feedback/grievance submission; profile (allergies editable)
- **Allergy pre-population** — on first intake for a patient, `ClinicalController` converts `patients.allergies` text to `allergies_json` format to pre-fill the History tab
- **Staff portal messages** — `portal_messages.php` → `PatientPortalController::staffMessages()`; sidebar "Patient Messages" link with unread badge (gated by `patient_data` read)
- **Account management** — SUPER_ADMIN / ADMIN can create, reset password, and toggle portal accounts from the patient profile page; all actions POST to `patients.php` with `portal_action`
- **Login page** — added "Patient? Sign in to the Patient Portal" footer link on the staff login form

### Landing Page *(2026-04-11)*
- `index.php` replaced from a bare redirect with a full branded landing page
- Authenticated staff redirect to `dashboard.php`; authenticated patients redirect to `patient_portal.php`
- Unauthenticated visitors see two sign-in cards: "Staff Login" and "Patient Portal"

### Asset Library Upgrade *(2026-04-11)*
- **AssetController** extracted from `AdminController` into its own `app/controllers/AssetController.php`; `assets.php` entry point updated accordingly
- **Migration 027** extends `asset_type` enum with `AUDIO` and `FORM`; adds `local_file_path` column for uploaded files; creates `patient_assets` table (staff sends an asset to a specific patient's portal; public assets visible to all portal patients)
- **File upload** — assets can now be stored locally (`LOCAL` storage type) or linked externally (`URL`); uploads stored under `uploads/assets/YYYY/MM/` with UUID filenames; MIME validation via `finfo`; 20 MB cap
- **Send to patient** — staff with both `assets` and `patient_data` read access can send any asset to a specific patient's portal via a patient-search modal; each delivery tracked in `patient_assets`
- **Asset library UI** (`app/views/admin/assets.php`) fully rebuilt: type-filter bar, colour-coded type badges, create/edit/delete modal, send-to-patient modal with AJAX patient search, per-asset send-count badge

### Analytics *(2026-04-08)*
- Analytics dashboard (`analytics.php`) accessible to all authenticated roles; `AnalyticsController::buildScope()` gates what each role can see (admins see system-wide data; clinical roles see own-department data)
- Migration 025 adds `analytics` resource to the role permissions table
- Sidebar "Analytics" link visible to all authenticated users

### Sidebar i18n — Analytics & Patient Messages *(2026-04-09)*
- "Analytics" and "Patient Messages" sidebar links were the last two hard-coded English strings in `_sidebar.php`; both now run through `__()`
- `nav_analytics` and `nav_patient_messages` keys added to `lang/labels_en.csv` and `lang/labels_te.csv`

### Access Control Hardening *(2026-04-15)*
- `admin.php` — added `session.php` include, CSRF token setup, and a `can('users')` role guard; previously any authenticated user could reach the admin dashboard and admin panel by navigating directly to the URL
- `emp_register.php` — added auth middleware and `can('users')` role guard; page was previously unauthenticated and open to any visitor (registration code only blocked account creation, not page access)
- `AdminController::dashboard()` and `AdminController::adminPanel()` — added matching role guards as defence-in-depth so the entry-point check is enforced even if the controller is called from another path

### Intake & Review — Sticky Scrollable Tab Bar *(2026-04-15)*
- Tab navigation on the intake form (`intake.php`) and doctor review page (`review.php`) moved from the fixed top navbar into a dedicated sticky bar pinned just below it; tabs are now horizontally scrollable on small screens instead of being hidden below `d-lg-flex`
- Active tab indicated by a bottom-border underline (replaces the filled pill background) for cleaner visual separation from the page content
- Full dark-mode support for the new tab bar via `assets/css/theme.css`

### Landing Page — Admin Redirect Fix *(2026-04-15)*
- Authenticated admins visiting `index.php` are now redirected to `admin.php` instead of `dashboard.php`, consistent with the post-login redirect in `UserController::processLogin()`

### Labwork — Text-Based Lab Order Entry *(2026-04-13)*
- Replaced the 50+ categorised checkbox picker in the "Order Lab Test" modal (intake and doctor review) with a free-text entry system
- Each order row has a **Test** field (required) and a **Notes** field (optional); users can add as many rows as needed via **Add Another Test**, and remove any row with the × button
- Notes are now per-test rather than shared across all tests in a single order, enabling more precise instructions per ordered item
- `ClinicalController::orderLabTest()` updated to accept `tests` as an array of `{test_name, notes}` objects; each entry is inserted as a separate row in `lab_orders` with its own `order_notes`
- Submitted orders appear immediately in the inline lab orders table (intake) or reload via AJAX (doctor review) and flow through to the Labwork queue page unchanged
