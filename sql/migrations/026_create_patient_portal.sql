    -- Migration 026: Patient Portal
    --
    -- Creates tables for the patient-facing portal:
    --   patient_accounts        – portal login credentials (1:1 with patients)
    --   portal_message_threads  – conversation threads between patient and staff
    --   portal_messages         – individual messages within a thread
    --   portal_feedback         – patient grievances, complaints, suggestions
    --
    -- Admins create portal accounts via the patient profile page.
    -- Patients log in at patient_login.php (separate from staff login).

    -- ── 1. Portal authentication ────────────────────────────────────────────────
    CREATE TABLE IF NOT EXISTS patient_accounts (
        patient_account_id  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
        patient_id          INT UNSIGNED      NOT NULL,
        username            VARCHAR(60)       NULL     COMMENT 'Optional username; email is primary login',
        email               VARCHAR(190)      NULL     COMMENT 'Login email – must match patients.email if set',
        password_hash       VARCHAR(255)      NOT NULL,
        is_active           TINYINT(1)        NOT NULL DEFAULT 1,
        last_login_at       DATETIME          NULL,
        created_by_user_id  INT UNSIGNED      NULL     COMMENT 'Staff member who created this account',
        created_at          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (patient_account_id),
        UNIQUE KEY uq_pac_patient  (patient_id),
        UNIQUE KEY uq_pac_username (username),
        UNIQUE KEY uq_pac_email    (email),
        CONSTRAINT fk_pac_patient     FOREIGN KEY (patient_id)
            REFERENCES patients (patient_id) ON UPDATE CASCADE,
        CONSTRAINT fk_pac_created_by  FOREIGN KEY (created_by_user_id)
            REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ── 2. Message threads ──────────────────────────────────────────────────────
    CREATE TABLE IF NOT EXISTS portal_message_threads (
        thread_id           BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
        patient_account_id  BIGINT UNSIGNED   NOT NULL,
        subject             VARCHAR(200)      NOT NULL,
        last_message_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
        patient_unread      TINYINT(1)        NOT NULL DEFAULT 0
                                COMMENT '1 = patient has unread staff reply',
        staff_unread        TINYINT(1)        NOT NULL DEFAULT 1
                                COMMENT '1 = staff has unread patient message',
        created_at          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (thread_id),
        KEY idx_pmt_patient (patient_account_id, last_message_at),
        KEY idx_pmt_staff_unread (staff_unread),
        CONSTRAINT fk_pmt_pac FOREIGN KEY (patient_account_id)
            REFERENCES patient_accounts (patient_account_id) ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ── 3. Individual messages ──────────────────────────────────────────────────
    CREATE TABLE IF NOT EXISTS portal_messages (
        portal_message_id   BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
        thread_id           BIGINT UNSIGNED   NOT NULL,
        sender_type         ENUM('PATIENT','STAFF') NOT NULL,
        sender_user_id      INT UNSIGNED      NULL     COMMENT 'Populated when sender_type = STAFF',
        body                TEXT              NOT NULL,
        sent_at             DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (portal_message_id),
        KEY idx_pm_thread (thread_id, sent_at),
        CONSTRAINT fk_pm_thread FOREIGN KEY (thread_id)
            REFERENCES portal_message_threads (thread_id) ON UPDATE CASCADE,
        CONSTRAINT fk_pm_user   FOREIGN KEY (sender_user_id)
            REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ── 4. Patient feedback ─────────────────────────────────────────────────────
    CREATE TABLE IF NOT EXISTS portal_feedback (
        portal_feedback_id  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
        patient_account_id  BIGINT UNSIGNED   NOT NULL,
        feedback_type       ENUM('GRIEVANCE','COMPLAINT','POSITIVE','SUGGESTION')
                                NOT NULL DEFAULT 'SUGGESTION',
        subject             VARCHAR(200)      NOT NULL,
        description         TEXT              NOT NULL,
        related_user_id     INT UNSIGNED      NULL     COMMENT 'Staff member this feedback is about',
        rating              TINYINT UNSIGNED  NULL     COMMENT '1–5 optional star rating',
        status              ENUM('NEW','REVIEWED','ACTIONED','CLOSED')
                                NOT NULL DEFAULT 'NEW',
        staff_notes         TEXT              NULL,
        created_at          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (portal_feedback_id),
        KEY idx_pf_patient (patient_account_id, created_at),
        KEY idx_pf_status  (status),
        CONSTRAINT fk_pf_pac  FOREIGN KEY (patient_account_id)
            REFERENCES patient_accounts (patient_account_id) ON UPDATE CASCADE,
        CONSTRAINT fk_pf_user FOREIGN KEY (related_user_id)
            REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
