-- Migration 020: Create lab_orders table
--
-- Stores individual lab test orders placed from the Labs tab of a case sheet.
-- Each row is one ordered test for one case sheet.
-- Orders start as PENDING and move to COMPLETED when the lab processes them
-- and records result notes via the Labwork page.
--
-- Run AFTER migration 019.

CREATE TABLE IF NOT EXISTS `lab_orders` (
  `order_id`              bigint unsigned NOT NULL AUTO_INCREMENT,
  `case_sheet_id`         bigint unsigned NOT NULL,
  `patient_id`            int unsigned    NOT NULL,
  `test_name`             varchar(200)    NOT NULL,
  `order_notes`           text            DEFAULT NULL,
  `status`                enum('PENDING','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `ordered_by_user_id`    int unsigned    NOT NULL,
  `ordered_at`            datetime        NOT NULL DEFAULT current_timestamp(),
  `completed_by_user_id`  int unsigned    DEFAULT NULL,
  `completed_at`          datetime        DEFAULT NULL,
  `result_notes`          text            DEFAULT NULL,
  `notes`                 text            DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `idx_lab_orders_case_sheet` (`case_sheet_id`),
  KEY `idx_lab_orders_patient`    (`patient_id`),
  KEY `idx_lab_orders_status`     (`status`),
  CONSTRAINT `fk_lab_orders_case_sheet`   FOREIGN KEY (`case_sheet_id`)        REFERENCES `case_sheets` (`case_sheet_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_orders_patient`      FOREIGN KEY (`patient_id`)           REFERENCES `patients`    (`patient_id`)    ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_orders_ordered_by`   FOREIGN KEY (`ordered_by_user_id`)   REFERENCES `users`       (`user_id`)       ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_orders_completed_by` FOREIGN KEY (`completed_by_user_id`) REFERENCES `users`       (`user_id`)       ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
