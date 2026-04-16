# Patient Portal — Test Account Guide

**D3S3 CareSystem**
Last updated: 2026-04-15

---

## Overview

The patient portal is a separate, green-themed interface that patients use to access their own health data. It is completely independent from the staff portal — different login page, different session, different colour scheme.

This document covers everything you need to get the test accounts working and explore the portal as a patient.

---

## Prerequisites

Before the test accounts will exist in the database, you must have completed the full local setup:

1. XAMPP running (Apache + MySQL both green)
2. Database `core_app` created and `sql/core_app.sql` imported
3. All 27 migrations run in order (`sql/migrations/001_...` through `027_...`)
4. All three test data files loaded in order:
   - `sql/test_data.sql`
   - `sql/test_data_phase2.sql`
   - `sql/test_data_phase3.sql` ← **this file creates the portal accounts**

If you have not done this yet, follow the full setup guide in `README.md` first, then come back here.

---

## Test Account Credentials

All three portal test accounts are created by `sql/test_data_phase3.sql`.

**Password for all three accounts: `Portal@1234`**

You can log in using either the **username** or the **email address** — both are accepted on the login form.

| # | Username | Email | Password |
|---|----------|-------|----------|
| 1 | `patient_priya` | `priya.patient@example.com` | `Portal@1234` |
| 2 | `patient_rahul` | `rahul.patient@example.com` | `Portal@1234` |
| 3 | `patient_ananya` | `ananya.patient@example.com` | `Portal@1234` |

---

## How to Log In

1. Open your browser and go to:
   ```
   http://localhost/d3s3/patient_login.php
   ```
2. Enter a **username** (e.g. `patient_priya`) or **email** (e.g. `priya.patient@example.com`)
3. Enter the password: `Portal@1234`
4. Click **Sign In**

You will land on the patient portal dashboard.

> The homepage at `http://localhost/d3s3/` has a direct link to the patient login if you prefer to start there.

---

## What Each Portal Page Shows

| Page | How to get there | What you see |
|------|-----------------|--------------|
| Dashboard | Default after login | Next appointment alert, unread message count, new resource notifications, quick-link tiles |
| Appointments | Click **Appointments** in the top nav | Upcoming and past clinic visits — read-only |
| Health Record | Click **Health Record** | Closed case sheets: diagnosis, treatment plan, prescriptions, and advice — read-only |
| Lab Results | Click **Lab Results** | Completed test results with notes; pending orders still awaiting results |
| Messages | Click **Messages** | Threaded inbox — compose a new message to the clinic or reply to staff messages |
| Feedback | Click **Feedback** | Submit a complaint, suggestion, or positive review with optional star rating |
| Resources | Click **Resources** | Educational materials sent to you by staff, plus the public library |
| Profile | Click your name or **Profile** | Read-only personal details; the **Allergies** field is editable |

All clinical data (appointments, health record, lab results) is **read-only** for patients. The only field a patient can change is their allergy information in the Profile page.

---

## Demonstrating the Staff ↔ Patient Message Flow

This is the best way to show the portal working end-to-end.

### Step 1 — Patient sends a message

1. Log in to the patient portal at `http://localhost/d3s3/patient_login.php`
2. Click **Messages** in the top navigation bar
3. Click **New Message** (top right of the messages panel)
4. Enter a subject and message body → click **Send**

### Step 2 — Staff sees and replies

1. Open a **new browser tab** (keep the patient tab open)
2. Go to `http://localhost/d3s3/login.php`
3. Log in as a staff member — for example:
   - Email: `a.gupta@d3s3.com`
   - Password: `Test1234!`
   - Role: ADMIN (has full access)
4. In the left sidebar, click **Patient Messages**
   - You will see a yellow badge showing the number of unread threads
5. Click the thread from your patient
6. Type a reply in the text box at the bottom → click **Send**

### Step 3 — Patient sees the reply

1. Switch back to the patient portal tab
2. Click **Messages** (or refresh the page)
3. The thread now shows a **New** badge and the staff reply is visible

---

## Creating Additional Portal Accounts

The three seed accounts are linked to early test patients (IDs 1–3) which have minimal clinical data. If you want to test with richer data (appointments, lab results, health records), create a portal account for one of the Phase 2 or 3 patients (IDs 15–30).

### Via the staff UI

1. Log in to the staff portal at `http://localhost/d3s3/login.php` as ADMIN or SUPER_ADMIN
2. Click **Patients** in the sidebar → search for any patient by name
3. Click the patient's name to open their profile
4. Scroll down to the **Patient Portal Account** card
5. Click **Create Portal Account**
6. Enter an email address, optional username, and a password → submit
7. Use those credentials to log in at `patient_login.php`

### Via the bulk-create script

1. Open `create_test_portal_accounts.php` (in the project root) in a text editor
2. Edit the `$accounts` array — add an entry with `patient_id`, `email`, `username`, and `password`
   - The `patient_id` must match a row that already exists in the `patients` table
3. Visit `http://localhost/d3s3/create_test_portal_accounts.php` in your browser
4. The page prints a success or error for each account
5. **Delete the file after use** — it creates accounts without requiring any authentication

---

## Troubleshooting

### "Incorrect email/username or password"

**Check 1 — Did you load the phase 3 data?**

Open phpMyAdmin → select `core_app` → run:
```sql
SELECT * FROM patient_accounts;
```
If the table is empty, you have not run `sql/test_data_phase3.sql`. Go to phpMyAdmin → SQL tab → paste the contents of that file → click **Go**.

**Check 2 — Is the password hash correct?**

An earlier version of `test_data_phase3.sql` contained a broken hash. If you loaded the file before the fix was committed (2026-04-15), run this update query in phpMyAdmin → SQL tab:

```sql
UPDATE patient_accounts
SET password_hash = '$2y$10$iEbnZFWXi/SriBcbT7FDEepvsVRu9NzL3VYOB5SwT8nHT7jMk930e'
WHERE patient_id IN (1, 2, 3);
```

Then try logging in again with `Portal@1234`.

### "Page not found" on patient_login.php

Confirm the app is at `htdocs/d3s3/` and Apache is running. The URL must be exactly:
```
http://localhost/d3s3/patient_login.php
```

### Portal shows no appointments or health records

Accounts 1–3 are linked to early placeholder patients with limited seed data. Create a new portal account for a patient with richer data (see **Creating Additional Portal Accounts** above). Patients in the ID 15–30 range (loaded by `test_data_phase3.sql`) have full case sheets, appointments, and lab orders attached.

---

## Quick Reference

| What | Where |
|------|-------|
| Patient login page | `http://localhost/d3s3/patient_login.php` |
| Staff login page | `http://localhost/d3s3/login.php` |
| Homepage (links to both) | `http://localhost/d3s3/` |
| Staff portal messages inbox | Sidebar → **Patient Messages** (or `portal_messages.php`) |
| Portal account management | Staff login → Patients → open patient profile → Portal Account card |
| Test data file | `sql/test_data_phase3.sql` |
| Bulk account script | `create_test_portal_accounts.php` (delete after use) |
| Full setup guide | `README.md` |
