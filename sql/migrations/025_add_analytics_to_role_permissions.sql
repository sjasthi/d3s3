-- Migration 025: Add analytics resource to role_permissions
--
-- Adds the 'analytics' resource to the live permission matrix so admins
-- can control which roles can access the Analytics & Reporting page through
-- the Permissions Management UI.
--
-- Default access:
--   ALL roles -> R
--   What each role actually sees is controlled by
--   AnalyticsController::buildScope(), not by this permission entry.
--   The R row simply ensures the sidebar link and entry point are
--   reachable by every authenticated user.
--
-- Run AFTER migration 024.

INSERT IGNORE INTO role_permissions (role, resource, permission) VALUES
  ('SUPER_ADMIN',          'analytics', 'R'),
  ('ADMIN',                'analytics', 'R'),
  ('DOCTOR',               'analytics', 'R'),
  ('TRIAGE_NURSE',         'analytics', 'R'),
  ('NURSE',                'analytics', 'R'),
  ('PARAMEDIC',            'analytics', 'R'),
  ('GRIEVANCE_OFFICER',    'analytics', 'R'),
  ('EDUCATION_TEAM',       'analytics', 'R'),
  ('DATA_ENTRY_OPERATOR',  'analytics', 'R');
