-- Rollback: 012_alter_search_sessions_prospec_id.sql
DROP INDEX IF EXISTS idx_search_prospec_id;
ALTER TABLE search_sessions DROP COLUMN IF EXISTS prospec_search_id;