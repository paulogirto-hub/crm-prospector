-- 013_add_missing_lead_columns.sql
-- Adiciona colunas faltantes à tabela leads que o código espera

ALTER TABLE leads ADD COLUMN IF NOT EXISTS name VARCHAR(255);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS email VARCHAR(255);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS phone VARCHAR(50);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS city VARCHAR(100);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS state VARCHAR(10);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS country VARCHAR(10) DEFAULT 'BR';
ALTER TABLE leads ADD COLUMN IF NOT EXISTS niche VARCHAR(100);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS website VARCHAR(255);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS description TEXT;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS owner_id INTEGER REFERENCES users(id) ON DELETE SET NULL;

-- Corrigir audit_log com colunas que o código espera
ALTER TABLE audit_log ADD COLUMN IF NOT EXISTS user_agent TEXT;
ALTER TABLE audit_log ADD COLUMN IF NOT EXISTS session_id VARCHAR(128);
ALTER TABLE audit_log ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE; -- para soft delete

-- Adiciona coluna deleted_at à leads (já coberto acima, mas redundante aqui para consistência)
ALTER TABLE leads ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE;
