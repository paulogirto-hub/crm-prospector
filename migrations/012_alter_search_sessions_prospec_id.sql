-- 012_alter_search_sessions_prospec_id.sql
-- BUG-004: Prospector API gera IDs como hex strings (ex: "22c923da")
-- mas search_sessions.id é INTEGER, causando type mismatch
-- Solução: adicionar campo prospec_search_id VARCHAR(32) para armazenar o ID do Prospector

ALTER TABLE search_sessions ADD COLUMN IF NOT EXISTS prospec_search_id VARCHAR(32);

-- Criar índice para lookup rápido por prospec_search_id
CREATE INDEX IF NOT EXISTS idx_search_prospec_id ON search_sessions(prospec_search_id);