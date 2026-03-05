-- Migration 019: Add appointments resource to role_permissions
--
-- Adds the 'appointments' resource to the live permission matrix so admins
-- can control which roles can create/manage appointments via the
-- Permissions Management UI.
--
-- Default access:
--   SUPER_ADMIN, ADMIN          → RW  (locked in UI, always full access)
--   NURSE, TRIAGE_NURSE         → RW  (primary appointment creators)
--   DOCTOR, PARAMEDIC           → R   (view schedule, no create)
--   GRIEVANCE_OFFICER,
--   EDUCATION_TEAM,
--   DATA_ENTRY_OPERATOR         → N   (no access)
--
-- Run AFTER migration 018.

INSERT IGNORE INTO role_permissions (role, resource, permission) VALUES
  ('SUPER_ADMIN',          'appointments', 'RW'),
  ('ADMIN',                'appointments', 'RW'),
  ('DOCTOR',               'appointments', 'R'),
  ('TRIAGE_NURSE',         'appointments', 'RW'),
  ('NURSE',                'appointments', 'RW'),
  ('PARAMEDIC',            'appointments', 'R'),
  ('GRIEVANCE_OFFICER',    'appointments', 'N'),
  ('EDUCATION_TEAM',       'appointments', 'N'),
  ('DATA_ENTRY_OPERATOR',  'appointments', 'N');
