-- ============================================================
-- migration_appointments.sql
-- D3S3 CareSystem — Appointments feature
--
-- Safe to run multiple times (IF NOT EXISTS / MODIFY is idempotent
-- for enum changes when value already present).
-- Run against: core_app
-- ============================================================

-- ── 1. Add SCHEDULED to case_sheets.status enum ─────────────
ALTER TABLE `case_sheets`
  MODIFY COLUMN `status`
    ENUM(
      'INTAKE_IN_PROGRESS',
      'INTAKE_COMPLETE',
      'SCHEDULED',
      'QUEUED',
      'DOCTOR_REVIEW',
      'CLOSED'
    ) NOT NULL DEFAULT 'INTAKE_IN_PROGRESS';

-- ── 2. Create appointments table ────────────────────────────
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id`       BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `case_sheet_id`        BIGINT UNSIGNED    NOT NULL,
  `doctor_user_id`       INT UNSIGNED       NOT NULL,
  `scheduled_date`       DATE               NOT NULL,
  `scheduled_time`       TIME               NULL COMMENT 'NULL = time not specified',
  `visit_mode`           ENUM('IN_PERSON','REMOTE','CAMP') NOT NULL DEFAULT 'IN_PERSON',
  `event_id`             BIGINT UNSIGNED    NULL COMMENT 'FK to events for CAMP visits',
  `status`               ENUM('SCHEDULED','CONFIRMED','IN_PROGRESS','COMPLETED','CANCELLED','NO_SHOW')
                           NOT NULL DEFAULT 'SCHEDULED',
  `notes`                TEXT               NULL,
  `created_by_user_id`   INT UNSIGNED       NULL,
  `created_at`           DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`appointment_id`),

  KEY `idx_appt_date_doctor`    (`scheduled_date`, `doctor_user_id`),
  KEY `idx_appt_case_sheet`     (`case_sheet_id`),
  KEY `idx_appt_status_date`    (`status`, `scheduled_date`),

  CONSTRAINT `fk_appt_case_sheet`
    FOREIGN KEY (`case_sheet_id`)   REFERENCES `case_sheets`(`case_sheet_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appt_doctor`
    FOREIGN KEY (`doctor_user_id`)  REFERENCES `users`(`user_id`)             ON UPDATE CASCADE,
  CONSTRAINT `fk_appt_event`
    FOREIGN KEY (`event_id`)        REFERENCES `events`(`event_id`)           ON UPDATE CASCADE,
  CONSTRAINT `fk_appt_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`user_id`)          ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Verification (no information_schema required) ─────────
-- Review the output of these two to confirm the migration landed:
--   case_sheets.status should show SCHEDULED in the Type column
--   appointments should show all expected columns
DESCRIBE `case_sheets`;
DESCRIBE `appointments`;