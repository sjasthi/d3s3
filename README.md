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
| `SUPER_ADMIN` | Full access — manage all users and settings |
| `ADMIN` | User management and admin dashboard |
| `DATA_ENTRY_OPERATOR` | Standard user — dashboard and profile |

## Phase 1 Features

- User login / logout with secure session handling
- Role-based access control
- Admin dashboard
- User management (Admin+)
- Employee self-registration (gated by registration code)
- User profile with password change

## License

MIT — see [LICENSE](LICENSE) for details.
