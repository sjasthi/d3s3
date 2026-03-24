-- Migration 022: Add thread_id to messages for multi-recipient support
--
-- Each send operation groups one row per recipient under a shared thread_id.
-- Existing single-recipient messages are each assigned their own unique thread_id.

ALTER TABLE messages
    ADD COLUMN thread_id CHAR(32) NOT NULL DEFAULT '' AFTER message_id;

ALTER TABLE messages
    ADD INDEX idx_messages_thread_id (thread_id);

-- Backfill: give every existing message its own thread (MD5 of PK → 32-char hex)
UPDATE messages
   SET thread_id = MD5(message_id)
 WHERE thread_id = '';
