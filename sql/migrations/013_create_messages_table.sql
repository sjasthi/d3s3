-- Migration 013: Create messages table
--
-- Stores internal messages between users.
-- All roles have RW access to messages.

CREATE TABLE IF NOT EXISTS messages (
    message_id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    sender_user_id      INT UNSIGNED    NOT NULL,
    recipient_user_id   INT UNSIGNED    NOT NULL,
    subject             VARCHAR(200)    NOT NULL,
    body                TEXT            NOT NULL,
    is_read             TINYINT(1)      NOT NULL DEFAULT 0,
    sent_at             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_user_id)    REFERENCES users (user_id),
    CONSTRAINT fk_messages_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
