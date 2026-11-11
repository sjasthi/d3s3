
-- Migration 030: Add per-user message deletion
-- Deleting a message only hides the user's own copy.

ALTER TABLE messages
    ADD COLUMN sender_deleted TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN recipient_deleted TINYINT(1) NOT NULL DEFAULT 0;

CREATE INDEX idx_messages_sender_deleted
    ON messages (sender_user_id, sender_deleted);

CREATE INDEX idx_messages_recipient_deleted
    ON messages (recipient_user_id, recipient_deleted);
