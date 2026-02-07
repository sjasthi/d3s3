# D3S3 CareSystem - Simplified Schema Guide

## Overview
Reduced from **11 tables** to **7 tables** (+ 1 helper table for patient codes)

---

## 📊 Tables (7 + 1 helper)

### 1. **users** (Staff/Employees)
Staff members who use the system - doctors, nurses, admins, data entry operators.

**Key Fields:**
- `user_id` - Primary key
- `first_name`, `last_name`, `email`, `username`
- `password_hash` - Bcrypt hashed password
- `role` - SUPER_ADMIN, ADMIN, DOCTOR, NURSE, DATA_ENTRY_OPERATOR
- `is_active` - Account status

---

### 2. **patients** ✨ (Merged: patients + patient_accounts)
Patient demographics AND optional portal account (all in one table).

**Key Fields:**
- `patient_id` - Primary key
- **`patient_code`** - Auto-generated format: **YYYYMMDDNNN** (e.g., 20260226005)
  - Generated ONCE when patient is first created
  - YYY is the year, MM is month, DD is day, NNN is the sequence for that day
- `first_name`, `last_name`, `sex`, `date_of_birth`
- `phone_e164`, `email`
- `blood_group`, `allergies`
- **Portal Account (nullable):**
  - `username` - For patient portal login (optional)
  - `password_hash` - Bcrypt hashed (optional)
  - `last_login_at` - Last portal login (optional)

**Why merge?** Most patients won't need portal accounts. Making these fields nullable is simpler than a separate 1:1 table.

---

### 2a. **patient_daily_sequence** (Helper table)
Tracks the daily sequence number for patient_code generation.

**Structure:**
```sql
seq_date | last_n
---------|-------
2026-02-26 | 5
2026-02-27 | 12
```

**How it works:**
1. Patient is created on 2026-02-26
2. Trigger looks up this date, increments `last_n` from 4 → 5
3. Patient code becomes: **20260226005**
4. Next patient same day becomes: **20260226006**

**Simple trigger** - Just increments counter, no complex logic!

---

### 3. **case_sheets** ✨ (Merged: case_sheets + case_closures)
Medical visit records INCLUDING closure/discharge information (all in one place).

**Visit Information:**
- `patient_id`, `visit_datetime`, `visit_type`
- `chief_complaint`, `diagnosis`, `prescriptions`
- `vitals_json` - Blood pressure, temp, etc. stored as JSON
- `created_by_user_id`, `assigned_doctor_user_id`

**Closure/Discharge Information (nullable until closed):**
- `is_closed` - 0 = open, 1 = closed
- `closed_at`, `closed_by_user_id`
- `closure_type` - DISCHARGED, FOLLOW_UP, REFERRAL, PENDING
- `disposition` - Brief discharge summary
- `advice` - Instructions for patient
- `follow_up_date` - Next appointment
- `referral_to`, `referral_reason` - If referred elsewhere

**Why merge?** Every case_sheet had exactly ONE case_closure (1:1 relationship). Merging eliminates the need for triggers and simplifies queries.

---

### 4. **events**
Scheduled events like medical camps, seminars, training sessions.

**Key Fields:**
- `event_type` - MEDICAL_CAMP, EDUCATIONAL_SEMINAR, TRAINING, etc.
- `title`, `description`
- `start_datetime`, `end_datetime`
- `location_name`, `address`, `city`
- `status` - DRAFT, SCHEDULED, ACTIVE, COMPLETED, CANCELLED

**Simplified:** Removed UUIDs, GPS coordinates, timezone complexity

---

### 5. **messages** ✨ (Simplified: removed threads)
Direct messages between patients and staff (no threading complexity).

**Key Fields:**
- `patient_id` - Who the conversation is with
- `sender_type` - PATIENT or STAFF
- `sender_user_id` - NULL if sent by patient, otherwise staff member
- `subject`, `message_text`
- `is_read`, `read_at`
- `case_sheet_id` - Optional link to specific visit

**How it works:**
- All messages for a patient are retrieved with: `WHERE patient_id = ?`
- No complex threading - just a simple conversation log
- Can filter by sender_type to show only patient messages or only staff messages

**Why simplified?** Threading adds complexity (thread_uuid, thread status, etc.) that's overkill for a student project. Direct messaging is easier to implement and understand.

---

### 6. **patient_feedback**
Patient feedback, complaints, and suggestions.

**Key Fields:**
- `patient_id`
- `case_sheet_id` - Optional link to specific visit
- `related_user_id` - Staff member feedback is about (optional)
- `feedback_type` - POSITIVE, COMPLAINT, SUGGESTION
- `rating` - 1-5 stars
- `feedback_text`
- `status` - NEW, REVIEWED, RESOLVED, CLOSED
- `admin_notes` - Internal admin notes

**Simplified:** Removed sentiment analysis fields (sentiment_label, sentiment_score, etc.) and voice recording options

---

### 7. **assets**
Educational materials, documents, videos, PDFs.

