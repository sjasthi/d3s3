-- Migration 014: Create tasks table
--
-- Stores to-do / task items.
-- All roles have RW access to tasks.

CREATE TABLE IF NOT EXISTS tasks (
    task_id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    title                VARCHAR(200)    NOT NULL,
    description          TEXT            NULL,
    status               ENUM('PENDING','IN_PROGRESS','DONE')
                                         NOT NULL DEFAULT 'PENDING',
    priority             ENUM('LOW','MEDIUM','HIGH')
                                         NOT NULL DEFAULT 'MEDIUM',
    assigned_to_user_id  INT UNSIGNED    NULL,
    created_by_user_id   INT UNSIGNED    NOT NULL,
    due_date             DATE            NULL,
    created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id),
    CONSTRAINT fk_tasks_assigned_to
        FOREIGN KEY (assigned_to_user_id) REFERENCES users (user_id),
    CONSTRAINT fk_tasks_created_by
        FOREIGN KEY (created_by_user_id)  REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
