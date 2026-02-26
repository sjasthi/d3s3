-- Migration 012: Create feedback table
--
-- Stores grievance/feedback submissions.
-- GRIEVANCE_OFFICER has RW; SUPER_ADMIN and ADMIN have RW.
-- Clinical roles (DOCTOR, TRIAGE_NURSE, NURSE, PARAMEDIC, EDUCATION_TEAM) have R.
-- DATA_ENTRY_OPERATOR has N (no access).

CREATE TABLE IF NOT EXISTS feedback (
    feedback_id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    subject              VARCHAR(200)    NOT NULL,
    description          TEXT            NOT NULL,
    status               ENUM('OPEN','UNDER_REVIEW','RESOLVED','CLOSED')
                                         NOT NULL DEFAULT 'OPEN',
    submitted_by_user_id INT UNSIGNED    NOT NULL,
    assigned_to_user_id  INT UNSIGNED    NULL,
    created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (feedback_id),
    CONSTRAINT fk_feedback_submitted_by
        FOREIGN KEY (submitted_by_user_id) REFERENCES users (user_id),
    CONSTRAINT fk_feedback_assigned_to
        FOREIGN KEY (assigned_to_user_id)  REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
