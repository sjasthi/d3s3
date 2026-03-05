-- Migration 021: Add labwork resource to role_permissions
--
-- Adds the 'labwork' resource to the live permission matrix so admins
-- can control which roles can place and process lab orders through the
-- Permissions Management UI.
--
-- Default access:
--   SUPER_ADMIN, ADMIN                 -> RW  (full access)
--   DOCTOR, TRIAGE_NURSE, NURSE        -> RW  (place orders and record results)
--   PARAMEDIC                          -> R   (view pending orders only)
--   GRIEVANCE_OFFICER,
--   EDUCATION_TEAM,
--   DATA_ENTRY_OPERATOR                -> N   (no access)
--
-- Run AFTER migration 020.

INSERT IGNORE INTO role_permissions (role, resource, permission) VALUES
  ('SUPER_ADMIN',          'labwork', 'RW'),
  ('ADMIN',                'labwork', 'RW'),
  ('DOCTOR',               'labwork', 'RW'),
  ('TRIAGE_NURSE',         'labwork', 'RW'),
  ('NURSE',                'labwork', 'RW'),
  ('PARAMEDIC',            'labwork', 'R'),
  ('GRIEVANCE_OFFICER',    'labwork', 'N'),
  ('EDUCATION_TEAM',       'labwork', 'N'),
  ('DATA_ENTRY_OPERATOR',  'labwork', 'N');
