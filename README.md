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

## Features

**Phase 1** — Authentication, role-based access control, admin dashboard, user management, employee self-registration, and user profiles.

**Phase 2** — Case sheet system, patient records, appointments, labwork queue, internal messaging, task management, feedback/grievance tracking, shared calendar, asset library (with file upload and patient delivery), analytics dashboard, patient-facing portal (appointments, health record, lab results, messaging, feedback), and security hardening. Multilingual support (English / Telugu) throughout.

See [CHANGELOG.md](CHANGELOG.md) for a full history of changes.

## License

MIT — see [LICENSE](LICENSE) for details.
