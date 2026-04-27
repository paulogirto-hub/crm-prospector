-- 010_create_settings.sql
-- Configurações por usuário

CREATE TABLE settings (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    key         VARCHAR(100) NOT NULL,
    value       TEXT,
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    
    UNIQUE(user_id, key)
);

CREATE INDEX idx_settings_user_key ON settings(user_id, key);