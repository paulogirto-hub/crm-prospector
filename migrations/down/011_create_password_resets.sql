-- Rollback: 011_create_password_resets.sql
DROP TABLE IF EXISTS password_resets;
ALTER TABLE users DROP COLUMN IF EXISTS reset_token;
ALTER TABLE users DROP COLUMN IF EXISTS reset_token_expires_at;