**Key Fields:**
- `title`, `description`
- `asset_type` - VIDEO, PDF, IMAGE, DOCUMENT, OTHER
- `category` - e.g., "diabetes", "hypertension", "training"
- `file_name`, `file_size_bytes`
- `storage_type` - URL, LOCAL, S3
- `resource_url` - Path or URL to file
- `is_public` - 1 = public, 0 = staff only
- `uploaded_by_user_id`

**Simplified:** Removed UUIDs, language codes, duration/page count tracking

---

## 🔑 What Changed?

### ✅ Merged Tables:
1. **patient_accounts → patients** (1:1 relationship eliminated)
2. **case_closures → case_sheets** (1:1 relationship eliminated)

### ✅ Simplified Tables:
1. **message_threads removed** - Direct messaging only
2. **Removed UUIDs** from events, assets, feedback (just use auto_increment IDs)
3. **Removed sentiment analysis** from patient_feedback
4. **Removed voice recording fields** from messages/feedback
5. **Simplified address fields** (removed separate address_line1/line2, just use one field)

### ✅ Kept (Because It's The Simplest Approach):
- **patient_daily_sequence + trigger** for patient_code generation
  - While triggers can be complex, this one is VERY simple
  - Alternative would be generating codes in PHP with race condition risks
  - This trigger is just 10 lines and very maintainable

---

## 🚀 How to Apply

### Option 1: Fresh Install (New Database)
```bash
mysql -u root -p -e "DROP DATABASE IF EXISTS core_app; CREATE DATABASE core_app;"
mysql -u root -p core_app < database/schema_simplified.sql
```

### Option 2: Migrate Existing Database
```bash
# BACKUP FIRST!
mysqldump -u root -p core_app > backup_$(date +%Y%m%d).sql

# Run migration
mysql -u root -p core_app < database/migrate_to_simplified_schema.sql
```

---

## 📝 Code Changes Needed

After applying the schema, you'll need to update PHP code:

### 1. **Patient Portal Login** (if implementing)
```php
// Old: SELECT from patient_accounts WHERE patient_id = ?
// New: SELECT from patients WHERE patient_id = ? AND username IS NOT NULL
$stmt = $pdo->prepare('SELECT * FROM patients WHERE username = ? LIMIT 1');
```

### 2. **Case Closure**
```php
// Old: INSERT INTO case_closures ...
// New: UPDATE case_sheets SET is_closed = 1, closed_at = NOW(), closure_type = ?, ...
$stmt = $pdo->prepare('UPDATE case_sheets SET is_closed = 1, closed_at = NOW(),
  closure_type = ?, disposition = ?, closed_by_user_id = ? WHERE case_sheet_id = ?');
```

### 3. **Messages**
```php
// Old: INSERT INTO message_threads, then INSERT INTO messages
// New: Just INSERT INTO messages directly
$stmt = $pdo->prepare('INSERT INTO messages (patient_id, sender_type, sender_user_id,
  subject, message_text) VALUES (?, ?, ?, ?, ?)');

// Retrieve all messages for a patient
$stmt = $pdo->prepare('SELECT * FROM messages WHERE patient_id = ? ORDER BY sent_at');
```

---

## 🎯 Benefits

1. ✅ **37% fewer tables** (11 → 7 + 1 helper)
2. ✅ **No unnecessary 1:1 relationships**
3. ✅ **Simpler queries** (fewer JOINs needed)
4. ✅ **No trigger complexity** (just one simple trigger for patient_code)
5. ✅ **Easier to understand** for student projects
6. ✅ **Better performance** (fewer tables = faster queries)
7. ✅ **Still maintains data integrity** with proper foreign keys

---

## ❓ FAQ

**Q: Why keep patient_daily_sequence if your instructor doesn't like it?**
A: It's actually the CLEANEST way to generate patient codes in the YYYYMMDDNNN format. The alternative is:
- Generate in PHP (race conditions if 2 patients register simultaneously)
- Use a complex stored procedure
- Use MySQL's LAST_INSERT_ID tricks (harder to understand)

The trigger is only 10 lines and very simple. If your instructor still objects, we can generate codes in PHP, but it's more complex.

**Q: Can patients exist without portal accounts?**
A: Yes! The `username`, `password_hash`, and `last_login_at` fields are nullable. Most patients won't have portal accounts - those fields are only filled if they register for the portal.

**Q: What if I need message threading later?**
A: Easy to add! Just add a `thread_id` column to messages and create a `message_threads` table. But start simple - you probably won't need it.

**Q: Should I remove tables I'm not using (like assets or events)?**
A: Yes! If you're not implementing those features, drop those tables. The schema includes them so you have options, but remove what you don't need.

---

## 📚 Next Steps

1. **Backup your database**
2. **Review the simplified schema** in `schema_simplified.sql`
3. **Run the migration** with `migrate_to_simplified_schema.sql`
4. **Update your PHP code** to work with the new structure
5. **Remove unused tables** (assets, events, feedback) if not implementing those features

Good luck with your project! 🚀
