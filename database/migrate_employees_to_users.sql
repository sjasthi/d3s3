-- Migration: Rename employees table to users
-- Date: 2026-02-06
-- Description: Renames the employees table to users and updates all employee_id columns to user_id

-- IMPORTANT: Back up your database before running this migration!

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Step 1: Rename the main table
-- ============================================================
ALTER TABLE `employees` RENAME TO `users`;

-- ============================================================
-- Step 2: Rename primary key column in users table
-- ============================================================
ALTER TABLE `users`
    CHANGE COLUMN `employee_id` `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT;

-- ============================================================
-- Step 3: Rename unique keys and indexes in users table
-- ============================================================
ALTER TABLE `users`
    DROP INDEX `uq_employees_email`,
    ADD UNIQUE KEY `uq_users_email` (`email`);

ALTER TABLE `users`
    DROP INDEX `uq_employees_username`,
    ADD UNIQUE KEY `uq_users_username` (`username`);

ALTER TABLE `users`
    DROP INDEX `idx_employees_role`,
    ADD KEY `idx_users_role` (`role`);

ALTER TABLE `users`
    DROP INDEX `idx_employees_active`,
    ADD KEY `idx_users_active` (`is_active`);

-- ============================================================
-- Step 4: Rename foreign key columns in other tables
-- ============================================================

-- assets table
ALTER TABLE `assets`
    CHANGE COLUMN `uploaded_by_employee_id` `uploaded_by_user_id` int(10) unsigned DEFAULT NULL;

-- case_closures table
ALTER TABLE `case_closures`
    CHANGE COLUMN `closed_by_employee_id` `closed_by_user_id` int(10) unsigned DEFAULT NULL;

-- case_sheets table
ALTER TABLE `case_sheets`
    CHANGE COLUMN `created_by_employee_id` `created_by_user_id` int(10) unsigned DEFAULT NULL,
    CHANGE COLUMN `assigned_doctor_employee_id` `assigned_doctor_user_id` int(10) unsigned DEFAULT NULL;

-- events table
ALTER TABLE `events`
    CHANGE COLUMN `created_by_employee_id` `created_by_user_id` int(10) unsigned DEFAULT NULL;

-- message_threads table
ALTER TABLE `message_threads`
    CHANGE COLUMN `assigned_employee_id` `assigned_user_id` int(10) unsigned DEFAULT NULL;

-- Update index in message_threads
ALTER TABLE `message_threads`
    DROP INDEX `idx_threads_assigned`,
    ADD KEY `idx_threads_assigned` (`assigned_user_id`, `status`);

-- messages table
ALTER TABLE `messages`
    CHANGE COLUMN `sender_employee_id` `sender_user_id` int(10) unsigned DEFAULT NULL;

-- Update index in messages
ALTER TABLE `messages`
    DROP INDEX `idx_messages_sender_employee`,
    ADD KEY `idx_messages_sender_user` (`sender_user_id`);

-- patient_feedback table
ALTER TABLE `patient_feedback`
    CHANGE COLUMN `related_employee_id` `related_user_id` int(10) unsigned DEFAULT NULL;

-- Update index in patient_feedback
ALTER TABLE `patient_feedback`
    DROP INDEX `idx_feedback_related_employee`,
    ADD KEY `idx_feedback_related_user` (`related_user_id`);

-- ============================================================
-- Step 5: Re-enable foreign key checks
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verification queries (optional - run these to verify)
-- ============================================================
-- SELECT 'Migration complete!' AS status;
-- SHOW TABLES LIKE 'users';
-- DESCRIBE users;
-- SELECT COUNT(*) AS user_count FROM users;
