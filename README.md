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
├── app/
│   ├── config/
│   │   ├── database.php        # PDO connection (reads .env)
│   │   └── session.php         # Secure session configuration
│   ├── controllers/
│   │   ├── UserController.php  # Login, profile, registration
│   │   └── AdminController.php # Dashboard, user management
│   ├── middleware/
│   │   └── auth.php            # Session guard
│   └── views/
│       ├── login.php
│       ├── profile.php
│       ├── _sidebar.php
│       └── admin/
│           ├── dashboard.php
│           ├── users.php
│           └── emp_register.php
├── assets/                     # CSS, JS, icons
├── sql/                        # Database schema & SQL files
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
- `app/config/permissions.php` — 9-role × 8-resource access matrix
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

## License

MIT — see [LICENSE](LICENSE) for details.
