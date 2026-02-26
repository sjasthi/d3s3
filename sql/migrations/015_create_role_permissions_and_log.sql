-- Migration 015: Dynamic role permissions storage and audit log
--
-- Creates two tables:
--   role_permissions       – live permission matrix (replaces hardcoded PHP constant)
--   permission_change_log  – immutable audit trail of every permission edit
--
-- Run AFTER migration 014.

-- ─── 1. Role permissions matrix ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS role_permissions (
  role       VARCHAR(30)            NOT NULL,
  resource   VARCHAR(30)            NOT NULL,
  permission ENUM('R', 'RW', 'N')  NOT NULL DEFAULT 'N',
  PRIMARY KEY (role, resource)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 2. Audit log ──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS permission_change_log (
  log_id      BIGINT(20) UNSIGNED    NOT NULL AUTO_INCREMENT,
  changed_by  INT(10) UNSIGNED       NOT NULL,
  role        VARCHAR(30)            NOT NULL,
  resource    VARCHAR(30)            NOT NULL,
  old_perm    ENUM('R', 'RW', 'N')  NOT NULL,
  new_perm    ENUM('R', 'RW', 'N')  NOT NULL,
  ip_address  VARCHAR(45)            NULL,
  changed_at  DATETIME               NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY idx_permlog_changed_at (changed_at),
  KEY idx_permlog_changed_by (changed_by),
  CONSTRAINT fk_permlog_user
    FOREIGN KEY (changed_by) REFERENCES users (user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 3. Seed data (current hardcoded matrix) ───────────────────────────────────
--
-- INSERT IGNORE so re-running the migration is safe.

INSERT IGNORE INTO role_permissions (role, resource, permission) VALUES
  -- SUPER_ADMIN (always RW – never editable via UI)
  ('SUPER_ADMIN', 'assets',       'RW'),
  ('SUPER_ADMIN', 'case_sheets',  'RW'),
  ('SUPER_ADMIN', 'events',       'RW'),
  ('SUPER_ADMIN', 'patient_data', 'RW'),
  ('SUPER_ADMIN', 'users',        'RW'),
  ('SUPER_ADMIN', 'feedback',     'RW'),
  ('SUPER_ADMIN', 'messages',     'RW'),
  ('SUPER_ADMIN', 'tasks',        'RW'),

  -- ADMIN (always RW – never editable via UI)
  ('ADMIN', 'assets',       'RW'),
  ('ADMIN', 'case_sheets',  'RW'),
  ('ADMIN', 'events',       'RW'),
  ('ADMIN', 'patient_data', 'RW'),
  ('ADMIN', 'users',        'RW'),
  ('ADMIN', 'feedback',     'RW'),
  ('ADMIN', 'messages',     'RW'),
  ('ADMIN', 'tasks',        'RW'),

  -- DOCTOR
  ('DOCTOR', 'assets',       'R'),
  ('DOCTOR', 'case_sheets',  'RW'),
  ('DOCTOR', 'events',       'R'),
  ('DOCTOR', 'patient_data', 'RW'),
  ('DOCTOR', 'users',        'N'),
  ('DOCTOR', 'feedback',     'R'),
  ('DOCTOR', 'messages',     'RW'),
  ('DOCTOR', 'tasks',        'RW'),

  -- TRIAGE_NURSE
  ('TRIAGE_NURSE', 'assets',       'R'),
  ('TRIAGE_NURSE', 'case_sheets',  'RW'),
  ('TRIAGE_NURSE', 'events',       'R'),
  ('TRIAGE_NURSE', 'patient_data', 'RW'),
  ('TRIAGE_NURSE', 'users',        'N'),
  ('TRIAGE_NURSE', 'feedback',     'R'),
  ('TRIAGE_NURSE', 'messages',     'RW'),
  ('TRIAGE_NURSE', 'tasks',        'RW'),

  -- NURSE
  ('NURSE', 'assets',       'R'),
  ('NURSE', 'case_sheets',  'RW'),
  ('NURSE', 'events',       'R'),
  ('NURSE', 'patient_data', 'RW'),
  ('NURSE', 'users',        'N'),
  ('NURSE', 'feedback',     'R'),
  ('NURSE', 'messages',     'RW'),
  ('NURSE', 'tasks',        'RW'),

  -- PARAMEDIC
  ('PARAMEDIC', 'assets',       'R'),
  ('PARAMEDIC', 'case_sheets',  'RW'),
  ('PARAMEDIC', 'events',       'R'),
  ('PARAMEDIC', 'patient_data', 'RW'),
  ('PARAMEDIC', 'users',        'N'),
  ('PARAMEDIC', 'feedback',     'R'),
  ('PARAMEDIC', 'messages',     'RW'),
  ('PARAMEDIC', 'tasks',        'RW'),

  -- GRIEVANCE_OFFICER
  ('GRIEVANCE_OFFICER', 'assets',       'N'),
  ('GRIEVANCE_OFFICER', 'case_sheets',  'R'),
  ('GRIEVANCE_OFFICER', 'events',       'N'),
  ('GRIEVANCE_OFFICER', 'patient_data', 'R'),
  ('GRIEVANCE_OFFICER', 'users',        'N'),
  ('GRIEVANCE_OFFICER', 'feedback',     'RW'),
  ('GRIEVANCE_OFFICER', 'messages',     'RW'),
  ('GRIEVANCE_OFFICER', 'tasks',        'RW'),

  -- EDUCATION_TEAM
  ('EDUCATION_TEAM', 'assets',       'RW'),
  ('EDUCATION_TEAM', 'case_sheets',  'N'),
  ('EDUCATION_TEAM', 'events',       'RW'),
  ('EDUCATION_TEAM', 'patient_data', 'N'),
  ('EDUCATION_TEAM', 'users',        'N'),
  ('EDUCATION_TEAM', 'feedback',     'R'),
  ('EDUCATION_TEAM', 'messages',     'RW'),
  ('EDUCATION_TEAM', 'tasks',        'RW'),

  -- DATA_ENTRY_OPERATOR
  ('DATA_ENTRY_OPERATOR', 'assets',       'R'),
  ('DATA_ENTRY_OPERATOR', 'case_sheets',  'RW'),
  ('DATA_ENTRY_OPERATOR', 'events',       'R'),
  ('DATA_ENTRY_OPERATOR', 'patient_data', 'RW'),
  ('DATA_ENTRY_OPERATOR', 'users',        'N'),
  ('DATA_ENTRY_OPERATOR', 'feedback',     'N'),
  ('DATA_ENTRY_OPERATOR', 'messages',     'RW'),
  ('DATA_ENTRY_OPERATOR', 'tasks',        'RW');
